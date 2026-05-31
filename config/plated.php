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

    /*
    |--------------------------------------------------------------------------
    | Cook Threshold
    |--------------------------------------------------------------------------
    |
    | The net vote score (upvotes minus downvotes) a dish must reach for the
    | kitchen to "cook" it — the dish's emoji flares across the screen and the
    | ticket leaves the pass. Enough diners calling for it fires the dish.
    |
    */

    'cook_threshold' => (int) env('PLATED_COOK_THRESHOLD', 5),

    /*
    |--------------------------------------------------------------------------
    | Menu Interval
    |--------------------------------------------------------------------------
    |
    | How often, in seconds, the scheduler fires a fresh AI-generated dish onto
    | the pass. Mapped to the nearest sub-minute scheduler frequency, so values
    | of 5, 10, 15, 20, or 30 land exactly; anything larger runs every minute.
    |
    */

    'menu_interval' => (int) env('PLATED_MENU_INTERVAL', 30),

];
