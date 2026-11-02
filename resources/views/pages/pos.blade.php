{{-- POS PAGE — uses app-layout but overrides the main padding for full-screen feel --}}
<x-app-layout>
<x-slot name="title">Point of Sale — FreshTrack</x-slot>

@push('scripts')
<style>
/* ══ POS-SPECIFIC OVERRIDES ══ */
/* Remove main padding so POS fills the full content area */
main.pos-full { padding: 0 !important; overflow: hidden !important; }

/* Product card */
.fruit-card {
    background:#fff;
    border:1.5px solid rgba(124,58,237,.09);
    border-radius:20px;
    transition:all .22s ease;
    cursor:pointer;
    position:relative;
    overflow:hidden;
}
.fruit-card:hover {
    border-color:rgba(124,58,237,.35);
    box-shadow:0 8px 28px rgba(124,58,237,.14);
    transform:translateY(-3px);
}
.fruit-card.in-cart {
    border-color:#10B981;
    box-shadow:0 4px 18px rgba(16,185,129,.18);
}
.fruit-card .badge-freshness { font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; }
.fruit-card .add-btn {
    width:100%; padding:8px 0; border-radius:12px;
    background:linear-gradient(135deg,#7C3AED,#6D28D9);
    color:#fff; font-size:12.5px; font-weight:700;
    border:none; cursor:pointer;
    transition:all .18s ease;
    box-shadow:0 4px 14px rgba(124,58,237,.3);
}
.fruit-card .add-btn:hover { box-shadow:0 6px 20px rgba(124,58,237,.45); transform:translateY(-1px); }
.fruit-card .add-btn:active { transform:scale(.97); }

/* Ripple effect */
.ripple { position:relative; overflow:hidden; }
.ripple::after {
    content:''; position:absolute; border-radius:50%;
    background:rgba(255,255,255,.45); transform:scale(0);
    animation:rippleAnim .5s linear;
    width:200px; height:200px;
    top:50%; left:50%; margin:-100px;
    pointer-events:none;
}
@keyframes rippleAnim { to { transform:scale(2.5); opacity:0; } }

/* Cart item */
.cart-item {
    background:#FAFAFE;
    border:1px solid rgba(124,58,237,.08);
    border-radius:14px;
    padding:10px 12px;
    transition:all .2s ease;
}
.cart-item:hover { background:#F5F3FF; border-color:rgba(124,58,237,.18); }

/* Qty buttons */
.qty-btn {
    width:28px; height:28px; border-radius:8px; border:none; cursor:pointer;
    font-size:15px; font-weight:700; display:flex; align-items:center; justify-content:center;
    transition:all .15s ease;
}
.qty-btn.minus { background:#FEE2E2; color:#DC2626; }
.qty-btn.minus:hover { background:#DC2626; color:#fff; }
.qty-btn.plus  { background:#D1FAE5; color:#059669; }
.qty-btn.plus:hover  { background:#10B981; color:#fff; }
.qty-btn:active { transform:scale(.9); }

/* Payment method buttons */
.pay-btn {
    flex:1; padding:10px 8px; border-radius:14px; border:1.5px solid #E5E7EB;
    background:#fff; font-size:12px; font-weight:700; color:#6B7280;
    cursor:pointer; transition:all .18s ease;
    display:flex; flex-direction:column; align-items:center; gap:4px;
}
.pay-btn:hover { border-color:#7C3AED; background:#F5F3FF; color:#7C3AED; }
.pay-btn.active { border-color:#7C3AED; background:linear-gradient(135deg,#7C3AED,#6D28D9); color:#fff; box-shadow:0 4px 16px rgba(124,58,237,.35); }

/* Category pill */
.cat-pill {
    padding:7px 16px; border-radius:20px; font-size:12.5px; font-weight:600;
    border:1.5px solid transparent; cursor:pointer; transition:all .18s ease;
    white-space:nowrap;
}
.cat-pill.active { background:linear-gradient(135deg,#7C3AED,#6D28D9); color:#fff; box-shadow:0 4px 14px rgba(124,58,237,.3); }
.cat-pill:not(.active) { background:#fff; border-color:#E5E7EB; color:#6B7280; }
.cat-pill:not(.active):hover { border-color:#7C3AED; color:#7C3AED; background:#F5F3FF; }

/* Checkout btn */
.checkout-btn {
    width:100%; padding:16px; border-radius:16px;
    background:linear-gradient(135deg,#10B981,#059669);
    color:#fff; font-size:16px; font-weight:800;
    border:none; cursor:pointer; letter-spacing:.02em;
    box-shadow:0 6px 24px rgba(16,185,129,.4);
    transition:all .2s ease;
    font-family:'Poppins',sans-serif;
}
.checkout-btn:hover { box-shadow:0 10px 32px rgba(16,185,129,.55); transform:translateY(-2px); }
.checkout-btn:active { transform:scale(.98); }

/* Success modal */
.success-checkmark { animation:scaleIn .5s cubic-bezier(.175,.885,.32,1.275); }
@keyframes scaleIn { from{transform:scale(0) rotate(-15deg);opacity:0} to{transform:scale(1) rotate(0);opacity:1} }
.confetti-pop { animation:confettiPop .6s ease forwards; }
@keyframes confettiPop { 0%{transform:translateY(0);opacity:1} 100%{transform:translateY(-40px);opacity:0} }

/* Slide-in cart */
.cart-slide { animation:slideInRight .3s ease; }
@keyframes slideInRight { from{transform:translateX(20px);opacity:0} to{transform:translateX(0);opacity:1} }

/* Floating alerts */
.alert-float {
    background:#fff; border-radius:16px; padding:12px 14px;
    box-shadow:0 8px 28px rgba(0,0,0,.12); border:1px solid rgba(0,0,0,.06);
    animation:slideUp .4s ease;
}
@keyframes slideUp { from{transform:translateY(16px);opacity:0} to{transform:translateY(0);opacity:1} }

/* POS header */
.pos-header {
    background:#fff; border-bottom:1px solid rgba(124,58,237,.07);
    height:64px; display:flex; align-items:center;
    padding:0 20px; gap:16px; flex-shrink:0;
    box-shadow:0 1px 0 rgba(124,58,237,.05);
}

/* Summary row */
.sum-row { display:flex; align-items:center; justify-content:space-between; padding:7px 0; font-size:13.5px; }
.sum-row.total { font-size:17px; font-weight:800; font-family:'Poppins',sans-serif; border-top:2px solid rgba(124,58,237,.12); margin-top:4px; padding-top:12px; color:#1F2937; }

/* Low stock floating panel */
.ls-panel {
    position:fixed; bottom:24px; left:80px;
    z-index:500; display:flex; flex-direction:column; gap:8px;
    max-width:240px;
}

/* Fruit modal image area */
.fruit-modal-img {
    width:140px; height:140px; border-radius:30px;
    display:flex; align-items:center; justify-content:center;
    font-size:80px; line-height:1;
    margin:0 auto 20px;
    background:linear-gradient(135deg,#F5F3FF,#EDE9FE);
    box-shadow:0 8px 32px rgba(124,58,237,.18);
}

/* Scrollable product area */
.product-scroll { overflow-y:auto; flex:1; }
.cart-scroll { overflow-y:auto; max-height: 120px !important; min-height: 60px; }

/* Input number hide arrows */
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button { -webkit-appearance:none; margin:0; }
input[type=number] { -moz-appearance: textfield; }
</style>
@endpush

{{-- Tell main to go full-screen (no padding) --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const main = document.querySelector('main');
    if(main){ main.classList.add('pos-full'); main.style.padding='0'; main.style.overflow='hidden'; }
});
</script>
@endpush

{{-- ═══════════════════════════════════════════════════════════
     POS WRAPPER  (x-data holds all state)
═══════════════════════════════════════════════════════════ --}}
<div x-data="posApp()" class="flex flex-col h-full bg-[#F5F3FF]" style="height:calc(100vh - 68px)">

    {{-- ══════════════════ POS HEADER ══════════════════ --}}
    <div class="pos-header z-30">
        {{-- Logo + name --}}
        <div class="flex items-center gap-2.5 flex-shrink-0">
            <div class="w-9 h-9 g-violet rounded-xl flex items-center justify-center shadow-lg shadow-violet-300/40 flex-shrink-0">
                <svg class="w-4.5 h-4.5 w-[18px] h-[18px] text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div class="hidden sm:block">
                <p class="font-black text-[13.5px] text-gray-900 leading-tight" style="font-family:Poppins,sans-serif">FreshTrack POS</p>
                <p class="text-[10.5px] text-violet-500 font-medium">Point of Sale Terminal</p>
            </div>
        </div>

        <div class="w-px h-6 bg-gray-200 flex-shrink-0"></div>

        {{-- Search bar --}}
        <div class="flex-1 max-w-xl relative">
            <div class="flex items-center gap-2.5 bg-gray-100 rounded-2xl px-4 py-2.5 border-2 transition-all duration-300"
                 :class="searchFocus ? 'border-violet-400 bg-white shadow-md shadow-violet-100' : 'border-transparent'">
                <svg class="w-4 h-4 flex-shrink-0 transition-colors" :class="searchFocus ? 'text-violet-500' : 'text-gray-400'"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" placeholder="Search fruit..." x-model="search"
                       @focus="searchFocus=true" @blur="searchFocus=false"
                       class="bg-transparent text-[13.5px] text-gray-700 outline-none w-full placeholder-gray-400">
                <button x-show="search" @click="search=''" class="text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Barcode --}}
        <button class="p-2.5 rounded-xl hover:bg-[#F5F3FF] text-gray-400 hover:text-violet-600 transition-all flex-shrink-0" title="Scan Barcode">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12v.01M4 4h4V2M4 20v-4m16-16v4h-4M4 8V4m16 0v4m0 12v-4M12 4H8"/>
            </svg>
        </button>

        {{-- Date/Time --}}
        <div class="hidden lg:flex items-center gap-2 bg-gray-100 rounded-xl px-3 py-2 flex-shrink-0">
            <svg class="w-3.5 h-3.5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-[11.5px] text-gray-600 font-medium" x-text="currentTime"></span>
        </div>

        {{-- Cashier --}}
        <div class="hidden md:flex items-center gap-2 flex-shrink-0">
            <div class="w-8 h-8 g-violet rounded-full flex items-center justify-center text-white text-xs font-black shadow-md shadow-violet-200">JD</div>
            <div class="hidden xl:block">
                <p class="text-[12px] font-semibold text-gray-900 leading-tight">Criss Banawa</p>
                <p class="text-[10.5px] text-violet-500">Cashier</p>
            </div>
        </div>

        {{-- Quick action pills --}}
        <div class="flex items-center gap-1.5 flex-shrink-0 ml-auto">
            <button @click="suspendSale()" title="Suspend Sale"
                    class="hidden xl:flex items-center gap-1.5 text-[11.5px] font-semibold text-gray-500 hover:text-violet-700 bg-gray-100 hover:bg-violet-50 px-3 py-2 rounded-xl transition-all border border-transparent hover:border-violet-200">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Suspend
            </button>
            <button @click="clearCart()" title="Clear Cart"
                    class="hidden xl:flex items-center gap-1.5 text-[11.5px] font-semibold text-gray-500 hover:text-red-600 bg-gray-100 hover:bg-red-50 px-3 py-2 rounded-xl transition-all border border-transparent hover:border-red-200">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Clear
            </button>
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-1.5 text-[11.5px] font-semibold text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-xl transition-all">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Exit
            </a>
        </div>
    </div>

    {{-- ══════════════════ SPLIT LAYOUT ══════════════════ --}}
    <div class="flex flex-1 overflow-hidden">

        {{-- ═══════ LEFT PANEL — FRUIT CATALOG (70%) ═══════ --}}
        <div class="flex flex-col bg-[#F5F3FF]" style="width:70%;border-right:1px solid rgba(124,58,237,.08)">

            {{-- Category + search bar --}}
            <div class="bg-white border-b border-[rgba(124,58,237,.07)] px-5 py-3 flex-shrink-0">
                <div class="flex items-center gap-2 overflow-x-auto pb-0.5" style="scrollbar-width:none">
                    <template x-for="cat in categories" :key="cat">
                        <button @click="activeCategory=cat"
                                :class="activeCategory===cat ? 'active' : ''"
                                class="cat-pill flex-shrink-0" x-text="cat"></button>
                    </template>
                </div>
            </div>

            {{-- Fruit count bar --}}
            <div class="flex items-center justify-between px-5 py-2.5 bg-white border-b border-[rgba(124,58,237,.05)] flex-shrink-0">
                <p class="text-[12.5px] text-gray-500 font-medium">
                    Showing <span class="font-bold text-violet-700" x-text="filteredFruits.length"></span> products
                    <span x-show="search" class="text-gray-400"> for "<span class="text-violet-600 font-semibold" x-text="search"></span>"</span>
                </p>
                <div class="flex items-center gap-2">
                    <button @click="gridCols=2" :class="gridCols===2?'text-violet-600 bg-violet-50':'text-gray-400'" class="p-1.5 rounded-lg transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    </button>
                    <button @click="gridCols=3" :class="gridCols===3?'text-violet-600 bg-violet-50':'text-gray-400'" class="p-1.5 rounded-lg transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                    </button>
                    <button @click="gridCols=4" :class="gridCols===4?'text-violet-600 bg-violet-50':'text-gray-400'" class="p-1.5 rounded-lg transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm8 0a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1V4zM3 12a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm8 0a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z"/></svg>
                    </button>
                </div>
            </div>

            {{-- Product Grid --}}
            <div class="product-scroll p-4">
                {{-- Empty state --}}
                <div x-show="filteredFruits.length===0" class="flex flex-col items-center justify-center h-full py-20">
                    <div class="text-6xl mb-4">🔍</div>
                    <p class="text-gray-500 font-semibold text-[15px]" x-text="loading ? 'Loading products...' : 'No products found'"></p>
                    <p class="text-gray-400 text-[13px] mt-1" x-show="!loading">Try a different search or category</p>
                </div>

                <div :class="`grid gap-3 grid-cols-${gridCols}`" x-show="filteredFruits.length>0">
                    <template x-for="fruit in filteredFruits" :key="fruit.id">
                        <div class="fruit-card p-3.5"
                             :class="isInCart(fruit.id) ? 'in-cart' : ''"
                             @click="openFruitModal(fruit)">
                            {{-- In-cart indicator --}}
                            <div x-show="isInCart(fruit.id)"
                                 class="absolute top-2.5 right-2.5 w-5 h-5 bg-emerald-500 rounded-full flex items-center justify-center z-10 shadow-md">
                                <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            {{-- Spoilage badge --}}
                            <div x-show="fruit.spoilageRisk" class="absolute top-2.5 left-2.5 z-10">
                                <span class="badge badge-amber text-[9px] px-1.5 py-0.5"
                                      x-text="'⚠ '+fruit.spoilageRisk+' Risk'"></span>
                            </div>
                            {{-- Fruit image --}}
                            <div class="flex items-center justify-center mb-3 rounded-2xl"
                                 style="height:90px;background:linear-gradient(135deg,#F5F3FF,#EDE9FE)">
                                <span class="text-6xl leading-none select-none" x-text="fruit.emoji"></span>
                            </div>
                            {{-- Info --}}
                            <p class="font-bold text-gray-900 text-[13px] mb-0.5 truncate" x-text="fruit.name"></p>
                            <p class="font-black text-violet-700 text-[14px]" x-text="fruit.priceLabel"></p>
                            <div class="flex items-center justify-between mt-1 mb-2.5">
                                <span class="text-[11px] text-gray-400 font-medium" x-text="'Stock: '+fruit.stock+(fruit.unit?' '+fruit.unit:'')"></span>
                                <span class="badge-freshness"
                                      :class="fruit.freshness>=90 ? 'bg-green-100 text-green-700' : (fruit.freshness>=75 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-600')"
                                      x-show="fruit.freshness"
                                      x-text="fruit.freshness+'%'"></span>
                            </div>
                            {{-- Add to cart btn --}}
                            <button class="add-btn ripple"
                                    :disabled="fruit.stock===0"
                                    :class="fruit.stock===0 ? 'opacity-40 cursor-not-allowed' : ''"
                                    @click.stop="addToCart(fruit)"
                                    x-text="fruit.stock===0 ? '✕ Out of Stock' : (isInCart(fruit.id) ? '✓ In Cart — Add More' : '+ Add to Cart')">
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ══ BOTTOM SALES SUMMARY WIDGET ══ --}}
            <div class="flex-shrink-0 bg-white border-t border-[rgba(124,58,237,.07)] px-5 py-3">
                <div class="grid grid-cols-4 gap-3">
                    @php
                    $posSummary = [
                        ['M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z','text-violet-500',"Today's Sales",'₱18,450','text-violet-700'],
                        ['M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z','text-green-500','Transactions','24','text-green-700'],
                        ['M5 3l14 9-14 9V3z','text-amber-500','Best Seller','Mango','text-amber-700'],
                        ['M13 7h8m0 0v8m0-8l-8 8-4-4-6 6','text-blue-500','Revenue','₱342,800','text-blue-700'],
                    ];
                    @endphp
                    @foreach($posSummary as [$iconPath,$iconColor,$label,$val,$cls])
                    <div class="flex items-center gap-2.5 bg-gray-50 rounded-xl px-3 py-2.5 border border-gray-100">
                        <svg class="w-5 h-5 flex-shrink-0 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
                        </svg>
                        <div>
                            <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide">{{ $label }}</p>
                            <p class="text-[13px] font-black {{ $cls }} leading-tight">{{ $val }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ═══════ RIGHT PANEL — CART & CHECKOUT (30%) ═══════ --}}
        <div class="flex flex-col bg-white" style="width:30%; height: 100%; max-height: calc(100vh - 68px); overflow: hidden;">

            {{-- Cart header --}}
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-[rgba(124,58,237,.07)] flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 g-violet rounded-xl flex items-center justify-center shadow-md shadow-violet-200 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold text-gray-900 text-[13.5px]" style="font-family:Poppins,sans-serif">Shopping Cart</p>
                        <p class="text-[10.5px] text-gray-400" x-text="cart.length===0 ? 'Empty' : cart.length+' item'+(cart.length>1?'s':'')"></p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="badge badge-violet text-[11px] px-2.5" x-text="'#'+orderNumber"></span>
                    <button @click="clearCart()" x-show="cart.length>0"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all" title="Clear Cart">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Order meta --}}
            <div class="px-5 py-2 bg-[#FAFAFE] border-b border-[rgba(124,58,237,.06)] flex-shrink-0">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="inp-label text-[10px]">Customer Name</label>
                        <input type="text" x-model="customerName" placeholder="Walk-in Customer"
                               class="inp py-2 text-[12.5px]">
                    </div>
                    <div>
                        <label class="inp-label text-[10px]">Payment Method</label>
                        <select x-model="paymentMethod" class="inp py-2 text-[12.5px] font-semibold">
                            <option value="cash">💵 Cash</option>
                            <option value="gcash">📱 GCash</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Cart items + Summary + Cash + Payment - ALL SCROLLABLE --}}
            <div class="flex-1 overflow-y-auto" style="max-height: calc(100vh - 200px);">
                {{-- Cart items --}}
                <div class="px-4 py-2 space-y-2">
                    {{-- Empty cart --}}
                <div x-show="cart.length===0" class="flex flex-col items-center justify-center h-full py-12 text-center">
                    <div class="w-20 h-20 bg-[#F5F3FF] rounded-3xl flex items-center justify-center mb-4 mx-auto">
                        <svg class="w-9 h-9 text-violet-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <p class="text-[13.5px] font-bold text-gray-400">Cart is empty</p>
                    <p class="text-[12px] text-gray-300 mt-1">Click a fruit to add it</p>
                </div>

                {{-- Cart item list --}}
                <template x-for="(item, idx) in cart" :key="item.id">
                    <div class="cart-item cart-slide">
                        <div class="flex items-start gap-2.5">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 text-2xl"
                                 style="background:linear-gradient(135deg,#F5F3FF,#EDE9FE)"
                                 x-text="item.emoji"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-1">
                                    <p class="font-semibold text-gray-800 text-[12.5px] leading-tight truncate" x-text="item.name"></p>
                                    <button @click="removeFromCart(idx)"
                                            class="flex-shrink-0 text-gray-300 hover:text-red-500 transition-colors p-0.5 rounded">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-[11px] text-violet-600 font-semibold" x-text="item.priceLabel"></p>
                                <div class="flex items-center justify-between mt-1.5">
                                    <div class="flex items-center gap-1.5">
                                        <button @click="decreaseQty(idx)" class="qty-btn minus">−</button>
                                        <input type="number" 
                                               x-model.number="item.qty"
                                               @input="updateQty(idx, $event.target.value)"
                                               @blur="validateQty(idx)"
                                               step="0.01"
                                               min="0.01"
                                               class="w-16 text-center font-bold text-[13px] text-gray-800 border border-gray-200 rounded-lg py-1 focus:border-violet-500 focus:outline-none"
                                               style="appearance: textfield;">
                                        <button @click="increaseQty(idx)" class="qty-btn plus">+</button>
                                    </div>
                                    <p class="font-black text-gray-900 text-[13.5px]" x-text="'₱'+formatNum(item.subtotal)"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- ══ CHECKOUT BUTTON - MINIMIZED ══ --}}
            <div class="px-4 py-2 flex-shrink-0 bg-white border-b border-gray-100" x-show="cart.length>0">
                <button @click="completeSale()"
                        :disabled="(paymentMethod==='cash' && cashReceived<total && cashReceived>0) || loading"
                        class="checkout-btn ripple w-full py-2.5"
                        :class="(paymentMethod==='cash' && cashReceived>0 && cashReceived<total) || loading ? 'opacity-50 cursor-not-allowed' : ''">
                    <span class="flex items-center justify-center gap-2 text-[13px] font-bold">
                        <template x-if="!loading">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </template>
                        <template x-if="loading">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <span x-text="loading ? 'Processing...' : 'COMPLETE SALE • ₱'+formatNum(total)"></span>
                    </span>
                </button>
            </div>

            {{-- ══ SUMMARY ══ --}}
            <div class="flex-shrink-0 border-t border-[rgba(124,58,237,.07)] px-4 py-1.5 space-y-0" x-show="cart.length>0">
                <div class="sum-row text-gray-500 text-[11px] py-0.5">
                    <span>Subtotal</span>
                    <span class="font-semibold text-gray-700" x-text="'₱'+formatNum(subtotal)"></span>
                </div>
                <div class="sum-row text-gray-500 text-[11px] py-0.5">
                    <div class="flex items-center gap-2">
                        <span>Discount</span>
                        <button @click="discountModal=true" class="text-[9px] text-violet-600 font-semibold hover:underline">[Edit]</button>
                    </div>
                    <span class="font-semibold text-green-600" x-text="discount>0 ? '-₱'+formatNum(discountAmt) : '—'"></span>
                </div>
                <div class="sum-row text-gray-500 text-[11px] py-0.5">
                    <span>Tax (12%)</span>
                    <span class="font-semibold text-gray-700" x-text="'₱'+formatNum(taxAmt)"></span>
                </div>
                <div class="sum-row total text-[14px] pt-1">
                    <span>Total</span>
                    <span class="text-violet-700" x-text="'₱'+formatNum(total)"></span>
                </div>
            </div>

            {{-- ══ CASH RECEIVED + CHANGE ══ --}}
            <div class="px-4 py-2 flex-shrink-0 border-t border-gray-100" x-show="cart.length>0 && paymentMethod==='cash'">
                <div class="grid grid-cols-2 gap-2 mb-1.5">
                    <div>
                        <label class="inp-label text-[9px]">Cash</label>
                        <input type="number" x-model.number="cashReceived" placeholder="0"
                               class="inp py-1.5 text-[12px] font-bold" min="0">
                    </div>
                    <div>
                        <label class="inp-label text-[9px]">Change</label>
                        <div class="inp py-1.5 text-[12px] font-black"
                             :class="change<0 ? 'text-red-500' : 'text-green-600'"
                             x-text="cashReceived>0 ? (change>=0 ? '₱'+formatNum(change) : 'Low') : '—'"></div>
                    </div>
                </div>
                <div class="flex gap-1 flex-wrap">
                    <template x-for="amt in [100,200,500,1000,total]" :key="amt">
                        <button @click="cashReceived=amt"
                                :class="cashReceived===amt ? 'bg-violet-600 text-white' : 'bg-gray-100 text-gray-600'"
                                class="text-[10px] font-bold px-2 py-1 rounded-lg"
                                x-text="amt===total ? 'Exact' : '₱'+amt"></button>
                    </template>
                </div>
            </div>
            </div>{{-- END SCROLLABLE SECTION --}}

        </div>
    </div>{{-- end split --}}

    {{-- ══════════════════════════════════════════════════════
         MODALS
    ══════════════════════════════════════════════════════ --}}

    {{-- ── FRUIT DETAIL MODAL ── --}}
    <div x-show="fruitModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center modal-bg"
         style="background:rgba(17,24,39,.45)"
         @click.self="fruitModal=false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             @click.stop>
            <template x-if="selectedFruit">
                <div>
                    {{-- Header gradient --}}
                    <div class="relative p-6 text-center" style="background:linear-gradient(135deg,#F5F3FF,#EDE9FE)">
                        <button @click="fruitModal=false" class="absolute top-4 right-4 p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-white/70 transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        <div class="fruit-modal-img">
                            <span x-text="selectedFruit.emoji"></span>
                        </div>
                        <h2 class="font-black text-xl text-gray-900" style="font-family:Poppins,sans-serif" x-text="selectedFruit.name"></h2>
                        <p class="text-2xl font-black text-violet-700 mt-1" x-text="selectedFruit.priceLabel"></p>
                        <div class="flex items-center justify-center gap-2 mt-2 flex-wrap">
                            <span class="badge badge-violet text-[11px]" x-text="selectedFruit.category"></span>
                            <span class="badge text-[11px]"
                                  :class="selectedFruit.freshness>=90 ? 'badge-green' : (selectedFruit.freshness>=75 ? 'badge-amber' : 'badge-red')"
                                  x-text="'Freshness '+selectedFruit.freshness+'%'" x-show="selectedFruit.freshness"></span>
                            <span class="badge badge-amber text-[11px]" x-show="selectedFruit.spoilageRisk"
                                  x-text="'⚠ '+selectedFruit.spoilageRisk+' Spoilage'"></span>
                        </div>
                    </div>
                    {{-- Details --}}
                    <div class="px-6 py-4 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-gray-50 rounded-2xl p-3.5 text-center border border-gray-100">
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wide">Stock</p>
                                <p class="text-[17px] font-black text-gray-800 mt-0.5" x-text="selectedFruit.stock+(selectedFruit.unit?' '+selectedFruit.unit:'')"></p>
                            </div>
                            <div class="bg-gray-50 rounded-2xl p-3.5 text-center border border-gray-100">
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wide">Category</p>
                                <p class="text-[15px] font-black text-gray-800 mt-0.5" x-text="selectedFruit.category"></p>
                            </div>
                        </div>
                        <div class="bg-violet-50 rounded-2xl p-3.5 border border-violet-100">
                            <p class="text-[10.5px] font-bold text-violet-600 uppercase tracking-wide mb-1.5">💡 Storage Recommendation</p>
                            <p class="text-[12.5px] text-violet-800" x-text="selectedFruit.storage"></p>
                        </div>
                        <div x-show="selectedFruit.spoilageRisk" class="bg-amber-50 rounded-2xl p-3.5 border border-amber-100">
                            <p class="text-[10.5px] font-bold text-amber-600 uppercase tracking-wide mb-1">⚠ Spoilage Info</p>
                            <p class="text-[12.5px] text-amber-700" x-text="selectedFruit.spoilageNote"></p>
                        </div>
                    </div>
                    {{-- Add to cart --}}
                    <div class="px-6 pb-6">
                        <button @click="addToCart(selectedFruit); fruitModal=false"
                                :disabled="selectedFruit.stock===0"
                                class="w-full py-3.5 rounded-2xl font-bold text-[14px] transition-all ripple"
                                :class="selectedFruit.stock===0 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'g-violet text-white shadow-lg shadow-violet-300/40 hover:shadow-violet-400/50 hover:-translate-y-0.5'"
                                x-text="selectedFruit.stock===0 ? 'Out of Stock' : (isInCart(selectedFruit.id) ? '✓ Add One More' : '+ Add to Cart')">
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ── CHECKOUT SUCCESS MODAL ── --}}
    <div x-show="successModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center modal-bg"
         style="background:rgba(17,24,39,.5)"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md mx-4 overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-90"
             x-transition:enter-end="opacity-100 scale-100">
            {{-- Success top --}}
            <div class="relative p-8 text-center" style="background:linear-gradient(135deg,#D1FAE5,#A7F3D0)">
                <div class="w-24 h-24 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-4 success-checkmark shadow-2xl shadow-green-300">
                    <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="flex justify-center gap-1 mb-3">
                    <template x-for="e in ['🎉','✨','🥭','💚','⭐']" :key="e">
                        <span class="text-2xl confetti-pop" x-text="e" :style="`animation-delay:${Math.random()*0.4}s`"></span>
                    </template>
                </div>
                <h2 class="font-black text-2xl text-green-800" style="font-family:Poppins,sans-serif">Sale Completed!</h2>
                <p class="text-green-600 text-[13.5px] mt-1">Transaction processed successfully</p>
            </div>
            {{-- Receipt info --}}
            <div class="px-7 py-5 space-y-3">
                <div class="flex justify-between text-[13.5px]">
                    <span class="text-gray-400 font-medium">Transaction No.</span>
                    <span class="font-black text-gray-900" x-text="completedOrder.receiptNo"></span>
                </div>
                <div class="flex justify-between text-[13.5px]">
                    <span class="text-gray-400 font-medium">Customer</span>
                    <span class="font-semibold text-gray-700" x-text="completedOrder.customer||'Walk-in Customer'"></span>
                </div>
                <div class="flex justify-between text-[13.5px]">
                    <span class="text-gray-400 font-medium">Items Sold</span>
                    <span class="font-semibold text-gray-700" x-text="completedOrder.items+' item(s)'"></span>
                </div>
                <div class="flex justify-between text-[13.5px]">
                    <span class="text-gray-400 font-medium">Payment</span>
                    <span class="badge badge-violet text-[11px]" x-text="completedOrder.method"></span>
                </div>
                <div class="flex justify-between items-center border-t border-gray-100 pt-3">
                    <span class="text-[15px] font-bold text-gray-500">Total Paid</span>
                    <span class="text-[22px] font-black text-green-700" x-text="'₱'+formatNum(completedOrder.total)"></span>
                </div>
                <div class="flex justify-between text-[13.5px]" x-show="completedOrder.change>0">
                    <span class="text-gray-400 font-medium">Change</span>
                    <span class="font-bold text-gray-700" x-text="'₱'+formatNum(completedOrder.change)"></span>
                </div>
            </div>
            {{-- Action buttons --}}
            <div class="px-7 pb-7 grid grid-cols-3 gap-2.5">
                <button @click="printReceipt()"
                        class="flex flex-col items-center gap-1.5 py-3 px-2 rounded-2xl border-2 border-gray-200 hover:border-violet-400 hover:bg-violet-50 transition-all">
                    <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span class="text-[11px] font-semibold text-gray-500">Print</span>
                </button>
                <button @click="downloadReceipt()"
                        class="flex flex-col items-center gap-1.5 py-3 px-2 rounded-2xl border-2 border-gray-200 hover:border-violet-400 hover:bg-violet-50 transition-all">
                    <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span class="text-[11px] font-semibold text-gray-500">Download</span>
                </button>
                <button @click="newTransaction()"
                        class="flex flex-col items-center gap-1.5 py-3 px-2 rounded-2xl border-2 border-green-400 bg-green-50 hover:bg-green-100 transition-all">
                    <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <span class="text-[11px] font-bold text-green-700">New Sale</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── DISCOUNT MODAL ── --}}
    <div x-show="discountModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center modal-bg"
         style="background:rgba(17,24,39,.4)"
         @click.self="discountModal=false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xs mx-4 p-6"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             @click.stop>
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 g-violet rounded-2xl flex items-center justify-center shadow-lg shadow-violet-200"><svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg></div>
                <h3 class="font-bold text-gray-900 text-[15px]" style="font-family:Poppins,sans-serif">Apply Discount</h3>
            </div>
            <p class="text-[11.5px] text-gray-400 uppercase font-bold tracking-wide mb-2">Discount %</p>
            <div class="flex gap-2 mb-4 flex-wrap">
                <template x-for="d in [5,10,15,20,25]" :key="d">
                    <button @click="discount=d"
                            :class="discount===d ? 'g-violet text-white shadow-md shadow-violet-200' : 'bg-gray-100 text-gray-600 hover:bg-violet-50 hover:text-violet-700'"
                            class="flex-1 py-2.5 rounded-xl font-bold text-[13px] transition-all min-w-[48px]"
                            x-text="d+'%'"></button>
                </template>
            </div>
            <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-4 py-2.5 mb-5">
                <input type="number" x-model.number="discount" min="0" max="100" placeholder="Custom %"
                       class="bg-transparent flex-1 outline-none text-[13.5px] font-bold text-gray-800">
                <span class="text-gray-400 font-semibold">%</span>
            </div>
            <div x-show="discount>0" class="mb-4 p-3 bg-green-50 rounded-xl border border-green-200">
                <p class="text-[12.5px] text-green-700 font-semibold">
                    Saving <span class="font-black" x-text="'₱'+formatNum(discountAmt)"></span> on ₱<span x-text="formatNum(subtotal)"></span>
                </p>
            </div>
            <div class="flex gap-2.5">
                <button @click="discount=0; discountModal=false" class="flex-1 py-2.5 rounded-xl bg-gray-100 text-gray-600 font-semibold text-[13px] hover:bg-gray-200 transition-all">Remove</button>
                <button @click="discountModal=false" class="flex-2 py-2.5 px-5 rounded-xl g-violet text-white font-bold text-[13px] shadow-md shadow-violet-200 hover:shadow-violet-300 transition-all">Apply</button>
            </div>
        </div>
    </div>

    {{-- ══ LOW STOCK FLOATING ALERTS ══ --}}
    <div class="ls-panel" x-show="showLowStockAlerts">
        <div class="alert-float">
            <div class="flex items-start gap-2.5">
                <span class="text-xl">⚠️</span>
                <div>
                    <p class="text-[11.5px] font-bold text-amber-800">Low Stock Alert</p>
                    <p class="text-[12px] text-gray-700 font-semibold">Pomelo</p>
                    <p class="text-[11px] text-gray-500">Remaining: <strong>8 kg</strong></p>
                </div>
                <button @click="showLowStockAlerts=false" class="text-gray-300 hover:text-gray-500 ml-auto">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        <div class="alert-float">
            <div class="flex items-start gap-2.5">
                <span class="text-xl">🔴</span>
                <div>
                    <p class="text-[11.5px] font-bold text-red-700">High Spoilage Risk</p>
                    <p class="text-[12px] text-gray-700 font-semibold">Pineapple</p>
                    <p class="text-[11px] text-gray-500">Priority: <strong class="text-red-600">Sell Today</strong></p>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ AI SMART RECOMMENDATIONS PANEL ══ --}}
    <div class="fixed bottom-24 right-4 z-40 w-72 space-y-2" x-show="showAiRecs" x-cloak>
        <div class="bg-white rounded-2xl shadow-xl border border-violet-100 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-violet-50" style="background:linear-gradient(135deg,#F5F3FF,#EDE9FE)">
                <div class="flex items-center gap-2">
                    <span class="text-lg">🧠</span>
                    <p class="font-bold text-[13px] text-violet-800" style="font-family:Poppins,sans-serif">AI Recommendations</p>
                </div>
                <button @click="showAiRecs=false" class="text-violet-400 hover:text-violet-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-3 space-y-2">
                @foreach([
                    ['🥭','Recommend selling Mango first.','Batch MNG-002 expires in 2 days.','badge-amber'],
                    ['🍍','Apply 10% discount on Pineapple.','Demand 20% below forecast.','badge-green'],
                    ['🍈','Restock Pomelo tomorrow.','Only 8 kg remaining, demand is 20/day.','badge-violet'],
                    ['🍌','Banana demand up 15% this week.','Festival season boost expected.','badge-blue'],
                ] as [$e,$rec,$note,$b])
                <div class="flex items-start gap-2.5 p-2.5 bg-gray-50 rounded-xl hover:bg-violet-50 cursor-pointer transition-colors border border-transparent hover:border-violet-100">
                    <span class="text-xl flex-shrink-0">{{ $e }}</span>
                    <div>
                        <p class="text-[12px] font-bold text-gray-800">{{ $rec }}</p>
                        <p class="text-[11px] text-gray-500 mt-0.5">{{ $note }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- AI toggle button --}}
    <button @click="showAiRecs=!showAiRecs"
            class="fixed bottom-6 right-4 z-40 w-12 h-12 g-violet rounded-2xl flex items-center justify-center shadow-xl shadow-violet-300/50 hover:shadow-violet-400/60 transition-all hover:-translate-y-0.5 active:scale-95"
            title="AI Recommendations">
        <span class="text-xl" x-text="showAiRecs ? '✕' : '🧠'"></span>
    </button>

    {{-- ══ RECENT TRANSACTIONS STRIP ══ --}}
    <div class="fixed top-20 right-4 z-30" x-show="showRecentTxn">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden w-80">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                <p class="font-bold text-[13px] text-gray-900" style="font-family:Poppins,sans-serif">Recent Transactions</p>
                <button @click="showRecentTxn=false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="divide-y divide-gray-50 max-h-72 overflow-y-auto">
                @foreach([
                    ['#POS-8841','Maria Santos','3 items','₱1,240','Cash','10:32 AM','badge-green'],
                    ['#POS-8840','Walk-in','5 items','₱2,150','GCash','10:15 AM','badge-green'],
                    ['#POS-8839','Pedro Reyes','2 items','₱680','Maya','09:50 AM','badge-green'],
                    ['#POS-8838','Walk-in','7 items','₱3,420','Card','09:20 AM','badge-green'],
                    ['#POS-8837','Ana Lim','1 item','₱250','Cash','08:55 AM','badge-amber'],
                ] as [$txn,$cust,$items,$amt,$method,$time,$b])
                <div class="flex items-center justify-between px-4 py-2.5 hover:bg-gray-50 transition-colors">
                    <div>
                        <p class="text-[11.5px] font-bold text-gray-800">{{ $txn }}</p>
                        <p class="text-[10.5px] text-gray-400">{{ $cust }} · {{ $items }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[12px] font-black text-gray-900">{{ $amt }}</p>
                        <p class="text-[10px] text-gray-400">{{ $method }} · {{ $time }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>{{-- end posApp --}}

@push('scripts')
<script>
function posApp() {
    return {
        /* ── State ── */
        search: '',
        searchFocus: false,
        activeCategory: 'All',
        gridCols: 3,
        cart: [],
        customerName: '',
        paymentMethod: 'cash',
        cashReceived: 0,
        discount: 0,
        fruitModal: false,
        selectedFruit: null,
        successModal: false,
        discountModal: false,
        showLowStockAlerts: true,
        showAiRecs: false,
        showRecentTxn: false,
        completedOrder: {},
        orderNumber: Date.now(),
        currentTime: '',
        currentDate: '',
        loading: false,
        error: null,

        /* ── Data (loaded from API) ── */
        categories: ['All'],
        fruits: [],
        rawProducts: [],

        paymentMethods: [
            { key:'cash',   icon:'💵', label:'Cash' },
            { key:'gcash',  icon:'📱', label:'GCash' },
        ],

        /* ── Product emoji mapping ── */
        productEmoji: {
            'Mango': '🥭',
            'Banana': '🍌',
            'Pomelo': '🍈',
            'Pineapple': '🍍',
            'Durian': '🍊',
            'Mangosteen': '🍇',
            'Lanzones': '🍋',
            'Rambutan': '🫐',
            'Strawberry': '🍓',
            'Papaya': '🍑',
            'Kiwi': '🥝',
            'Watermelon': '🍉',
            'Orange': '🍊',
            'Apple': '🍎',
            'Grapes': '🍇',
            'Avocado': '🥑',
            'Lemon': '🍋',
            'Coconut': '🥥',
            'Peach': '🍑',
            'Cherry': '🍒'
        },

        /* ── Computed getters ── */
        get filteredFruits() {
            return this.fruits.filter(f => {
                const matchSearch = !this.search || f.name.toLowerCase().includes(this.search.toLowerCase());
                const matchCat    = this.activeCategory === 'All' || f.category === this.activeCategory;
                return matchSearch && matchCat;
            });
        },
        get subtotal() {
            return this.cart.reduce((s, i) => s + i.subtotal, 0);
        },
        get discountAmt() {
            return Math.round(this.subtotal * this.discount / 100 * 100) / 100;
        },
        get taxAmt() {
            return Math.round((this.subtotal - this.discountAmt) * 0.12 * 100) / 100;
        },
        get total() {
            return Math.round((this.subtotal - this.discountAmt + this.taxAmt) * 100) / 100;
        },
        get change() {
            return Math.round((this.cashReceived - this.total) * 100) / 100;
        },

        /* ── Methods ── */
        async init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);
            await this.loadProducts();
        },

        async loadProducts() {
            this.loading = true;
            this.error = null;
            try {
                const response = await fetch('/api/pos/products');
                if (!response.ok) throw new Error('Failed to load products');
                const data = await response.json();
                
                this.rawProducts = data;
                
                // Extract categories
                const cats = new Set(['All']);
                data.forEach(p => {
                    if (p.category) cats.add(p.category);
                });
                this.categories = Array.from(cats);
                
                // Transform products to fruit format
                this.fruits = data.map(product => {
                    const firstBatch = product.batches && product.batches[0];
                    const emoji = this.productEmoji[product.name] || '🍎';
                    const price = firstBatch ? parseFloat(firstBatch.price_per_unit) : 0;
                    const stock = product.available_stock || 0;
                    
                    // Calculate freshness based on days to expiry
                    let freshness = 95;
                    let spoilageRisk = null;
                    let spoilageNote = '';
                    
                    if (firstBatch && firstBatch.expiry_date) {
                        const expiryDate = new Date(firstBatch.expiry_date);
                        const today = new Date();
                        const daysToExpiry = Math.floor((expiryDate - today) / (1000 * 60 * 60 * 24));
                        
                        if (daysToExpiry <= 2) {
                            freshness = 70;
                            spoilageRisk = 'High';
                            spoilageNote = `Expires in ${daysToExpiry} day(s). Sell ASAP or discount.`;
                        } else if (daysToExpiry <= 5) {
                            freshness = 82;
                            spoilageRisk = 'Medium';
                            spoilageNote = `Expires in ${daysToExpiry} days. Monitor closely.`;
                        } else if (daysToExpiry <= 10) {
                            freshness = 90;
                        }
                    }
                    
                    return {
                        id: product.id,
                        emoji: emoji,
                        name: product.name,
                        price: price,
                        priceLabel: `₱${price.toFixed(2)}/${product.unit || 'unit'}`,
                        stock: stock,
                        unit: product.unit || 'unit',
                        freshness: freshness,
                        spoilageRisk: spoilageRisk,
                        category: product.category || 'Uncategorized',
                        storage: 'Store in cool, dry place. Refrigerate when ripe.',
                        spoilageNote: spoilageNote,
                        batches: product.batches || [],
                        batch_id: firstBatch ? firstBatch.id : null,
                        batch_code: firstBatch ? firstBatch.batch_code : null
                    };
                });
                
            } catch (err) {
                console.error('Error loading products:', err);
                this.error = err.message;
                alert('Failed to load products: ' + err.message);
            } finally {
                this.loading = false;
            }
        },

        updateClock() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString('en-PH', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
            this.currentDate = now.toLocaleDateString('en-PH', { month:'long', day:'numeric', year:'numeric' });
        },

        formatNum(n) {
            if (!n && n !== 0) return '0.00';
            return Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        isInCart(id) {
            return this.cart.some(i => i.id === id);
        },

        addToCart(fruit) {
            if (fruit.stock <= 0) {
                alert(`${fruit.name} is out of stock!`);
                return;
            }
            
            const existing = this.cart.find(i => i.id === fruit.id);
            if (existing) {
                if (existing.qty < fruit.stock) {
                    existing.qty++;
                    existing.subtotal = Math.round(existing.qty * existing.price * 100) / 100;
                } else {
                    alert(`Maximum stock available: ${fruit.stock} ${fruit.unit}`);
                }
            } else {
                this.cart.push({ 
                    ...fruit, 
                    qty: 1, 
                    subtotal: fruit.price 
                });
            }
        },

        removeFromCart(idx) {
            this.cart.splice(idx, 1);
        },

        increaseQty(idx) {
            const item = this.cart[idx];
            const fruit = this.fruits.find(f => f.id === item.id);
            const newQty = Math.round((item.qty + 0.5) * 100) / 100;
            
            if (newQty <= fruit.stock) {
                item.qty = newQty;
                item.subtotal = Math.round(item.qty * item.price * 100) / 100;
            } else {
                alert(`Maximum stock available: ${fruit.stock} ${fruit.unit}`);
            }
        },

        decreaseQty(idx) {
            const item = this.cart[idx];
            if (item.qty > 0.5) {
                item.qty = Math.round((item.qty - 0.5) * 100) / 100;
                item.subtotal = Math.round(item.qty * item.price * 100) / 100;
            } else {
                this.removeFromCart(idx);
            }
        },

        updateQty(idx, value) {
            const item = this.cart[idx];
            const fruit = this.fruits.find(f => f.id === item.id);
            let qty = parseFloat(value) || 0;
            
            // Round to 2 decimal places
            qty = Math.round(qty * 100) / 100;
            
            if (qty <= 0) {
                qty = 0.01; // Minimum quantity
            }
            
            if (qty > fruit.stock) {
                qty = fruit.stock;
                alert(`Maximum stock available: ${fruit.stock} ${fruit.unit}`);
            }
            
            item.qty = qty;
            item.subtotal = Math.round(qty * item.price * 100) / 100;
        },

        validateQty(idx) {
            const item = this.cart[idx];
            
            if (!item.qty || item.qty <= 0) {
                if (confirm('Remove this item from cart?')) {
                    this.removeFromCart(idx);
                } else {
                    item.qty = 1;
                    item.subtotal = Math.round(item.qty * item.price * 100) / 100;
                }
            }
        },

        clearCart() {
            if (this.cart.length === 0) return;
            if (confirm('Clear all items from cart?')) {
                this.cart = [];
                this.discount = 0;
                this.cashReceived = 0;
                this.customerName = '';
            }
        },

        openFruitModal(fruit) {
            this.selectedFruit = fruit;
            this.fruitModal = true;
        },

        applyDiscount() {
            this.discountModal = true;
        },

        async completeSale() {
            if (this.cart.length === 0) {
                alert('Cart is empty!');
                return;
            }

            // Validate cash payment
            if (this.paymentMethod === 'cash' && this.cashReceived > 0 && this.cashReceived < this.total) {
                alert('Insufficient cash received!');
                return;
            }

            this.loading = true;
            try {
                // Prepare sale data
                const saleData = {
                    items: this.cart.map(item => ({
                        product_id: item.id,
                        batch_id: item.batch_id,
                        quantity: item.qty,
                        price: item.price
                    })),
                    payment_method: this.paymentMethod,
                    discount_amount: this.discountAmt,
                    notes: this.customerName ? `Customer: ${this.customerName}` : null
                };

                // Submit sale to backend
                const response = await fetch('/api/pos/sale', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(saleData)
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to complete sale');
                }

                // Show success modal
                this.completedOrder = {
                    receiptNo: result.data.transaction_code,
                    customer: this.customerName || 'Walk-in Customer',
                    items: this.cart.reduce((s, i) => s + i.qty, 0),
                    total: result.data.total_amount,
                    method: this.paymentMethods.find(p => p.key === this.paymentMethod)?.label || 'Cash',
                    change: this.change > 0 ? this.change : 0,
                };
                
                this.successModal = true;

                // Reload products to update stock
                await this.loadProducts();

            } catch (err) {
                console.error('Error completing sale:', err);
                alert('Failed to complete sale: ' + err.message);
            } finally {
                this.loading = false;
            }
        },

        newTransaction() {
            this.successModal = false;
            this.cart = [];
            this.discount = 0;
            this.cashReceived = 0;
            this.customerName = '';
            this.paymentMethod = 'cash';
            this.orderNumber = Date.now();
        },

        suspendSale() {
            if (this.cart.length === 0) {
                alert('Cart is empty!');
                return;
            }
            alert('Sale suspended. Order #' + this.orderNumber + ' saved (feature coming soon).');
        },

        voidTransaction() {
            if (this.cart.length === 0) return;
            if (confirm('Void this transaction? All items will be removed from cart.')) {
                this.cart = [];
                this.discount = 0;
                this.cashReceived = 0;
                this.customerName = '';
            }
        },

        priceCheck() {
            const name = prompt('Enter product name to check price:');
            if (!name) return;
            const fruit = this.fruits.find(f => f.name.toLowerCase().includes(name.toLowerCase()));
            if (fruit) {
                alert(fruit.name + ' — ' + fruit.priceLabel + '\nStock: ' + fruit.stock + ' ' + fruit.unit);
            } else {
                alert('Product not found.');
            }
        },

        printReceipt() {
            alert('Sending to printer… Receipt ' + this.completedOrder.receiptNo);
        },

        downloadReceipt() {
            alert('Receipt downloaded as PDF — ' + this.completedOrder.receiptNo);
        },
    };
}
</script>
@endpush

</x-app-layout>
