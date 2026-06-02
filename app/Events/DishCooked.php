<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DishCooked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public int $dishId,
        public string $glyph,
    ) {}

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
        return 'DishCooked';
    }

    /**
     * A dish hit its vote threshold — flare its glyph and clear the ticket.
     *
     * @return array{dishId: int, glyph: string}
     */
    public function broadcastWith(): array
    {
        return [
            'dishId' => $this->dishId,
            'glyph' => $this->glyph,
        ];
    }
}
