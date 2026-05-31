<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * A demo-grade spending cap on AI dish generation. It counts how many dishes
 * have been generated today and refuses further generation past the daily cap
 * — mirroring the spending caps Laravel Cloud enforces at the infra level.
 */
class SpendingCap
{
    public function __construct(private int $dailyCap)
    {
        //
    }

    /**
     * Whether another AI dish may be generated today.
     */
    public function canSpend(): bool
    {
        return $this->spentToday() < $this->dailyCap;
    }

    /**
     * Record that an AI dish was generated, expiring the counter after a day.
     */
    public function record(): void
    {
        $key = $this->key();

        Cache::add($key, 0, now()->endOfDay());
        Cache::increment($key);
    }

    /**
     * How many AI dishes have been generated today.
     */
    public function spentToday(): int
    {
        return (int) Cache::get($this->key(), 0);
    }

    /**
     * The daily cap.
     */
    public function cap(): int
    {
        return $this->dailyCap;
    }

    private function key(): string
    {
        return 'stats:ai:dishes:'.now()->toDateString();
    }
}
