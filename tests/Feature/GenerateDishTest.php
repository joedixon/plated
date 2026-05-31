<?php

use App\Ai\Agents\DishComposer;
use App\Enums\DishStatus;
use App\Events\DishPlated;
use App\Jobs\GenerateDish;
use App\Models\Dish;
use App\Support\ArrayVoteTally;
use App\Support\Contracts\VoteTally;
use App\Support\SpendingCap;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\mock;

beforeEach(function () {
    $this->tally = new ArrayVoteTally;
    $this->app->instance(VoteTally::class, $this->tally);
});

it('plates a new dish, seeds its tally, and announces it', function () {
    Event::fake([DishPlated::class]);
    DishComposer::fake();

    mock(SpendingCap::class, function ($mock) {
        $mock->shouldReceive('canSpend')->once()->andReturnTrue();
        $mock->shouldReceive('record')->once();
    });

    GenerateDish::dispatchSync();

    expect(Dish::count())->toBe(1);

    $dish = Dish::sole();
    expect($dish->status)->toBe(DishStatus::Plated)
        ->and($dish->name)->not->toBeEmpty()
        ->and($dish->sequence)->toBeGreaterThan(0)
        ->and($this->tally->counts($dish->id))->toBe(['up' => 0, 'down' => 0]);

    Event::assertDispatched(DishPlated::class, fn (DishPlated $e) => $e->dish->is($dish));
});

it('assigns the next sequence number', function () {
    DishComposer::fake();
    Dish::factory()->create(['sequence' => 200]);

    mock(SpendingCap::class, function ($mock) {
        $mock->shouldReceive('canSpend')->andReturnTrue();
        $mock->shouldReceive('record');
    });

    GenerateDish::dispatchSync();

    expect(Dish::max('sequence'))->toBe(201);
});

it('briefs the agent to avoid dishes already on the menu', function () {
    DishComposer::fake();

    Dish::factory()->create(['sequence' => 1, 'name' => 'Charred Leek, Burnt Lemon']);
    Dish::factory()->create(['sequence' => 2, 'name' => 'Smoked Beetroot Tartare']);

    mock(SpendingCap::class, function ($mock) {
        $mock->shouldReceive('canSpend')->andReturnTrue();
        $mock->shouldReceive('record');
    });

    GenerateDish::dispatchSync();

    DishComposer::assertPrompted(fn ($prompt) => $prompt->contains('Charred Leek, Burnt Lemon')
        && $prompt->contains('Smoked Beetroot Tartare'));
});

it('clears stale voters when a dish id is seeded again', function () {
    DishComposer::fake();

    // A previous dish with this id had a voter recorded against it.
    $this->tally->seed(7, 5, 1);
    expect($this->tally->recordVoter(7, 'voter-1'))->toBeTrue();
    expect($this->tally->recordVoter(7, 'voter-1'))->toBeFalse();

    mock(SpendingCap::class, function ($mock) {
        $mock->shouldReceive('canSpend')->andReturnTrue();
        $mock->shouldReceive('record');
    });

    // Generating a new dish reuses id-based seeding and must start clean, so
    // the same voter can vote again on the freshly plated dish.
    $this->tally->seed(7, 0, 0);

    expect($this->tally->recordVoter(7, 'voter-1'))->toBeTrue();
});

it('does not generate a dish once the spending cap is hit', function () {
    Event::fake([DishPlated::class]);
    DishComposer::fake();

    mock(SpendingCap::class, function ($mock) {
        $mock->shouldReceive('canSpend')->once()->andReturnFalse();
        $mock->shouldReceive('record')->never();
    });

    GenerateDish::dispatchSync();

    expect(Dish::count())->toBe(0);
    Event::assertNotDispatched(DishPlated::class);
    DishComposer::assertNeverPrompted();
});
