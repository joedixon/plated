<?php

use App\Enums\DishStatus;
use App\Events\DishCooked;
use App\Models\Dish;
use Illuminate\Support\Facades\Event;

it('cooks plated dishes already past the threshold and leaves the rest', function () {
    config()->set('plated.cook_threshold', 5);
    Event::fake([DishCooked::class]);

    $ready = Dish::factory()->create(['status' => DishStatus::Plated, 'glyph' => '🍤']);
    $shy = Dish::factory()->create(['status' => DishStatus::Plated]);

    seedTally($ready->id, 7, 1);   // net 6, over the line
    seedTally($shy->id, 5, 2);     // net 3, short

    $this->artisan('plated:expedite')->assertSuccessful();

    expect($ready->fresh()->status)->toBe(DishStatus::Cooked)
        ->and($shy->fresh()->status)->toBe(DishStatus::Plated);

    Event::assertDispatched(DishCooked::class, fn (DishCooked $e) => $e->dishId === $ready->id
        && $e->glyph === '🍤');
    Event::assertDispatchedTimes(DishCooked::class, 1);
});

it('ignores dishes already off the pass', function () {
    config()->set('plated.cook_threshold', 5);
    Event::fake([DishCooked::class]);

    $cooked = Dish::factory()->create(['status' => DishStatus::Cooked]);
    seedTally($cooked->id, 9, 0);

    $this->artisan('plated:expedite')->assertSuccessful();

    Event::assertNotDispatched(DishCooked::class);
});
