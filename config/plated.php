<?php

return [

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
