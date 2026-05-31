<?php

namespace App\Support\Contracts;

interface VoteTally
{
    /**
     * Register an upvote for a dish and return its new upvote total.
     */
    public function up(int $dishId): int;

    /**
     * Register a downvote for a dish and return its new downvote total.
     */
    public function down(int $dishId): int;

    /**
     * Get the up/down counts for a single dish.
     *
     * @return array{up: int, down: int}
     */
    public function counts(int $dishId): array;

    /**
     * Get the up/down counts for many dishes in a single round trip.
     *
     * @param  array<int, int>  $dishIds
     * @return array<int, array{up: int, down: int}>
     */
    public function countsForMany(array $dishIds): array;

    /**
     * Seed a dish's counters to a known starting value and clear any recorded
     * voters, so a freshly plated dish always starts clean.
     */
    public function seed(int $dishId, int $up, int $down): void;

    /**
     * Record that a voter has voted on a dish. Returns true if this is the
     * first time the voter has voted on this dish (i.e. the vote counts).
     */
    public function recordVoter(int $dishId, string $voterId): bool;
}
