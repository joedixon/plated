<?php

namespace App\Jobs;

use App\Ai\Agents\DishComposer;
use App\Enums\DishStatus;
use App\Events\DishPlated;
use App\Models\Dish;
use App\Support\SpendingCap;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateDish implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job may run before timing out.
     */
    public int $timeout = 120;

    /**
     * Execute the job.
     */
    public function handle(SpendingCap $cap): void
    {
        if (! $cap->canSpend()) {
            Log::info('Plated spending cap reached; skipping dish generation.');

            return;
        }

        $composed = $this->compose();

        if ($composed === null) {
            return;
        }

        $dish = Dish::create([
            ...$composed,
            'sequence' => (int) Dish::max('sequence') + 1,
            'status' => DishStatus::Plated,
        ]);

        Cache::forever("dish:{$dish->id}:up", 0);
        Cache::forever("dish:{$dish->id}:down", 0);

        $cap->record();

        DishPlated::dispatch($dish);
    }

    /**
     * Compose a new dish via the AI agent. Isolated so the SDK contact lives
     * in exactly one place. Returns null on failure so the worker survives.
     *
     * @return array{name: string, description: string, pairing: string, glyph: string}|null
     */
    private function compose(): ?array
    {
        try {
            $response = (new DishComposer)->prompt($this->brief());

            return [
                'name' => $response['name'],
                'description' => $response['description'],
                'pairing' => $response['pairing'],
                'glyph' => $response['glyph'],
            ];
        } catch (Throwable $e) {
            Log::error('Plated dish generation failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Build the brief for the next dish. The agent has no memory between calls,
     * so we hand it the dishes already on tonight's menu to steer clear of, plus
     * a random culinary anchor to break it out of its default repertoire.
     */
    private function brief(): string
    {
        $brief = "Invent tonight's next dish. ".$this->anchor();

        $recent = Dish::query()
            ->orderByDesc('sequence')
            ->limit(40)
            ->pluck('name')
            ->all();

        if ($recent !== []) {
            $brief .= "\n\nThese dishes are already on tonight's menu — do not repeat, ".
                'rephrase, or reuse the same hero ingredient, technique, or title pattern '.
                "as any of them:\n- ".implode("\n- ", $recent);
        }

        return $brief;
    }

    /**
     * A random culinary starting point so successive dishes diverge in cuisine,
     * star ingredient, and technique rather than circling the same few ideas.
     */
    private function anchor(): string
    {
        return sprintf(
            'Draw inspiration from %s cooking, build it around %s, and let %s define the technique.',
            Arr::random(['Japanese', 'Peruvian', 'Nordic', 'Levantine', 'West African', 'Sichuan', 'Basque', 'Oaxacan', 'Vietnamese', 'Georgian', 'Korean', 'Sardinian']),
            Arr::random(['heritage carrots', 'line-caught mackerel', 'aged duck', 'koji', 'wild mushrooms', 'sea urchin', 'fermented black garlic', 'venison', 'stone fruit', 'celeriac', 'langoustine', 'buckwheat']),
            Arr::random(['live-fire grilling', 'curing', 'fermentation', 'smoking', 'raw preparation', 'slow braising', 'charcoal embers', 'pickling', 'whey poaching', 'clay-pot roasting']),
        );
    }
}
