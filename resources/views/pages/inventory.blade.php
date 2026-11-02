<x-app-layout>
<x-slot name="title">Inventory — FreshTrack</x-slot>

<script>
function inventoryData() {
    return {
    addProductModal:false,
    stockInModal:false,
    stockOutModal:false,
    editModal:false, 
    viewModal:false, 
    deleteModal:false,
    selected:null,
    sidebarOpen: true,
    activeSection: 'overview',
    stockInStep: 1,
    stockInItems: [],
    itemsPerPage: 5,
    currentPage: 1,
    
    // Stock Out data
    stockOutItems: [],
    stockOutType: 'sale',
    stockOutReason: '',
    stockOutDate: new Date().toISOString().split('T')[0],
    stockOutReference: '',
    stockOutSearchQuery: '',
    stockOutFilterCategory: '',
    selectedBatch: null,
    stockOutQuantity: 0,
    
    // Stock Adjustment data
    adjustmentModal: false,
    adjustmentType: 'add',
    adjustmentItems: [],
    adjustmentReason: '',
    adjustmentNotes: '',
    adjustmentDate: new Date().toISOString().split('T')[0],
    adjustmentReference: '',
    selectedProductForAdjustment: null,
    adjustmentQuantity: 0,
    adjustmentSearchQuery: '',
    adjustmentFilterCategory: '',
    currentItem: {
        product: '',
        quantity: 0,
        unitCost: 0,
        batchId: '',
        expirationDate: '',
        shelfLife: 0
    },
    supplier: '',
    dateReceived: new Date().toISOString().split('T')[0],
    referenceNumber: '',
    
    // Add Product data
    newProduct: {
        name: '',
        category: '',
        unit: '',
        description: '',
        skuCode: '',
        shelfLife: 0
    },
    
    // Product shelf life data (in days)
    productShelfLife: {
        'Mango': 14,
        'Durian': 7,
        'Pomelo': 21,
        'Mangosteen': 14,
        'Lanzones': 10,
        'Banana': 7,
        'Pineapple': 14
    },
    
    generateSKUCode(productName) {
        if (!productName) return '';
        const prefix = productName.substring(0, 3).toUpperCase();
        const timestamp = Date.now().toString().slice(-4);
        const random = Math.floor(Math.random() * 100).toString().padStart(2, '0');
        return `${prefix}-${timestamp}${random}`;
    },
    
    getShelfLifeForProduct(productName) {
        return this.productShelfLife[productName] || 14;
    },
    
    updateProductSKU() {
        if (this.newProduct.name) {
            this.newProduct.skuCode = this.generateSKUCode(this.newProduct.name);
            this.newProduct.shelfLife = this.getShelfLifeForProduct(this.newProduct.name);
        }
    },
    
    generateBatchId(product) {
        const prefix = product.substring(0, 3).toUpperCase();
        const random = Math.floor(Math.random() * 900) + 100;
        return `${prefix}-${random}`;
    },
    
    generateReferenceNumber() {
        const date = new Date();
        const year = date.getFullYear().toString().substr(-2);
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const random = Math.floor(Math.random() * 9000) + 1000;
        return `SI-${year}${month}-${random}`;
    },
    
    calculateDates(product, dateReceived) {
        const shelfLife = this.productShelfLife[product] || 14;
        const received = new Date(dateReceived);
        const expiration = new Date(received);
        expiration.setDate(expiration.getDate() + shelfLife);
        
        return {
            shelfLife: shelfLife,
            expirationDate: expiration.toISOString().split('T')[0]
        };
    },
    
    addStockItem() {
        if (this.currentItem.product && this.currentItem.quantity > 0 && this.currentItem.unitCost > 0) {
            const dates = this.calculateDates(this.currentItem.product, this.dateReceived);
            const batchId = this.generateBatchId(this.currentItem.product);
            
            this.stockInItems.push({
                product: this.currentItem.product,
                batchId: batchId,
                quantity: this.currentItem.quantity,
                unitCost: this.currentItem.unitCost,
                totalCost: this.currentItem.quantity * this.currentItem.unitCost,
                expirationDate: dates.expirationDate,
                shelfLife: dates.shelfLife
            });
            
            // Reset current item form
            this.currentItem = { product: '', quantity: 0, unitCost: 0, batchId: '', expirationDate: '', shelfLife: 0 };
        }
    },
    
    removeStockItem(index) {
        this.stockInItems.splice(index, 1);
        // Adjust current page if needed
        if (this.stockInItems.length > 0 && this.currentPage > this.totalPages) {
            this.currentPage = this.totalPages;
        }
        // Reset to page 1 if all items are removed
        if (this.stockInItems.length === 0) {
            this.currentPage = 1;
        }
    },
    
    getTotalAmount() {
        return this.stockInItems.reduce((sum, item) => sum + item.totalCost, 0);
    },
    
    get paginatedItems() {
        const start = (this.currentPage - 1) * this.itemsPerPage;
        const end = start + this.itemsPerPage;
        return this.stockInItems.slice(start, end);
    },
    
    get totalPages() {
        return Math.ceil(this.stockInItems.length / this.itemsPerPage);
    },
    
    nextPage() {
        if (this.currentPage < this.totalPages) {
            this.currentPage++;
        }
    },
    
    prevPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
        }
    },
    
    goToPage(page) {
        this.currentPage = page;
    },
    
    // Stock Out functions
    generateStockOutReference() {
        const date = new Date();
        const year = date.getFullYear().toString().substr(-2);
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const random = Math.floor(Math.random() * 9000) + 1000;
        return `SO-${year}${month}-${random}`;
    },
    
    get availableStock() {
        // Get from actual inventory data passed from backend
        var stock = [];
        @if(isset($inventoryItems))
            @foreach($inventoryItems as $item)
                @foreach($item->batches as $batch)
                    @if($batch->status === 'available' && $batch->quantity > 0)
                        stock.push({
                            product: '{{ $item->name }}',
                            batchId: {{ $batch->id }},
                            batchCode: '{{ $batch->batch_code }}',
                            quantity: {{ $batch->quantity }},
                            category: '{{ $item->category }}',
                            freshness: {{ $batch->remaining_shelf_life ?? 0 }}
                        });
                    @endif
                @endforeach
            @endforeach
        @endif
        return stock;
    },
    
    get filteredStock() {
        let filtered = this.availableStock;
        
        // Search filter
        if (this.stockOutSearchQuery) {
            const query = this.stockOutSearchQuery.toLowerCase();
            filtered = filtered.filter(item => 
                item.product.toLowerCase().includes(query) ||
                item.batchId.toLowerCase().includes(query)
            );
        }
        
        // Category filter
        if (this.stockOutFilterCategory) {
            filtered = filtered.filter(item => item.category === this.stockOutFilterCategory);
        }
        
        return filtered;
    },
    
    get filteredAdjustmentStock() {
        let filtered = this.availableStock;
        
        // Search filter
        if (this.adjustmentSearchQuery) {
            const query = this.adjustmentSearchQuery.toLowerCase();
            filtered = filtered.filter(item => 
                item.product.toLowerCase().includes(query) ||
                item.batchId.toLowerCase().includes(query)
            );
        }
        
        // Category filter
        if (this.adjustmentFilterCategory) {
            filtered = filtered.filter(item => item.category === this.adjustmentFilterCategory);
        }
        
        return filtered;
    },
    
    selectBatchForStockOut(batch) {
        this.selectedBatch = batch;
        this.stockOutQuantity = 0;
    },
    
    addSelectedBatch() {
        if (this.selectedBatch && this.stockOutQuantity > 0 && this.stockOutQuantity <= this.selectedBatch.quantity) {
            // Check if batch already added
            const existingIndex = this.stockOutItems.findIndex(item => item.batchId === this.selectedBatch.batchId);
            
            if (existingIndex >= 0) {
                // Update existing quantity
                this.stockOutItems[existingIndex].quantity = parseFloat(this.stockOutQuantity);
            } else {
                // Add new item
                this.stockOutItems.push({
                    product: this.selectedBatch.product,
                    batchId: this.selectedBatch.batchId,
                    batchCode: this.selectedBatch.batchCode,
                    quantity: parseFloat(this.stockOutQuantity),
                    availableQty: this.selectedBatch.quantity
                });
            }
            
            // Reset selection
            this.selectedBatch = null;
            this.stockOutQuantity = 0;
            this.stockOutSearchQuery = '';
        }
    },
    
    addStockOutItem(product, batchId, quantity) {
        this.stockOutItems.push({
            product: product,
            batchId: batchId,
            quantity: quantity,
            availableQty: quantity
        });
    },
    
    removeStockOutItem(index) {
        this.stockOutItems.splice(index, 1);
    },
    
    saveStockOut() {
        var self = this;
        if (this.stockOutType && this.stockOutDate && this.stockOutItems.length > 0) {
            var mappedItems = [];
            for (var i = 0; i < this.stockOutItems.length; i++) {
                var item = this.stockOutItems[i];
                mappedItems.push({
                    batch_id: item.batchId,
                    quantity: parseFloat(item.quantity)
                });
            }
            
            var stockOutData = {
                items: mappedItems,
                type: this.stockOutType,
                notes: this.stockOutReason || 'Stock out transaction',
                reference: this.stockOutReference,
                date: this.stockOutDate
            };

            console.log('Sending Stock Out:', stockOutData);

            fetch('/api/inventory/stock-out', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(stockOutData)
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    alert('✓ Stock Out completed successfully!');
                    self.resetStockOutModal();
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to process stock out'));
                    console.error('Stock Out Error:', result);
                }
            })
            .catch(function(error) {
                alert('Error: Failed to connect to server');
                console.error('Stock Out Error:', error);
            });
        }
    },
    
    resetStockOutModal() {
        this.stockOutModal = false;
        this.stockOutItems = [];
        this.stockOutType = 'sale';
        this.stockOutReason = '';
        this.stockOutDate = new Date().toISOString().split('T')[0];
        this.stockOutReference = '';
        this.stockOutSearchQuery = '';
        this.stockOutFilterCategory = '';
        this.selectedBatch = null;
        this.stockOutQuantity = 0;
    },
    
    // Stock Adjustment functions
    generateAdjustmentReference() {
        const date = new Date();
        const year = date.getFullYear().toString().substr(-2);
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const random = Math.floor(Math.random() * 900) + 100;
        return `ADJ-${year}${month}-${random}`;
    },
    
    selectProductForAdjustment(product) {
        this.selectedProductForAdjustment = product;
        this.adjustmentQuantity = 0;
    },
    
    addAdjustmentItem() {
        if (this.selectedProductForAdjustment && this.adjustmentQuantity > 0) {
            const existingIndex = this.adjustmentItems.findIndex(
                item => item.batchId === this.selectedProductForAdjustment.batchId
            );
            
            if (existingIndex >= 0) {
                // Update existing item
                this.adjustmentItems[existingIndex].quantity = parseFloat(this.adjustmentQuantity);
                this.adjustmentItems[existingIndex].type = this.adjustmentType;
            } else {
                // Add new item
                this.adjustmentItems.push({
                    product: this.selectedProductForAdjustment.product,
                    batchId: this.selectedProductForAdjustment.batchId,
                    currentStock: this.selectedProductForAdjustment.quantity,
                    quantity: parseFloat(this.adjustmentQuantity),
                    type: this.adjustmentType
                });
            }
            
            // Reset selection
            this.selectedProductForAdjustment = null;
            this.adjustmentQuantity = 0;
        }
    },
    
    removeAdjustmentItem(index) {
        this.adjustmentItems.splice(index, 1);
    },
    
    saveAdjustment() {
        var self = this;
        if (this.adjustmentItems.length > 0 && this.adjustmentReason) {
            var mappedItems = [];
            for (var i = 0; i < this.adjustmentItems.length; i++) {
                var item = this.adjustmentItems[i];
                mappedItems.push({
                    batch_id: item.batchId,
                    quantity: parseFloat(item.quantity)
                });
            }
            
            var adjustmentData = {
                items: mappedItems,
                type: this.adjustmentType,
                reason: this.adjustmentReason,
                notes: this.adjustmentNotes || '',
                reference: this.adjustmentReference,
                date: this.adjustmentDate
            };

            console.log('Sending Stock Adjustment:', adjustmentData);

            fetch('/api/inventory/stock-adjustment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(adjustmentData)
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    alert('✓ Stock Adjustment completed successfully!');
                    self.resetAdjustmentModal();
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to process adjustment'));
                    console.error('Adjustment Error:', result);
                }
            })
            .catch(function(error) {
                alert('Error: Failed to connect to server');
                console.error('Adjustment Error:', error);
            });
        }
    },
    
    resetAdjustmentModal() {
        this.adjustmentModal = false;
        this.adjustmentType = 'add';
        this.adjustmentItems = [];
        this.adjustmentReason = '';
        this.adjustmentNotes = '';
        this.adjustmentDate = new Date().toISOString().split('T')[0];
        this.adjustmentReference = '';
        this.selectedProductForAdjustment = null;
        this.adjustmentQuantity = 0;
        this.adjustmentSearchQuery = '';
        this.adjustmentFilterCategory = '';
    },
    
    saveStockIn() {
        var self = this;
        if (this.supplier && this.dateReceived && this.stockInItems.length > 0) {
            // Prepare data for API
            var mappedItems = [];
            for (var i = 0; i < this.stockInItems.length; i++) {
                var item = this.stockInItems[i];
                mappedItems.push({
                    product_id: item.product_id || null,
                    product_name: item.product,
                    quantity: parseFloat(item.quantity),
                    price_per_unit: parseFloat(item.unitCost),
                    batch_code: item.batchId,
                    expiry_date: item.expirationDate,
                    supplier: self.supplier
                });
            }
            
            var stockInData = {
                supplier: this.supplier,
                received_date: this.dateReceived,
                reference_number: this.referenceNumber,
                items: mappedItems
            };

            console.log('Sending Stock In:', stockInData);

            // Call API
            fetch('/api/inventory/stock-in', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(stockInData)
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    alert('✓ Stock In completed successfully!\n\nReference: ' + self.referenceNumber);
                    
                    // Reset form
                    self.stockInItems = [];
                    self.supplier = '';
                    self.dateReceived = new Date().toISOString().split('T')[0];
                    self.referenceNumber = '';
                    self.stockInStep = 1;
                    self.stockInModal = false;
                    
                    // Reload page to show updated data
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to save stock in'));
                    console.error('Stock In Error:', result);
                }
            })
            .catch(function(error) {
                alert('Error: Failed to connect to server');
                console.error('Stock In Error:', error);
            });
        }
    },
    
    nextStep() {
        if (this.stockInStep === 1 && this.supplier && this.dateReceived) {
            if (!this.referenceNumber) {
                this.referenceNumber = this.generateReferenceNumber();
            }
            this.stockInStep = 2;
        } else if (this.stockInStep === 2 && this.stockInItems.length > 0) {
            this.stockInStep = 3;
        }
    },
    
    prevStep() {
        if (this.stockInStep > 1) {
            this.stockInStep--;
        }
    },
    
    resetStockInModal() {
        this.stockInModal = false;
        this.stockInStep = 1;
        this.stockInItems = [];
        this.currentPage = 1;
        this.supplier = '';
        this.dateReceived = new Date().toISOString().split('T')[0];
        this.referenceNumber = '';
    },
    
    saveProduct() {
        var self = this;
        if (this.newProduct.name && this.newProduct.category && this.newProduct.unit) {
            var productData = {
                name: this.newProduct.name,
                category: this.newProduct.category,
                unit: this.newProduct.unit,
                description: this.newProduct.description || '',
                sku_code: this.newProduct.skuCode,
                shelf_life: this.newProduct.shelfLife,
                price_per_unit: 0, // Default price, will be set during stock in
                reorder_level: 50
            };

            console.log('Saving Product:', productData);

            fetch('/api/products', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(productData)
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    alert('✓ Product added successfully!');
                    
                    // Reset form
                    self.newProduct = {
                        name: '',
                        category: '',
                        unit: '',
                        description: '',
                        skuCode: '',
                        shelfLife: 0
                    };
                    self.addProductModal = false;
                    
                    // Reload page to show new product
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to add product'));
                    console.error('Add Product Error:', result);
                }
            })
            .catch(function(error) {
                alert('Error: Failed to connect to server');
                console.error('Add Product Error:', error);
            });
        } else {
            alert('Please fill in all required fields (Name, Category, Unit)');
        }
    }
    };
}
</script>

<div x-data="inventoryData()" @open-add.window="stockInModal=true" class="flex gap-6">

{{-- Expandable Sidebar --}}
<div class="transition-all duration-300 flex-shrink-0"
     :class="sidebarOpen ? 'w-64' : 'w-20'">
    <div class="card sticky top-6 flex flex-col h-[calc(100vh-140px)]">
        {{-- Toggle Button --}}
        <button @click="sidebarOpen = !sidebarOpen" 
                class="absolute -right-3 top-6 w-6 h-6 bg-violet-600 text-white rounded-full shadow-lg hover:bg-violet-700 transition-colors flex items-center justify-center z-10">
            <svg class="w-3.5 h-3.5 transition-transform duration-300" 
                 :class="sidebarOpen ? 'rotate-0' : 'rotate-180'" 
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>

        {{-- Sidebar Header --}}
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center gap-3" :class="!sidebarOpen && 'justify-center'">
                <div class="w-10 h-10 bg-violet-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div x-show="sidebarOpen" x-transition class="overflow-hidden">
                    <p class="font-bold text-gray-900 text-sm">Inventory</p>
                    <p class="text-xs text-gray-500">Management</p>
                </div>
            </div>
        </div>

        {{-- Navigation Menu --}}
        <nav class="flex-1 overflow-y-auto py-4">
            <div class="space-y-1 px-3">
                {{-- Inventory Overview --}}
                <a @click.prevent="activeSection = 'overview'" 
                   :class="activeSection === 'overview' ? 'bg-violet-50 text-violet-700 border-violet-200' : 'text-gray-600 hover:bg-gray-50 border-transparent'"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all cursor-pointer border"
                   :title="!sidebarOpen ? 'Overview' : ''">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="text-sm font-semibold whitespace-nowrap">Overview</span>
                </a>

                {{-- Products --}}
                <a @click.prevent="activeSection = 'products'" 
                   :class="activeSection === 'products' ? 'bg-violet-50 text-violet-700 border-violet-200' : 'text-gray-600 hover:bg-gray-50 border-transparent'"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all cursor-pointer border"
                   :title="!sidebarOpen ? 'Products' : ''">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="text-sm font-semibold whitespace-nowrap">Products</span>
                </a>

                {{-- Stock In --}}
                <a @click.prevent="activeSection = 'stock-in'" 
                   :class="activeSection === 'stock-in' ? 'bg-violet-50 text-violet-700 border-violet-200' : 'text-gray-600 hover:bg-gray-50 border-transparent'"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all cursor-pointer border"
                   :title="!sidebarOpen ? 'Stock In' : ''">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="text-sm font-semibold whitespace-nowrap">Stock In</span>
                </a>

                {{-- Stock Out --}}
                <a @click.prevent="activeSection = 'stock-out'" 
                   :class="activeSection === 'stock-out' ? 'bg-violet-50 text-violet-700 border-violet-200' : 'text-gray-600 hover:bg-gray-50 border-transparent'"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all cursor-pointer border"
                   :title="!sidebarOpen ? 'Stock Out' : ''">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16v-4m0 0V8m0 4h-4m4 0h4m-6 8h-2a2 2 0 01-2-2v-2m0 0V8m0 4h4m-4 0H7"/>
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="text-sm font-semibold whitespace-nowrap">Stock Out</span>
                </a>

                {{-- Stock Adjustment --}}
                <a @click.prevent="activeSection = 'adjustment'" 
                   :class="activeSection === 'adjustment' ? 'bg-violet-50 text-violet-700 border-violet-200' : 'text-gray-600 hover:bg-gray-50 border-transparent'"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all cursor-pointer border"
                   :title="!sidebarOpen ? 'Adjustment' : ''">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="text-sm font-semibold whitespace-nowrap">Adjustment</span>
                </a>

                {{-- Inventory History --}}
                <a @click.prevent="activeSection = 'history'" 
                   :class="activeSection === 'history' ? 'bg-violet-50 text-violet-700 border-violet-200' : 'text-gray-600 hover:bg-gray-50 border-transparent'"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all cursor-pointer border"
                   :title="!sidebarOpen ? 'History' : ''">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="text-sm font-semibold whitespace-nowrap">History</span>
                </a>
            </div>
        </nav>

        {{-- Sidebar Footer --}}
        <div class="p-4 border-t border-gray-200" x-show="sidebarOpen" x-transition>
            <div class="bg-violet-50 rounded-xl p-3">
                <p class="text-xs font-bold text-violet-900">Quick Stats</p>
                <p class="text-lg font-black text-violet-700 mt-1">1,240 kg</p>
                <p class="text-xs text-violet-600">Total Stock</p>
            </div>
        </div>
    </div>
</div>

{{-- Main Content Area --}}
<div class="flex-1 min-w-0">

{{-- Inventory Overview Section --}}
<div x-show="activeSection === 'overview'" x-transition>
{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-7 fade-up">
    <div>
        <h1 class="text-[26px] font-black text-gray-900">Inventory Management</h1>
        <p class="text-[13.5px] text-gray-500 mt-0.5">Monitor stock levels, batches, and supplier data in real-time</p>
    </div>
    <div class="flex items-center gap-3">
        <button class="btn btn-outline btn-md text-[13px]">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export
        </button>
        <button @click="stockInModal=true" class="btn btn-violet btn-md text-[13px]">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Stock
        </button>
    </div>
</div>

{{-- Summary --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 fade-up delay-1">
@php
$summaryCards = [
    ['Total Stock',   '1,240 kg', 'g-violet', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',                                   'All batches'],
    ['Available',     '1,102 kg', 'g-green',  'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',                                                       'Ready to sell'],
    ['Critical Items','3 items',  'g-rose',   'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z','Needs restock'],
    ['Out of Stock',  '1 item',   'g-amber',  'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636',       'Depleted'],
];
@endphp
@foreach($summaryCards as [$l,$v,$g,$iconPath,$s])
<div class="card shimmer card-lift p-5">
    <div class="icon-ring mb-3">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
        </svg>
    </div>
    <p class="text-[22px] font-black text-gray-900">{{ $v }}</p>
    <p class="text-[12.5px] font-semibold text-gray-600">{{ $l }}</p>
    <p class="text-[11.5px] text-gray-400 mt-0.5">{{ $s }}</p>
</div>
@endforeach
</div>

{{-- Filters --}}
<div class="card p-4 mb-5 fade-up delay-2">
    <div class="flex flex-wrap gap-3">
        <div class="flex items-center gap-2.5 bg-gray-100 rounded-2xl px-4 py-2.5 flex-1 min-w-52 max-w-sm border-2 border-transparent focus-within:border-violet-400 focus-within:bg-white transition-all">
            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" placeholder="Search fruit, batch ID, supplier…" class="bg-transparent text-[13px] outline-none w-full placeholder-gray-400">
        </div>
        <select class="inp" style="width:auto;padding:10px 14px;border-radius:12px">
            <option>All Status</option><option>Available</option><option>Low Stock</option><option>Critical</option><option>Out of Stock</option>
        </select>
        <select class="inp" style="width:auto;padding:10px 14px;border-radius:12px">
            <option>All Suppliers</option><option>Davao Fresh Farms</option><option>Mt. Apo Growers</option><option>Sta. Cruz Orchards</option>
        </select>
        <select class="inp" style="width:auto;padding:10px 14px;border-radius:12px">
            <option>All Fruits</option><option>Mango</option><option>Durian</option><option>Pomelo</option><option>Mangosteen</option>
        </select>
    </div>
</div>

{{-- Grid Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 mb-6 fade-up delay-3">
@php
$fruitIconPath = 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4';
@endphp

@if(isset($groupedItems) && count($groupedItems) > 0)
@foreach($groupedItems as $fruit => $batches)
@php
    // Calculate total quantity and worst status for the fruit
    $totalQty = 0;
    $worstStatus = 'Available';
    $worstBadge = 'badge-green';
    $statusPriority = ['Out of Stock' => 4, 'Critical' => 3, 'Low Stock' => 2, 'Available' => 1];
    $highestPriority = 0;
    $totalValue = 0;
    $batchCount = 0;
    
    foreach ($batches as $batch) {
        $qty = (float) str_replace(' kg', '', $batch[2]);
        $totalQty += $qty;
        
        // Extract price from string like "₱120.00/kg"
        $priceString = $batch[5];
        $priceValue = (float) str_replace(['₱', '/kg', ','], '', $priceString);
        $totalValue += ($qty * $priceValue);
        $batchCount++;
        
        $status = $batch[6];
        $priority = $statusPriority[$status] ?? 0;
        if ($priority > $highestPriority) {
            $highestPriority = $priority;
            $worstStatus = $status;
            $worstBadge = $batch[7];
        }
    }
    
    // Calculate average price
    $avgPrice = $totalQty > 0 ? ($totalValue / $totalQty) : 0;
    $avgPriceFormatted = '₱' . number_format($avgPrice, 2) . '/kg';
@endphp

<div class="card overflow-hidden card-lift fade-up delay-{{ min($loop->index+1,8) }}">
    {{-- Fruit header --}}
    <div class="bg-gray-50 border-b border-gray-100 px-5 pt-5 pb-4 relative">
        <div class="absolute top-3 right-3">
            <span class="badge {{ $worstBadge }} text-[10px]">{{ $worstStatus }}</span>
        </div>
        <div class="w-10 h-10 bg-white border border-gray-200 rounded-xl flex items-center justify-center mb-3 shadow-sm">
            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $fruitIconPath }}"/>
            </svg>
        </div>
        <p class="font-black text-gray-900 text-[16px] leading-tight">{{ $fruit }}</p>
        <p class="text-gray-400 text-[11px] font-medium mt-0.5">{{ count($batches) }} batch{{ count($batches) > 1 ? 'es' : '' }}</p>
    </div>
    
    {{-- Card body --}}
    <div class="p-4">
        {{-- Total Quantity Summary --}}
        <div class="mb-4 pb-3 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[22px] font-black text-gray-900">{{ $totalQty }} kg</p>
                    <p class="text-[11.5px] text-gray-400">Total stock</p>
                </div>
                <div class="text-right">
                    <p class="text-[14px] font-bold text-violet-700">{{ $avgPriceFormatted }}</p>
                    <p class="text-[11px] text-gray-400">Avg price</p>
                </div>
            </div>
        </div>
        
        {{-- Batches List --}}
        <div class="space-y-2 mb-3">
            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Batches</p>
            
            @foreach($batches as $batch)
            <div class="bg-gray-50 rounded-lg p-2.5 border border-gray-200 hover:border-violet-300 transition-all cursor-pointer"
                 x-on:click="viewModal=true; selected='{{ $batch[1] }}'">
                <div class="flex items-start justify-between mb-1.5">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <p class="font-mono text-[11.5px] font-bold text-gray-900">{{ $batch[1] }}</p>
                            <span class="badge {{ $batch[7] }} text-[9px]">{{ $batch[6] }}</span>
                            @if($batch[11] === 'New Stock')
                            <span class="badge bg-blue-100 text-blue-700 border-blue-200 text-[9px]">New Stock</span>
                            @else
                            <span class="badge bg-gray-100 text-gray-600 border-gray-200 text-[9px]">Old Stock</span>
                            @endif
                        </div>
                        <p class="text-[13px] font-bold text-gray-900">{{ $batch[2] }}</p>
                    </div>
                    <div class="text-right flex-shrink-0 ml-2">
                        <p class="text-[11px] font-semibold text-violet-700">{{ $batch[5] }}</p>
                    </div>
                </div>
                
                <div class="flex items-center justify-between text-[10px] text-gray-400">
                    <span class="flex items-center gap-1 truncate">
                        <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Exp: {{ $batch[3] }}
                    </span>
                    <span class="flex items-center gap-1 ml-2">
                        <span class="{{ $batch[10] <= 7 ? 'text-red-500' : ($batch[10] <= 14 ? 'text-amber-500' : 'text-green-600') }} font-semibold">{{ $batch[10] }}d</span>
                    </span>
                </div>
                
                {{-- Freshness progress bar --}}
                <div class="mt-2">
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full {{ $batch[8] < 20 ? 'bg-red-400' : ($batch[8] < 50 ? 'bg-amber-400' : 'bg-violet-500') }}"
                             style="width:{{ $batch[8] }}%"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        {{-- Add Batch Button --}}
        <button @click="stockInModal=true" class="w-full btn btn-outline btn-sm text-[11px] py-2 flex items-center justify-center gap-1.5 border-dashed hover:border-violet-500 hover:text-violet-700 hover:bg-violet-50">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Batch
        </button>
    </div>
</div>
@endforeach
@else
{{-- Empty State --}}
<div class="col-span-full">
    <div class="card p-12 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-2">No Products Yet</h3>
        <p class="text-sm text-gray-500 mb-4">Add your first product to get started</p>
        <button @click="addProductModal=true" class="btn btn-violet btn-sm mx-auto">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Product
        </button>
    </div>
</div>
@endif
</div>
</div>

{{-- Products Section --}}
<div x-show="activeSection === 'products'" x-transition x-cloak>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-7 fade-up">
        <div>
            <h1 class="text-[26px] font-black text-gray-900">Products Catalog</h1>
            <p class="text-[13.5px] text-gray-500 mt-0.5">Manage all product types and their details</p>
        </div>
        <button @click="addProductModal=true" class="btn btn-violet btn-md text-[13px]">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Product
        </button>
    </div>
    
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 fade-up delay-1">
        @php
        $productSummary = [
            ['Total Products', '7 items', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'All types'],
            ['Active Products', '6 items', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'In stock'],
            ['Low Stock', '2 items', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'Needs restock'],
            ['Out of Stock', '0 items', 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636', 'Depleted'],
        ];
        @endphp
        @foreach($productSummary as $card)
        <div class="card shimmer card-lift p-5">
            <div class="icon-ring mb-3">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card[2] }}"/>
                </svg>
            </div>
            <p class="text-[22px] font-black text-gray-900">{{ $card[1] }}</p>
            <p class="text-[12.5px] font-semibold text-gray-600">{{ $card[0] }}</p>
            <p class="text-[11.5px] text-gray-400 mt-0.5">{{ $card[3] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Search and Filters --}}
    <div class="card p-4 mb-5 fade-up delay-2">
        <div class="flex flex-wrap gap-3">
            {{-- Search Bar --}}
            <div class="flex items-center gap-2.5 bg-gray-100 rounded-2xl px-4 py-2.5 flex-1 min-w-52 max-w-sm border-2 border-transparent focus-within:border-violet-400 focus-within:bg-white transition-all">
                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" placeholder="Search products by name..." class="bg-transparent text-[13px] outline-none w-full placeholder-gray-400">
            </div>
            
            {{-- Filter 1: Category --}}
            <select class="inp" style="width:auto;padding:10px 14px;border-radius:12px">
                <option>All Categories</option>
                <option>Tropical Fruit</option>
                <option>Citrus</option>
                <option>Seasonal</option>
            </select>
            
            {{-- Filter 2: Status --}}
            <select class="inp" style="width:auto;padding:10px 14px;border-radius:12px">
                <option>All Status</option>
                <option>Available</option>
                <option>Low Stock</option>
                <option>Out of Stock</option>
            </select>
        </div>
    </div>

    {{-- Products Table --}}
    <div class="card p-6 fade-up delay-3">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-xs font-bold text-gray-600 uppercase">Product Name</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-gray-600 uppercase">Category</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-gray-600 uppercase">Unit Price</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-gray-600 uppercase">Total Stock</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-gray-600 uppercase">Status</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-gray-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($inventoryItems) && $inventoryItems->count() > 0)
                        @foreach($inventoryItems as $item)
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <span class="font-semibold text-gray-900 text-sm">{{ $item->name }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-sm text-gray-600">{{ $item->category ?? 'N/A' }}</td>
                            <td class="py-4 px-4 text-sm font-semibold text-gray-900">₱{{ number_format($item->price_per_unit, 2) }}/{{ $item->unit }}</td>
                            <td class="py-4 px-4 text-sm font-semibold text-gray-900">{{ number_format($item->stock_quantity, 0) }} {{ $item->unit }}</td>
                            <td class="py-4 px-4">
                                @php
                                    $status = 'Available';
                                    $badgeClass = 'badge-green';
                                    if ($item->stock_quantity <= 0) {
                                        $status = 'Out of Stock';
                                        $badgeClass = 'badge-gray';
                                    } elseif ($item->stock_quantity <= $item->reorder_level) {
                                        $status = 'Low Stock';
                                        $badgeClass = 'badge-amber';
                                    }
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $status }}</span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-2">
                                    <button class="p-2 hover:bg-violet-50 rounded-lg text-gray-400 hover:text-violet-600 transition-colors" title="View Details">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    <button class="p-2 hover:bg-violet-50 rounded-lg text-gray-400 hover:text-violet-600 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button class="p-2 hover:bg-red-50 rounded-lg text-gray-400 hover:text-red-600 transition-colors" title="Delete">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <p class="text-gray-500 text-sm font-semibold">No products found</p>
                                    <p class="text-gray-400 text-xs mt-1">Add your first product to get started</p>
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Stock In Section --}}
<div x-show="activeSection === 'stock-in'" x-transition x-cloak>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-7 fade-up">
        <div>
            <h1 class="text-[26px] font-black text-gray-900">Stock In Records</h1>
            <p class="text-[13.5px] text-gray-500 mt-0.5">Track all incoming inventory and deliveries</p>
        </div>
        <button @click="stockInModal=true" class="btn btn-violet btn-md text-[13px]">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Stock In
        </button>
    </div>

    <div class="card p-6">
        <div class="space-y-4">
            @if(isset($stockInRecords) && count($stockInRecords) > 0)
            @foreach($stockInRecords as $stockIn)
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors border border-gray-100">
                <div class="flex items-center gap-4 flex-1">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <p class="font-bold text-gray-900 text-sm">{{ $stockIn[1] }}</p>
                            <span class="text-xs font-mono text-gray-500">{{ $stockIn[0] }}</span>
                        </div>
                        <div class="flex items-center gap-4 mt-1">
                            <p class="text-xs text-gray-500">Supplier: {{ $stockIn[3] }}</p>
                            <p class="text-xs text-gray-500">Date: {{ $stockIn[4] }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-gray-900">{{ $stockIn[2] }}</p>
                        <p class="text-sm font-semibold text-green-600">{{ $stockIn[5] }}</p>
                    </div>
                    <div>
                        <span class="badge badge-green text-xs">{{ $stockIn[6] }}</span>
                    </div>
                </div>
            </div>
            @endforeach
            @else
            {{-- Empty State --}}
            <div class="text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">No Stock In Records Yet</h3>
                <p class="text-sm text-gray-500 mb-4">Start adding inventory to see stock in history</p>
                <button @click="stockInModal=true" class="btn btn-violet btn-sm mx-auto">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Stock In
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Stock Out Section --}}
<div x-show="activeSection === 'stock-out'" x-transition x-cloak>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-7 fade-up">
        <div>
            <h1 class="text-[26px] font-black text-gray-900">Stock Out Records</h1>
            <p class="text-[13.5px] text-gray-500 mt-0.5">Track all sales and outgoing inventory</p>
        </div>
        <button @click="stockOutModal=true; stockOutReference=generateStockOutReference()" class="btn btn-violet btn-md text-[13px]">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Record Stock Out
        </button>
    </div>

    <div class="card p-6">
        <div class="space-y-4">
            @if(isset($stockOutRecords) && count($stockOutRecords) > 0)
            @foreach($stockOutRecords as $stockOut)
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors border border-gray-100">
                <div class="flex items-center gap-4 flex-1">
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16v-4m0 4h4"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <p class="font-bold text-gray-900 text-sm">{{ $stockOut[1] }}</p>
                            <span class="text-xs font-mono text-gray-500">{{ $stockOut[0] }}</span>
                        </div>
                        <div class="flex items-center gap-4 mt-1">
                            <p class="text-xs text-gray-500">Type: {{ $stockOut[3] }}</p>
                            <p class="text-xs text-gray-500">Date: {{ $stockOut[4] }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-gray-900">{{ $stockOut[2] }}</p>
                        <p class="text-sm font-semibold text-violet-600">{{ $stockOut[5] }}</p>
                    </div>
                    <div>
                        <span class="badge badge-green text-xs">{{ $stockOut[6] }}</span>
                    </div>
                </div>
            </div>
            @endforeach
            @else
            {{-- Empty State --}}
            <div class="text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16v-4m0 4h4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">No Stock Out Records Yet</h3>
                <p class="text-sm text-gray-500 mb-4">Record your first stock out to see history</p>
                <button @click="stockOutModal=true; stockOutReference=generateStockOutReference()" class="btn btn-violet btn-sm mx-auto">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Record Stock Out
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Stock Adjustment Section --}}
<div x-show="activeSection === 'adjustment'" x-transition x-cloak>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-7 fade-up">
        <div>
            <h1 class="text-[26px] font-black text-gray-900">Stock Adjustments</h1>
            <p class="text-[13.5px] text-gray-500 mt-0.5">Manual corrections and inventory adjustments</p>
        </div>
        <button @click="adjustmentModal=true; adjustmentReference=generateAdjustmentReference()" class="btn btn-violet btn-md text-[13px]">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Adjustment
        </button>
    </div>

    <div class="card p-6">
        <div class="space-y-4">
            @if(isset($adjustmentRecords) && count($adjustmentRecords) > 0)
            @foreach($adjustmentRecords as $adjustment)
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors border border-gray-100">
                <div class="flex items-center gap-4 flex-1">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <p class="font-bold text-gray-900 text-sm">{{ $adjustment[1] }}</p>
                            <span class="text-xs font-mono text-gray-500">{{ $adjustment[0] }}</span>
                            <span class="badge {{ $adjustment[6] }} text-xs">{{ $adjustment[2] }}</span>
                        </div>
                        <div class="flex items-center gap-4 mt-1">
                            <p class="text-xs text-gray-500">Reason: {{ $adjustment[3] }}</p>
                        </div>
                        <div class="flex items-center gap-4 mt-1">
                            <p class="text-xs text-gray-400">By: {{ $adjustment[5] }} · {{ $adjustment[4] }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            @else
            {{-- Empty State --}}
            <div class="text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">No Adjustments Yet</h3>
                <p class="text-sm text-gray-500 mb-4">Create your first stock adjustment</p>
                <button @click="adjustmentModal=true; adjustmentReference=generateAdjustmentReference()" class="btn btn-violet btn-sm mx-auto">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Adjustment
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Inventory History Section --}}
<div x-show="activeSection === 'history'" x-transition x-cloak>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-7 fade-up">
        <div>
            <h1 class="text-[26px] font-black text-gray-900">Inventory History</h1>
            <p class="text-[13.5px] text-gray-500 mt-0.5">Complete audit trail of all inventory movements</p>
        </div>
        <div class="flex items-center gap-3">
            <button class="btn btn-outline btn-md text-[13px]">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
            </button>
            <button class="btn btn-outline btn-md text-[13px]">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export
            </button>
        </div>
    </div>

    <div class="card p-6">
        <div class="space-y-3">
            @foreach([
                ['Stock In', 'Mango', 'MNG-001', '+285 kg', 'Davao Fresh Farms', 'Today, 2:30 PM', 'green'],
                ['Sale', 'Mango', 'MNG-001', '-45 kg', 'POS Transaction #1245', 'Today, 2:30 PM', 'blue'],
                ['Stock In', 'Banana', 'BNA-041', '+210 kg', 'Davao Fresh Farms', 'Today, 11:15 AM', 'green'],
                ['Sale', 'Banana', 'BNA-041', '-78 kg', 'POS Transaction #1244', 'Today, 11:15 AM', 'blue'],
                ['Adjustment', 'Mango', 'MNG-002', '+12 kg', 'Recount correction', 'Yesterday, 10:30 AM', 'amber'],
                ['Stock Out', 'Pomelo', 'POM-034', '-15 kg', 'Spoilage removal', 'Yesterday, 10:00 AM', 'red'],
                ['Sale', 'Durian', 'DUR-112', '-22 kg', 'POS Transaction #1243', 'Yesterday, 4:45 PM', 'blue'],
                ['Stock In', 'Durian', 'DUR-112', '+145 kg', 'Mt. Apo Growers', 'Jun 19, 8:00 AM', 'green']
            ] as $history)
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors border border-gray-100">
                <div class="flex items-center gap-3 flex-1">
                    <div class="w-8 h-8 bg-{{ $history[6] }}-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        @if($history[6] === 'green')
                        <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4"/>
                        </svg>
                        @elseif($history[6] === 'red')
                        <svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7"/>
                        </svg>
                        @elseif($history[6] === 'blue')
                        <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        @else
                        <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4"/>
                        </svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="badge badge-{{ $history[6] }} text-xs">{{ $history[0] }}</span>
                            <p class="font-bold text-gray-900 text-sm">{{ $history[1] }}</p>
                            <span class="text-xs font-mono text-gray-400">{{ $history[2] }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $history[4] }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-bold text-gray-900">{{ $history[3] }}</p>
                        <p class="text-xs text-gray-400">{{ $history[5] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

</div>

{{-- View Modal --}}
<div x-show="viewModal" @click.self="viewModal=false" class="modal-bg fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" x-cloak x-transition>
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg border border-violet-100" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div class="bg-gray-50 border-b border-gray-100 p-6 relative rounded-t-3xl">
            <div class="absolute top-4 right-4"><button @click="viewModal=false" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-xl hover:bg-gray-200 transition-colors"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white border border-gray-200 rounded-2xl flex items-center justify-center shadow-sm">
                    <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-gray-900">Mango</h3>
                    <p class="text-gray-500 text-[13px] font-mono" x-text="'Batch: '+selected"></p>
                    <span class="badge badge-green text-[10px] mt-1">Available</span>
                </div>
            </div>
        </div>
        <div class="p-6 grid grid-cols-2 gap-4">
            @foreach([['Current Stock','285 kg'],['Unit Price','₱120/kg'],['Supplier','Davao Fresh Farms'],['Expiration','Jun 26, 2026'],['Received Date','Jun 16, 2026'],['Shelf Life','12 days'],['Storage','Room A · Shelf 3'],['Freshness Score','92/100']] as [$l,$v])
            <div class="bg-gray-50 rounded-xl p-3.5 border border-gray-100">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wide">{{ $l }}</p>
                <p class="text-[14px] font-bold text-gray-900 mt-1">{{ $v }}</p>
            </div>
            @endforeach
        </div>
        <div class="flex gap-3 px-6 pb-6">
            <button @click="viewModal=false" class="btn btn-violet btn-md flex-1">Close</button>
        </div>
    </div>
</div>

{{-- Add Stock In Transaction Modal --}}
<div x-show="stockInModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
    {{-- Backdrop for Stock In Modal --}}
    <div x-show="stockInModal" @click="resetStockInModal()" 
         class="fixed inset-0 bg-black/40 transition-opacity"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"></div>
    
    {{-- Stock In Modal Content --}}
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-5xl border border-violet-100 max-h-[90vh] overflow-hidden flex flex-col z-10" 
         x-show="stockInModal"
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        {{-- Header with Progress Steps --}}
        <div class="border-b border-gray-100 bg-gradient-to-r from-violet-50 to-purple-50">
            <div class="flex items-center justify-between px-6 py-5">
                <div>
                    <h3 class="font-black text-gray-900 text-[18px]">New Stock In Transaction</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Record incoming inventory from supplier</p>
                </div>
                <button @click="resetStockInModal()" class="p-2 hover:bg-white rounded-xl text-gray-400 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            {{-- Progress Indicator --}}
            <div class="px-6 pb-5">
                <div class="flex items-center justify-between max-w-2xl mx-auto">
                    {{-- Step 1 --}}
                    <div class="flex items-center flex-1">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all"
                                 :class="stockInStep >= 1 ? 'bg-violet-600 text-white' : 'bg-gray-200 text-gray-400'">
                                <span x-show="stockInStep > 1">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                                <span x-show="stockInStep <= 1">1</span>
                            </div>
                            <div class="hidden sm:block">
                                <p class="text-xs font-bold" :class="stockInStep >= 1 ? 'text-violet-700' : 'text-gray-400'">Transaction Info</p>
                                <p class="text-[10px] text-gray-400">Supplier & Date</p>
                            </div>
                        </div>
                        <div class="flex-1 h-0.5 mx-3" :class="stockInStep >= 2 ? 'bg-violet-600' : 'bg-gray-200'"></div>
                    </div>
                    
                    {{-- Step 2 --}}
                    <div class="flex items-center flex-1">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all"
                                 :class="stockInStep >= 2 ? 'bg-violet-600 text-white' : 'bg-gray-200 text-gray-400'">
                                <span x-show="stockInStep > 2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                                <span x-show="stockInStep <= 2">2</span>
                            </div>
                            <div class="hidden sm:block">
                                <p class="text-xs font-bold" :class="stockInStep >= 2 ? 'text-violet-700' : 'text-gray-400'">Add Items</p>
                                <p class="text-[10px] text-gray-400">Products & Batches</p>
                            </div>
                        </div>
                        <div class="flex-1 h-0.5 mx-3" :class="stockInStep >= 3 ? 'bg-violet-600' : 'bg-gray-200'"></div>
                    </div>
                    
                    {{-- Step 3 --}}
                    <div class="flex items-center">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all"
                                 :class="stockInStep >= 3 ? 'bg-violet-600 text-white' : 'bg-gray-200 text-gray-400'">3</div>
                            <div class="hidden sm:block">
                                <p class="text-xs font-bold" :class="stockInStep >= 3 ? 'text-violet-700' : 'text-gray-400'">Review & Submit</p>
                                <p class="text-[10px] text-gray-400">Confirm Details</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Content Area --}}
        <div class="flex-1 overflow-y-auto">
            
            {{-- STEP 1: Transaction Information --}}
            <div x-show="stockInStep === 1" x-transition class="p-8">
                <div class="max-w-2xl mx-auto space-y-6">
                    <div class="text-center mb-8">
                        <div class="w-16 h-16 bg-violet-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h4 class="text-xl font-black text-gray-900 mb-2">Transaction Details</h4>
                        <p class="text-sm text-gray-500">Enter supplier information and delivery date</p>
                    </div>
                    
                    <div class="bg-violet-50 border border-violet-200 rounded-xl p-4">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-violet-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-xs font-bold text-violet-900">Reference Number</p>
                                <p class="text-sm font-mono text-violet-700" x-text="referenceNumber || 'Auto-generated'"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="inp-label">Supplier *</label>
                            <select x-model="supplier" class="inp" required>
                                <option value="">Select Supplier</option>
                                <option>Davao Fresh Farms</option>
                                <option>Mt. Apo Growers</option>
                                <option>Sta. Cruz Orchards</option>
                                <option>Mindanao Fruit Co.</option>
                                <option>Golden Harvest Trading</option>
                            </select>
                        </div>
                        <div>
                            <label class="inp-label">Date Received *</label>
                            <input type="date" x-model="dateReceived" class="inp" required>
                        </div>
                    </div>
                    
                    <div>
                        <label class="inp-label">Notes (Optional)</label>
                        <textarea rows="3" placeholder="Delivery notes, quality observations, special conditions..." class="inp"></textarea>
                    </div>
                </div>
            </div>
            
            {{-- STEP 2: Add Items --}}
            <div x-show="stockInStep === 2" x-transition class="p-8">
                <div class="max-w-5xl mx-auto">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        {{-- Left: Add Item Form --}}
                        <div class="lg:col-span-1">
                            <div class="sticky top-0">
                                <div class="bg-gradient-to-br from-violet-50 to-purple-50 rounded-2xl border-2 border-violet-200 p-6">
                                    <div class="flex items-center gap-3 mb-5">
                                        <div class="w-10 h-10 bg-violet-600 rounded-xl flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-black text-gray-900">Add Product Item</h4>
                                            <p class="text-xs text-gray-500">Fill in product details</p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="inp-label">Product *</label>
                                            <select x-model="currentItem.product" class="inp bg-white" required>
                                                <option value="">Select Product</option>
                                                @if(isset($inventoryItems) && $inventoryItems->count() > 0)
                                                    @foreach($inventoryItems as $item)
                                                        <option value="{{ $item->name }}">{{ $item->name }}</option>
                                                    @endforeach
                                                @else
                                                    <option disabled>No products available - Add products first</option>
                                                @endif
                                            </select>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="inp-label">Quantity (kg) *</label>
                                                <input type="number" x-model="currentItem.quantity" min="0" step="0.01" placeholder="0.00" class="inp bg-white" required>
                                            </div>
                                            <div>
                                                <label class="inp-label">Unit Cost (₱) *</label>
                                                <input type="number" x-model="currentItem.unitCost" min="0" step="0.01" placeholder="0.00" class="inp bg-white" required>
                                            </div>
                                        </div>
                                        
                                        {{-- Auto-generated Details Preview --}}
                                        <template x-if="currentItem.product && currentItem.quantity > 0 && currentItem.unitCost > 0">
                                            <div class="bg-white rounded-xl p-4 border-2 border-violet-300 shadow-sm">
                                                <p class="text-xs font-bold text-violet-700 uppercase mb-3 flex items-center gap-2">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    Auto-Generated
                                                </p>
                                                <div class="space-y-2.5">
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs text-gray-600">Batch ID</span>
                                                        <span class="text-xs font-bold font-mono text-gray-900 bg-gray-100 px-2 py-1 rounded" x-text="generateBatchId(currentItem.product)"></span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs text-gray-600">Shelf Life</span>
                                                        <span class="text-xs font-bold text-gray-900" x-text="calculateDates(currentItem.product, dateReceived).shelfLife + ' days'"></span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs text-gray-600">Expiration</span>
                                                        <span class="text-xs font-bold text-gray-900" x-text="calculateDates(currentItem.product, dateReceived).expirationDate"></span>
                                                    </div>
                                                    <div class="h-px bg-gray-200 my-2"></div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs font-bold text-gray-700">Total Cost</span>
                                                        <span class="text-sm font-black text-violet-700" x-text="'₱' + (currentItem.quantity * currentItem.unitCost).toFixed(2)"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                        
                                        <button @click.stop="addStockItem()" 
                                                :disabled="!currentItem.product || currentItem.quantity <= 0 || currentItem.unitCost <= 0"
                                                :class="(!currentItem.product || currentItem.quantity <= 0 || currentItem.unitCost <= 0) ? 'opacity-50 cursor-not-allowed' : 'hover:shadow-lg'"
                                                class="w-full btn btn-violet btn-md text-sm transition-all">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Add to List
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Right: Items List --}}
                        <div class="lg:col-span-2">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h4 class="text-lg font-black text-gray-900">Items in Transaction</h4>
                                    <p class="text-sm text-gray-500 mt-1">
                                        <span x-show="stockInItems.length === 0">No items added yet</span>
                                        <span x-show="stockInItems.length > 0" x-text="stockInItems.length + ' item' + (stockInItems.length !== 1 ? 's' : '') + ' added'"></span>
                                    </p>
                                </div>
                                <div x-show="stockInItems.length > itemsPerPage" class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500">
                                        Showing <span x-text="((currentPage - 1) * itemsPerPage) + 1"></span>-<span x-text="Math.min(currentPage * itemsPerPage, stockInItems.length)"></span> of <span x-text="stockInItems.length"></span>
                                    </span>
                                </div>
                            </div>
                    <div class="border-2 border-dashed border-gray-200 rounded-2xl overflow-hidden min-h-[400px]">
                        <template x-if="stockInItems.length === 0">
                            <div class="text-center py-20 bg-gray-50 h-full flex flex-col items-center justify-center">
                                <div class="w-20 h-20 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <p class="text-base font-bold text-gray-500 mb-1">No items in transaction</p>
                                <p class="text-sm text-gray-400">Use the form on the left to add products</p>
                            </div>
                        </template>

                        <template x-if="stockInItems.length > 0">
                            <div>
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-gray-50 border-b-2 border-gray-200">
                                            <tr>
                                                <th class="text-left py-3 px-4 text-xs font-bold text-gray-600 uppercase">Product</th>
                                                <th class="text-left py-3 px-4 text-xs font-bold text-gray-600 uppercase">Batch ID</th>
                                                <th class="text-right py-3 px-4 text-xs font-bold text-gray-600 uppercase">Quantity</th>
                                                <th class="text-right py-3 px-4 text-xs font-bold text-gray-600 uppercase">Unit Cost</th>
                                                <th class="text-right py-3 px-4 text-xs font-bold text-gray-600 uppercase">Total</th>
                                                <th class="text-left py-3 px-4 text-xs font-bold text-gray-600 uppercase">Expiration</th>
                                                <th class="text-center py-3 px-4 text-xs font-bold text-gray-600 uppercase">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white">
                                            <template x-for="(item, index) in paginatedItems" :key="index">
                                                <tr class="border-b border-gray-100 hover:bg-violet-50 transition-colors">
                                                    <td class="py-4 px-4">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center">
                                                                <svg class="w-4 h-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                                </svg>
                                                            </div>
                                                            <span class="text-sm font-bold text-gray-900" x-text="item.product"></span>
                                                        </div>
                                                    </td>
                                                    <td class="py-4 px-4">
                                                        <span class="text-xs font-mono bg-gray-100 px-2 py-1 rounded text-gray-600" x-text="item.batchId"></span>
                                                    </td>
                                                    <td class="py-4 px-4 text-right">
                                                        <span class="text-sm font-bold text-gray-900" x-text="item.quantity + ' kg'"></span>
                                                    </td>
                                                    <td class="py-4 px-4 text-right">
                                                        <span class="text-sm text-gray-600" x-text="'₱' + item.unitCost.toFixed(2)"></span>
                                                    </td>
                                                    <td class="py-4 px-4 text-right">
                                                        <span class="text-sm font-black text-violet-700" x-text="'₱' + item.totalCost.toFixed(2)"></span>
                                                    </td>
                                                    <td class="py-4 px-4">
                                                        <div>
                                                            <p class="text-xs font-semibold text-gray-700" x-text="item.expirationDate"></p>
                                                            <p class="text-xs text-gray-400" x-text="item.shelfLife + ' days'"></p>
                                                        </div>
                                                    </td>
                                                    <td class="py-4 px-4 text-center">
                                                        <button @click.stop="removeStockItem((currentPage - 1) * itemsPerPage + index)" 
                                                                class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                                                title="Remove item">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- Pagination Controls --}}
                                <div x-show="stockInItems.length > itemsPerPage" class="bg-gray-50 border-t border-gray-200 px-6 py-3">
                                    <div class="flex items-center justify-between">
                                        <div class="text-xs text-gray-600">
                                            Page <span class="font-bold" x-text="currentPage"></span> of <span class="font-bold" x-text="totalPages"></span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            {{-- Previous Button --}}
                                            <button @click="prevPage()" 
                                                    :disabled="currentPage === 1"
                                                    :class="currentPage === 1 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-200'"
                                                    class="p-2 rounded-lg text-gray-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                                </svg>
                                            </button>
                                            
                                            {{-- Page Numbers --}}
                                            <template x-for="page in totalPages" :key="page">
                                                <button @click="goToPage(page)"
                                                        :class="currentPage === page ? 'bg-violet-600 text-white' : 'text-gray-600 hover:bg-gray-200'"
                                                        class="w-8 h-8 rounded-lg text-xs font-bold transition-colors"
                                                        x-text="page">
                                                </button>
                                            </template>
                                            
                                            {{-- Next Button --}}
                                            <button @click="nextPage()" 
                                                    :disabled="currentPage === totalPages"
                                                    :class="currentPage === totalPages ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-200'"
                                                    class="p-2 rounded-lg text-gray-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- Summary Bar --}}
                                <div class="bg-violet-50 border-t-2 border-violet-200 px-6 py-4">
                                    <div class="flex items-center justify-between max-w-md ml-auto">
                                        <div class="text-center">
                                            <p class="text-xs font-bold text-violet-600 uppercase">Total Items</p>
                                            <p class="text-2xl font-black text-violet-900" x-text="stockInItems.length"></p>
                                        </div>
                                        <div class="w-px h-12 bg-violet-200"></div>
                                        <div class="text-center">
                                            <p class="text-xs font-bold text-violet-600 uppercase">Total Quantity</p>
                                            <p class="text-2xl font-black text-violet-900" x-text="stockInItems.reduce((sum, item) => sum + parseFloat(item.quantity), 0).toFixed(2) + ' kg'"></p>
                                        </div>
                                        <div class="w-px h-12 bg-violet-200"></div>
                                        <div class="text-center">
                                            <p class="text-xs font-bold text-violet-600 uppercase">Total Amount</p>
                                            <p class="text-2xl font-black text-violet-900" x-text="'₱' + getTotalAmount().toFixed(2)"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- STEP 3: Review & Submit --}}
            <div x-show="stockInStep === 3" x-transition class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 300px);">
                <div class="max-w-3xl mx-auto">
                    <div class="text-center mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h4 class="text-lg font-black text-gray-900 mb-1">Review Transaction</h4>
                        <p class="text-xs text-gray-500">Verify all details before submitting</p>
                    </div>
                    
                    {{-- Transaction Summary --}}
                    <div class="bg-gradient-to-br from-violet-50 to-purple-50 rounded-xl border-2 border-violet-200 p-4 mb-3">
                        <h5 class="text-xs font-bold text-violet-900 uppercase mb-2 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Transaction Details
                        </h5>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="bg-white rounded-lg p-2.5 border border-violet-100">
                                <p class="text-[10px] text-gray-500 mb-0.5">Reference Number</p>
                                <p class="text-xs font-bold font-mono text-gray-900" x-text="referenceNumber"></p>
                            </div>
                            <div class="bg-white rounded-lg p-2.5 border border-violet-100">
                                <p class="text-[10px] text-gray-500 mb-0.5">Date Received</p>
                                <p class="text-xs font-bold text-gray-900" x-text="dateReceived"></p>
                            </div>
                            <div class="bg-white rounded-lg p-2.5 border border-violet-100 col-span-2">
                                <p class="text-[10px] text-gray-500 mb-0.5">Supplier</p>
                                <p class="text-xs font-bold text-gray-900" x-text="supplier"></p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Items Summary --}}
                    <div class="bg-white rounded-xl border-2 border-gray-200 overflow-hidden mb-3">
                        <div class="bg-gray-50 border-b border-gray-200 px-4 py-2">
                            <h5 class="text-xs font-bold text-gray-900 uppercase flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Stock Items (<span x-text="stockInItems.length"></span>)
                            </h5>
                        </div>
                        <div class="p-3 space-y-1.5 max-h-40 overflow-y-auto">
                            <template x-for="(item, index) in stockInItems" :key="index">
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="flex items-center gap-2 flex-1 min-w-0">
                                        <div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-gray-900 text-xs truncate" x-text="item.product"></p>
                                            <p class="text-[10px] text-gray-500 truncate">
                                                <span class="font-mono" x-text="item.batchId"></span> · 
                                                <span x-text="item.expirationDate"></span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0 ml-2">
                                        <p class="text-sm font-black text-gray-900" x-text="item.quantity + ' kg'"></p>
                                        <p class="text-xs font-bold text-violet-700" x-text="'₱' + item.totalCost.toFixed(2)"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="bg-violet-600 px-4 py-2.5">
                            <div class="flex items-center justify-between text-white">
                                <span class="font-bold uppercase text-xs">Grand Total</span>
                                <span class="text-xl font-black" x-text="'₱' + getTotalAmount().toFixed(2)"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start gap-2">
                        <svg class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <p class="text-xs font-bold text-amber-900 mb-0.5">Confirm Transaction</p>
                            <p class="text-[10px] text-amber-700">Review carefully. This will update inventory levels.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Footer with Navigation --}}
        <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">
            <div class="flex items-center justify-between">
                {{-- Back Button --}}
                <button @click="prevStep()" 
                        x-show="stockInStep > 1"
                        class="btn btn-outline btn-md">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back
                </button>
                <button @click="resetStockInModal()" 
                        x-show="stockInStep === 1"
                        class="btn btn-outline btn-md">
                    Cancel
                </button>
                
                <div class="flex gap-3">
                    {{-- Next Button (Steps 1-2) --}}
                    <button @click="nextStep()" 
                            x-show="stockInStep < 3"
                            :disabled="(stockInStep === 1 && (!supplier || !dateReceived)) || (stockInStep === 2 && stockInItems.length === 0)"
                            :class="(stockInStep === 1 && (!supplier || !dateReceived)) || (stockInStep === 2 && stockInItems.length === 0) ? 'opacity-50 cursor-not-allowed' : ''"
                            class="btn btn-violet btn-md min-w-32">
                        <span x-show="stockInStep === 1">Continue</span>
                        <span x-show="stockInStep === 2">Review</span>
                        <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    
                    {{-- Submit Button (Step 3) --}}
                    <button @click="saveStockIn()" 
                            x-show="stockInStep === 3"
                            class="btn btn-violet btn-md min-w-32">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Submit Transaction
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Product to Master List Modal --}}
<div x-show="addProductModal" @click.self="addProductModal=false" class="modal-bg fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" x-cloak x-transition>
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg border border-violet-100" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100">
            <div>
                <h3 class="font-bold text-gray-900 text-[17px]">Add New Product</h3>
                <p class="text-xs text-gray-500 mt-0.5">Add a new product to the master catalog</p>
            </div>
            <button @click="addProductModal=false" class="p-2 hover:bg-gray-100 rounded-xl text-gray-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="inp-label">Product Name *</label>
                <input type="text" 
                       x-model="newProduct.name" 
                       @input="updateProductSKU()"
                       placeholder="e.g., Mango, Durian, Pomelo" 
                       class="inp" 
                       required>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="inp-label">Category *</label>
                    <select x-model="newProduct.category" class="inp" required>
                        <option value="">Select Category</option>
                        <option>Tropical Fruit</option>
                        <option>Citrus</option>
                        <option>Seasonal</option>
                    </select>
                </div>
                <div>
                    <label class="inp-label">Unit of Measure *</label>
                    <select x-model="newProduct.unit" class="inp" required>
                        <option value="">Select Unit</option>
                        <option>kg (Kilogram)</option>
                        <option>pc (Piece)</option>
                        <option>box (Box)</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="inp-label">SKU/Code (Auto-generated)</label>
                <input type="text" 
                       x-model="newProduct.skuCode" 
                       placeholder="Will be generated automatically" 
                       class="inp bg-gray-50 cursor-not-allowed" 
                       readonly>
                <p class="text-xs text-gray-500 mt-1">SKU code is automatically generated based on product name</p>
            </div>
            <div>
                <label class="inp-label">Description</label>
                <textarea rows="3" 
                          x-model="newProduct.description" 
                          placeholder="Product description, origin, or special notes..." 
                          class="inp"></textarea>
            </div>
        </div>
        <div class="flex gap-3 px-6 pb-6">
            <button @click="addProductModal=false" class="btn btn-outline btn-md flex-1">Cancel</button>
            <button @click="saveProduct()" class="btn btn-violet btn-md flex-1">Add Product</button>
        </div>
    </div>
</div>

{{-- Edit Modal (Keep for editing individual items from Overview) --}}
<div x-show="editModal" @click.self="editModal=false" class="modal-bg fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" x-cloak x-transition>
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md border border-violet-100" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100">
            <h3 class="font-bold text-gray-900 text-[15px]" x-text="'Edit Stock — '+selected"></h3>
            <button @click="editModal=false" class="p-2 hover:bg-gray-100 rounded-xl text-gray-400"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="p-6 space-y-4">
            <div><label class="inp-label">Fruit Type</label><select class="inp"><option>Mango</option><option>Durian</option><option>Pomelo</option><option>Mangosteen</option><option>Lanzones</option><option>Banana</option><option>Pineapple</option></select></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="inp-label">Batch ID</label><input type="text" value="MNG-001" placeholder="MNG-003" class="inp"></div>
                <div><label class="inp-label">Quantity (kg)</label><input type="number" value="285" placeholder="0" class="inp"></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="inp-label">Unit Price (₱)</label><input type="number" value="120" placeholder="0" class="inp"></div>
                <div><label class="inp-label">Expiration Date</label><input type="date" value="2026-06-26" class="inp"></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="inp-label">Remaining Shelf Life (days)</label><input type="number" value="12" placeholder="0" class="inp"></div>
                <div><label class="inp-label">Supplier</label><select class="inp"><option>Davao Fresh Farms</option><option>Mt. Apo Growers</option><option>Sta. Cruz Orchards</option></select></div>
            </div>
        </div>
        <div class="flex gap-3 px-6 pb-6">
            <button @click="editModal=false" class="btn btn-outline btn-md flex-1">Cancel</button>
            <button @click="editModal=false" class="btn btn-violet btn-md flex-1">Save Changes</button>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div x-show="deleteModal" @click.self="deleteModal=false" class="modal-bg fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" x-cloak x-transition>
    <div class="bg-white rounded-3xl shadow-2xl p-8 w-full max-w-sm text-center border border-red-100" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-5"><svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></div>
        <h3 class="text-[18px] font-bold text-gray-900 mb-2">Remove Stock Item?</h3>
        <p class="text-[13.5px] text-gray-500 mb-6"><span class="font-bold text-gray-800" x-text="selected"></span> will be permanently removed from inventory.</p>
        <div class="flex gap-3">
            <button @click="deleteModal=false" class="btn btn-outline btn-md flex-1">Cancel</button>
            <button @click="deleteModal=false" class="btn bg-red-600 hover:bg-red-700 text-white btn-md flex-1">Remove</button>
        </div>
    </div>
</div>

{{-- Stock Out Modal --}}
<div x-show="stockOutModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
    {{-- Backdrop --}}
    <div x-show="stockOutModal" @click="resetStockOutModal()" 
         class="fixed inset-0 bg-black/40 transition-opacity"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"></div>
    
    {{-- Modal Content --}}
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl border border-violet-100 max-h-[90vh] overflow-hidden flex flex-col z-10" 
         x-show="stockOutModal"
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        {{-- Header --}}
        <div class="border-b border-gray-100 bg-gradient-to-r from-violet-50 to-purple-50">
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <h3 class="font-black text-gray-900 text-base">Record Stock Out</h3>
                    <p class="text-[10px] text-gray-500 mt-0.5">Remove inventory from stock</p>
                </div>
                <button @click="resetStockOutModal()" class="p-1.5 hover:bg-white rounded-lg text-gray-400 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        
        {{-- Content --}}
        <div class="flex-1 overflow-y-auto" style="max-height: calc(90vh - 150px);">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 p-4">
                
                {{-- Left Column: Transaction Details --}}
                <div class="lg:col-span-1 space-y-3">
                    <div class="bg-violet-50 border-2 border-violet-200 rounded-xl p-3 sticky top-0">
                        <h4 class="text-xs font-black text-violet-900 mb-3 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Transaction Details
                        </h4>
                        
                        <div class="space-y-2.5">
                            <div>
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">Reference Number</label>
                                <input type="text" x-model="stockOutReference" class="inp bg-white text-xs py-1.5" readonly>
                            </div>
                            
                            <div>
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">Date *</label>
                                <input type="date" x-model="stockOutDate" class="inp bg-white text-xs py-1.5" required>
                            </div>
                            
                            <div>
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">Type *</label>
                                <select x-model="stockOutType" class="inp bg-white text-xs py-1.5" required>
                                    <option value="sale">Sale</option>
                                    <option value="spoilage">Spoilage</option>
                                    <option value="wastage">Wastage</option>
                                    <option value="return">Return</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="sample">Sample</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">Notes</label>
                                <textarea rows="2" x-model="stockOutReason" placeholder="Optional..." class="inp bg-white text-xs py-1.5"></textarea>
                            </div>
                        </div>
                        
                        {{-- Summary --}}
                        <div class="mt-3 pt-3 border-t-2 border-violet-300">
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="text-violet-900 font-semibold">Items:</span>
                                <span class="font-black text-violet-900" x-text="stockOutItems.length"></span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-violet-900 font-semibold">Total:</span>
                                <span class="font-black text-violet-900" x-text="stockOutItems.reduce((sum, item) => sum + parseFloat(item.quantity || 0), 0).toFixed(2) + ' kg'"></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Right Column: Product Selection --}}
                <div class="lg:col-span-2 space-y-3">
                    
                    {{-- Search & Filter Section --}}
                    <div class="bg-white rounded-xl border-2 border-gray-200 p-3">
                        <h4 class="text-xs font-black text-gray-900 mb-2 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Search Products
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-2.5 py-1.5 border-2 border-transparent focus-within:border-violet-300 focus-within:bg-white transition-all">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="text" 
                                       x-model="stockOutSearchQuery" 
                                       placeholder="Search..." 
                                       class="bg-transparent text-xs outline-none w-full">
                            </div>
                            
                            <select x-model="stockOutFilterCategory" class="inp text-xs py-1.5">
                                <option value="">All Categories</option>
                                <option value="Tropical Fruit">Tropical Fruit</option>
                                <option value="Citrus">Citrus</option>
                                <option value="Seasonal">Seasonal</option>
                            </select>
                        </div>
                    </div>
                    
                    {{-- Available Products List --}}
                    <div class="bg-white rounded-xl border-2 border-gray-200 overflow-hidden">
                        <div class="bg-gray-50 border-b border-gray-200 px-3 py-2">
                            <h4 class="text-xs font-black text-gray-900 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Available Inventory
                                <span class="ml-auto text-[10px] font-normal bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded" x-text="filteredStock.length + ' items'"></span>
                            </h4>
                        </div>
                        
                        <div class="p-2 max-h-40 overflow-y-auto space-y-1.5">
                            <template x-if="filteredStock.length === 0">
                                <div class="text-center py-6">
                                    <svg class="w-8 h-8 text-gray-300 mx-auto mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <p class="text-xs text-gray-500">No products found</p>
                                </div>
                            </template>
                            
                            <template x-for="batch in filteredStock" :key="batch.batchId">
                                <div @click="selectBatchForStockOut(batch)"
                                     :class="selectedBatch?.batchId === batch.batchId ? 'border-violet-500 bg-violet-50' : 'border-gray-200 hover:border-violet-300 hover:bg-violet-50'"
                                     class="cursor-pointer p-2 border-2 rounded-lg transition-all group">
                                    <div class="flex items-center gap-2">
                                        <div :class="selectedBatch?.batchId === batch.batchId ? 'bg-violet-200' : 'bg-gray-100 group-hover:bg-violet-100'" 
                                             class="w-7 h-7 rounded-lg flex items-center justify-center transition-colors">
                                            <svg class="w-3.5 h-3.5" :class="selectedBatch?.batchId === batch.batchId ? 'text-violet-700' : 'text-gray-400 group-hover:text-violet-600'" 
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-1.5 mb-0.5">
                                                <p class="font-bold text-gray-900 text-xs truncate" x-text="batch.product"></p>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded font-mono bg-gray-100 text-gray-600" x-text="batch.batchCode"></span>
                                            </div>
                                            <div class="flex items-center gap-2 text-[10px] text-gray-500">
                                                <span>Avail: <strong x-text="batch.quantity + ' kg'"></strong></span>
                                                <span class="px-1.5 py-0.5 rounded" 
                                                      :class="batch.freshness > 14 ? 'bg-green-100 text-green-700' : (batch.freshness > 7 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')"
                                                      x-text="batch.freshness + 'd'"></span>
                                            </div>
                                        </div>
                                        <div x-show="selectedBatch?.batchId === batch.batchId" class="flex-shrink-0">
                                            <svg class="w-4 h-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    {{-- Add Quantity Section --}}
                    <div x-show="selectedBatch" x-transition class="bg-gradient-to-br from-violet-50 to-purple-50 rounded-xl border-2 border-violet-300 p-3">
                        <h4 class="text-xs font-black text-violet-900 mb-2">Enter Quantity</h4>
                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">Quantity (kg) *</label>
                                <input type="number" 
                                       x-model="stockOutQuantity"
                                       :max="selectedBatch?.quantity"
                                       min="0.01"
                                       step="0.01"
                                       placeholder="0.00"
                                       class="inp bg-white text-sm font-bold py-1.5"
                                       @keydown.enter="addSelectedBatch()">
                                <p class="text-[10px] text-violet-700 mt-0.5">
                                    Max: <span class="font-bold" x-text="selectedBatch?.quantity + ' kg'"></span>
                                </p>
                            </div>
                            <button @click="addSelectedBatch()"
                                    :disabled="!selectedBatch || stockOutQuantity <= 0 || stockOutQuantity > selectedBatch?.quantity"
                                    :class="(!selectedBatch || stockOutQuantity <= 0 || stockOutQuantity > selectedBatch?.quantity) ? 'opacity-50 cursor-not-allowed' : ''"
                                    class="btn bg-violet-600 hover:bg-violet-700 text-white btn-sm whitespace-nowrap text-xs px-3 py-1.5">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add
                            </button>
                        </div>
                    </div>
                    
                    {{-- Selected Items Table --}}
                    <div class="bg-white rounded-xl border-2 border-gray-200 overflow-hidden">
                        <div class="bg-gray-50 border-b border-gray-200 px-3 py-2">
                            <h4 class="text-xs font-black text-gray-900 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Items to Remove (<span x-text="stockOutItems.length"></span>)
                            </h4>
                        </div>
                        
                        <div class="min-h-[120px] max-h-40 overflow-y-auto">
                            <template x-if="stockOutItems.length === 0">
                                <div class="text-center py-8 bg-gray-50">
                                    <svg class="w-8 h-8 text-gray-300 mx-auto mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <p class="text-xs text-gray-500 font-medium">No items selected</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">Select products above</p>
                                </div>
                            </template>
                            
                            <template x-if="stockOutItems.length > 0">
                                <div class="p-2 space-y-1.5">
                                    <template x-for="(item, index) in stockOutItems" :key="index">
                                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg border border-gray-200 hover:bg-violet-50">
                                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                                <div class="w-7 h-7 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-3.5 h-3.5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-bold text-gray-900 text-xs truncate" x-text="item.product"></p>
                                                    <p class="text-[10px] text-gray-500">
                                                        <span class="font-mono" x-text="item.batchCode || item.batchId"></span>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                <span class="text-sm font-black text-violet-600" x-text="item.quantity + ' kg'"></span>
                                                <button @click="removeStockOutItem(index)" 
                                                        class="p-1 text-gray-400 hover:text-red-500 hover:bg-red-100 rounded transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        
        {{-- Footer --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 py-2.5">
            <div class="flex items-center justify-between">
                <button @click="resetStockOutModal()" class="btn btn-outline btn-sm text-xs px-4">
                    Cancel
                </button>
                <button @click="saveStockOut()" 
                        :disabled="!stockOutType || !stockOutDate || stockOutItems.length === 0"
                        :class="(!stockOutType || !stockOutDate || stockOutItems.length === 0) ? 'opacity-50 cursor-not-allowed' : ''"
                        class="btn bg-violet-600 hover:bg-violet-700 text-white btn-sm text-xs px-4">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Record Stock Out
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Stock Adjustment Modal --}}
<div x-show="adjustmentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
    {{-- Backdrop --}}
    <div x-show="adjustmentModal" @click="resetAdjustmentModal()" 
         class="fixed inset-0 bg-black/40 transition-opacity"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"></div>
    
    {{-- Modal Content --}}
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl border border-violet-100 max-h-[90vh] overflow-hidden flex flex-col z-10" 
         x-show="adjustmentModal"
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        {{-- Header --}}
        <div class="border-b border-gray-100 bg-gradient-to-r from-violet-50 to-purple-50">
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <h3 class="font-black text-gray-900 text-base">Stock Adjustment</h3>
                    <p class="text-[10px] text-gray-500 mt-0.5">Correct inventory discrepancies</p>
                </div>
                <button @click="resetAdjustmentModal()" class="p-1.5 hover:bg-white rounded-lg text-gray-400 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        
        {{-- Content --}}
        <div class="flex-1 overflow-y-auto" style="max-height: calc(90vh - 135px);">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 p-4">
                
                {{-- Left Column: Adjustment Details --}}
                <div class="lg:col-span-1 space-y-3">
                    <div class="bg-violet-50 border-2 border-violet-200 rounded-xl p-3 sticky top-0">
                        <h4 class="text-xs font-black text-violet-900 mb-3 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Adjustment Details
                        </h4>
                        
                        <div class="space-y-2.5">
                            <div>
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">Reference Number</label>
                                <input type="text" x-model="adjustmentReference" class="inp bg-white text-xs py-1.5" readonly>
                            </div>
                            
                            <div>
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">Date *</label>
                                <input type="date" x-model="adjustmentDate" class="inp bg-white text-xs py-1.5" required>
                            </div>
                            
                            <div>
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">Reason *</label>
                                <select x-model="adjustmentReason" class="inp bg-white text-xs py-1.5" required>
                                    <option value="">Select Reason</option>
                                    <option value="inventory_count">Physical Inventory Count</option>
                                    <option value="data_entry_error">Data Entry Error</option>
                                    <option value="system_glitch">System Glitch</option>
                                    <option value="measurement_error">Measurement Error</option>
                                    <option value="found_stock">Found/Discovered Stock</option>
                                    <option value="reconciliation">Account Reconciliation</option>
                                    <option value="audit">Audit Correction</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">Notes</label>
                                <textarea rows="2" x-model="adjustmentNotes" placeholder="Optional details..." class="inp bg-white text-xs py-1.5"></textarea>
                            </div>
                        </div>
                        
                        {{-- Summary --}}
                        <div class="mt-3 pt-3 border-t-2 border-violet-300">
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="text-violet-900 font-semibold">Items:</span>
                                <span class="font-black text-violet-900" x-text="adjustmentItems.length"></span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-violet-900 font-semibold">Adjustments:</span>
                                <div class="text-right">
                                    <div class="font-bold text-green-600" x-text="'+' + adjustmentItems.filter(i => i.type === 'add').reduce((sum, i) => sum + i.quantity, 0).toFixed(2) + ' kg'"></div>
                                    <div class="font-bold text-red-600" x-text="'-' + adjustmentItems.filter(i => i.type === 'subtract').reduce((sum, i) => sum + i.quantity, 0).toFixed(2) + ' kg'"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Items to Adjust List --}}
                    <div class="bg-white rounded-xl border-2 border-violet-200 overflow-hidden">
                        <div class="bg-violet-50 border-b-2 border-violet-200 px-3 py-2">
                            <h4 class="text-xs font-black text-violet-900 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Items to Adjust
                                <span class="ml-auto text-[10px] font-normal bg-violet-200 text-violet-900 px-1.5 py-0.5 rounded" x-text="adjustmentItems.length"></span>
                            </h4>
                        </div>
                        
                        <div class="p-2 max-h-32 overflow-y-auto space-y-1.5">
                            <template x-if="adjustmentItems.length === 0">
                                <div class="text-center py-6">
                                    <svg class="w-8 h-8 text-gray-300 mx-auto mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <p class="text-xs text-gray-500">No items added</p>
                                </div>
                            </template>
                            
                            <template x-for="(item, index) in adjustmentItems" :key="index">
                                <div class="p-2 bg-gray-50 border-2 border-gray-200 rounded-lg">
                                    <div class="flex items-start gap-2">
                                        <div :class="item.type === 'add' ? 'bg-green-100' : 'bg-red-100'" 
                                             class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <svg class="w-3.5 h-3.5" :class="item.type === 'add' ? 'text-green-600' : 'text-red-600'" 
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.type === 'add' ? 'M12 4v16m8-8H4' : 'M20 12H4'"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-1.5 mb-1">
                                                <p class="font-bold text-gray-900 text-xs truncate" x-text="item.product"></p>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded font-mono bg-gray-200 text-gray-600" x-text="item.batchId"></span>
                                            </div>
                                            <div class="flex items-center gap-2 text-[10px]">
                                                <span class="text-gray-500">Current: <strong x-text="item.currentStock + ' kg'"></strong></span>
                                                <span class="font-bold" 
                                                      :class="item.type === 'add' ? 'text-green-600' : 'text-red-600'"
                                                      x-text="(item.type === 'add' ? '+' : '-') + item.quantity + ' kg'"></span>
                                            </div>
                                        </div>
                                        <button @click="removeAdjustmentItem(index)" 
                                                class="p-1 hover:bg-red-100 rounded text-red-500 transition-colors flex-shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                
                {{-- Right Column: Product Selection --}}
                <div class="lg:col-span-2 space-y-3">
                    
                    {{-- Adjustment Type Selector --}}
                    <div class="bg-white rounded-xl border-2 border-gray-200 p-3">
                        <h4 class="text-xs font-black text-gray-900 mb-2 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                            </svg>
                            Adjustment Type
                        </h4>
                        
                        <div class="grid grid-cols-2 gap-2">
                            <button @click="adjustmentType='add'" 
                                    :class="adjustmentType === 'add' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-gray-50 border-gray-300 text-gray-600 hover:border-gray-400'"
                                    class="p-2.5 border-2 rounded-xl transition-all flex items-center justify-center gap-2 font-semibold text-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Stock
                            </button>
                            <button @click="adjustmentType='subtract'" 
                                    :class="adjustmentType === 'subtract' ? 'bg-red-50 border-red-500 text-red-700' : 'bg-gray-50 border-gray-300 text-gray-600 hover:border-gray-400'"
                                    class="p-2.5 border-2 rounded-xl transition-all flex items-center justify-center gap-2 font-semibold text-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                                </svg>
                                Subtract Stock
                            </button>
                        </div>
                    </div>
                    
                    {{-- Search & Filter Section --}}
                    <div class="bg-white rounded-xl border-2 border-gray-200 p-3">
                        <h4 class="text-xs font-black text-gray-900 mb-2 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Search Products
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-2.5 py-1.5 border-2 border-transparent focus-within:border-violet-300 focus-within:bg-white transition-all">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="text" 
                                       x-model="adjustmentSearchQuery" 
                                       placeholder="Search product or batch..." 
                                       class="bg-transparent text-xs outline-none w-full">
                            </div>
                            
                            <select x-model="adjustmentFilterCategory" class="inp text-xs py-1.5">
                                <option value="">All Categories</option>
                                <option value="Tropical Fruit">Tropical Fruit</option>
                                <option value="Citrus">Citrus</option>
                                <option value="Seasonal">Seasonal</option>
                            </select>
                        </div>
                    </div>
                    
                    {{-- Available Products List --}}
                    <div class="bg-white rounded-xl border-2 border-gray-200 overflow-hidden">
                        <div class="bg-gray-50 border-b border-gray-200 px-3 py-2">
                            <h4 class="text-xs font-black text-gray-900 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Select Product Batch
                                <span class="ml-auto text-[10px] font-normal bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded" x-text="filteredAdjustmentStock.length + ' items'"></span>
                            </h4>
                        </div>
                        
                        <div class="p-2 max-h-36 overflow-y-auto space-y-1.5">
                            <template x-if="filteredAdjustmentStock.length === 0">
                                <div class="text-center py-6">
                                    <svg class="w-8 h-8 text-gray-300 mx-auto mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <p class="text-xs text-gray-500">No products found</p>
                                </div>
                            </template>
                            
                            <template x-for="batch in filteredAdjustmentStock" :key="batch.batchId">
                                <div @click="selectProductForAdjustment(batch)"
                                     :class="selectedProductForAdjustment?.batchId === batch.batchId ? 'border-violet-500 bg-violet-50' : 'border-gray-200 hover:border-violet-300 hover:bg-violet-50'"
                                     class="cursor-pointer p-2 border-2 rounded-lg transition-all group">
                                    <div class="flex items-center gap-2">
                                        <div :class="selectedProductForAdjustment?.batchId === batch.batchId ? 'bg-violet-200' : 'bg-gray-100 group-hover:bg-violet-100'" 
                                             class="w-7 h-7 rounded-lg flex items-center justify-center transition-colors flex-shrink-0">
                                            <svg class="w-3.5 h-3.5" :class="selectedProductForAdjustment?.batchId === batch.batchId ? 'text-violet-700' : 'text-gray-400 group-hover:text-violet-600'" 
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-1.5 mb-0.5">
                                                <p class="font-bold text-gray-900 text-xs truncate" x-text="batch.product"></p>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded font-mono bg-gray-100 text-gray-600" x-text="batch.batchId"></span>
                                            </div>
                                            <div class="flex items-center gap-2 text-[10px] text-gray-500">
                                                <span>Current: <strong x-text="batch.quantity + ' kg'"></strong></span>
                                                <span class="px-1.5 py-0.5 rounded" 
                                                      :class="batch.freshness > 70 ? 'bg-green-100 text-green-700' : (batch.freshness > 40 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')"
                                                      x-text="batch.freshness + '%'"></span>
                                            </div>
                                        </div>
                                        <div x-show="selectedProductForAdjustment?.batchId === batch.batchId" class="flex-shrink-0">
                                            <svg class="w-4 h-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    {{-- Add Quantity Section --}}
                    <div x-show="selectedProductForAdjustment" x-transition class="bg-gradient-to-br from-violet-50 to-purple-50 rounded-xl border-2 border-violet-300 p-3">
                        <h4 class="text-xs font-black text-violet-900 mb-2">Enter Adjustment Quantity</h4>
                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">
                                    <span x-show="adjustmentType === 'add'">Quantity to Add (kg) *</span>
                                    <span x-show="adjustmentType === 'subtract'">Quantity to Subtract (kg) *</span>
                                </label>
                                <input type="number" 
                                       x-model="adjustmentQuantity"
                                       min="0.01"
                                       step="0.01"
                                       placeholder="0.00"
                                       class="inp text-xs py-1.5 font-bold"
                                       :class="adjustmentType === 'add' ? 'border-green-300 focus:border-green-500' : 'border-red-300 focus:border-red-500'"
                                       @keydown.enter="addAdjustmentItem()">
                            </div>
                            <button @click="addAdjustmentItem()"
                                    :disabled="!selectedProductForAdjustment || adjustmentQuantity <= 0"
                                    :class="(!selectedProductForAdjustment || adjustmentQuantity <= 0) ? 'opacity-50 cursor-not-allowed' : ''"
                                    class="btn bg-violet-600 hover:bg-violet-700 text-white text-xs px-3 py-2 h-[34px]">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add
                            </button>
                        </div>
                        <p class="text-[10px] text-violet-700 mt-1.5 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Current stock: <strong x-text="selectedProductForAdjustment?.quantity + ' kg'"></strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Footer --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 py-2.5">
            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-500">
                    <span x-show="adjustmentItems.length === 0">Add products to adjust inventory</span>
                    <span x-show="adjustmentItems.length > 0" class="font-semibold text-gray-700">
                        <span x-text="adjustmentItems.length"></span> item<span x-show="adjustmentItems.length !== 1">s</span> ready to adjust
                    </span>
                </p>
                <div class="flex items-center gap-2">
                    <button @click="resetAdjustmentModal()" class="btn btn-outline text-xs px-3 py-1.5">
                        Cancel
                    </button>
                    <button @click="saveAdjustment()" 
                            :disabled="adjustmentItems.length === 0 || !adjustmentReason"
                            :class="(adjustmentItems.length === 0 || !adjustmentReason) ? 'opacity-50 cursor-not-allowed' : ''"
                            class="btn bg-violet-600 hover:bg-violet-700 text-white text-xs px-3 py-1.5">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Submit Adjustment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
</x-app-layout>
