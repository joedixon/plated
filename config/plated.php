<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Queue Workers
    |--------------------------------------------------------------------------
    |
    | The number of queue workers shown on the board's header. On Laravel
    | Cloud this maps to the worker concurrency that scale-to-zero spins up
    | on demand to plate new tickets.
    |
    */

    'workers' => (int) env('PLATED_WORKERS', 4),

    /*
    |--------------------------------------------------------------------------
    | AI Daily Dish Cap
    |--------------------------------------------------------------------------
    |
    | The maximum number of AI-generated dishes allowed per day. Mirrors the
    | spending caps enforced at the infrastructure level on Laravel Cloud —
    | once the cap is hit, dish generation stops until the next day.
    |
    */

    'ai_daily_dish_cap' => (int) env('PLATED_AI_DAILY_DISH_CAP', 50),

];
