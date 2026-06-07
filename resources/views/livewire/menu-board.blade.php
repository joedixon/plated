<?php

use App\Enums\DishStatus;
use App\Events\DishCooked;
use App\Events\TallyUpdated;
use App\Models\Dish;
use App\Support\Approval;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

new #[Layout('layouts.app')] class extends Component {
    /**
     * How many dishes the pass shows at once.
     */
    private const BOARD_SIZE = 20;

    /**
     * The dishes on the board, each a flat row ready for the view.
     *
     * @var list<array{id: int, name: string, description: string, pairing: string, glyph: string, sequence: int, up: int, down: int, pct: int}>
     */
    public array $dishes = [];

    /**
     * A stable id for this visitor, used to keep voting to one per dish.
     */
    public string $voterId = '';

    /**
     * Whether the live tally store (Redis) is reachable. When it isn't, the
     * board still renders the menu — votes just can't be read or cast.
     */
    public bool $tallyOnline = true;

    public function mount(): void
    {
        $this->voterId = session()->getId();

        $this->dishes = Dish::onThePass()->ordered()->limit(self::BOARD_SIZE)->get()
            ->map(fn (Dish $dish): array => $this->rowForModel($dish))
            ->all();
    }

    /**
     * Build a board row from a dish model, pulling in its live tally.
     *
     * @return array{id: int, name: string, description: string, pairing: string, glyph: string, sequence: int, up: int, down: int, pct: int}
     */
    private function rowForModel(Dish $dish): array
    {
        ['up' => $up, 'down' => $down] = $this->tallyFor($dish->id);

        return [
            'id' => $dish->id,
            'name' => $dish->name,
            'description' => $dish->description,
            'pairing' => $dish->pairing,
            'glyph' => $dish->glyph,
            'sequence' => $dish->sequence,
            'up' => $up,
            'down' => $down,
            'pct' => Approval::percentage($up, $down),
        ];
    }

    /**
     * Read a dish's up/down counts, degrading to zeros if the tally store is
     * unreachable so a missing Redis never takes the whole board down.
     *
     * @return array{up: int, down: int}
     */
    private function tallyFor(int $dishId): array
    {
        try {
            return [
                'up' => (int) Cache::store('redis')->get("dish:{$dishId}:up", 0),
                'down' => (int) Cache::store('redis')->get("dish:{$dishId}:down", 0),
            ];
        } catch (\Throwable) {
            $this->tallyOnline = false;

            return ['up' => 0, 'down' => 0];
        }
    }

    /**
     * The running total of every vote cast in the room. Backed by its own
     * counter so it keeps climbing as dishes cook off the board rather than
     * dropping by a ticket's tally when it leaves the pass.
     */
    #[Computed]
    public function totalVotes(): int
    {
        try {
            return (int) Cache::store('redis')->get('votes:total', 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * An inline SVG QR code pointing at the board so the room can scan to join.
     * Generated server-side from APP_URL — no external service, no JS.
     */
    #[Computed]
    public function joinQr(): string
    {
        return QrCode::format('svg')
            ->size(180)
            ->margin(1)
            ->errorCorrection('M')
            ->generate(config('app.url'));
    }

    /**
     * Cast a vote for a dish and broadcast the new tally to the room.
     */
    public function vote(int $dishId, string $direction): void
    {
        if (! in_array($direction, ['up', 'down'], true)) {
            return;
        }

        try {
            // One vote per visitor per dish: add() only succeeds the first time,
            // so a repeat vote is dropped without ever touching the tally.
            if (! Cache::store('redis')->add("dish:{$dishId}:voter:{$this->voterId}", true, now()->addDay())) {
                return;
            }

            // add() guarantees the counter exists, then increment() is an atomic
            // bump — correct no matter how many visitors vote at the same instant.
            $key = "dish:{$dishId}:{$direction}";
            Cache::store('redis')->add($key, 0);
            Cache::store('redis')->increment($key);

            // Bump the room-wide running total alongside the per-dish counter.
            Cache::store('redis')->add('votes:total', 0);
            Cache::store('redis')->increment('votes:total');

            $up = (int) Cache::store('redis')->get("dish:{$dishId}:up", 0);
            $down = (int) Cache::store('redis')->get("dish:{$dishId}:down", 0);
        } catch (\Throwable) {
            $this->tallyOnline = false;

            return;
        }

        $this->applyCounts($dishId, $up, $down);

        TallyUpdated::dispatch($dishId, $up, $down);

        $this->cookIfReady($dishId, $up, $down);
    }

    /**
     * Fire the dish off the pass once enough diners have called for it. The
     * status transition doubles as the guard, so only the request that first
     * crosses the threshold broadcasts — even under concurrent votes.
     */
    private function cookIfReady(int $dishId, int $up, int $down): void
    {
        if ($up - $down < config('plated.cook_threshold')) {
            return;
        }

        $cooked = Dish::where('id', $dishId)
            ->where('status', DishStatus::Plated)
            ->update(['status' => DishStatus::Cooked]);

        if ($cooked === 0) {
            return;
        }

        $glyph = collect($this->dishes)->firstWhere('id', $dishId)['glyph'] ?? '🍽️';

        DishCooked::dispatch($dishId, $glyph);
    }

    /**
     * Apply a tally broadcast from anywhere in the room.
     */
    #[On('echo:board,.TallyUpdated')]
    public function onTallyUpdated(array $event): void
    {
        $this->applyCounts((int) $event['dishId'], (int) $event['up'], (int) $event['down']);
    }

    /**
     * A new dish was plated — slide it in at the top of the pass.
     */
    #[On('echo:board,.DishPlated')]
    public function onDishPlated(array $event): void
    {
        foreach ($this->dishes as $dish) {
            if ($dish['id'] === (int) $event['id']) {
                return;
            }
        }

        array_unshift($this->dishes, [
            'id' => (int) $event['id'],
            'name' => $event['name'],
            'description' => $event['description'],
            'pairing' => $event['pairing'],
            'glyph' => $event['glyph'],
            'sequence' => (int) $event['sequence'],
            'up' => (int) $event['up'],
            'down' => (int) $event['down'],
            'pct' => (int) $event['pct'],
        ]);

        // Keep the board capped at its size, matching mount().
        $this->dishes = array_slice($this->dishes, 0, self::BOARD_SIZE);
    }

    /**
     * A dish hit its threshold — flare its glyph and clear it off the pass.
     * Driven by the broadcast so every board, including the voter who tipped
     * it over, plays the animation and drops the ticket exactly once.
     */
    #[On('echo:board,.DishCooked')]
    public function onDishCooked(array $event): void
    {
        $this->dishes = array_values(array_filter(
            $this->dishes,
            fn (array $dish): bool => $dish['id'] !== (int) $event['dishId'],
        ));

        $this->backfill();

        $this->dispatch('dish-cooked', glyph: $event['glyph']);
    }

    /**
     * Top the pass back up to its full size after a dish cooks off, pulling in
     * the next oldest plated tickets that aren't already on the board.
     */
    private function backfill(): void
    {
        $needed = self::BOARD_SIZE - count($this->dishes);

        if ($needed < 1) {
            return;
        }

        Dish::onThePass()->ordered()
            ->whereNotIn('id', array_column($this->dishes, 'id'))
            ->limit($needed)
            ->get()
            ->each(fn (Dish $dish) => $this->dishes[] = $this->rowForModel($dish));
    }

    /**
     * Update the cached counts for a single dish row.
     */
    private function applyCounts(int $dishId, int $up, int $down): void
    {
        foreach ($this->dishes as $index => $dish) {
            if ($dish['id'] === $dishId) {
                $this->dishes[$index]['up'] = $up;
                $this->dishes[$index]['down'] = $down;
                $this->dishes[$index]['pct'] = Approval::percentage($up, $down);

                return;
            }
        }
    }
}; ?>

<main class="isolate min-h-dvh bg-[#F5EFE0] font-mono text-[#1A1814] [background-image:radial-gradient(#1a18141a_1px,transparent_1px)] [background-size:14px_14px]">
    <div
        x-data="{ cooks: [] }"
        x-on:dish-cooked.window="
            const id = Date.now() + Math.random();
            cooks.push({ id, glyph: $event.detail.glyph });
            setTimeout(() => cooks = cooks.filter(c => c.id !== id), 1600);
        "
        class="pointer-events-none fixed inset-0 z-50 flex items-center justify-center"
        aria-hidden="true"
    >
        <template x-for="cook in cooks" :key="cook.id">
            <span x-text="cook.glyph" class="absolute animate-plate-pop text-[10rem] drop-shadow-xl sm:text-[16rem]"></span>
        </template>
    </div>

    <div class="mx-auto max-w-7xl px-5 pt-10 pb-20 sm:px-8 lg:px-12">

        @unless ($tallyOnline)
            <div class="mb-8 flex items-center gap-3 rounded-sm border-2 border-[#C8362E] bg-[#C8362E]/10 px-4 py-3 text-sm text-[#1A1814]" role="status">
                <span class="font-stencil text-base uppercase tracking-wider text-[#C8362E]">Tally offline</span>
                <span class="text-[#5B5147]">The live vote store is unreachable, so voting is paused. The menu is still being plated.</span>
            </div>
        @endunless

        <header class="mb-8 grid gap-6 border-b-2 border-dashed border-[#1A1814]/30 pb-8 lg:grid-cols-[1fr_auto] lg:items-end">
            <div>
                <div class="mb-3 flex items-center gap-3 text-xs uppercase tracking-wider text-[#3FA35A]">
                    <span class="relative flex size-2">
                        <span class="absolute inset-0 animate-ping rounded-full bg-[#3FA35A] opacity-75"></span>
                        <span class="relative inline-flex size-2 rounded-full bg-[#3FA35A]"></span>
                    </span>
                    <span class="font-semibold">On the pass · Live</span>
                </div>
                <h1 class="font-stencil text-6xl uppercase tracking-tight text-balance text-[#1A1814] sm:text-7xl lg:text-8xl">
                    Plated<span class="text-[#C8362E]">.</span>
                </h1>
                <p class="mt-4 max-w-[60ch] text-sm leading-6 text-[#5B5147] text-pretty">
                    AI plates a fresh dish onto the pass every few seconds. <span class="text-[#1A1814]">Vote each ticket up or down</span> — win the room over and the kitchen cooks it off the pass.
                </p>
            </div>
            <div class="flex flex-col gap-4 lg:w-80">
                <div class="flex items-center gap-4 rounded-sm border-2 border-[#1A1814] bg-[#FBF7EC] p-3">
                    <div class="shrink-0 rounded-sm bg-white p-1.5 [&>svg]:size-28 [&>svg]:fill-[#1A1814]">
                        {!! $this->joinQr !!}
                    </div>
                    <div class="text-left">
                        <p class="font-stencil text-sm uppercase tracking-wider text-[#1A1814]">Scan to join</p>
                        <p class="mt-1 text-[0.7rem] leading-4 text-[#5B5147]">Point your camera here to open the pass and vote.</p>
                    </div>
                </div>
                <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-sm border-2 border-[#1A1814] bg-[#1A1814] text-center text-[#F5EFE0]">
                    <div class="bg-[#1A1814] px-3 py-3">
                        <dt class="text-[0.65rem] uppercase tracking-wider text-[#F5EFE0]/70">Orders</dt>
                        <dd class="mt-1 font-stencil text-2xl tabular-nums">{{ count($dishes) }}</dd>
                    </div>
                    <div class="bg-[#1A1814] px-3 py-3">
                        <dt class="text-[0.65rem] uppercase tracking-wider text-[#F5EFE0]/70">Votes</dt>
                        <dd class="mt-1 font-stencil text-2xl tabular-nums">{{ number_format($this->totalVotes) }}</dd>
                    </div>
                </dl>
            </div>
        </header>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" role="list">
                @php
                    $rotations = ['-rotate-[0.6deg]', 'rotate-[0.4deg]', '-rotate-[0.3deg]', 'rotate-[0.7deg]', '-rotate-[0.5deg]', 'rotate-[0.2deg]'];
                @endphp
                @foreach ($dishes as $i => $meal)
                    <article wire:key="dish-{{ $meal['id'] }}" class="relative flex flex-col bg-[#FBF7EC] shadow-[0_2px_0_#1a18141a,0_18px_30px_-20px_#1a181466] {{ $rotations[$i % count($rotations)] }}" role="listitem">
                        <span class="absolute -top-2 left-1/2 h-2 w-8 -translate-x-1/2 rounded-sm bg-[#1A1814]/80" aria-hidden="true"></span>
                        <div class="border-b border-dashed border-[#1A1814]/40 px-5 py-3">
                            <div class="flex items-center justify-between text-[0.7rem] uppercase tracking-wider text-[#5B5147]">
                                <span class="tabular-nums">Order #{{ str_pad($meal['sequence'], 4, '0', STR_PAD_LEFT) }}</span>
                                <span class="rounded-sm bg-[#C8362E] px-1.5 py-0.5 text-[0.625rem] font-semibold text-[#F5EFE0]">ASAP</span>
                            </div>
                        </div>
                        <div class="flex-1 px-5 py-5">
                            <div class="mb-3 flex size-12 items-center justify-center rounded-full bg-[#F5EFE0] text-2xl ring-1 ring-[#1A1814]/30">
                                <span aria-hidden="true">{{ $meal['glyph'] }}</span>
                            </div>
                            <h2 class="font-stencil text-xl uppercase tracking-tight text-balance text-[#1A1814]">
                                {{ $meal['name'] }}
                            </h2>
                            <p class="mt-2 text-[0.8125rem] leading-5 text-[#5B5147] text-pretty">{{ $meal['description'] }}</p>
                            <p class="mt-3 text-[0.7rem] uppercase tracking-wider text-[#1A1814]/60">Pairs with · <span class="text-[#1A1814]">{{ $meal['pairing'] }}</span></p>
                        </div>
                        <div class="border-t border-dashed border-[#1A1814]/40 px-5 py-3">
                            <div class="mb-2 flex items-center justify-between text-[0.7rem] uppercase tracking-wider text-[#5B5147]">
                                <span>Tally</span>
                                <span class="tabular-nums">{{ $meal['pct'] }}% approval</span>
                            </div>
                            <div class="h-2 overflow-hidden bg-[#1A1814]/10">
                                <div class="h-full bg-[#3E5B3E] w-(--bar)" style="--bar: {{ $meal['pct'] }}%"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 border-t-2 border-[#1A1814]">
                            <button type="button" wire:click="vote({{ $meal['id'] }}, 'up')" class="flex items-center justify-center gap-2 border-r-2 border-[#1A1814] bg-[#FBF7EC] px-4 py-3 text-sm font-semibold uppercase tracking-wider text-[#1A1814] transition hover:bg-[#3E5B3E] hover:text-[#F5EFE0]">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 11V21H4a1 1 0 01-1-1v-8a1 1 0 011-1h3zm0 0l4-7a2 2 0 012-2h.5a1.5 1.5 0 011.5 1.5V8h5.5a2 2 0 012 2l-2 9a2 2 0 01-2 1.5H7"/></svg>
                                <span class="tabular-nums">{{ $meal['up'] }}</span>
                            </button>
                            <button type="button" wire:click="vote({{ $meal['id'] }}, 'down')" class="flex items-center justify-center gap-2 bg-[#FBF7EC] px-4 py-3 text-sm font-semibold uppercase tracking-wider text-[#1A1814] transition hover:bg-[#C8362E] hover:text-[#F5EFE0]">
                                <svg class="size-4 -scale-y-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 11V21H4a1 1 0 01-1-1v-8a1 1 0 011-1h3zm0 0l4-7a2 2 0 012-2h.5a1.5 1.5 0 011.5 1.5V8h5.5a2 2 0 012 2l-2 9a2 2 0 01-2 1.5H7"/></svg>
                                <span class="tabular-nums">{{ $meal['down'] }}</span>
                            </button>
                        </div>
                    </article>
                @endforeach
        </div>

        <footer class="mt-16 grid gap-3 border-t-2 border-dashed border-[#1A1814]/30 pt-6 text-[0.7rem] uppercase tracking-wider text-[#5B5147] sm:grid-cols-3">
            <p>Pass closes at the end of the webinar. Tickets keep firing on the queue.</p>
            <p
                class="sm:text-center"
                x-data="{ n: 0 }"
                x-init="window.Echo.join('board')
                    .here(users => n = users.length)
                    .joining(() => n++)
                    .leaving(() => n--)"
            >Connections <span class="ml-2 tabular-nums text-[#1A1814]" x-text="n">0</span></p>
            <p class="sm:text-right">Plated · Laravel Cloud 2.0 demo</p>
        </footer>
    </div>
</main>
