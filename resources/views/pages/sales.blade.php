<x-app-layout>
<x-slot name="title">Sales — FreshTrack</x-slot>

<div x-data="salesData()" x-init="init()">

<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-7 fade-up">
    <div>
        <h1 class="text-[26px] font-black text-gray-900">Sales Transactions</h1>
        <p class="text-[13.5px] text-gray-500 mt-0.5">Track, manage and analyze all fruit sales · June 2026</p>
    </div>
    <div class="flex items-center gap-3">
        <button class="btn btn-outline btn-md text-[13px]">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export PDF
        </button>
        <button class="btn btn-outline btn-md text-[13px]">
            <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export CSV
        </button>
        <button @click="addModal=true" class="btn btn-violet btn-md text-[13px]">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Transaction
        </button>
    </div>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 fade-up delay-1">
    <div class="card shimmer card-lift p-5">
        <div class="icon-ring mb-3">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <p class="text-[22px] font-black text-gray-900">₱{{ number_format($todayTotal, 2) }}</p>
        <p class="text-[12.5px] text-gray-500 font-medium mt-0.5">Today's Total</p>
    </div>
    
    <div class="card shimmer card-lift p-5">
        <div class="icon-ring mb-3">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <p class="text-[22px] font-black text-gray-900">{{ $todayTransactions }} sales</p>
        <p class="text-[12.5px] text-gray-500 font-medium mt-0.5">Transactions</p>
    </div>
    
    <div class="card shimmer card-lift p-5">
        <div class="icon-ring mb-3">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <p class="text-[22px] font-black text-gray-900">₱{{ number_format($avgSaleValue, 0) }}</p>
        <p class="text-[12.5px] text-gray-500 font-medium mt-0.5">Avg Sale Value</p>
    </div>
    
    <div class="card shimmer card-lift p-5">
        <div class="icon-ring mb-3">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <p class="text-[22px] font-black text-gray-900">{{ $topSellerName }}</p>
        <p class="text-[12.5px] text-gray-500 font-medium mt-0.5">Top Seller</p>
    </div>
</div>

{{-- Filters --}}
<div class="card p-4 mb-5 fade-up delay-2">
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2.5 bg-gray-100 rounded-2xl px-4 py-2.5 flex-1 min-w-52 max-w-sm border-2 border-transparent focus-within:border-violet-400 focus-within:bg-white transition-all">
            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" placeholder="Search transactions, fruit, cashier…" class="bg-transparent text-[13px] text-gray-700 outline-none w-full placeholder-gray-400">
        </div>
        <input type="date" value="2026-06-23" class="inp" style="width:auto;padding:10px 14px;border-radius:12px">
        <select class="inp" style="width:auto;padding:10px 14px;border-radius:12px">
            <option>All Fruits</option>
            <option>Mango</option><option>Durian</option><option>Pomelo</option>
            <option>Mangosteen</option><option>Lanzones</option><option>Banana</option><option>Pineapple</option>
        </select>
        <select class="inp" style="width:auto;padding:10px 14px;border-radius:12px">
            <option>All Status</option><option>Completed</option><option>Pending</option><option>Cancelled</option>
        </select>
        <button class="btn btn-outline btn-sm text-[12px] text-gray-500">Clear Filters</button>
    </div>
</div>

{{-- Table --}}
<div class="card overflow-hidden fade-up delay-3">
    <div class="overflow-x-auto">
        <table class="tbl w-full">
            <thead><tr>
                <th class="text-left">Transaction ID</th><th class="text-left">Fruit</th><th class="text-left">Qty</th><th class="text-left">Unit Price</th><th class="text-left">Total</th><th class="text-left">Cashier</th><th class="text-left">Date & Time</th><th class="text-left">Status</th><th class="text-left">Actions</th>
            </tr></thead>
            <tbody>
@if(isset($sales) && count($sales) > 0)
@foreach($sales as $sale)
<tr>
    <td class="font-mono text-[12px] text-gray-500">{{ $sale->transaction_code }}</td>
    <td>
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-gray-100 border border-gray-200 text-gray-400 flex-shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <span class="font-semibold text-gray-800 text-[13.5px]">
                @php
                    $firstItem = $sale->salesItems->first();
                    $itemName = $firstItem && $firstItem->inventoryBatch && $firstItem->inventoryBatch->inventoryItem 
                        ? $firstItem->inventoryBatch->inventoryItem->name 
                        : 'N/A';
                    $itemCount = $sale->salesItems->count();
                @endphp
                {{ $itemName }}@if($itemCount > 1) <span class="text-gray-400 text-xs">+{{ $itemCount - 1 }} more</span>@endif
            </span>
        </div>
    </td>
    <td class="text-gray-500 text-[13px]">{{ number_format($sale->salesItems->sum('quantity'), 2) }} kg</td>
    <td class="text-gray-500 text-[13px]">
        @php
            $firstItem = $sale->salesItems->first();
            $unitPrice = $firstItem ? $firstItem->unit_price : 0;
        @endphp
        ₱{{ number_format($unitPrice, 2) }}/kg
    </td>
    <td class="font-bold text-gray-900">₱{{ number_format($sale->total_amount, 2) }}</td>
    <td>
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 g-violet rounded-full flex items-center justify-center text-white text-[10px] font-bold">
                {{ $sale->user ? substr($sale->user->name, 0, 1) : 'S' }}
            </div>
            <span class="text-[12.5px] text-gray-600">{{ $sale->user ? $sale->user->name : 'System' }}</span>
        </div>
    </td>
    <td class="text-[12px] text-gray-400">
        @if($sale->created_at->isToday())
            Today, {{ $sale->created_at->format('g:i A') }}
        @elseif($sale->created_at->isYesterday())
            Yesterday, {{ $sale->created_at->format('g:i A') }}
        @else
            {{ $sale->created_at->format('M d, g:i A') }}
        @endif
    </td>
    <td>
        <span class="badge {{ $sale->status === 'completed' ? 'badge-green' : ($sale->status === 'pending' ? 'badge-amber' : 'badge-red') }} text-[11px]">
            {{ ucfirst($sale->status) }}
        </span>
    </td>
    <td>
        <div class="flex items-center gap-1.5">
            <button class="p-1.5 text-gray-400 hover:text-violet-600 hover:bg-violet-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>
            <button class="p-1.5 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
            <button class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
        </div>
    </td>
</tr>
@endforeach
@else
<tr>
    <td colspan="9" class="text-center py-12">
        <div class="flex flex-col items-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">No Sales Transactions Yet</h3>
            <p class="text-sm text-gray-500">Start selling to see transactions here</p>
        </div>
    </td>
</tr>
@endif
            </tbody>
        </table>
    </div>
    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 bg-gray-50/50">
        <p class="text-[12.5px] text-gray-500">Showing <span class="font-bold text-gray-800">1–10</span> of <span class="font-bold text-gray-800">247</span> transactions</p>
        <div class="flex items-center gap-1.5">
            <button class="w-8 h-8 flex items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100 border border-gray-200 transition-colors"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
            @foreach([1,2,3,'…',24,25] as $p)
            <button class="w-8 h-8 flex items-center justify-center rounded-lg text-[12.5px] font-semibold transition-all {{ $p===1 ? 'g-violet text-white shadow-sm' : 'text-gray-600 hover:bg-gray-200' }}">{{ $p }}</button>
            @endforeach
            <button class="w-8 h-8 flex items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100 border border-gray-200 transition-colors"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
        </div>
    </div>
</div>

{{-- Add Modal --}}
<div x-show="addModal" @click.self="addModal=false" class="modal-bg fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" x-cloak
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl border border-violet-100"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-gray-100 border border-gray-200 text-gray-400 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div><h3 class="font-bold text-gray-900 text-[15px]">New Transaction</h3>
                <p class="text-[11.5px] text-gray-400">Record a new sale</p></div>
            </div>
            <button @click="resetModal()" class="p-2 hover:bg-gray-100 rounded-xl text-gray-400 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <div class="p-6">
            {{-- Product Selection --}}
            <div class="mb-4">
                <label class="inp-label">Select Product</label>
                <select x-model.number="selectedProductId" @change="selectProduct(products.find(p => p.id === selectedProductId))" class="inp">
                    <option value="">Choose a product...</option>
                    <template x-for="product in products" :key="product.id">
                        <option :value="product.id" x-text="product.name + ' (Available: ' + product.available_stock + ' kg)'"></option>
                    </template>
                </select>
            </div>
            
            {{-- Selected Product Display --}}
            <div x-show="selectedProduct" class="mb-4 p-3 bg-violet-50 rounded-lg border border-violet-200">
                <p class="text-sm text-gray-600">Selected: <span class="font-bold text-violet-700" x-text="selectedProduct ? selectedProduct.name : ''"></span></p>
            </div>
            
            {{-- Quantity and Price --}}
            <div x-show="selectedProduct" class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="inp-label">Quantity (kg)</label>
                    <input type="number" x-model="quantity" placeholder="Enter quantity" class="inp" step="0.01" min="0">
                </div>
                <div>
                    <label class="inp-label">Price per kg (Editable)</label>
                    <input type="number" x-model="editablePrice" placeholder="0.00" class="inp" step="0.01" min="0">
                </div>
            </div>
            
            <button @click="addToCart()" x-show="selectedProduct && quantity > 0" class="btn btn-violet btn-sm mb-4">Add to Cart</button>
            
            {{-- Cart --}}
            <div x-show="cart.length > 0" class="border-t border-gray-200 pt-4">
                <h4 class="font-bold text-sm mb-3">Cart Items</h4>
                <div class="space-y-2 mb-4 max-h-40 overflow-y-auto">
                    <template x-for="(item, index) in cart" :key="index">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <p class="font-semibold text-sm" x-text="item.product_name"></p>
                                <p class="text-xs text-gray-500" x-text="item.quantity + ' kg × ₱' + item.unit_price + ' = ₱' + item.subtotal.toFixed(2)"></p>
                            </div>
                            <button @click="removeFromCart(index)" class="p-1 text-red-500 hover:bg-red-100 rounded">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
                
                <div class="flex justify-between items-center p-4 bg-violet-50 rounded-lg mb-4">
                    <span class="font-bold text-gray-900">Total:</span>
                    <span class="font-black text-xl text-violet-600" x-text="'₱' + cartTotal.toFixed(2)"></span>
                </div>
                
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="inp-label">Payment Method</label>
                        <select x-model="paymentMethod" class="inp">
                            <option value="cash">Cash</option>
                            <option value="gcash">GCash</option>
                            <option value="card">Card</option>
                        </select>
                    </div>
                    <div>
                        <label class="inp-label">Notes (Optional)</label>
                        <input type="text" x-model="notes" placeholder="Add notes..." class="inp">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="flex gap-3 px-6 pb-6">
            <button @click="resetModal()" class="btn btn-outline btn-md flex-1">Cancel</button>
            <button @click="saveSale()" :disabled="cart.length === 0" class="btn btn-violet btn-md flex-1" :class="cart.length === 0 ? 'opacity-50 cursor-not-allowed' : ''">
                Complete Sale
            </button>
        </div>
    </div>
</div>

</div>
</x-app-layout>


<script>
function salesData() {
    return {
        addModal: false,
        products: [],
        selectedProductId: '',
        selectedProduct: null,
        selectedBatch: null,
        quantity: 0,
        editablePrice: 0,
        cart: [],
        paymentMethod: 'cash',
        notes: '',
        
        init() {
            this.loadProducts();
        },
        
        loadProducts() {
            var self = this;
            fetch('/api/pos/products')
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    self.products = data;
                })
                .catch(function(error) {
                    console.error('Error loading products:', error);
                });
        },
        
        selectProduct(product) {
            if (!product) {
                this.selectedProductId = '';
                this.selectedProduct = null;
                this.selectedBatch = null;
                this.quantity = 0;
                this.editablePrice = 0;
                return;
            }
            
            this.selectedProduct = product;
            this.selectedBatch = product.batches && product.batches.length > 0 ? product.batches[0] : null;
            this.quantity = 0;
            this.editablePrice = this.selectedBatch ? this.selectedBatch.price_per_unit : 0;
        },
        
        addToCart() {
            var self = this;
            if (!self.selectedProduct || !self.selectedBatch || self.quantity <= 0 || self.editablePrice <= 0) {
                alert('Please fill in all fields');
                return;
            }
            
            // Get the actual available quantity from the selected product
            var availableQty = self.selectedProduct.available_stock || 0;
            
            if (self.quantity > availableQty) {
                alert('Quantity exceeds available stock (' + availableQty + ' kg)!');
                return;
            }
            
            var existingIndex = -1;
            for (var i = 0; i < self.cart.length; i++) {
                if (self.cart[i].batch_id === self.selectedBatch.id && self.cart[i].unit_price === self.editablePrice) {
                    existingIndex = i;
                    break;
                }
            }
            
            if (existingIndex >= 0) {
                self.cart[existingIndex].quantity = parseFloat(self.quantity);
                self.cart[existingIndex].subtotal = self.quantity * self.editablePrice;
            } else {
                self.cart.push({
                    product_id: self.selectedProduct.id,
                    product_name: self.selectedProduct.name,
                    batch_id: self.selectedBatch.id,
                    batch_code: self.selectedBatch.batch_code,
                    quantity: parseFloat(self.quantity),
                    unit_price: parseFloat(self.editablePrice),
                    subtotal: parseFloat(self.quantity) * parseFloat(self.editablePrice)
                });
            }
            
            self.selectedProductId = '';
            self.selectedProduct = null;
            self.selectedBatch = null;
            self.quantity = 0;
            self.editablePrice = 0;
        },
        
        removeFromCart(index) {
            this.cart.splice(index, 1);
        },
        
        get cartTotal() {
            var total = 0;
            for (var i = 0; i < this.cart.length; i++) {
                total += this.cart[i].subtotal;
            }
            return total;
        },
        
        saveSale() {
            var self = this;
            if (self.cart.length === 0) {
                alert('Please add items to cart');
                return;
            }
            
            var saleData = {
                items: self.cart.map(function(item) {
                    return {
                        product_id: item.product_id,
                        batch_id: item.batch_id,
                        quantity: item.quantity,
                        price: item.unit_price
                    };
                }),
                payment_method: self.paymentMethod,
                notes: self.notes
            };
            
            console.log('Creating sale:', saleData);
            
            fetch('/api/pos/sale', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(saleData)
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    alert('✓ Sale completed successfully!');
                    self.resetModal();
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to create sale'));
                    console.error('Sale Error:', result);
                }
            })
            .catch(function(error) {
                alert('Error: Failed to connect to server');
                console.error('Sale Error:', error);
            });
        },
        
        resetModal() {
            this.addModal = false;
            this.cart = [];
            this.selectedProductId = '';
            this.selectedProduct = null;
            this.selectedBatch = null;
            this.quantity = 0;
            this.editablePrice = 0;
            this.paymentMethod = 'cash';
            this.notes = '';
        }
    };
}
</script>
