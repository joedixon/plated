<?php

use App\Models\Dish;
use App\Models\Vote;
use App\Support\DatabaseVoteTally;

beforeEach(function () {
    $this->tally = new DatabaseVoteTally;
});

it('increments the up and down columns on the dish', function () {
    $dish = Dish::factory()->create(['up' => 0, 'down' => 0]);

    expect($this->tally->up($dish->id))->toBe(1)
        ->and($this->tally->up($dish->id))->toBe(2)
        ->and($this->tally->down($dish->id))->toBe(1);

    expect($dish->fresh()->only('up', 'down'))->toBe(['up' => 2, 'down' => 1]);
});

it('reads counts from the dish columns', function () {
    $dish = Dish::factory()->create(['up' => 30, 'down' => 10]);

    expect($this->tally->counts($dish->id))->toBe(['up' => 30, 'down' => 10]);
});

it('reads counts for many dishes in one go', function () {
    $a = Dish::factory()->create(['up' => 5, 'down' => 1]);
    $b = Dish::factory()->create(['up' => 0, 'down' => 0]);

    expect($this->tally->countsForMany([$a->id, $b->id]))->toBe([
        $a->id => ['up' => 5, 'down' => 1],
        $b->id => ['up' => 0, 'down' => 0],
    ]);
});

it('records a voter only once per dish', function () {
    $dish = Dish::factory()->create();

    expect($this->tally->recordVoter($dish->id, 'voter-1'))->toBeTrue()
        ->and($this->tally->recordVoter($dish->id, 'voter-1'))->toBeFalse()
        ->and($this->tally->recordVoter($dish->id, 'voter-2'))->toBeTrue();

    expect(Vote::where('dish_id', $dish->id)->count())->toBe(2);
});

it('seeds counts and clears any recorded voters', function () {
    $dish = Dish::factory()->create(['up' => 1, 'down' => 1]);
    $this->tally->recordVoter($dish->id, 'voter-1');

    $this->tally->seed($dish->id, 142, 8);

    expect($this->tally->counts($dish->id))->toBe(['up' => 142, 'down' => 8])
        ->and(Vote::where('dish_id', $dish->id)->count())->toBe(0)
        // The same voter can vote again on the freshly seeded dish.
        ->and($this->tally->recordVoter($dish->id, 'voter-1'))->toBeTrue();
});
