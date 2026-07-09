<?php

namespace App\Models;

use App\Enums\ProductMode;
use App\Enums\ProductTier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'slug', 'tier', 'mode',
        'ref_price', 'restock_cadence', 'qty_scales_by', 'triggers', 'icon',
    ];

    protected function casts(): array
    {
        return [
            'tier' => ProductTier::class,
            'mode' => ProductMode::class,
            'ref_price' => 'integer',
            'triggers' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function pairedProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_pairings',
            'product_id',
            'paired_product_id',
        );
    }

    public function cheapestPrice(): ?int
    {
        return $this->prices->min('price') ?? $this->ref_price;
    }
}
