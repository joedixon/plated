<?php

namespace Database\Seeders;

use App\Enums\DishStatus;
use App\Models\Dish;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class DishSeeder extends Seeder
{
    /**
     * The dishes the menu opens with, mirroring the original board design.
     *
     * @var list<array{name: string, description: string, pairing: string, glyph: string, sequence: int, up: int, down: int}>
     */
    private const OPENING_MENU = [
        [
            'name' => 'Miso-Glazed Pumpkin Wellington',
            'description' => 'Roasted kabocha wrapped in flaky pastry, red miso butter, crisp sage.',
            'pairing' => 'Junmai sake, served cool',
            'glyph' => '🥧',
            'sequence' => 142,
            'up' => 142,
            'down' => 8,
        ],
        [
            'name' => 'Honey-Lacquered Duck, Sour Cherries',
            'description' => 'Buckwheat-honey duck breast, morello cherry jus, charred spring onion.',
            'pairing' => 'Burgundy pinot noir',
            'glyph' => '🦆',
            'sequence' => 143,
            'up' => 203,
            'down' => 11,
        ],
        [
            'name' => 'Charred Tomato Carbonara, Black Lime',
            'description' => 'Bucatini, smoked yolk, guanciale, fire-blistered datterini, dried lime.',
            'pairing' => 'Etna rosso, lightly chilled',
            'glyph' => '🍝',
            'sequence' => 144,
            'up' => 89,
            'down' => 23,
        ],
        [
            'name' => 'Smoked Beet Tartare on Rye Crisps',
            'description' => 'Hay-smoked beetroot, capers, smoked yolk, charred shallot on sour rye.',
            'pairing' => 'Aquavit, frozen',
            'glyph' => '🥩',
            'sequence' => 145,
            'up' => 67,
            'down' => 41,
        ],
        [
            'name' => 'Coconut Dal, Curry Leaf Tarka',
            'description' => 'Yellow lentils, coconut milk, hot mustard seed and fried curry leaf finish.',
            'pairing' => 'Fennel-lime soda',
            'glyph' => '🍛',
            'sequence' => 146,
            'up' => 156,
            'down' => 4,
        ],
        [
            'name' => 'Pickled Strawberry Caprese',
            'description' => 'Tarragon-pickled berries, smoked stracciatella, peppercorn oil, basil flower.',
            'pairing' => 'Dry riesling',
            'glyph' => '🍓',
            'sequence' => 147,
            'up' => 34,
            'down' => 78,
        ],
    ];

    /**
     * Seed the opening menu and its starting vote tallies.
     */
    public function run(): void
    {
        foreach (self::OPENING_MENU as $dish) {
            $model = Dish::updateOrCreate(
                ['sequence' => $dish['sequence']],
                [
                    'name' => $dish['name'],
                    'description' => $dish['description'],
                    'pairing' => $dish['pairing'],
                    'glyph' => $dish['glyph'],
                    'status' => DishStatus::Plated,
                ],
            );

            Cache::store('redis')->forever("dish:{$model->id}:up", $dish['up']);
            Cache::store('redis')->forever("dish:{$model->id}:down", $dish['down']);
        }

        // Seed the room-wide running total to match the opening tallies.
        $total = collect(self::OPENING_MENU)->sum(fn (array $dish): int => $dish['up'] + $dish['down']);
        Cache::store('redis')->forever('votes:total', $total);
    }
}
