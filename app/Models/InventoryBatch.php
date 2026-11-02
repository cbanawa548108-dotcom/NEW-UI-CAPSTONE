<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class InventoryBatch extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventory_batches';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inventory_item_id',
        'batch_code',
        'quantity',
        'initial_quantity',
        'price_per_unit',
        'received_date',
        'expiry_date',
        'supplier',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'price_per_unit' => 'decimal:2',
        'received_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Get the inventory item that owns the batch.
     */
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * Get the sales items for the batch.
     */
    public function salesItems()
    {
        return $this->hasMany(SalesItem::class, 'inventory_batch_id');
    }

    /**
     * Get remaining shelf life in days.
     *
     * @return int|null
     */
    public function getRemainingShelfLifeAttribute()
    {
        if (!$this->expiry_date) {
            return null;
        }

        $now = Carbon::now();
        $expiryDate = Carbon::parse($this->expiry_date);

        if ($expiryDate->isPast()) {
            return 0;
        }

        return $now->diffInDays($expiryDate);
    }

    /**
     * Check if batch is expired.
     *
     * @return bool
     */
    public function getIsExpiredAttribute()
    {
        if (!$this->expiry_date) {
            return false;
        }

        return Carbon::parse($this->expiry_date)->isPast();
    }

    /**
     * Check if batch is expiring soon (within 7 days).
     *
     * @return bool
     */
    public function getIsExpiringSoonAttribute()
    {
        if (!$this->expiry_date) {
            return false;
        }

        $daysUntilExpiry = $this->remaining_shelf_life;
        return $daysUntilExpiry !== null && $daysUntilExpiry > 0 && $daysUntilExpiry <= 7;
    }

    /**
     * Get total value of the batch.
     *
     * @return float
     */
    public function getTotalValueAttribute()
    {
        return $this->quantity * $this->price_per_unit;
    }

    /**
     * Scope a query to only include available batches.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope a query to only include expired batches.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', Carbon::now());
    }

    /**
     * Scope a query to only include expiring soon batches.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->whereBetween('expiry_date', [
            Carbon::now(),
            Carbon::now()->addDays($days)
        ]);
    }

    /**
     * Scope a query to filter by supplier.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $supplier
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySupplier($query, $supplier)
    {
        return $query->where('supplier', $supplier);
    }
}
