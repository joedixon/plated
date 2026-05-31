<?php

namespace App\Models;

use App\Enums\DishStatus;
use Database\Factories\DishFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dish extends Model
{
    /** @use HasFactory<DishFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'pairing',
        'glyph',
        'sequence',
        'up',
        'down',
        'status',
    ];

    protected $attributes = [
        'status' => DishStatus::Plated->value,
    ];

    /**
     * Order dishes the way they land on the pass — newest first.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('sequence');
    }

    /**
     * Limit the query to dishes still live on the pass — once a dish is cooked
     * it has been sent to the table and no longer belongs on the board.
     */
    public function scopeOnThePass(Builder $query): Builder
    {
        return $query->where('status', DishStatus::Plated);
    }

    /**
     * @return array{status: class-string<DishStatus>}
     */
    protected function casts(): array
    {
        return [
            'up' => 'integer',
            'down' => 'integer',
            'status' => DishStatus::class,
        ];
    }
}
