<?php

use App\Ai\Agents\DishComposer;
use App\Enums\DishStatus;
use App\Events\DishPlated;
use App\Jobs\GenerateDish;
use App\Models\Dish;
use Illuminate\Support\Facades\Event;

it('plates a new dish, seeds its tally, and announces it', function () {
    Event::fake([DishPlated::class]);
    DishComposer::fake();

    GenerateDish::dispatchSync();

    expect(Dish::count())->toBe(1);

    $dish = Dish::sole();
    expect($dish->status)->toBe(DishStatus::Plated)
        ->and($dish->name)->not->toBeEmpty()
        ->and($dish->sequence)->toBeGreaterThan(0)
        ->and(tallyCounts($dish->id))->toBe(['up' => 0, 'down' => 0]);

    Event::assertDispatched(DishPlated::class, fn (DishPlated $e) => $e->dish->is($dish));
});

it('assigns the next sequence number', function () {
    DishComposer::fake();
    Dish::factory()->create(['sequence' => 200]);

    GenerateDish::dispatchSync();

    expect(Dish::max('sequence'))->toBe(201);
});

it('briefs the agent to avoid dishes already on the menu', function () {
    DishComposer::fake();

    Dish::factory()->create(['sequence' => 1, 'name' => 'Charred Leek, Burnt Lemon']);
    Dish::factory()->create(['sequence' => 2, 'name' => 'Smoked Beetroot Tartare']);

    GenerateDish::dispatchSync();

    DishComposer::assertPrompted(fn ($prompt) => $prompt->contains('Charred Leek, Burnt Lemon')
        && $prompt->contains('Smoked Beetroot Tartare'));
});
