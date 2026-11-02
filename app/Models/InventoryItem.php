<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventory_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'category',
        'unit',
        'price_per_unit',
        'stock_quantity',
        'reorder_level',
        'freshness_score',
        'spoilage_risk',
        'status',
        'storage_notes',
        'remaining_shelf_life',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_per_unit' => 'decimal:2',
        'stock_quantity' => 'decimal:2',
        'reorder_level' => 'decimal:2',
        'freshness_score' => 'integer',
        'remaining_shelf_life' => 'integer',
    ];

    /**
     * Get the batches for the inventory item.
     */
    public function batches()
    {
        return $this->hasMany(InventoryBatch::class, 'inventory_item_id');
    }

    /**
     * Get the sales items for the inventory item.
     */
    public function salesItems()
    {
        return $this->hasMany(SalesItem::class, 'inventory_item_id');
    }

    /**
     * Get the available batches (not sold or expired).
     */
    public function availableBatches()
    {
        return $this->hasMany(InventoryBatch::class, 'inventory_item_id')
                    ->where('status', 'available');
    }

    /**
     * Get total stock from all available batches.
     *
     * @return float
     */
    public function getTotalStockAttribute()
    {
        return $this->availableBatches()->sum('quantity');
    }

    /**
     * Check if item is low stock.
     *
     * @return bool
     */
    public function getIsLowStockAttribute()
    {
        return $this->stock_quantity <= $this->reorder_level;
    }

    /**
     * Check if item is out of stock.
     *
     * @return bool
     */
    public function getIsOutOfStockAttribute()
    {
        return $this->stock_quantity <= 0;
    }

    /**
     * Get stock status (Available, Low Stock, Out of Stock).
     *
     * @return string
     */
    public function getStockStatusAttribute()
    {
        if ($this->is_out_of_stock) {
            return 'Out of Stock';
        } elseif ($this->is_low_stock) {
            return 'Low Stock';
        }
        return 'Available';
    }

    /**
     * Get badge class for stock status.
     *
     * @return string
     */
    public function getStockStatusBadgeAttribute()
    {
        if ($this->is_out_of_stock) {
            return 'badge-gray';
        } elseif ($this->is_low_stock) {
            return 'badge-amber';
        }
        return 'badge-green';
    }

    /**
     * Scope a query to only include active items.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include low stock items.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock_quantity <= reorder_level');
    }

    /**
     * Scope a query to only include out of stock items.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('stock_quantity', '<=', 0);
    }

    /**
     * Scope a query to filter by category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
