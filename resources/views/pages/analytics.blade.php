<x-app-layout>
<x-slot name="title">Analytics — FreshTrack</x-slot>

<div x-data="analyticsController()" x-init="init()">

{{-- Loading Overlay --}}
<div x-show="loading" class="fixed inset-0 bg-white/70 backdrop-blur-sm z-50 flex items-center justify-center">
    <div class="text-center">
        <svg class="animate-spin h-10 w-10 text-violet-600 mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-600 font-medium">Loading analytics data...</p>
    </div>
</div>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-7 fade-up">
    <div>
        <h1 class="text-[26px] font-black text-gray-900">Analytics Dashboard</h1>
        <p class="text-[13.5px] text-gray-500 mt-0.5">Deep-dive analytics and business performance metrics</p>
    </div>
    <select x-model="dateRange" @change="reloadData()" class="inp" style="width:auto;padding:10px 18px;border-radius:14px">
        <option value="30">Last 30 Days</option>
        <option value="90">Last 90 Days</option>
        <option value="180">Last 6 Months</option>
        <option value="365">Last Year</option>
    </select>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-7 fade-up delay-1">
    {{-- Revenue --}}
    <div class="card shimmer card-lift p-5">
        <div class="icon-ring mb-3">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <p class="text-[22px] font-black text-gray-900" x-text="kpis ? '₱' + formatNum(kpis.total_revenue) : '₱0'">₱0</p>
        <p class="text-[12px] text-gray-500 font-medium mt-0.5">Revenue</p>
        <span class="badge text-[10.5px] mt-1.5" 
              :class="kpis && kpis.revenue_change_pct >= 0 ? 'badge-green' : 'badge-red'"
              x-text="kpis ? (kpis.revenue_change_pct >= 0 ? '↑ ' : '↓ ') + Math.abs(kpis.revenue_change_pct).toFixed(1) + '%' : '—'">—</span>
    </div>
    
    {{-- Profit --}}
    <div class="card shimmer card-lift p-5">
        <div class="icon-ring mb-3">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </div>
        <p class="text-[22px] font-black text-gray-900" x-text="kpis ? '₱' + formatNum(kpis.gross_profit) : '₱0'">₱0</p>
        <p class="text-[12px] text-gray-500 font-medium mt-0.5">Profit</p>
        <span class="badge text-[10.5px] mt-1.5"
              :class="kpis && kpis.profit_change_pct >= 0 ? 'badge-green' : 'badge-red'"
              x-text="kpis ? (kpis.profit_change_pct >= 0 ? '↑ ' : '↓ ') + Math.abs(kpis.profit_change_pct).toFixed(1) + '%' : '—'">—</span>
    </div>
    
    {{-- Sales Volume --}}
    <div class="card shimmer card-lift p-5">
        <div class="icon-ring mb-3">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <p class="text-[22px] font-black text-gray-900" x-text="kpis ? formatNum(kpis.sales_volume) + ' kg' : '0 kg'">0 kg</p>
        <p class="text-[12px] text-gray-500 font-medium mt-0.5">Sales Volume</p>
        <span class="badge text-[10.5px] mt-1.5"
              :class="kpis && kpis.volume_change_pct >= 0 ? 'badge-green' : 'badge-red'"
              x-text="kpis && kpis.volume_change_pct ? (kpis.volume_change_pct >= 0 ? '↑ ' : '↓ ') + Math.abs(kpis.volume_change_pct).toFixed(1) + '%' : '—'">—</span>
    </div>
    
    {{-- Waste Reduced --}}
    <div class="card shimmer card-lift p-5">
        <div class="icon-ring mb-3">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        </div>
        <p class="text-[22px] font-black text-gray-900" x-text="kpis ? kpis.waste_reduced_pct + '%' : '—'">—</p>
        <p class="text-[12px] text-gray-500 font-medium mt-0.5">Waste Reduced</p>
        <span class="badge text-[10.5px] mt-1.5"
              :class="kpis && kpis.waste_change_pct >= 0 ? 'badge-green' : 'badge-red'"
              x-text="kpis && kpis.waste_change_pct !== undefined ? (kpis.waste_change_pct >= 0 ? '↑ ' : '↓ ') + Math.abs(kpis.waste_change_pct).toFixed(1) + '%' : '—'">—</span>
    </div>
    
    {{-- Forecast Accuracy --}}
    <div class="card shimmer card-lift p-5">
        <div class="icon-ring mb-3">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </div>
        <p class="text-[22px] font-black text-gray-900" x-text="kpis ? kpis.forecast_accuracy + '%' : '—'">—</p>
        <p class="text-[12px] text-gray-500 font-medium mt-0.5">Forecast Acc.</p>
        <span class="badge text-[10.5px] mt-1.5"
              :class="kpis && kpis.forecast_change_pct >= 0 ? 'badge-green' : 'badge-red'"
              x-text="kpis && kpis.forecast_change_pct !== undefined ? (kpis.forecast_change_pct >= 0 ? '↑ ' : '↓ ') + Math.abs(kpis.forecast_change_pct).toFixed(1) + '%' : '—'">—</span>
    </div>
</div>

{{-- Row 1 --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5 fade-up delay-2">
    <div class="lg:col-span-2 card p-6">
        <div class="flex items-center justify-between mb-5">
            <div><h3 class="font-bold text-gray-900 text-[15px]">Revenue vs Profit Trend</h3><p class="text-[12px] text-gray-400 mt-0.5">Monthly — last 6 months</p></div>
            <div class="flex gap-2">
                @foreach(['Revenue','Profit','COGS'] as $l)
                <span class="badge badge-gray text-[11px]">{{ $l }}</span>
                @endforeach
            </div>
        </div>
        <canvas id="revProfitChart" height="130"></canvas>
    </div>
    <div class="card p-6">
        <h3 class="font-bold text-gray-900 text-[15px] mb-1">Sales by Fruit</h3>
        <p class="text-[12px] text-gray-400 mb-4">Revenue distribution this month</p>
        <canvas id="fruitPieChart" height="170"></canvas>
        <div class="mt-4 space-y-2">
            <template x-for="(fruit, index) in salesByFruit" :key="index">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full" :style="'background:' + fruit.color"></span>
                        <span class="text-[12.5px] text-gray-600" x-text="fruit.name"></span>
                    </div>
                    <span class="text-[12.5px] font-bold text-gray-800" x-text="fruit.percentage + '%'"></span>
                </div>
            </template>
        </div>
    </div>
</div>

{{-- Row 2 --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-5 fade-up delay-3">
    <div class="card p-5"><h3 class="font-bold text-gray-900 text-[14px] mb-1">Weekly Sales</h3><p class="text-[11.5px] text-gray-400 mb-3">Last 8 weeks</p><canvas id="weeklyBar" height="150"></canvas></div>
    <div class="card p-5"><h3 class="font-bold text-gray-900 text-[14px] mb-1">Cumulative Revenue</h3><p class="text-[11.5px] text-gray-400 mb-3">Month-to-date</p><canvas id="cumulativeChart" height="150"></canvas></div>
    <div class="card p-5">
        <h3 class="font-bold text-gray-900 text-[14px] mb-1">Forecast Accuracy</h3><p class="text-[11.5px] text-gray-400 mb-3">By fruit category</p>
        <canvas id="accDonut" height="110"></canvas>
        <div class="mt-3 space-y-1.5">
            @foreach([['Mango','99.8%','#7C3AED'],['Durian','96%','#10B981'],['Pineapple','92.9%','#3B82F6'],['Others','95.2%','#8B5CF6']] as [$n,$v,$c])
            <div class="flex justify-between"><div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full" style="background:{{ $c }}"></span><span class="text-[12px] text-gray-600">{{ $n }}</span></div><span class="text-[12px] font-bold text-gray-800">{{ $v }}</span></div>
            @endforeach
        </div>
    </div>
    <div class="card p-5"><h3 class="font-bold text-gray-900 text-[14px] mb-1">Waste Reduction</h3><p class="text-[11.5px] text-gray-400 mb-3">Monthly spoilage rate</p><canvas id="wasteBar" height="150"></canvas></div>
</div>

{{-- Row 3 --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-7 fade-up delay-4">
    <div class="card p-6"><h3 class="font-bold text-gray-900 text-[15px] mb-1">Sales by Fruit per Month</h3><p class="text-[12px] text-gray-400 mb-4">Stacked revenue breakdown</p><canvas id="stackedChart" height="150"></canvas></div>
    <div class="card p-6"><h3 class="font-bold text-gray-900 text-[15px] mb-1">Performance Radar</h3><p class="text-[12px] text-gray-400 mb-4">Multi-dimensional business health</p><canvas id="radarChart" height="150"></canvas></div>
</div>

{{-- Summary Banners --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-5 fade-up delay-5">
    <div class="rounded-3xl p-6 text-white shadow-xl" style="background:linear-gradient(135deg,#7C3AED,#6D28D9)">
        <p class="text-white/70 text-[12px] font-medium mb-1">Revenue Growth Rate</p>
        <p class="text-[40px] font-black">+8.3%</p>
        <p class="text-white/60 text-[12px] mt-1">Month-over-month · On track for ₱400K</p>
        <div class="mt-4 bg-white/10 rounded-full h-2"><div class="h-2 rounded-full bg-green-300" style="width:83%"></div></div>
        <p class="text-white/50 text-[11px] mt-1.5">83% of ₱400K monthly goal</p>
    </div>
    <div class="rounded-3xl p-6 text-white shadow-xl" style="background:linear-gradient(135deg,#3B82F6,#2563EB)">
        <p class="text-white/70 text-[12px] font-medium mb-1">Customer Satisfaction Score</p>
        <p class="text-[40px] font-black">4.8<span class="text-[22px]">/5</span></p>
        <p class="text-white/60 text-[12px] mt-1">Based on 312 feedback responses</p>
        <div class="flex gap-1 mt-4">@for($i=0;$i<5;$i++)<svg class="w-5 h-5 {{ $i<4 ? 'text-yellow-300' : 'text-yellow-200' }}" fill="{{ $i<4 ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>@endfor</div>
    </div>
    <div class="rounded-3xl p-6 text-white shadow-xl" style="background:linear-gradient(135deg,#10B981,#059669)">
        <p class="text-white/70 text-[12px] font-medium mb-1">AI System Performance</p>
        <p class="text-[40px] font-black">94<span class="text-[22px]">/100</span></p>
        <p class="text-white/60 text-[12px] mt-1">Combined AI score across all modules</p>
        <div class="grid grid-cols-3 gap-2 mt-4 text-center">
            @foreach([['Forecast','96%'],['Spoilage','94%'],['Quality','92%']] as [$l,$v])
            <div><p class="text-white/50 text-[11px]">{{ $l }}</p><p class="font-black text-[14px]">{{ $v }}</p></div>
            @endforeach
        </div>
    </div>
</div>

</div>{{-- End Alpine controller --}}

@push('scripts')
<script>
function analyticsController() {
    return {
        loading: true,
        dateRange: 30,
        kpis: null,
        salesByFruit: [],
        charts: {},
        
        formatNum(n) {
            if (!n && n !== 0) return '—';
            if (n >= 1000000) return (n/1000000).toFixed(1) + 'M';
            if (n >= 1000) return (n/1000).toFixed(1) + 'k';
            return parseFloat(n).toFixed(0);
        },
        
        async init() {
            await this.reloadData();
        },
        
        async reloadData() {
            this.loading = true;
            try {
                const [kpis, trend, fruit, weekly, cumul, waste, stacked] = await Promise.all([
                    fetch(`/api/analytics/kpis?days=${this.dateRange}`).then(r=>r.json()),
                    fetch(`/api/analytics/revenue-trend?days=${this.dateRange}`).then(r=>r.json()),
                    fetch(`/api/analytics/sales-by-fruit?days=${this.dateRange}`).then(r=>r.json()),
                    fetch(`/api/analytics/weekly-sales?weeks=8`).then(r=>r.json()),
                    fetch(`/api/analytics/cumulative-revenue?days=${this.dateRange}`).then(r=>r.json()),
                    fetch(`/api/analytics/waste-reduction?months=6`).then(r=>r.json()),
                    fetch(`/api/analytics/stacked-sales?months=6`).then(r=>r.json()),
                ]);
                this.kpis = kpis;
                this.salesByFruit = fruit;
                this.$nextTick(() => this.initCharts(kpis, trend, fruit, weekly, cumul, waste, stacked));
            } catch(e) {
                console.error('Analytics load error:', e);
            } finally {
                this.loading = false;
            }
        },
        
        initCharts(kpis, trend, fruit, weekly, cumul, waste, stacked) {
            const gc = 'rgba(124,58,237,.06)';
            // Destroy any existing chart instances
            Object.values(this.charts).forEach(c => c?.destroy());
            this.charts = {};
            
            // Revenue vs Profit Trend
            this.charts.revProfit = new Chart(document.getElementById('revProfitChart'), {
                type:'line',
                data:{
                    labels: trend.labels || [],
                    datasets:[
                        {label:'Revenue', data: trend.revenue||[], borderColor:'#7C3AED', backgroundColor:'rgba(124,58,237,.07)', fill:true, tension:.4, borderWidth:2.5, pointRadius:4},
                        {label:'Profit', data: trend.profit||[], borderColor:'#10B981', backgroundColor:'rgba(16,185,129,.07)', fill:true, tension:.4, borderWidth:2.5, pointRadius:4},
                        {label:'COGS', data: trend.cogs||[], borderColor:'#F97316', borderDash:[5,5], fill:false, tension:.4, borderWidth:2, pointRadius:3}
                    ]
                },
                options:{responsive:true,plugins:{legend:{position:'top',labels:{font:{size:11}}}},scales:{x:{grid:{display:false}},y:{grid:{color:gc},border:{display:false},ticks:{font:{size:10},color:'#9CA3AF',callback:v=>'₱'+(v/1000).toFixed(0)+'k'}}}}
            });
            
            // Fruit Pie
            this.charts.fruitPie = new Chart(document.getElementById('fruitPieChart'), {
                type:'pie',
                data:{
                    labels: (fruit||[]).map(f=>f.name),
                    datasets:[{data:(fruit||[]).map(f=>f.percentage), backgroundColor:(fruit||[]).map(f=>f.color), borderWidth:0, hoverOffset:10}]
                },
                options:{responsive:true,plugins:{legend:{display:false}}}
            });
            
            // Weekly Bar
            this.charts.weekly = new Chart(document.getElementById('weeklyBar'), {
                type:'bar',
                data:{
                    labels: weekly.labels||[],
                    datasets:[{data:weekly.data||[], backgroundColor:'#7C3AED', borderRadius:8}]
                },
                options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{font:{size:9},color:'#9CA3AF'}},y:{grid:{color:gc},border:{display:false},ticks:{font:{size:9},color:'#9CA3AF',callback:v=>'₱'+(v/1000)+'k'}}}}
            });
            
            // Cumulative
            this.charts.cumul = new Chart(document.getElementById('cumulativeChart'), {
                type:'line',
                data:{
                    labels: cumul.labels||[],
                    datasets:[{data:cumul.data||[], borderColor:'#6366F1', backgroundColor:'rgba(99,102,241,.1)', fill:true, tension:.4, borderWidth:2, pointRadius:0}]
                },
                options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{font:{size:9}}},y:{grid:{color:gc},border:{display:false},ticks:{font:{size:9},color:'#9CA3AF',callback:v=>'₱'+(v/1000).toFixed(0)+'k'}}}}
            });
            
            // Forecast Accuracy Donut (static placeholder)
            this.charts.accDonut = new Chart(document.getElementById('accDonut'), {
                type:'doughnut',
                data:{
                    labels:['Mango','Durian','Pineapple','Others'],
                    datasets:[{data:[99.8,96,92.9,95.2], backgroundColor:['#7C3AED','#10B981','#3B82F6','#8B5CF6'], borderWidth:0}]
                },
                options:{responsive:true,cutout:'65%',plugins:{legend:{display:false}}}
            });
            
            // Waste Reduction Bar
            this.charts.waste = new Chart(document.getElementById('wasteBar'), {
                type:'bar',
                data:{
                    labels: waste.labels||[],
                    datasets:[{data:waste.data||[], backgroundColor:waste.colors||[], borderRadius:8}]
                },
                options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{font:{size:9}}},y:{grid:{color:gc},border:{display:false},ticks:{font:{size:9},color:'#9CA3AF',callback:v=>v+'%'}}}}
            });
            
            // Stacked Sales
            this.charts.stacked = new Chart(document.getElementById('stackedChart'), {
                type:'bar',
                data:{
                    labels: stacked.labels||[],
                    datasets: (stacked.datasets||[]).map(d=>({...d, borderRadius:0}))
                },
                options:{responsive:true,plugins:{legend:{position:'top',labels:{font:{size:10}}}},scales:{x:{stacked:true,grid:{display:false}},y:{stacked:true,grid:{color:gc},border:{display:false},ticks:{font:{size:10},color:'#9CA3AF',callback:v=>'₱'+(v/1000).toFixed(0)+'k'}}}}
            });
            
            // Radar Chart
            this.charts.radar = new Chart(document.getElementById('radarChart'), {
                type:'radar',
                data:{
                    labels:['Revenue','Profit Margin','Forecast Acc.','Stock Efficiency','Waste Reduction','Customer Rating'],
                    datasets:[
                        {label:'This Month', data:[kpis?Math.min(100,Math.round(kpis.total_revenue/5000)):88,kpis?Math.min(100,Math.round(kpis.profit_margin)):84,96,90,kpis?Math.min(100,Math.round(kpis.waste_reduced_pct)):62,96], borderColor:'#7C3AED',backgroundColor:'rgba(124,58,237,.12)',borderWidth:2,pointBackgroundColor:'#7C3AED',pointRadius:4},
                        {label:'Last Month', data:[82,78,95,85,52,92], borderColor:'#9CA3AF',backgroundColor:'rgba(156,163,175,.08)',borderWidth:2,borderDash:[5,5],pointRadius:3}
                    ]
                },
                options:{responsive:true,plugins:{legend:{position:'top',labels:{font:{size:11}}}},scales:{r:{grid:{color:'#E5E7EB'},ticks:{font:{size:9}},pointLabels:{font:{size:11}},beginAtZero:true,max:100}}}
            });
        }
    };
}
</script>
@endpush
</x-app-layout>
