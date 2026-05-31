<?php

namespace App\Support;

final class Approval
{
    /**
     * The approval percentage for a dish given its up and down votes.
     */
    public static function percentage(int $up, int $down): int
    {
        return (int) round($up / max(1, $up + $down) * 100);
    }
}
