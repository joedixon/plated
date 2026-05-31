<?php

namespace App\Support;

use App\Models\Dish;
use App\Models\Vote;
use App\Support\Contracts\VoteTally;

/**
 * A database-backed VoteTally. Counts live as denormalized columns on the
 * dishes table, while a votes table records who has voted to enforce one vote
 * per visitor per dish.
 */
class DatabaseVoteTally implements VoteTally
{
    public function up(int $dishId): int
    {
        Dish::whereKey($dishId)->increment('up');

        return (int) Dish::whereKey($dishId)->value('up');
    }

    public function down(int $dishId): int
    {
        Dish::whereKey($dishId)->increment('down');

        return (int) Dish::whereKey($dishId)->value('down');
    }

    public function counts(int $dishId): array
    {
        $dish = Dish::whereKey($dishId)->first(['up', 'down']);

        return [
            'up' => (int) ($dish->up ?? 0),
            'down' => (int) ($dish->down ?? 0),
        ];
    }

    public function countsForMany(array $dishIds): array
    {
        if ($dishIds === []) {
            return [];
        }

        $counts = [];

        foreach ($dishIds as $dishId) {
            $counts[$dishId] = ['up' => 0, 'down' => 0];
        }

        Dish::whereIn('id', $dishIds)
            ->get(['id', 'up', 'down'])
            ->each(function (Dish $dish) use (&$counts): void {
                $counts[$dish->id] = ['up' => (int) $dish->up, 'down' => (int) $dish->down];
            });

        return $counts;
    }

    public function seed(int $dishId, int $up, int $down): void
    {
        Dish::whereKey($dishId)->update(['up' => $up, 'down' => $down]);

        // A freshly seeded dish starts with no voters, so a reused dish id can
        // never inherit an earlier dish's voters.
        Vote::where('dish_id', $dishId)->delete();
    }

    public function recordVoter(int $dishId, string $voterId): bool
    {
        $inserted = Vote::insertOrIgnore([
            'dish_id' => $dishId,
            'voter_id' => $voterId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $inserted === 1;
    }
}
