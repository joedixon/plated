<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Seed a dish's cached vote counters, the way the seeder and job do in the app.
 */
function seedTally(int $dishId, int $up, int $down): void
{
    Cache::forever("dish:{$dishId}:up", $up);
    Cache::forever("dish:{$dishId}:down", $down);
}

/**
 * Read a dish's cached vote counters.
 *
 * @return array{up: int, down: int}
 */
function tallyCounts(int $dishId): array
{
    return [
        'up' => (int) Cache::get("dish:{$dishId}:up", 0),
        'down' => (int) Cache::get("dish:{$dishId}:down", 0),
    ];
}
