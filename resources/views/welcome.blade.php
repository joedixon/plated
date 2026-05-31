@php
    $meals = [
        [
            'name' => 'Miso-Glazed Pumpkin Wellington',
            'desc' => 'Roasted kabocha wrapped in flaky pastry, red miso butter, crisp sage.',
            'pairing' => 'Junmai sake, served cool',
            'glyph' => '🥧',
            'up' => 142,
            'down' => 8,
            'order' => 142,
        ],
        [
            'name' => 'Honey-Lacquered Duck, Sour Cherries',
            'desc' => 'Buckwheat-honey duck breast, morello cherry jus, charred spring onion.',
            'pairing' => 'Burgundy pinot noir',
            'glyph' => '🦆',
            'up' => 203,
            'down' => 11,
            'order' => 143,
        ],
        [
            'name' => 'Charred Tomato Carbonara, Black Lime',
            'desc' => 'Bucatini, smoked yolk, guanciale, fire-blistered datterini, dried lime.',
            'pairing' => 'Etna rosso, lightly chilled',
            'glyph' => '🍝',
            'up' => 89,
            'down' => 23,
            'order' => 144,
        ],
        [
            'name' => 'Smoked Beet Tartare on Rye Crisps',
            'desc' => 'Hay-smoked beetroot, capers, smoked yolk, charred shallot on sour rye.',
            'pairing' => 'Aquavit, frozen',
            'glyph' => '🥩',
            'up' => 67,
            'down' => 41,
            'order' => 145,
        ],
        [
            'name' => 'Coconut Dal, Curry Leaf Tarka',
            'desc' => 'Yellow lentils, coconut milk, hot mustard seed and fried curry leaf finish.',
            'pairing' => 'Fennel-lime soda',
            'glyph' => '🍛',
            'up' => 156,
            'down' => 4,
            'order' => 146,
        ],
        [
            'name' => 'Pickled Strawberry Caprese',
            'desc' => 'Tarragon-pickled berries, smoked stracciatella, peppercorn oil, basil flower.',
            'pairing' => 'Dry riesling',
            'glyph' => '🍓',
            'up' => 34,
            'down' => 78,
            'order' => 147,
        ],
    ];

    $totalUp = array_sum(array_column($meals, 'up'));
    $totalDown = array_sum(array_column($meals, 'down'));
    $totalVotes = $totalUp + $totalDown;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Plated — live</title>

        <link rel="preconnect" href="https://rsms.me/">
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="antialiased">
        <main class="isolate min-h-dvh bg-[#F5EFE0] font-mono text-[#1A1814] [background-image:radial-gradient(#1a18141a_1px,transparent_1px)] [background-size:14px_14px]">
            <div class="mx-auto max-w-7xl px-5 pt-10 pb-20 sm:px-8 lg:px-12">

                <header class="mb-12 grid gap-6 border-b-2 border-dashed border-[#1A1814]/30 pb-8 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div>
                        <div class="mb-3 flex items-center gap-3 text-xs uppercase tracking-wider text-[#C8362E]">
                            <span class="relative flex size-2">
                                <span class="absolute inset-0 animate-ping rounded-full bg-[#C8362E] opacity-75"></span>
                                <span class="relative inline-flex size-2 rounded-full bg-[#C8362E]"></span>
                            </span>
                            <span class="font-semibold">On the pass · Live</span>
                        </div>
                        <h1 class="font-stencil text-6xl uppercase tracking-tight text-balance text-[#1A1814] sm:text-7xl lg:text-8xl">
                            Plated<span class="text-[#C8362E]">.</span>
                        </h1>
                        <p class="mt-4 max-w-[60ch] text-sm leading-6 text-[#5B5147] text-pretty">
                            Tonight's menu is generated as we go. Tickets land on the pass when the line cooks (a.k.a. your queue workers) finish them. <span class="text-[#1A1814]">Vote thumbs up or down on each ticket</span> and the tally rings in for the whole room.
                        </p>
                    </div>
                    <dl class="grid grid-cols-3 gap-px overflow-hidden rounded-sm border-2 border-[#1A1814] bg-[#1A1814] text-center text-[#F5EFE0] lg:w-80">
                        <div class="bg-[#1A1814] px-3 py-3">
                            <dt class="text-[0.65rem] uppercase tracking-wider text-[#F5EFE0]/70">Orders</dt>
                            <dd class="mt-1 font-stencil text-2xl tabular-nums">{{ count($meals) }}</dd>
                        </div>
                        <div class="bg-[#1A1814] px-3 py-3">
                            <dt class="text-[0.65rem] uppercase tracking-wider text-[#F5EFE0]/70">Votes</dt>
                            <dd class="mt-1 font-stencil text-2xl tabular-nums">{{ number_format($totalVotes) }}</dd>
                        </div>
                        <div class="bg-[#1A1814] px-3 py-3">
                            <dt class="text-[0.65rem] uppercase tracking-wider text-[#F5EFE0]/70">Workers</dt>
                            <dd class="mt-1 font-stencil text-2xl tabular-nums">4</dd>
                        </div>
                    </dl>
                </header>

                <div class="relative">
                    <div class="absolute inset-x-0 top-0 h-px bg-[#1A1814]/40" aria-hidden="true"></div>
                    <div class="grid gap-6 pt-6 sm:grid-cols-2 lg:grid-cols-3" role="list">
                        @foreach ($meals as $i => $meal)
                            @php
                                $pct = round($meal['up'] / max(1, $meal['up'] + $meal['down']) * 100);
                                $rotate = ['-rotate-[0.6deg]', 'rotate-[0.4deg]', '-rotate-[0.3deg]', 'rotate-[0.7deg]', '-rotate-[0.5deg]', 'rotate-[0.2deg]'][$i];
                            @endphp
                            <article class="relative bg-[#FBF7EC] shadow-[0_2px_0_#1a18141a,0_18px_30px_-20px_#1a181466] {{ $rotate }}" role="listitem">
                                <span class="absolute -top-2 left-1/2 h-2 w-8 -translate-x-1/2 rounded-sm bg-[#1A1814]/80" aria-hidden="true"></span>
                                <div class="border-b border-dashed border-[#1A1814]/40 px-5 py-3">
                                    <div class="flex items-center justify-between text-[0.7rem] uppercase tracking-wider text-[#5B5147]">
                                        <span class="tabular-nums">Order #{{ str_pad($meal['order'], 4, '0', STR_PAD_LEFT) }}</span>
                                        <span class="rounded-sm bg-[#C8362E] px-1.5 py-0.5 text-[0.625rem] font-semibold text-[#F5EFE0]">ASAP</span>
                                    </div>
                                </div>
                                <div class="px-5 py-5">
                                    <div class="mb-3 flex size-12 items-center justify-center rounded-full bg-[#F5EFE0] text-2xl ring-1 ring-[#1A1814]/30">
                                        <span aria-hidden="true">{{ $meal['glyph'] }}</span>
                                    </div>
                                    <h2 class="font-stencil text-xl uppercase tracking-tight text-balance text-[#1A1814]">
                                        {{ $meal['name'] }}
                                    </h2>
                                    <p class="mt-2 text-[0.8125rem] leading-5 text-[#5B5147] text-pretty">{{ $meal['desc'] }}</p>
                                    <p class="mt-3 text-[0.7rem] uppercase tracking-wider text-[#1A1814]/60">Pairs with · <span class="text-[#1A1814]">{{ $meal['pairing'] }}</span></p>
                                </div>
                                <div class="border-t border-dashed border-[#1A1814]/40 px-5 py-3">
                                    <div class="mb-2 flex items-center justify-between text-[0.7rem] uppercase tracking-wider text-[#5B5147]">
                                        <span>Tally</span>
                                        <span class="tabular-nums">{{ $pct }}% approval</span>
                                    </div>
                                    <div class="h-2 overflow-hidden bg-[#1A1814]/10">
                                        <div class="h-full bg-[#3E5B3E] w-(--bar)" style="--bar: {{ $pct }}%"></div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 border-t-2 border-[#1A1814]">
                                    <button type="button" class="flex items-center justify-center gap-2 border-r-2 border-[#1A1814] bg-[#FBF7EC] px-4 py-3 text-sm font-semibold uppercase tracking-wider text-[#1A1814] transition hover:bg-[#3E5B3E] hover:text-[#F5EFE0]">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 11V21H4a1 1 0 01-1-1v-8a1 1 0 011-1h3zm0 0l4-7a2 2 0 012-2h.5a1.5 1.5 0 011.5 1.5V8h5.5a2 2 0 012 2l-2 9a2 2 0 01-2 1.5H7"/></svg>
                                        <span class="tabular-nums">{{ $meal['up'] }}</span>
                                    </button>
                                    <button type="button" class="flex items-center justify-center gap-2 bg-[#FBF7EC] px-4 py-3 text-sm font-semibold uppercase tracking-wider text-[#1A1814] transition hover:bg-[#C8362E] hover:text-[#F5EFE0]">
                                        <svg class="size-4 -scale-y-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 11V21H4a1 1 0 01-1-1v-8a1 1 0 011-1h3zm0 0l4-7a2 2 0 012-2h.5a1.5 1.5 0 011.5 1.5V8h5.5a2 2 0 012 2l-2 9a2 2 0 01-2 1.5H7"/></svg>
                                        <span class="tabular-nums">{{ $meal['down'] }}</span>
                                    </button>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>

                <footer class="mt-16 grid gap-3 border-t-2 border-dashed border-[#1A1814]/30 pt-6 text-[0.7rem] uppercase tracking-wider text-[#5B5147] sm:grid-cols-3">
                    <p>Pass closes at the end of the webinar. Tickets keep firing on the queue.</p>
                    <p class="sm:text-center">Connections <span class="ml-2 tabular-nums text-[#1A1814]">214</span></p>
                    <p class="sm:text-right">Plated · Laravel Cloud 2.0 demo</p>
                </footer>
            </div>
        </main>
    </body>
</html>
