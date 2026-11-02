<x-app-layout>
<x-slot name="title">Notifications — FreshTrack</x-slot>

<div x-data="notifCenter(@js(['filter' => $filter, 'sort' => $sort, 'q' => $search]))" x-init="init()">

{{-- HEADER MATCHING DASHBOARD STYLE --}}
<div class="mb-6 fade-up">
    <div class="flex items-center justify-between mb-2">
        <h1 class="text-[32px] font-black text-gray-900">Notification Center</h1>
        <div class="flex items-center gap-3">
            <button @click="markAllRead()" 
                    class="btn btn-outline btn-sm hover:border-violet-600 hover:text-violet-600">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Mark All Read
            </button>
            <button @click="clearAll()" 
                    class="btn btn-sm"
                    style="background: #fff; border: 1.5px solid #E5E7EB; color: #EF4444;">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Clear All
            </button>
        </div>
    </div>
    <p class="text-[14px] text-gray-500">AI-powered alerts from SARIMAX, XGBoost, Weather API, and system monitoring</p>
</div>

{{-- STATS CARDS MATCHING DASHBOARD KPI STYLE --}}
<div class="grid grid-cols-5 gap-4 mb-6 fade-up delay-1">
    @php
    $stats = [
        [$counts['total'], 'Total Notifications', 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
        [$counts['critical'],  'Critical', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
        [$counts['high'],  'High Priority', 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        [$counts['unread'],  'Unread', 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
        [$counts['today'], 'Today', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];
    @endphp
    @foreach($stats as [$count, $label, $icon])
    <div class="card shimmer card-lift p-5">
        <div class="flex items-start justify-between mb-3">
            <div class="icon-ring">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                </svg>
            </div>
        </div>
        <p class="text-[26px] font-black text-gray-900 leading-none mb-1">{{ $count }}</p>
        <p class="text-[12.5px] font-semibold text-gray-600">{{ $label }}</p>
    </div>
    @endforeach
</div>

{{-- TAB FILTERS MATCHING DASHBOARD STYLE --}}
<div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200 fade-up delay-2">
    <div class="flex items-center gap-2">
        @php
        $tabs = [
            ['all', 'All', $counts['total']],
            ['forecast', 'Forecast', $moduleCounts['forecast'] ?? 0],
            ['spoilage', 'Spoilage', $moduleCounts['spoilage'] ?? 0],
            ['weather', 'Weather', $moduleCounts['weather'] ?? 0],
            ['inventory', 'Inventory', $moduleCounts['inventory'] ?? 0],
            ['sales', 'Sales', $moduleCounts['sales'] ?? 0],
        ];
        @endphp
        @foreach($tabs as [$key, $label, $count])
        <button @click="setFilter('{{ $key }}')"
                :class="activeFilter === '{{ $key }}' ? 'btn-violet text-white' : 'btn-outline text-gray-600'"
                class="btn btn-sm px-4">
            {{ $label }} <span class="opacity-60">({{ $count }})</span>
        </button>
        @endforeach
    </div>
    
    <div class="flex items-center gap-3">
        <div class="relative">
            <input type="text" 
                   x-ref="search"
                   x-model="searchQuery"
                   @input.debounce.500ms="filterNotifications()"
                   placeholder="Search notifications..."
                   class="inp text-[13px] py-2 pl-10 pr-4 w-64">
            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <select x-model="sortBy" @change="sortNotifications()" 
                class="inp text-[13px] py-2 px-3 w-40">
            <option value="recent">Most Recent</option>
            <option value="priority">Priority</option>
            <option value="unread">Unread First</option>
        </select>
    </div>
</div>

{{-- NOTIFICATION CARDS MATCHING DASHBOARD CARD STYLE --}}
<div class="space-y-4 fade-up delay-3">


@foreach($notifications as $n)
<div x-show="activeFilter === 'all' || activeFilter === '{{ strtolower($n->module) }}'"
     x-transition
     class="card shimmer card-lift"
     :class="dismissed.includes({{ $n->id }}) ? 'hidden' : ''">
    <div class="p-5">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <span class="badge badge-violet text-[10px] uppercase tracking-wide font-bold">
                    {{ $n->severity }}
                </span>
                @if(!$n->is_read)
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 bg-violet-600 rounded-full pulse-dot"></span>
                    <span class="text-[10px] font-bold text-violet-600">NEW</span>
                </span>
                @endif
                <span class="text-[11px] text-gray-400 font-medium">{{ $n->subtitle }}</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[12px] text-gray-400 font-medium flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $n->created_at->diffForHumans(null, \Carbon\CarbonInterface::DIFF_ABSOLUTE) }} ago
                </span>
                <button @click.stop="dismiss({{ $n->id }})" 
                        class="text-gray-300 hover:text-red-500 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <h3 class="text-[17px] font-black text-gray-900 mb-2">{{ $n->title }}</h3>
        <p class="text-[14px] text-gray-600 leading-relaxed mb-4">{{ $n->message }}</p>

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ url($n->action_link ?? '/notifications') }}" class="btn btn-violet btn-sm">
                    {{ $n->action_label ?? 'View Details' }}
                </a>
                @if(!$n->is_read)
                <button @click.stop="markRead({{ $n->id }})" 
                        class="btn btn-outline btn-sm">
                    Mark as Read
                </button>
                @endif
            </div>
            
            @if($n->is_read)
            <span class="flex items-center gap-2 text-[12px] text-gray-400 font-semibold">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Read
            </span>
            @endif
        </div>
    </div>
</div>
@endforeach

{{-- EMPTY STATE --}}
<div x-show="dismissed.length >= {{ $notifications->count() }}" 
     x-transition
     class="card text-center py-16">
    <div class="w-16 h-16 bg-violet-50 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <p class="text-[18px] font-bold text-gray-900 mb-1">All Caught Up!</p>
    <p class="text-[14px] text-gray-500 mb-5">No notifications to display</p>
    <button @click="restoreAll()" class="btn btn-violet btn-sm">
        Restore Notifications
    </button>
</div>

</div>

{{-- PAGINATION --}}
<div class="flex items-center justify-between mt-7 pt-5 border-t border-gray-200 fade-up delay-4">
    <p class="text-[13px] text-gray-500">
        Showing <span class="font-bold text-gray-900">{{ $notifications->firstItem() ?? 0 }}–{{ $notifications->lastItem() ?? 0 }}</span> of <span class="font-bold text-gray-900">{{ $notifications->total() }}</span> notifications
    </p>
    <div class="flex items-center gap-2">
        <button @click="goToPage({{ $notifications->currentPage() - 1 }})" @disabled($notifications->onFirstPage()) class="btn btn-outline btn-sm w-9 h-9 p-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        @for($page = 1; $page <= max($notifications->lastPage(), 1); $page++)
        <button @click="goToPage({{ $page }})" class="btn {{ $page === $notifications->currentPage() ? 'btn-violet' : 'btn-outline' }} btn-sm w-9 h-9 p-0">{{ $page }}</button>
        @endfor
        <button @click="goToPage({{ $notifications->currentPage() + 1 }})" @disabled(!$notifications->hasMorePages()) class="btn btn-outline btn-sm w-9 h-9 p-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>
</div>

</div>

@push('scripts')
<script>
function notifCenter(state) {
    return {
        activeFilter: state.filter,
        dismissed: [],
        sortBy: state.sort,
        searchQuery: state.q,

        init() {
            if (this.searchQuery) {
                const el = this.$refs.search;
                el.focus();
                el.setSelectionRange(el.value.length, el.value.length);
            }
        },

        async post(url) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            return res.ok;
        },

        navigate(params) {
            const url = new URL(window.location.href);
            Object.entries(params).forEach(([key, value]) => {
                if (value === null || value === '' || value === 'all' || (key === 'sort' && value === 'recent')) {
                    url.searchParams.delete(key);
                } else {
                    url.searchParams.set(key, value);
                }
            });
            window.location.href = url.toString();
        },

        setFilter(key) {
            this.navigate({ filter: key, page: null });
        },

        goToPage(page) {
            this.navigate({ page: page > 1 ? page : null });
        },

        async markAllRead() {
            if (await this.post('/api/notifications/read-all')) {
                alert('✓ All notifications marked as read');
                window.location.reload();
            }
        },

        async clearAll() {
            if (confirm('Clear all notifications? This action cannot be undone.')) {
                if (await this.post('/api/notifications/dismiss-all')) {
                    window.location.reload();
                }
            }
        },

        async markRead(id) {
            if (await this.post('/api/notifications/' + id + '/read')) {
                window.location.reload();
            }
        },

        async dismiss(id) {
            this.dismissed.push(id);
            if (await this.post('/api/notifications/' + id + '/dismiss')) {
                window.location.reload();
            } else {
                this.dismissed = this.dismissed.filter(d => d !== id);
            }
        },

        async restoreAll() {
            if (await this.post('/api/notifications/restore')) {
                window.location.reload();
            }
        },

        sortNotifications() {
            this.navigate({ sort: this.sortBy, page: null });
        },

        filterNotifications() {
            this.navigate({ q: this.searchQuery.trim(), page: null });
        }
    }
}
</script>
@endpush
</x-app-layout>
