<x-app-layout>
<x-slot name="title">Spoilage Prediction — FreshTrack</x-slot>

@php $fruitIconPath = 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'; @endphp

<div x-data="spoilagePage()" x-init="load()">

{{-- ── PAGE HEADER ── --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-7 fade-up">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 bg-gray-100 border border-gray-200 rounded-2xl flex items-center justify-center">
            <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div>
            <h1 class="text-[26px] font-black text-gray-900">Spoilage Prediction</h1>
            <p class="text-[13.5px] text-gray-500 mt-0.5">
                XGBoost regression on fruit type, stock age, turnover, sales trend & weather
                <template x-if="run"><span> · Trained <span x-text="fmtDate(run.trained_at)"></span></span></template>
            </p>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <span class="flex items-center gap-2 badge badge-red px-4 py-2.5 text-[12.5px]" x-show="hasPredictions">
            <span class="w-2 h-2 bg-red-500 rounded-full pulse-dot"></span>
            <span x-text="highCount + ' High Risk Batch' + (highCount === 1 ? '' : 'es')"></span>
        </span>
        <button class="btn btn-outline btn-md text-[13px]" @click="retrain()" :disabled="running">
            <svg class="w-4 h-4" :class="running && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            <span x-text="running ? 'Training model…' : 'Refresh Predictions'"></span>
        </button>
    </div>
</div>

<template x-if="message">
    <div class="card p-4 mb-5 border-l-4" :class="messageOk ? 'border-violet-400' : 'border-red-400'">
        <p class="text-[13px] font-semibold" :class="messageOk ? 'text-gray-700' : 'text-red-600'" x-text="message"></p>
    </div>
</template>

<template x-if="loading">
    <div class="card p-10 text-center text-gray-400 text-[13px]">Loading predictions…</div>
</template>

<template x-if="!loading && !hasPredictions">
    <div class="card p-10 text-center">
        <p class="font-bold text-gray-900 text-[15px]">No spoilage predictions yet</p>
        <p class="text-[13px] text-gray-500 mt-1">Click <b>Refresh Predictions</b> to train XGBoost on past batches and spoilage write-offs.</p>
        <p class="text-[12px] text-red-500 mt-2" x-show="run && run.message" x-text="run && run.message"></p>
    </div>
</template>

<div x-show="!loading && hasPredictions" x-cloak>

{{-- ── RISK SUMMARY CARDS ── --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-7 fade-up delay-1">
    <template x-for="c in cards" :key="c.label">
        <div class="card shimmer card-lift p-5">
            <div class="icon-ring mb-3">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" :d="c.icon"/></svg>
            </div>
            <p class="text-[24px] font-black text-gray-900" x-text="c.value"></p>
            <p class="text-[12.5px] font-semibold text-gray-600" x-text="c.label"></p>
            <p class="text-[11.5px] text-gray-400 mt-0.5" x-text="c.sub"></p>
        </div>
    </template>
</div>

{{-- ── CHARTS ── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-7 fade-up delay-2">
    <div class="card p-6">
        <h3 class="font-bold text-gray-900 text-[15px] mb-1">Spoilage Risk by Batch</h3>
        <p class="text-[12px] text-gray-400 mb-4">Predicted % of on-hand stock that will spoil</p>
        <canvas id="spoilageBarChart" height="220"></canvas>
    </div>
    <div class="card p-6">
        <h3 class="font-bold text-gray-900 text-[15px] mb-1">Risk Timeline</h3>
        <p class="text-[12px] text-gray-400 mb-4">Projected risk over 7 days if unsold (forecast weather)</p>
        <canvas id="riskTimelineChart" height="220"></canvas>
    </div>
    <div class="card p-6">
        <h3 class="font-bold text-gray-900 text-[15px] mb-1">Priority Distribution</h3>
        <p class="text-[12px] text-gray-400 mb-4">Risk level breakdown</p>
        <canvas id="priorityDonut" height="170"></canvas>
        <div class="mt-4 space-y-2">
            <template x-for="(lvl, i) in levels" :key="lvl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full" :style="'background:' + levelColors[i]"></span>
                        <span class="text-[12.5px] text-gray-600" x-text="lvl"></span>
                    </div>
                    <span class="text-[12.5px] font-bold text-gray-800" x-text="(summary.counts?.[lvl] ?? 0) + ' batches'"></span>
                </div>
            </template>
        </div>
    </div>
</div>

{{-- ── TOP RISK CARDS ── --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-7 fade-up delay-3">
    <template x-for="b in batches.slice(0, 3)" :key="b.batch_id">
        <div class="card p-5 border-l-4" :class="tone(b.risk_score).border">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-gray-100 border border-gray-200 text-gray-400 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $fruitIconPath }}"/></svg>
                    </div>
                    <div>
                        <p class="font-bold text-gray-900 text-[14px]" x-text="b.fruit + ' ' + b.batch_code"></p>
                        <span class="badge text-[10px]" x-text="b.risk_level"></span>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[26px] font-black" :class="tone(b.risk_score).text" x-text="Math.round(b.risk_score) + '%'"></p>
                    <p class="text-[10.5px] text-gray-400">Spoilage Risk</p>
                </div>
            </div>
            <div class="progress-bar mb-3">
                <div class="h-full rounded-full transition-all" :class="tone(b.risk_score).bar" :style="'width:' + b.risk_score + '%'"></div>
            </div>
            <p class="text-[11.5px] text-gray-500 mb-1" x-text="b.quantity + ' ' + b.unit + ' on hand · ' + b.stock_age_days + ' days old · expires ' + b.expiry_date"></p>
            <p class="text-[12px] font-semibold mb-3" x-show="b.remaining_shelf_life_days !== null"
               :class="b.remaining_shelf_life_days <= 1 ? 'text-red-600' : 'text-gray-700'"
               title="Shelf life minus temperature-adjusted age: hot days count as more than one day"
               x-text="b.remaining_shelf_life_days > 0 ? '≈ ' + b.remaining_shelf_life_days + ' days of shelf life left' : 'Past its shelf life'"></p>
            <div class="bg-gray-50 rounded-xl p-3 border border-gray-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-violet-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                <p class="text-[12px] font-semibold text-gray-700" x-text="b.recommendation"></p>
            </div>
        </div>
    </template>
</div>

{{-- ── BATCH TABLE ── --}}
<div class="card overflow-hidden mb-7 fade-up delay-4">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-bold text-gray-900 text-[15px]">Batch Spoilage Analysis</h3>
        <p class="text-[12px] text-gray-400 mt-0.5">Model inputs and XGBoost prediction for every batch in stock</p>
    </div>
    <div class="overflow-x-auto">
        <table class="tbl w-full">
            <thead><tr>
                <th class="text-left">Fruit / Batch</th>
                <th class="text-left">Stock Age</th>
                <th class="text-left">Turnover</th>
                <th class="text-left">Sales Trend</th>
                <th class="text-left">Temp</th>
                <th class="text-left">Humidity</th>
                <th class="text-left">Rain (3d)</th>
                <th class="text-left">Spoilage %</th>
                <th class="text-left">Value at Risk</th>
                <th class="text-left">Status</th>
                <th class="text-left">Recommendation</th>
            </tr></thead>
            <tbody>
            <template x-for="b in batches" :key="b.batch_id">
                <tr>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-gray-100 border border-gray-200 text-gray-400 flex-shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $fruitIconPath }}"/></svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800 text-[13px]" x-text="b.fruit"></p>
                                <p class="text-[11px] text-gray-400" x-text="b.batch_code + ' · ' + b.quantity + ' ' + b.unit"></p>
                            </div>
                        </div>
                    </td>
                    <td class="text-[13px] text-gray-600" x-text="b.stock_age_days + ' d'"></td>
                    <td class="text-[13px] text-gray-600" x-text="Math.round(b.turnover_rate * 100) + '%/day'"></td>
                    <td class="text-[13px] font-semibold" :class="b.sales_trend >= 1.05 ? 'text-green-600' : (b.sales_trend <= 0.95 ? 'text-red-500' : 'text-gray-600')"
                        x-text="(b.sales_trend >= 1 ? '↑ ' : '↓ ') + b.sales_trend.toFixed(2) + '×'"></td>
                    <td class="font-semibold text-[13px]" :class="b.temperature > 28 ? 'text-red-600' : 'text-gray-600'" x-text="b.temperature + '°C'"></td>
                    <td class="text-[13px]" :class="b.humidity > 80 ? 'text-amber-600 font-semibold' : 'text-gray-600'" x-text="Math.round(b.humidity) + '%'"></td>
                    <td class="text-[13px] text-gray-600" x-text="b.rainfall + ' mm'"></td>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="progress-bar w-16"><div class="h-full rounded-full" :class="tone(b.risk_score).bar" :style="'width:' + b.risk_score + '%'"></div></div>
                            <span class="text-[12.5px] font-bold" :class="tone(b.risk_score).text" x-text="b.risk_score.toFixed(1) + '%'"></span>
                        </div>
                    </td>
                    <td class="text-[13px] text-gray-700" x-text="peso(b.value_at_risk)"></td>
                    <td><span class="badge text-[11px]" x-text="b.risk_level"></span></td>
                    <td class="text-[12px] text-gray-500 max-w-36" x-text="b.recommendation"></td>
                </tr>
            </template>
            </tbody>
        </table>
    </div>
</div>

{{-- ── MODEL DETAILS ── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 fade-up delay-4" x-show="run && run.metrics">
    <div class="card p-6">
        <h3 class="font-bold text-gray-900 text-[15px] mb-1">XGBoost Model Evaluation</h3>
        <p class="text-[12px] text-gray-400 mb-4">Tested on the most recent 20% of batches (time-based split)</p>
        <div class="grid grid-cols-3 gap-3">
            <template x-for="m in evalStats" :key="m.label">
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-3">
                    <p class="text-[20px] font-black text-gray-900" x-text="m.value"></p>
                    <p class="text-[11.5px] font-semibold text-gray-600" x-text="m.label"></p>
                    <p class="text-[10.5px] text-gray-400" x-text="m.sub"></p>
                </div>
            </template>
        </div>
    </div>
    <div class="card p-6">
        <h3 class="font-bold text-gray-900 text-[15px] mb-1">Feature Importance</h3>
        <p class="text-[12px] text-gray-400 mb-4">Which inputs drive the spoilage prediction (fruit types combined)</p>
        <canvas id="importanceChart" height="170"></canvas>
    </div>
</div>

</div>
</div>

@push('scripts')
<script>
function spoilagePage() {
    const warn = 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z';
    return {
        loading: true, running: false, hasPredictions: false, message: '', messageOk: true,
        run: null, summary: {}, batches: [], timeline: [], horizonLabels: [], charts: {},
        levels: ['Critical', 'High Risk', 'Moderate', 'Low Risk', 'Fresh'],
        levelColors: ['#EF4444', '#F97316', '#FACC15', '#3B82F6', '#10B981'],

        get highCount() { return (this.summary.counts?.['Critical'] ?? 0) + (this.summary.counts?.['High Risk'] ?? 0); },
        get cards() {
            const c = this.summary.counts || {};
            return [
                { label: 'High Risk', value: this.highCount + ' batches', sub: 'Critical + high, act today', icon: warn },
                { label: 'Moderate Risk', value: (c['Moderate'] ?? 0) + ' batches', sub: 'Monitor closely', icon: warn },
                { label: 'Low Risk / Fresh', value: ((c['Low Risk'] ?? 0) + (c['Fresh'] ?? 0)) + ' batches', sub: 'Within safe range', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
                { label: 'Value at Risk', value: this.peso(this.summary.value_at_risk), sub: 'Avg risk ' + (this.summary.avg_risk ?? 0) + '% across stock', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
            ];
        },
        get evalStats() {
            const m = this.run?.metrics || {};
            return [
                { label: 'MAE', value: (m.mae ?? '—') + ' pts', sub: 'Mean absolute error' },
                { label: 'RMSE', value: (m.rmse ?? '—') + ' pts', sub: 'Root mean sq. error' },
                { label: 'R²', value: m.r2 ?? '—', sub: (m.batches ?? 0) + ' batches, ' + ((m.train_rows ?? 0) + (m.test_rows ?? 0)) + ' rows' },
            ];
        },

        async load() {
            this.loading = true;
            try {
                const res = await fetch('/api/spoilage/data').then(r => r.json());
                this.run = res.run;
                this.hasPredictions = res.has_predictions;
                if (res.has_predictions) {
                    this.summary = res.summary;
                    this.batches = res.batches;
                    this.timeline = res.timeline;
                    this.horizonLabels = res.horizon_labels;
                }
            } catch (e) {
                this.message = 'Failed to load predictions: ' + e.message; this.messageOk = false;
            }
            this.loading = false;
            if (this.hasPredictions) this.$nextTick(() => this.drawCharts());
        },

        async retrain() {
            this.running = true; this.message = '';
            try {
                const res = await fetch('/api/ml/run', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ model: 'spoilage' }),
                }).then(r => r.json());
                this.messageOk = res.success;
                this.message = res.success ? 'Spoilage predictions updated with the latest stock and weather.' : (res.message || 'Training failed');
                await this.load();
            } catch (e) {
                this.message = 'Training failed: ' + e.message; this.messageOk = false;
            }
            this.running = false;
        },

        tone(score) {
            if (score >= 70) return { text: 'text-red-600', bar: 'bg-red-500', border: 'border-red-400' };
            if (score >= 50) return { text: 'text-orange-600', bar: 'bg-orange-500', border: 'border-orange-400' };
            if (score >= 25) return { text: 'text-amber-600', bar: 'bg-amber-500', border: 'border-amber-400' };
            return { text: 'text-green-600', bar: 'bg-green-500', border: 'border-green-400' };
        },

        drawCharts() {
            Object.values(this.charts).forEach(c => c.destroy());
            const gc = 'rgba(124,58,237,.06)';
            const color = s => s >= 70 ? '#EF4444' : s >= 50 ? '#F97316' : s >= 25 ? '#F59E0B' : s >= 10 ? '#84CC16' : '#10B981';
            const top = this.batches.slice(0, 10);

            this.charts.bar = new Chart(document.getElementById('spoilageBarChart'), { type: 'bar',
                data: { labels: top.map(b => b.fruit + ' ' + b.batch_code.split('-').pop()), datasets: [{ label: 'Spoilage %', data: top.map(b => b.risk_score), backgroundColor: top.map(b => color(b.risk_score)), borderRadius: 6 }] },
                options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } }, scales: { x: { grid: { color: gc }, ticks: { font: { size: 10 }, color: '#9CA3AF', callback: v => v + '%' }, max: 100 }, y: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#6B7280' } } } } });

            const lineColors = ['#EF4444', '#F97316', '#F59E0B', '#3B82F6', '#10B981'];
            this.charts.timeline = new Chart(document.getElementById('riskTimelineChart'), { type: 'line',
                data: { labels: this.horizonLabels, datasets: this.timeline.map((t, i) => ({ label: t.label, data: t.data, borderColor: lineColors[i], tension: .3, fill: false, borderWidth: 2, pointRadius: 3 })) },
                options: { responsive: true, plugins: { legend: { position: 'top', labels: { font: { size: 10 } } } }, scales: { x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#9CA3AF' } }, y: { grid: { color: gc }, border: { display: false }, ticks: { font: { size: 10 }, color: '#9CA3AF', callback: v => v + '%' }, max: 100, min: 0 } } } });

            this.charts.donut = new Chart(document.getElementById('priorityDonut'), { type: 'doughnut',
                data: { labels: this.levels, datasets: [{ data: this.levels.map(l => this.summary.counts?.[l] ?? 0), backgroundColor: this.levelColors, borderWidth: 0, hoverOffset: 10 }] },
                options: { responsive: true, cutout: '68%', plugins: { legend: { display: false } } } });

            // Collapse the one-hot fruit columns into a single "fruit type" importance
            const imp = this.run?.metrics?.feature_importance || {};
            const names = { shelf_life_days: 'Shelf life', stock_age_days: 'Stock age', days_to_expiry: 'Days to expiry', remaining_fraction: 'Batch remaining',
                            turnover_rate: 'Inventory turnover', sales_trend: 'Sales trend', temperature: 'Temperature', humidity: 'Humidity', rainfall: 'Rainfall' };
            const grouped = {};
            Object.entries(imp).forEach(([k, v]) => { const key = k.startsWith('fruit_') ? 'Fruit type' : (names[k] || k); grouped[key] = (grouped[key] || 0) + v; });
            const sorted = Object.entries(grouped).sort((a, b) => b[1] - a[1]);
            if (sorted.length) {
                this.charts.importance = new Chart(document.getElementById('importanceChart'), { type: 'bar',
                    data: { labels: sorted.map(s => s[0]), datasets: [{ data: sorted.map(s => +(s[1] * 100).toFixed(1)), backgroundColor: '#7C3AED', borderRadius: 6 }] },
                    options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } }, scales: { x: { grid: { color: gc }, ticks: { font: { size: 10 }, color: '#9CA3AF', callback: v => v + '%' } }, y: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#6B7280' } } } } });
            }
        },

        peso(n) { return n == null ? '—' : '₱' + Number(n).toLocaleString('en-PH', { maximumFractionDigits: 0 }); },
        fmtDate(iso) { return new Date(iso).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }); },
    };
}
</script>
@endpush
</x-app-layout>
