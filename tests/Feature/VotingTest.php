<?php

use App\Enums\DishStatus;
use App\Events\DishCooked;
use App\Events\TallyUpdated;
use App\Models\Dish;
use App\Support\ArrayVoteTally;
use App\Support\Contracts\VoteTally;
use Illuminate\Support\Facades\Event;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->tally = new ArrayVoteTally;
    $this->app->instance(VoteTally::class, $this->tally);
});

it('records an upvote and broadcasts the new tally', function () {
    Event::fake([TallyUpdated::class]);

    $dish = Dish::factory()->create();
    $this->tally->seed($dish->id, 10, 2);

    Volt::test('menu-board')
        ->call('vote', $dish->id, 'up');

    expect($this->tally->counts($dish->id))->toBe(['up' => 11, 'down' => 2]);

    Event::assertDispatched(TallyUpdated::class, fn (TallyUpdated $e) => $e->dishId === $dish->id
        && $e->up === 11
        && $e->down === 2);
});

it('records a downvote', function () {
    $dish = Dish::factory()->create();
    $this->tally->seed($dish->id, 5, 5);

    Volt::test('menu-board')
        ->call('vote', $dish->id, 'down');

    expect($this->tally->counts($dish->id))->toBe(['up' => 5, 'down' => 6]);
});

it('only counts one vote per visitor per dish', function () {
    Event::fake([TallyUpdated::class]);

    $dish = Dish::factory()->create();
    $this->tally->seed($dish->id, 0, 0);

    $component = Volt::test('menu-board');

    $component->call('vote', $dish->id, 'up');
    $component->call('vote', $dish->id, 'up');
    $component->call('vote', $dish->id, 'down');

    expect($this->tally->counts($dish->id))->toBe(['up' => 1, 'down' => 0]);

    Event::assertDispatchedTimes(TallyUpdated::class, 1);
});

it('ignores an invalid vote direction', function () {
    Event::fake([TallyUpdated::class]);

    $dish = Dish::factory()->create();
    $this->tally->seed($dish->id, 1, 1);

    Volt::test('menu-board')
        ->call('vote', $dish->id, 'sideways');

    expect($this->tally->counts($dish->id))->toBe(['up' => 1, 'down' => 1]);

    Event::assertNotDispatched(TallyUpdated::class);
});

it('cooks a dish once its net score reaches the threshold', function () {
    config()->set('plated.cook_threshold', 5);
    Event::fake([DishCooked::class]);

    $dish = Dish::factory()->create(['status' => DishStatus::Plated, 'glyph' => '🍤']);

    $component = Volt::test('menu-board');

    foreach (range(1, 5) as $voter) {
        $component->set('voterId', "voter-{$voter}")
            ->call('vote', $dish->id, 'up');
    }

    expect($dish->fresh()->status)->toBe(DishStatus::Cooked);

    Event::assertDispatched(DishCooked::class, fn (DishCooked $e) => $e->dishId === $dish->id
        && $e->glyph === '🍤');
});

it('does not cook below the threshold and cooks at most once', function () {
    config()->set('plated.cook_threshold', 5);
    Event::fake([DishCooked::class]);

    $dish = Dish::factory()->create(['status' => DishStatus::Plated]);

    $component = Volt::test('menu-board');

    // Four upvotes is one short of the threshold.
    foreach (range(1, 4) as $voter) {
        $component->set('voterId', "voter-{$voter}")
            ->call('vote', $dish->id, 'up');
    }

    expect($dish->fresh()->status)->toBe(DishStatus::Plated);
    Event::assertNotDispatched(DishCooked::class);

    // Two more cross the threshold, but the dish only cooks once.
    foreach (range(5, 6) as $voter) {
        $component->set('voterId', "voter-{$voter}")
            ->call('vote', $dish->id, 'up');
    }

    expect($dish->fresh()->status)->toBe(DishStatus::Cooked);
    Event::assertDispatchedTimes(DishCooked::class, 1);
});
