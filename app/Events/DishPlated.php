<?php

namespace App\Events;

use App\Models\Dish;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DishPlated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Dish $dish) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('board'),
        ];
    }

    /**
     * The name the event is broadcast under.
     */
    public function broadcastAs(): string
    {
        return 'DishPlated';
    }

    /**
     * A freshly plated ticket lands on the pass with zeroed tallies.
     *
     * @return array{id: int, name: string, description: string, pairing: string, glyph: string, sequence: int, up: int, down: int, pct: int}
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->dish->id,
            'name' => $this->dish->name,
            'description' => $this->dish->description,
            'pairing' => $this->dish->pairing,
            'glyph' => $this->dish->glyph,
            'sequence' => $this->dish->sequence,
            'up' => 0,
            'down' => 0,
            'pct' => 0,
        ];
    }
}
