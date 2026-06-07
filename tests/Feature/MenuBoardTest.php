<?php

use App\Enums\DishStatus;
use App\Events\DishCooked;
use App\Events\DishPlated;
use App\Events\TallyUpdated;
use App\Models\Dish;
use Livewire\Volt\Volt;

it('renders the board with its dishes and tallies', function () {
    $dish = Dish::factory()->create(['name' => 'Charred Leek Velouté', 'sequence' => 142]);
    seedTally($dish->id, 30, 10);

    $this->get('/')
        ->assertOk()
        ->assertSee('Plated')
        ->assertSee('On the pass · Live')
        ->assertSee('Charred Leek Velouté')
        ->assertSee('Order #0142')
        ->assertSee('75% approval');
});

it('keeps cooked dishes off the pass', function () {
    Dish::factory()->create(['name' => 'Charred Leek Velouté', 'status' => DishStatus::Plated]);
    Dish::factory()->create(['name' => 'Sea Urchin Custard', 'status' => DishStatus::Cooked]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Charred Leek Velouté')
        ->assertDontSee('Sea Urchin Custard');
});

it('broadcasts a tally update on the public board channel', function () {
    $event = new TallyUpdated(dishId: 7, up: 8, down: 2);

    expect($event->broadcastOn()[0]->name)->toBe('board')
        ->and($event->broadcastAs())->toBe('TallyUpdated')
        ->and($event->broadcastWith())->toBe([
            'dishId' => 7,
            'up' => 8,
            'down' => 2,
            'pct' => 80,
        ]);
});

it('caps the board and refills it when a dish cooks off', function () {
    Dish::factory()->count(21)->create(['status' => DishStatus::Plated]);

    $component = Volt::test('menu-board');
    expect($component->get('dishes'))->toHaveCount(20);

    // Mirror the real flow: the dish is cooked in the DB before the broadcast.
    $cookedId = $component->get('dishes')[0]['id'];
    Dish::whereKey($cookedId)->update(['status' => DishStatus::Cooked]);
    $component->call('onDishCooked', ['dishId' => $cookedId, 'glyph' => '🍤']);

    expect($component->get('dishes'))->toHaveCount(20)
        ->and(collect($component->get('dishes'))->pluck('id'))->not->toContain($cookedId);
});

it('keeps the running vote total after a dish leaves the pass', function () {
    // High threshold so the vote itself doesn't cook the dish mid-request.
    config()->set('plated.cook_threshold', 100);

    $dish = Dish::factory()->create(['status' => DishStatus::Plated]);
    seedTally($dish->id, 0, 0);

    $component = Volt::test('menu-board');
    $component->call('vote', $dish->id, 'up');

    expect($component->instance()->totalVotes())->toBe(1);

    // The dish cooks off and is removed, but the room's total holds.
    $dish->update(['status' => DishStatus::Cooked]);
    $component->call('onDishCooked', ['dishId' => $dish->id, 'glyph' => '🍤']);

    expect($component->get('dishes'))->toBeEmpty()
        ->and($component->instance()->totalVotes())->toBe(1);
});

it('broadcasts a cooked dish on the public board channel', function () {
    $event = new DishCooked(dishId: 7, glyph: '🍤');

    expect($event->broadcastOn()[0]->name)->toBe('board')
        ->and($event->broadcastAs())->toBe('DishCooked')
        ->and($event->broadcastWith())->toBe([
            'dishId' => 7,
            'glyph' => '🍤',
        ]);
});

it('broadcasts a plated dish on the public board channel', function () {
    $dish = Dish::factory()->create();
    $event = new DishPlated($dish);

    expect($event->broadcastOn()[0]->name)->toBe('board')
        ->and($event->broadcastAs())->toBe('DishPlated')
        ->and($event->broadcastWith())->toMatchArray([
            'id' => $dish->id,
            'name' => $dish->name,
            'sequence' => $dish->sequence,
            'up' => 0,
            'down' => 0,
            'pct' => 0,
        ]);
});
