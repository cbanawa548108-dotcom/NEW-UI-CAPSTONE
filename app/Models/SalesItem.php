<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sales_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sale_transaction_id',
        'inventory_item_id',
        'inventory_batch_id',
        'quantity',
        'unit_price',
        'total_amount',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the sales transaction that owns the sales item.
     */
    public function saleTransaction()
    {
        return $this->belongsTo(SalesTransaction::class, 'sale_transaction_id');
    }

    /**
     * Get the inventory item that owns the sales item.
     */
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * Get the inventory batch that owns the sales item.
     */
    public function inventoryBatch()
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }
}
