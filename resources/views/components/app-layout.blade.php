<!DOCTYPE html>
<html lang="en" x-data="appState()" :class="darkMode ? 'dark' : ''" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'FreshTrack — Fruit Inventory System' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

    <style>
          /* ═══════════════════════════════════════════════════════
              FreshTrack DESIGN SYSTEM — SINGLE VIOLET BRAND
          ═══════════════════════════════════════════════════════ */
        :root {
            --white:       #FFFFFF;
            --bg-soft:     #F5F3FF;
            --violet:      #7C3AED;
            --violet-dark: #6D28D9;
            --violet-light:#8B5CF6;
            --violet-pale: #EDE9FE;
            --gray-50:     #F9FAFB;
            --gray-100:    #F3F4F6;
            --gray-200:    #E5E7EB;
            --gray-300:    #D1D5DB;
            --gray-400:    #9CA3AF;
            --gray-500:    #6B7280;
            --gray-600:    #4B5563;
            --gray-700:    #374151;
            --gray-800:    #1F2937;
            --gray-900:    #111827;
            --success:     #16A34A;
            --warning:     #FACC15;
            --danger:      #EF4444;
            --radius:      24px;
        }

        [x-cloak] { display: none !important; }

        *, *::before, *::after { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background: #F5F3FF;
            color: var(--gray-800);
        }

        h1,h2,h3,h4,h5 { font-family: 'Poppins', sans-serif; }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #DDD6FE; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #7C3AED; }

        /* ── Gradients ── */
        .g-violet   { background: linear-gradient(135deg, #7C3AED 0%, #6D28D9 100%); }
        .g-violet-r { background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 60%, #6D28D9 100%); }
        .g-green,
        .g-emerald,
        .g-rose,
        .g-amber,
        .g-blue,
        .g-teal,
        .g-orange,
        .g-indigo,
        .g-hero     { background: linear-gradient(135deg, #7C3AED 0%, #6D28D9 100%); }

        /* ── Cards ── */
        .card {
            background: #FFFFFF;
            border: 1px solid rgba(124,58,237,.08);
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(124,58,237,.06), 0 2px 12px rgba(124,58,237,.04);
            transition: box-shadow .25s ease, transform .25s ease;
        }
        .card:hover { box-shadow: 0 8px 30px rgba(124,58,237,.12), 0 2px 8px rgba(124,58,237,.06); }
        .card-lift:hover { transform: translateY(-3px); box-shadow: 0 12px 40px rgba(124,58,237,.14); }

        /* ── Shimmer ── */
        .shimmer { position: relative; overflow: hidden; }
        .shimmer::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(105deg, transparent 40%, rgba(255,255,255,.65) 50%, transparent 60%);
            transform: translateX(-100%);
            transition: transform .6s ease;
            pointer-events: none;
        }
        .shimmer:hover::after { transform: translateX(100%); }

        /* ── Sidebar ── */
        .sidebar { transition: width .3s cubic-bezier(.4,0,.2,1); }
        .nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 14px; border-radius: 14px;
            font-size: 13.5px; font-weight: 500;
            color: var(--gray-600); transition: all .18s ease;
            position: relative;
        }
        .nav-link:hover { background: #F5F3FF; color: #7C3AED; }
        .nav-link.active {
            background: linear-gradient(135deg, #7C3AED, #6D28D9);
            color: #fff; font-weight: 600;
            box-shadow: 0 4px 16px rgba(124,58,237,.35);
        }
        .nav-link.active::before {
            content: '';
            position: absolute; left: -14px; top: 50%;
            transform: translateY(-50%);
            width: 4px; height: 55%;
            background: #10B981; border-radius: 0 4px 4px 0;
        }
        .nav-group { font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #9CA3AF; padding: 16px 14px 6px; }

        /* ── Buttons ── */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border-radius: 12px; font-weight: 600; font-size: 13.5px; transition: all .2s ease; cursor: pointer; border: none; }
        .btn:active { transform: scale(.97); }
        .btn-violet { background: linear-gradient(135deg, #7C3AED, #6D28D9); color: #fff; box-shadow: 0 4px 16px rgba(124,58,237,.35); }
        .btn-violet:hover { box-shadow: 0 6px 22px rgba(124,58,237,.45); transform: translateY(-1px); }
        .btn-green  { background: linear-gradient(135deg, #7C3AED, #6D28D9); color: #fff; box-shadow: 0 4px 14px rgba(124,58,237,.3); }
        .btn-green:hover { box-shadow: 0 6px 20px rgba(124,58,237,.4); transform: translateY(-1px); }
        .btn-outline { background: #fff; border: 1.5px solid #E5E7EB; color: var(--gray-600); }
        .btn-outline:hover { border-color: #7C3AED; color: #7C3AED; background: #F5F3FF; }
        .btn-sm { padding: 7px 14px; font-size: 12px; border-radius: 10px; }
        .btn-md { padding: 10px 20px; }
        .btn-lg { padding: 14px 28px; font-size: 15px; border-radius: 14px; }

        /* ── Badges ── */
        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-violet  { background: #EDE9FE; color: #7C3AED; }
        .badge-green,
        .badge-red,
        .badge-amber,
        .badge-orange,
        .badge-blue,
        .badge-gray    { background: #EDE9FE; color: #6D28D9; }

        /* ── Form inputs ── */
        .inp {
            width: 100%; padding: 10px 16px; border-radius: 12px;
            border: 1.5px solid #E5E7EB; background: #FAFAFA;
            font-size: 13.5px; color: var(--gray-800);
            transition: all .2s ease; outline: none;
        }
        .inp:focus { border-color: #7C3AED; background: #fff; box-shadow: 0 0 0 3px rgba(124,58,237,.12); }
        .inp-label { font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-500); margin-bottom: 6px; display: block; }

        /* ── Table ── */
        .tbl thead th { background: #FAFAFA; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: var(--gray-400); padding: 13px 20px; border-bottom: 1px solid #F3F4F6; }
        .tbl tbody td { padding: 14px 20px; font-size: 13.5px; border-bottom: 1px solid #F9FAFB; }
        .tbl tbody tr:hover td { background: #F9F8FF; }
        .tbl tbody tr:last-child td { border-bottom: none; }

        /* ── Page animations ── */
        .page-enter { animation: pageEnter .3s ease; }
        @keyframes pageEnter { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }

        .fade-up { animation: fadeUp .4s ease both; }
        @keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }

        /* stagger delays */
        .delay-1 { animation-delay:.05s; }
        .delay-2 { animation-delay:.10s; }
        .delay-3 { animation-delay:.15s; }
        .delay-4 { animation-delay:.20s; }
        .delay-5 { animation-delay:.25s; }
        .delay-6 { animation-delay:.30s; }
        .delay-7 { animation-delay:.35s; }
        .delay-8 { animation-delay:.40s; }

        /* ── Pulse dot ── */
        .pulse-dot { animation: pulseDot 2.2s infinite; }
        @keyframes pulseDot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.3)} }

        /* ── Skeleton ── */
        .skeleton { background: linear-gradient(90deg,#F3F4F6 25%,#EDE9FE 50%,#F3F4F6 75%); background-size:200% 100%; animation:shimmerSkel 1.6s infinite; border-radius:8px; }
        @keyframes shimmerSkel { 0%{background-position:200%} 100%{background-position:-200%} }

        /* ── Modal ── */
        .modal-bg { backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); }

        /* ── Tooltip ── */
        [data-tip] { position: relative; }
        [data-tip]::after { content:attr(data-tip); position:absolute; left:calc(100% + 10px); top:50%; transform:translateY(-50%) translateX(-4px); background:rgba(17,24,39,.95); color:#fff; font-size:11px; font-weight:500; padding:5px 10px; border-radius:8px; white-space:nowrap; pointer-events:none; opacity:0; transition:all .2s ease; z-index:200; }
        [data-tip]:hover::after { opacity:1; transform:translateY(-50%) translateX(0); }

        /* ── Glassmorphism ── */
        .glass { background:rgba(255,255,255,.8); backdrop-filter:blur(20px) saturate(180%); -webkit-backdrop-filter:blur(20px) saturate(180%); border:1px solid rgba(255,255,255,.6); }

        /* ── Progress bar ── */
        .progress-bar { height:6px; border-radius:10px; background:#EDE9FE; overflow:hidden; }
        .progress-fill { height:100%; border-radius:10px; transition:width .8s ease; }

        /* ── Stat ring ── */
        .stat-ring { width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 4px 12px rgba(0,0,0,.12); }

        /* ── Icon ring (muted, neutral — reference style) ── */
        .icon-ring {
            width:44px; height:44px; border-radius:12px;
            display:flex; align-items:center; justify-content:center; flex-shrink:0;
            background:#F1F3F7;
            border:1px solid #E5E7EB;
            color:#8B96A8;
        }
        .icon-ring svg { width:20px; height:20px; stroke-width:1.6; }

        /* ── AI chat widget ── */
        /* Positioned inline on the component itself */

        /* ── Dark mode basics ── */
        .dark body { background:#0F0B1F; color:#E5E7EB; }
        .dark .card { background:#1A1433; border-color:rgba(124,58,237,.2); }
        .dark .tbl thead th { background:#16122B; }
        .dark .tbl tbody td { border-bottom-color:#1E1A35; }
        .dark .tbl tbody tr:hover td { background:#1A1433; }
    </style>
</head>
<body x-cloak>
<div class="flex h-screen overflow-hidden bg-[#F5F3FF]">

    {{-- ╔══════════ SIDEBAR ══════════╗ --}}
    @include('partials.sidebar')

    {{-- ╔══════════ MAIN AREA ══════════╗ --}}
    <div class="flex flex-col flex-1 overflow-hidden min-w-0">

        {{-- TOP NAV --}}
        @include('partials.navbar')

        {{-- PAGE CONTENT --}}
        <main class="flex-1 overflow-y-auto bg-[#F5F3FF] p-6">
            @include('partials.breadcrumb')
            <div class="page-enter">
                {{ $slot }}
            </div>
        </main>

        {{-- FOOTER --}}
        @include('partials.footer')
    </div>
</div>

{{-- ╔══════════ AI ASSISTANT WIDGET ══════════╗ --}}
@include('partials.ai-widget')

@stack('modals')
@stack('scripts')

<script>
function appState() {
    return {
        sidebarOpen: true,
        darkMode: false,
        aiOpen: false,
    }
}
Chart.defaults.font.family = 'Inter';
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.padding = 16;
</script>
</body>
</html>
