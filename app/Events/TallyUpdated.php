<?php

namespace App\Events;

use App\Support\Approval;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TallyUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public int $dishId,
        public int $up,
        public int $down,
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
        return 'TallyUpdated';
    }

    /**
     * The payload that lands on every connected board.
     *
     * @return array{dishId: int, up: int, down: int, pct: int}
     */
    public function broadcastWith(): array
    {
        return [
            'dishId' => $this->dishId,
            'up' => $this->up,
            'down' => $this->down,
            'pct' => Approval::percentage($this->up, $this->down),
        ];
    }
}
