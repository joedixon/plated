<?php

use App\Enums\DishStatus;
use App\Events\DishPlated;
use App\Events\TallyUpdated;
use App\Models\Dish;
use App\Support\ArrayVoteTally;
use App\Support\Contracts\VoteTally;

beforeEach(function () {
    $this->tally = new ArrayVoteTally;
    $this->app->instance(VoteTally::class, $this->tally);
});

it('renders the board with its dishes and tallies', function () {
    $dish = Dish::factory()->create(['name' => 'Charred Leek Velouté', 'sequence' => 142]);
    $this->tally->seed($dish->id, 30, 10);

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
