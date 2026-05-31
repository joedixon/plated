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

    /** @var array<string, int> */
    private array $heartbeats = [];

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
    }

    public function recordVoter(int $dishId, string $voterId): bool
    {
        if (isset($this->voters[$dishId][$voterId])) {
            return false;
        }

        $this->voters[$dishId][$voterId] = true;

        return true;
    }

    public function heartbeat(string $voterId): int
    {
        $this->heartbeats[$voterId] = now()->timestamp;

        return $this->connections();
    }

    public function connections(): int
    {
        $cutoff = now()->timestamp - 30;

        $this->heartbeats = array_filter($this->heartbeats, fn (int $at): bool => $at > $cutoff);

        return count($this->heartbeats);
    }
}
