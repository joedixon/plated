<?php

namespace App\Enums;

enum DishStatus: string
{
    case Firing = 'firing';
    case Plated = 'plated';
    case Cooked = 'cooked';
}
