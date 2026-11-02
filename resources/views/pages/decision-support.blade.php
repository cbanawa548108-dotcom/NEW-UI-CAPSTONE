<x-app-layout>
<x-slot name="title">Decision Support — FreshTrack</x-slot>

{{-- ── PAGE HEADER ── --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-7 fade-up">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 bg-gray-100 border border-gray-200 rounded-2xl flex items-center justify-center">
            <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
        </div>
        <div>
            <h1 class="text-[26px] font-black text-gray-900">Decision Support Dashboard</h1>
            <p class="text-[13.5px] text-gray-500 mt-0.5">AI-powered business intelligence and actionable recommendations</p>
        </div>
    </div>
    <span class="flex items-center gap-2 badge badge-violet px-4 py-2.5 text-[12.5px]">
        <span class="w-2 h-2 bg-violet-500 rounded-full pulse-dot"></span>
        8 Active Recommendations
    </span>
</div>

{{-- ── ACTION CARDS ── --}}
@php
$actionCards = [
    [
        'badgeClass' => 'badge-red',
        'urgency'    => 'Urgent',
        'title'      => 'Restock Now',
        'cardIcon'   => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'body'       => 'Pomelo stock at 8&nbsp;kg — critically low. Predicted demand: 20&nbsp;kg/day. Stock runs out in &lt;12 hours.',
        'progLabel'  => 'Stock Level',
        'progVal'    => '16%',
        'progPct'    => 16,
        'kvLabel'    => 'Recommended Order',
        'kvVal'      => '50 kg',
        'btn'        => 'Order Now',
    ],
    [
        'badgeClass' => 'badge-orange',
        'urgency'    => 'High Priority',
        'title'      => 'Sell Immediately',
        'cardIcon'   => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        'body'       => 'Mango batch MNG-002 has 78% spoilage risk. 155&nbsp;kg at risk — potential loss: &#8369;18,290 if not sold in 24h.',
        'progLabel'  => 'Spoilage Risk',
        'progVal'    => '78%',
        'progPct'    => 78,
        'kvLabel'    => 'Suggested Action',
        'kvVal'      => '-20%',
        'btn'        => 'Review Forecast',
    ],
    [
        'badgeClass' => 'badge-blue',
        'urgency'    => 'Revenue',
        'title'      => 'Review Forecast',
        'cardIcon'   => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
        'body'       => 'Pineapple demand is 20% below forecast. Review the forecast and adjust actions to clear 118&nbsp;kg.',
        'progLabel'  => 'Demand Gap',
        'progVal'    => '-20%',
        'progPct'    => 20,
        'kvLabel'    => 'Expected Revenue Boost',
        'kvVal'      => '+&#8369;2,800',
        'btn'        => 'Apply Now',
    ],
];
@endphp
<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-7 fade-up delay-1">
    @foreach($actionCards as $card)
    <div class="card p-6 card-lift">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="icon-ring">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['cardIcon'] }}"/>
                    </svg>
                </div>
                <div>
                    <span class="badge {{ $card['badgeClass'] }} text-[10px]">{{ $card['urgency'] }}</span>
                    <h3 class="font-bold text-gray-900 text-[15px] mt-1">{{ $card['title'] }}</h3>
                </div>
            </div>
        </div>
        <p class="text-[13px] text-gray-500 leading-relaxed mb-4">{!! $card['body'] !!}</p>
        <div class="bg-gray-50 rounded-2xl p-3.5 mb-4 border border-gray-100">
            <div class="flex items-center justify-between text-[12px] mb-2">
                <span class="font-semibold text-gray-600">{{ $card['progLabel'] }}</span>
                <span class="font-black text-gray-900">{{ $card['progVal'] }}</span>
            </div>
            <div class="progress-bar">
                <div class="bg-violet-500 h-full rounded-full" style="width:{{ $card['progPct'] }}%"></div>
            </div>
        </div>
        <div class="flex items-center justify-between mb-5">
            <span class="text-[12.5px] text-gray-400">{{ $card['kvLabel'] }}</span>
            <span class="font-black text-gray-900 text-[15px]">{!! $card['kvVal'] !!}</span>
        </div>
        <div class="flex gap-2">
            <button class="btn btn-violet btn-md flex-1 text-[12.5px]">{{ $card['btn'] }}</button>
            <button class="btn btn-outline btn-md flex-1 text-[12.5px]">Dismiss</button>
        </div>
    </div>
    @endforeach
</div>

{{-- ── PROFIT BANNER ── --}}
<div class="rounded-3xl p-7 mb-7 text-white overflow-hidden relative fade-up delay-2"
     style="background:linear-gradient(135deg,#7C3AED 0%,#6D28D9 50%,#059669 100%)">
    <div class="absolute inset-0 opacity-10"
         style="background-image:radial-gradient(circle,rgba(255,255,255,.6) 1px,transparent 1px);background-size:28px 28px"></div>
    <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
        <div>
            <p class="text-white/70 text-[13px] font-medium">If all AI recommendations are followed today</p>
            <h2 class="text-3xl font-black mt-1">Expected Profit Increase</h2>
            <p class="text-white/60 text-[13px] mt-1">Based on current inventory, pricing, and forecast data</p>
        </div>
        <div class="text-right">
            <p class="text-[52px] font-black text-green-300 leading-none">+&#8369;24,850</p>
            <p class="text-white/60 text-[13px] mt-1">+47.5% above current projection</p>
        </div>
    </div>
    <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-white/20">
        @php
        $bannerStats = [
            ['M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z','Spoilage Prevention','&#8369;18,290'],
            ['M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z','Expected Revenue Impact','&#8369;2,800'],
            ['M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4','Restock Profit','&#8369;3,760'],
        ];
        @endphp
        @foreach($bannerStats as [$iconPath,$label,$val])
        <div class="text-center bg-white/10 rounded-2xl p-4 backdrop-blur-sm">
            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
                </svg>
            </div>
            <p class="text-[22px] font-black text-green-300 mt-1">{!! $val !!}</p>
            <p class="text-white/60 text-[11.5px] mt-0.5">{{ $label }}</p>
        </div>
        @endforeach
    </div>
</div>

{{-- ── CHARTS ── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-7 fade-up delay-3">
    <div class="card p-6">
        <h3 class="font-bold text-gray-900 text-[15px] mb-1">Priority Action Matrix</h3>
        <p class="text-[12px] text-gray-400 mb-4">Urgency vs Impact — all 8 recommendations</p>
        <canvas id="matrixChart" height="200"></canvas>
    </div>
    <div class="card p-6">
        <h3 class="font-bold text-gray-900 text-[15px] mb-1">Revenue Optimization</h3>
        <p class="text-[12px] text-gray-400 mb-4">Before vs after applying AI recommendations</p>
        <canvas id="revenueOptChart" height="200"></canvas>
    </div>
</div>

{{-- ── INSIGHTS ── --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-5 fade-up delay-4">
    <div class="card p-6">
        <div class="flex items-center gap-2 mb-4">
            <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <h3 class="font-bold text-gray-900 text-[15px]">Inventory Optimization Insights</h3>
        </div>
        <div class="space-y-3">
            @php
            $invInsights = [
                ['Mango',     'Overstocked 20%',          'Reduce next order',        'neutral'],
                ['Durian',    'Optimal level',             'Maintain order size',      'neutral'],
                ['Pomelo',    'Critical shortage',         'Order 50 kg now',          'alert'],
                ['Lanzones',  'Slightly understocked',     'Increase order 15%',       'neutral'],
                ['Pineapple', 'Overstocked, low demand',  'Offer + reduce order',  'neutral'],
            ];
            $insightMap = [
                'neutral' => ['bg-gray-50', 'border-gray-100', 'text-gray-800', 'text-gray-500'],
                'alert'   => ['bg-gray-50', 'border-gray-200', 'text-gray-900', 'text-gray-500'],
            ];
            @endphp
            @foreach($invInsights as [$name,$status,$rec,$color])
            @php $m = $insightMap[$color]; @endphp
            <div class="flex items-center gap-3 p-3.5 {{ $m[0] }} rounded-2xl border {{ $m[1] }}">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <p class="text-[13px] font-bold {{ $m[2] }}">{{ $name }}</p>
                        <span class="text-[11.5px] font-semibold {{ $m[3] }}">{{ $status }}</span>
                    </div>
                    <p class="text-[12px] {{ $m[3] }} mt-0.5">{{ $rec }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="card p-6">
        <div class="flex items-center gap-2 mb-4">
            <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="font-bold text-gray-900 text-[15px]">Revenue Optimization Insights</h3>
        </div>
        <div class="space-y-4">
            @foreach([
                ['Peak Sales Hours',     '10AM–1PM and 4PM–6PM show highest traffic. Schedule more staff during these windows.',92],
                ['Best-Performing Fruit','Mango generates 35% of total revenue. Prioritize stock availability.',88],
                ['Weekend Surge',        'Sales increase 40% on Saturdays. Prepare extra stock by Friday.',85],
                ['Bundle Pricing',       'Mango + Pineapple bundles could boost avg sale by 18%.',78],
            ] as [$title,$desc,$conf])
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <p class="text-[13px] font-bold text-gray-900">{{ $title }}</p>
                    <span class="badge badge-gray text-[10.5px]">{{ $conf }}%</span>
                </div>
                <p class="text-[12.5px] text-gray-500 mb-2 leading-relaxed">{{ $desc }}</p>
                <div class="progress-bar">
                    <div class="bg-gray-300 h-full rounded-full" style="width:{{ $conf }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script>
const gc = 'rgba(124,58,237,.06)';
new Chart(document.getElementById('matrixChart'), {
    type: 'bubble',
    data: { datasets: [
        { label: 'Restock Pomelo',    data: [{ x:9, y:8, r:18 }], backgroundColor: 'rgba(239,68,68,.7)' },
        { label: 'Sell Mango',        data: [{ x:8, y:9, r:22 }], backgroundColor: 'rgba(249,115,22,.7)' },
        { label: 'Increase Pineapple Sales',data: [{ x:6, y:7, r:14 }], backgroundColor: 'rgba(99,102,241,.7)' },
        { label: 'Restock Lanzones',  data: [{ x:5, y:6, r:12 }], backgroundColor: 'rgba(245,158,11,.7)' },
        { label: 'Monitor Durian',    data: [{ x:4, y:5, r:10 }], backgroundColor: 'rgba(16,185,129,.7)' },
    ]},
    options: { responsive: true, plugins: { legend: { position: 'right', labels: { font: { size: 10 } } } }, scales: { x: { title: { display: true, text: 'Urgency', font: { size: 11 } }, min: 0, max: 10, grid: { color: gc } }, y: { title: { display: true, text: 'Impact', font: { size: 11 } }, min: 0, max: 10, grid: { color: gc } } } }
});
new Chart(document.getElementById('revenueOptChart'), {
    type: 'bar',
    data: {
        labels: ['This Week (Current)', 'This Week (Optimized)', 'Next Week (Forecast)', 'Next Week (Optimized)'],
        datasets: [{ label: 'Revenue', data: [52300,77150,58400,86200], backgroundColor: ['rgba(156,163,175,.5)','rgba(124,58,237,.8)','rgba(156,163,175,.5)','rgba(16,185,129,.8)'], borderRadius: 12 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#9CA3AF' } }, y: { grid: { color: gc }, border: { display: false }, ticks: { font: { size: 10 }, color: '#9CA3AF', callback: v => '&#8369;' + (v/1000).toFixed(0) + 'k' } } } }
});
</script>
@endpush
</x-app-layout>
