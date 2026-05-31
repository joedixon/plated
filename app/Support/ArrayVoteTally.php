<?php

namespace App\Support;

use App\Support\Contracts\VoteTally;

/**
 * An in-memory VoteTally used in tests so the suite does not depend on a
 * live Redis/Valkey server.
 */
class ArrayVoteTally implements VoteTally
{
    /** @var array<int, array{up: int, down: int}> */
    private array $counts = [];

    /** @var array<int, array<string, true>> */
    private array $voters = [];

    public function up(int $dishId): int
    {
        $this->counts[$dishId] ??= ['up' => 0, 'down' => 0];

        return ++$this->counts[$dishId]['up'];
    }

    public function down(int $dishId): int
    {
        $this->counts[$dishId] ??= ['up' => 0, 'down' => 0];

        return ++$this->counts[$dishId]['down'];
    }

    public function counts(int $dishId): array
    {
        return $this->counts[$dishId] ?? ['up' => 0, 'down' => 0];
    }

    public function countsForMany(array $dishIds): array
    {
        $counts = [];

        foreach ($dishIds as $dishId) {
            $counts[$dishId] = $this->counts($dishId);
        }

        return $counts;
    }

    public function seed(int $dishId, int $up, int $down): void
    {
        $this->counts[$dishId] = ['up' => $up, 'down' => $down];

        // A freshly plated dish starts with no voters; clearing keeps a reused
        // dish id from inheriting an earlier dish's voters.
        unset($this->voters[$dishId]);
    }

    public function recordVoter(int $dishId, string $voterId): bool
    {
        if (isset($this->voters[$dishId][$voterId])) {
            return false;
        }

        $this->voters[$dishId][$voterId] = true;

        return true;
    }
}
