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
        'status',
    ];

    protected $attributes = [
        'status' => DishStatus::Plated->value,
    ];

    /**
     * Order dishes the way they land on the pass.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sequence');
    }

    /**
     * @return array{status: class-string<DishStatus>}
     */
    protected function casts(): array
    {
        return [
            'status' => DishStatus::class,
        ];
    }
}
