<?php

namespace App\Support;

use App\Support\Contracts\VoteTally;
use Illuminate\Support\Facades\Redis;

class RedisVoteTally implements VoteTally
{
    /**
     * How long a voter's "already voted" record is remembered, in seconds.
     */
    private const VOTER_TTL = 86400;

    /**
     * How long a connection heartbeat counts as live, in seconds.
     */
    private const CONNECTION_TTL = 30;

    public function up(int $dishId): int
    {
        return (int) Redis::incr($this->upKey($dishId));
    }

    public function down(int $dishId): int
    {
        return (int) Redis::incr($this->downKey($dishId));
    }

    public function counts(int $dishId): array
    {
        [$up, $down] = Redis::mget([$this->upKey($dishId), $this->downKey($dishId)]);

        return ['up' => (int) $up, 'down' => (int) $down];
    }

    public function countsForMany(array $dishIds): array
    {
        if ($dishIds === []) {
            return [];
        }

        $keys = [];

        foreach ($dishIds as $dishId) {
            $keys[] = $this->upKey($dishId);
            $keys[] = $this->downKey($dishId);
        }

        $values = Redis::mget($keys);

        $counts = [];

        foreach (array_values($dishIds) as $index => $dishId) {
            $counts[$dishId] = [
                'up' => (int) ($values[$index * 2] ?? 0),
                'down' => (int) ($values[$index * 2 + 1] ?? 0),
            ];
        }

        return $counts;
    }

    public function seed(int $dishId, int $up, int $down): void
    {
        Redis::set($this->upKey($dishId), $up);
        Redis::set($this->downKey($dishId), $down);
    }

    public function recordVoter(int $dishId, string $voterId): bool
    {
        $key = $this->votersKey($dishId);

        $added = (int) Redis::sadd($key, $voterId);

        Redis::expire($key, self::VOTER_TTL);

        return $added === 1;
    }

    public function heartbeat(string $voterId): int
    {
        Redis::zadd($this->connectionsKey(), now()->timestamp, $voterId);

        return $this->connections();
    }

    public function connections(): int
    {
        $cutoff = now()->timestamp - self::CONNECTION_TTL;

        Redis::zremrangebyscore($this->connectionsKey(), '-inf', (string) $cutoff);

        return (int) Redis::zcard($this->connectionsKey());
    }

    private function upKey(int $dishId): string
    {
        return "dish:{$dishId}:up";
    }

    private function downKey(int $dishId): string
    {
        return "dish:{$dishId}:down";
    }

    private function votersKey(int $dishId): string
    {
        return "dish:{$dishId}:voters";
    }

    private function connectionsKey(): string
    {
        return 'stats:connections';
    }
}
