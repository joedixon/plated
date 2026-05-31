<?php

namespace App\Jobs;

use App\Ai\Agents\DishComposer;
use App\Enums\DishStatus;
use App\Events\DishPlated;
use App\Models\Dish;
use App\Support\Contracts\VoteTally;
use App\Support\SpendingCap;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
    public function handle(VoteTally $tally, SpendingCap $cap): void
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

        $tally->seed($dish->id, 0, 0);
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
            $response = (new DishComposer)->prompt('Invent tonight\'s next dish.');

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
}
