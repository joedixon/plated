<?php

namespace App\Console\Commands;

use App\Enums\DishStatus;
use App\Events\DishCooked;
use App\Models\Dish;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('plated:expedite')]
#[Description('Cook any plated dishes that already cleared the vote threshold')]
class ExpediteReady extends Command
{
    /**
     * Sweep the pass for dishes whose live tally already meets the cook
     * threshold but never crossed it during a vote — for example after the
     * threshold was lowered — and cook them off so no stale ticket lingers.
     */
    public function handle(): int
    {
        if (! config('plated.voting')) {
            $this->info('Voting is disabled (no cache configured); nothing to expedite.');

            return self::SUCCESS;
        }

        $threshold = (int) config('plated.cook_threshold');
        $cooked = 0;

        foreach (Dish::onThePass()->get() as $dish) {
            try {
                $up = (int) Cache::get("dish:{$dish->id}:up", 0);
                $down = (int) Cache::get("dish:{$dish->id}:down", 0);
            } catch (\Throwable) {
                $this->error('Tally store unreachable — aborting without cooking anything.');

                return self::FAILURE;
            }

            if ($up - $down < $threshold) {
                continue;
            }

            // Same race-safe guard the board uses: only the update that flips
            // the status off "plated" broadcasts, so a dish cooks exactly once.
            $flipped = Dish::where('id', $dish->id)
                ->where('status', DishStatus::Plated)
                ->update(['status' => DishStatus::Cooked]);

            if ($flipped === 0) {
                continue;
            }

            DishCooked::dispatch($dish->id, $dish->glyph);
            $cooked++;
        }

        $this->info("Expedited {$cooked} dish(es) off the pass.");

        return self::SUCCESS;
    }
}
