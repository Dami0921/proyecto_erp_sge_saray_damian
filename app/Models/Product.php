<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'stock', 'min_stock', 'is_controlled'];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'min_stock' => 'integer',
            'is_controlled' => 'boolean',
        ];
    }

    public function lots()
    {
        return $this->hasMany(Lot::class);
    }
}
