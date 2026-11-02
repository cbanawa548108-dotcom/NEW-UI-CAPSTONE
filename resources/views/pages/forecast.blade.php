<x-app-layout>
<x-slot name="title">AI Sales Forecast — FreshTrack</x-slot>

<div x-data="forecastPage()" x-init="load()">

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-7 fade-up">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 bg-gray-100 border border-gray-200 rounded-2xl flex items-center justify-center">
            <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </div>
        <div>
            <h1 class="text-[26px] font-black text-gray-900">AI Sales Forecast</h1>
            <p class="text-[13.5px] text-gray-500 mt-0.5">
                SARIMAX demand model with OpenWeather variables
                <template x-if="run"><span> · Trained <span x-text="fmtDate(run.trained_at)"></span></span></template>
                <template x-if="summary.accuracy != null"><span> · <span x-text="summary.accuracy.toFixed(1)"></span>% accuracy</span></template>
            </p>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <span class="flex items-center gap-2 badge badge-violet px-3.5 py-2 text-[12px]">
            <span class="w-2 h-2 rounded-full" :class="hasForecast ? 'bg-violet-500 pulse-dot' : 'bg-gray-400'"></span>
            <span x-text="hasForecast ? 'SARIMAX Active' : 'Not trained yet'"></span>
        </span>
        <button class="btn btn-outline btn-md text-[13px]" @click="retrain()" :disabled="running">
            <svg class="w-4 h-4" :class="running && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            <span x-text="running ? 'Training model…' : 'Refresh Forecast'"></span>
        </button>
    </div>
</div>

{{-- Status messages --}}
<template x-if="message">
    <div class="card p-4 mb-5 border-l-4" :class="messageOk ? 'border-violet-400' : 'border-red-400'">
        <p class="text-[13px] font-semibold" :class="messageOk ? 'text-gray-700' : 'text-red-600'" x-text="message"></p>
    </div>
</template>
<template x-if="weather.error">
    <div class="card p-4 mb-5 border-l-4 border-amber-400">
        <p class="text-[12.5px] text-gray-600"><span class="font-semibold text-amber-600">OpenWeather:</span> <span x-text="weather.error"></span>. Future weather falls back to stored data or monthly averages.</p>
    </div>
</template>

<template x-if="loading">
    <div class="card p-10 text-center text-gray-400 text-[13px]">Loading forecast…</div>
</template>

<template x-if="!loading && !hasForecast">
    <div class="card p-10 text-center">
        <p class="font-bold text-gray-900 text-[15px]">No forecast yet</p>
        <p class="text-[13px] text-gray-500 mt-1">Click <b>Refresh Forecast</b> to train SARIMAX on your sales history and the weather data.</p>
        <p class="text-[12px] text-red-500 mt-2" x-show="run && run.message" x-text="run && run.message"></p>
    </div>
</template>

<div x-show="!loading && hasForecast" x-cloak>

{{-- Summary Cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-7 fade-up delay-1">
    <div class="card shimmer card-lift p-5">
        <div class="icon-ring mb-3"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>
        <p class="text-[24px] font-black text-gray-900" x-text="num(summary.predicted_qty) + ' kg'"></p>
        <p class="text-[12.5px] font-semibold text-gray-600">Predicted Demand</p>
        <p class="text-[11.5px] text-gray-400 mt-0.5" x-text="'Next ' + summary.horizon + ' days, all fruits'"></p>
    </div>
    <div class="card shimmer card-lift p-5">
        <div class="icon-ring mb-3"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        <p class="text-[24px] font-black text-gray-900" x-text="peso(summary.predicted_revenue)"></p>
        <p class="text-[12.5px] font-semibold text-gray-600">Expected Revenue</p>
        <p class="text-[11.5px] mt-0.5" :class="(summary.revenue_change_pct ?? 0) >= 0 ? 'text-green-600' : 'text-red-500'"
           x-text="summary.revenue_change_pct == null ? 'No prior period' : (summary.revenue_change_pct >= 0 ? '+' : '') + summary.revenue_change_pct + '% vs last ' + summary.horizon + ' days'"></p>
    </div>
    <div class="card shimmer card-lift p-5">
        <div class="icon-ring mb-3"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
        <p class="text-[24px] font-black text-gray-900" x-text="summary.demand_level + (summary.demand_level === 'High' ? ' ↑' : summary.demand_level === 'Low' ? ' ↓' : '')"></p>
        <p class="text-[12.5px] font-semibold text-gray-600">Demand Level</p>
        <p class="text-[11.5px] text-gray-400 mt-0.5">Compared with recent sales</p>
    </div>
    <div class="card shimmer card-lift p-5">
        <div class="icon-ring mb-3"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        <p class="text-[24px] font-black text-gray-900" x-text="summary.accuracy != null ? summary.accuracy.toFixed(1) + '%' : '—'"></p>
        <p class="text-[12.5px] font-semibold text-gray-600">Forecast Accuracy</p>
        <p class="text-[11.5px] text-gray-400 mt-0.5">1 − WAPE on 14-day hold-out</p>
    </div>
</div>

{{-- Weather strip --}}
<div class="card p-5 mb-6 fade-up delay-2">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="font-bold text-gray-900 text-[15px]">Weather Outlook · <span x-text="weather.city"></span></h3>
            <p class="text-[12px] text-gray-400 mt-0.5">Exogenous variables fed to SARIMAX (OpenWeather)</p>
        </div>
        <template x-if="weather.current">
            <div class="text-right">
                <p class="text-[20px] font-black text-gray-900"><span x-text="weather.current.temperature"></span>°C</p>
                <p class="text-[11.5px] text-gray-500 capitalize" x-text="weather.current.description + ' · ' + weather.current.humidity + '% RH'"></p>
            </div>
        </template>
    </div>
    <div class="grid grid-cols-3 sm:grid-cols-7 gap-2">
        <template x-for="d in daily" :key="d.date">
            <div class="bg-gray-50 border border-gray-100 rounded-xl p-3 text-center">
                <p class="text-[11px] font-bold text-gray-500" x-text="d.label.split(' ')[0]"></p>
                <p class="text-[16px] font-black text-gray-900 mt-1"><span x-text="d.temperature != null ? Math.round(d.temperature) : '—'"></span>°</p>
                <p class="text-[10.5px] text-gray-500" x-text="(d.humidity != null ? Math.round(d.humidity) : '—') + '% RH'"></p>
                <p class="text-[10.5px] text-blue-600 font-semibold" x-text="(d.rainfall != null ? d.rainfall.toFixed(1) : '—') + ' mm'"></p>
            </div>
        </template>
    </div>
</div>

{{-- Charts --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6 fade-up delay-2">
    <div class="card p-6">
        <div class="flex items-center justify-between mb-5">
            <div><h3 class="font-bold text-gray-900 text-[15px]">Revenue Forecast</h3><p class="text-[12px] text-gray-400 mt-0.5">Predicted daily revenue vs the previous period</p></div>
            <span class="badge badge-violet text-[11px]">Line Chart</span>
        </div>
        <canvas id="forecastLineChart" height="160"></canvas>
    </div>
    <div class="card p-6">
        <div class="flex items-center justify-between mb-5">
            <div><h3 class="font-bold text-gray-900 text-[15px]">95% Confidence Band</h3><p class="text-[12px] text-gray-400 mt-0.5">SARIMAX upper & lower forecast bounds</p></div>
            <span class="badge badge-blue text-[11px]">Area Chart</span>
        </div>
        <canvas id="forecastAreaChart" height="160"></canvas>
    </div>
</div>

<div class="card p-6 mb-7 fade-up delay-3">
    <div class="flex items-center justify-between mb-5">
        <div><h3 class="font-bold text-gray-900 text-[15px]">Fruit-by-Fruit Demand Forecast</h3><p class="text-[12px] text-gray-400 mt-0.5">Average kg/day: last 7 days vs predicted</p></div>
        <span class="badge badge-green text-[11px]">Grouped Bar</span>
    </div>
    <canvas id="forecastBarChart" height="90"></canvas>
</div>

{{-- Forecast Table --}}
<div class="card overflow-hidden mb-7 fade-up delay-4">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div><h3 class="font-bold text-gray-900 text-[15px]">Detailed Forecast by Fruit</h3><p class="text-[12px] text-gray-400 mt-0.5">SARIMAX predictions compared against current stock</p></div>
    </div>
    <div class="overflow-x-auto">
        <table class="tbl w-full">
            <thead><tr>
                <th class="text-left">Fruit</th><th class="text-left">Current/Day</th><th class="text-left">Predicted/Day</th><th class="text-left">Δ Change</th><th class="text-left">Trend</th><th class="text-left">7-Day Demand (95% CI)</th><th class="text-left">Stock</th><th class="text-left">Accuracy</th><th class="text-left">Recommendation</th>
            </tr></thead>
            <tbody>
            <template x-for="f in fruits" :key="f.fruit">
                <tr>
                    <td><div class="flex items-center gap-2"><div class="w-8 h-8 bg-gradient-to-br from-green-400 to-teal-500 rounded-xl flex items-center justify-center"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div><span class="font-semibold text-gray-800 text-[13.5px]" x-text="f.fruit"></span></div></td>
                    <td class="text-gray-500 text-[13px]" x-text="f.current_per_day + ' ' + f.unit"></td>
                    <td class="font-bold text-gray-900" x-text="f.predicted_per_day + ' ' + f.unit"></td>
                    <td><span class="font-bold" :class="f.delta >= 0 ? 'text-green-600' : 'text-red-500'" x-text="(f.delta >= 0 ? '+' : '') + f.delta + ' ' + f.unit"></span></td>
                    <td><span class="font-semibold text-[13px]" :class="{'text-green-600': f.trend==='up', 'text-red-500': f.trend==='down', 'text-blue-600': f.trend==='stable'}"
                              x-text="{up:'↑ Rising', down:'↓ Declining', stable:'→ Stable'}[f.trend]"></span></td>
                    <td class="text-[12.5px] text-gray-600"><b class="text-gray-900" x-text="f.predicted_total"></b> <span x-text="'(' + f.lower_total + '–' + f.upper_total + ')'"></span></td>
                    <td class="text-[13px]" :class="f.stock < f.predicted_total ? 'text-red-600 font-semibold' : 'text-gray-600'" x-text="f.stock + ' ' + f.unit"></td>
                    <td>
                        <template x-if="f.model === 'SARIMAX'">
                            <div class="flex items-center gap-2">
                                <div class="progress-bar w-16"><div class="progress-fill bg-violet-600" :style="'width:' + f.accuracy + '%'"></div></div>
                                <span class="text-[12px] font-bold text-gray-700" x-text="Math.round(f.accuracy) + '%'"></span>
                            </div>
                        </template>
                        <template x-if="f.model !== 'SARIMAX'"><span class="text-[11.5px] text-gray-400">Baseline (little history)</span></template>
                    </td>
                    <td><span class="badge text-[11px]" :class="{'badge-green': f.recommendation.tone==='green', 'badge-amber': f.recommendation.tone==='amber', 'badge-red': f.recommendation.tone==='red'}" x-text="f.recommendation.text"></span></td>
                </tr>
            </template>
            </tbody>
        </table>
    </div>
</div>

{{-- Model details --}}
<div class="card overflow-hidden fade-up delay-4" x-show="modelRows.length">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-bold text-gray-900 text-[15px]">SARIMAX Model Details</h3>
        <p class="text-[12px] text-gray-400 mt-0.5">Order chosen by AIC per fruit. Coefficients are kg/day per 1 standard deviation of each input; weather conditions are compared with the reference condition shown. p &lt; 0.05 means the effect is statistically significant.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="tbl w-full">
            <thead><tr>
                <th class="text-left">Fruit</th><th class="text-left">Order (p,d,q)(P,D,Q,s)</th><th class="text-left">MAE</th><th class="text-left">RMSE</th><th class="text-left">Temperature</th><th class="text-left">Humidity</th><th class="text-left">Rainfall</th><th class="text-left">Holiday</th><th class="text-left">Condition</th>
            </tr></thead>
            <tbody>
            <template x-for="m in modelRows" :key="m.fruit">
                <tr>
                    <td class="font-semibold text-gray-800 text-[13px]" x-text="m.fruit"></td>
                    <td class="text-[12.5px] text-gray-600 font-mono" x-text="'(' + m.order.join(',') + ')(' + m.seasonal_order.join(',') + ')'"></td>
                    <td class="text-[12.5px] text-gray-600" x-text="m.mae + ' kg'"></td>
                    <td class="text-[12.5px] text-gray-600" x-text="m.rmse + ' kg'"></td>
                    <template x-for="w in ['temperature','humidity','rainfall','is_holiday']" :key="w">
                        <td class="text-[12.5px]" :class="m.exog_pvalues[w] < 0.05 ? 'font-bold text-gray-900' : 'text-gray-400'">
                            <span x-text="(m.exog_coefficients[w] >= 0 ? '+' : '') + m.exog_coefficients[w]"></span>
                            <span class="text-[10.5px]" x-text="'p=' + m.exog_pvalues[w]"></span>
                        </td>
                    </template>
                    <td class="text-[12.5px] whitespace-nowrap">
                        <div class="text-[10.5px] text-gray-400" x-text="'vs ' + m.condition_reference"></div>
                        <template x-for="c in Object.keys(m.exog_coefficients).filter(k => k.startsWith('cond_'))" :key="c">
                            <div :class="m.exog_pvalues[c] < 0.05 ? 'font-bold text-gray-900' : 'text-gray-400'">
                                <span x-text="c.slice(5).charAt(0).toUpperCase() + c.slice(6) + ' ' + (m.exog_coefficients[c] >= 0 ? '+' : '') + m.exog_coefficients[c]"></span>
                                <span class="text-[10.5px]" x-text="'p=' + m.exog_pvalues[c]"></span>
                            </div>
                        </template>
                    </td>
                </tr>
            </template>
            </tbody>
        </table>
    </div>
</div>

</div>
</div>

@push('scripts')
<script>
function forecastPage() {
    return {
        loading: true, running: false, hasForecast: false, message: '', messageOk: true,
        run: null, summary: {}, daily: [], lastPeriod: [], fruits: [], weather: {}, modelRows: [],
        charts: {},

        async load() {
            this.loading = true;
            try {
                const res = await fetch('/api/forecast/data').then(r => r.json());
                this.run = res.run;
                this.weather = res.weather || {};
                this.hasForecast = res.has_forecast;
                if (res.has_forecast) {
                    this.summary = res.summary;
                    this.daily = res.daily;
                    this.lastPeriod = res.last_period;
                    this.fruits = res.fruits;
                    const perFruit = res.run?.metrics?.per_fruit || {};
                    this.modelRows = Object.entries(perFruit).map(([fruit, m]) => ({ fruit, ...m }));
                }
            } catch (e) {
                this.message = 'Failed to load forecast: ' + e.message; this.messageOk = false;
            }
            this.loading = false;
            if (this.hasForecast) this.$nextTick(() => this.drawCharts());
        },

        async retrain() {
            this.running = true; this.message = '';
            try {
                const res = await fetch('/api/ml/run', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ model: 'sales' }),
                }).then(r => r.json());
                this.messageOk = res.success;
                this.message = res.success ? 'Forecast updated with the latest sales and weather.' : (res.message || 'Training failed');
                await this.load();
            } catch (e) {
                this.message = 'Training failed: ' + e.message; this.messageOk = false;
            }
            this.running = false;
        },

        drawCharts() {
            Object.values(this.charts).forEach(c => c.destroy());
            const labels = this.daily.map(d => d.label);
            const gc = 'rgba(124,58,237,.06)';
            const pesoTick = v => '₱' + (v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v);
            const axes = { x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#9CA3AF' } },
                           y: { grid: { color: gc }, border: { display: false }, ticks: { font: { size: 10 }, color: '#9CA3AF', callback: pesoTick } } };

            this.charts.line = new Chart(document.getElementById('forecastLineChart'), { type: 'line', data: { labels, datasets: [
                { label: 'Predicted', data: this.daily.map(d => d.revenue), borderColor: '#7C3AED', backgroundColor: 'rgba(124,58,237,.08)', borderWidth: 2.5, fill: true, tension: .4, pointBackgroundColor: '#7C3AED', pointRadius: 4, pointHoverRadius: 7 },
                { label: 'Previous ' + this.daily.length + ' days', data: this.lastPeriod, borderColor: '#D1D5DB', borderWidth: 1.5, borderDash: [5, 4], fill: false, tension: .4, pointRadius: 3 },
            ] }, options: { responsive: true, plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } }, scales: axes } });

            this.charts.area = new Chart(document.getElementById('forecastAreaChart'), { type: 'line', data: { labels, datasets: [
                { label: 'Upper Bound', data: this.daily.map(d => d.revenue_upper), borderColor: 'rgba(99,102,241,.3)', backgroundColor: 'rgba(99,102,241,.08)', fill: '+1', tension: .4, pointRadius: 0, borderWidth: 1 },
                { label: 'Predicted', data: this.daily.map(d => d.revenue), borderColor: '#6366F1', backgroundColor: 'rgba(99,102,241,.12)', fill: false, tension: .4, pointBackgroundColor: '#6366F1', pointRadius: 4, borderWidth: 2.5 },
                { label: 'Lower Bound', data: this.daily.map(d => d.revenue_lower), borderColor: 'rgba(99,102,241,.3)', fill: false, tension: .4, pointRadius: 0, borderWidth: 1 },
            ] }, options: { responsive: true, plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } }, scales: axes } });

            const palette = ['#7C3AED','#10B981','#3B82F6','#8B5CF6','#EC4899','#F59E0B','#14B8A6','#EF4444','#6366F1','#84CC16'];
            this.charts.bar = new Chart(document.getElementById('forecastBarChart'), { type: 'bar', data: { labels: this.fruits.map(f => f.fruit), datasets: [
                { label: 'Last 7 days (kg/day)', data: this.fruits.map(f => f.current_per_day), backgroundColor: 'rgba(156,163,175,.5)', borderRadius: 6 },
                { label: 'Predicted (kg/day)', data: this.fruits.map(f => f.predicted_per_day), backgroundColor: this.fruits.map((_, i) => palette[i % palette.length]), borderRadius: 6 },
            ] }, options: { responsive: true, plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
                scales: { x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#6B7280' } }, y: { grid: { color: gc }, border: { display: false }, ticks: { font: { size: 10 }, color: '#9CA3AF', callback: v => v + ' kg' } } } } });
        },

        num(n) { return n == null ? '—' : Number(n).toLocaleString('en-PH', { maximumFractionDigits: 0 }); },
        peso(n) { return n == null ? '—' : '₱' + Number(n).toLocaleString('en-PH', { maximumFractionDigits: 0 }); },
        fmtDate(iso) { return new Date(iso).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }); },
    };
}
</script>
@endpush
</x-app-layout>
