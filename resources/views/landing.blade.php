<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshTrack — AI-Powered Inventory & Sales Forecasting</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        *,*::before,*::after{box-sizing:border-box}
        body{font-family:'Inter',sans-serif;-webkit-font-smoothing:antialiased;background:#fff;color:#1F2937}
        h1,h2,h3,h4{font-family:'Poppins',sans-serif}
        .g-hero{background:linear-gradient(135deg,#7C3AED 0%,#6D28D9 50%,#4C1D95 100%)}
        .g-violet{background:linear-gradient(135deg,#7C3AED,#6D28D9)}
        .g-green{background:linear-gradient(135deg,#7C3AED,#6D28D9)}
        .hero-accent{color:#DDD6FE}
        .hero-dot{background:#C4B5FD}
        .hero-bar{background:rgba(196,181,253,.75)}
        .hero-pill{background:rgba(196,181,253,.2);color:#DDD6FE;border:1px solid rgba(196,181,253,.35)}
        .float-1{animation:float1 6s ease-in-out infinite}
        .float-2{animation:float2 8s ease-in-out infinite}
        .float-3{animation:float3 7s ease-in-out infinite}
        @keyframes float1{0%,100%{transform:translateY(0) rotate(0deg)}50%{transform:translateY(-20px) rotate(5deg)}}
        @keyframes float2{0%,100%{transform:translateY(0) rotate(0deg)}50%{transform:translateY(-15px) rotate(-5deg)}}
        @keyframes float3{0%,100%{transform:translateY(0) rotate(0deg)}50%{transform:translateY(-25px) rotate(3deg)}}
        .hero-card{background:rgba(255,255,255,.12);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.2);border-radius:20px}
        .feature-card{background:#fff;border:1px solid rgba(124,58,237,.08);border-radius:24px;box-shadow:0 2px 16px rgba(124,58,237,.06);transition:all .3s ease}
        .feature-card:hover{transform:translateY(-6px);box-shadow:0 16px 40px rgba(124,58,237,.14)}
        .stat-card{background:rgba(255,255,255,.1);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:24px}
        .btn-hero{display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border-radius:14px;font-weight:700;font-size:15px;transition:all .2s ease;cursor:pointer;border:none;font-family:'Poppins',sans-serif}
        .btn-hero:hover{transform:translateY(-2px)}
        .btn-hero:active{transform:scale(.97)}
    </style>
</head>
<body>

{{-- NAV --}}
<nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-xl border-b border-violet-100/50 shadow-sm">
    <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 g-violet rounded-xl flex items-center justify-center shadow-lg shadow-violet-300/40"><svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>
            <div>
                <p class="font-black text-gray-900 text-[15px]" style="font-family:Poppins,sans-serif">FreshTrack</p>
                <p class="text-[10px] text-violet-500 font-semibold -mt-0.5">AI-Powered Platform</p>
            </div>
        </div>
        <div class="hidden md:flex items-center gap-8 text-[13.5px] font-semibold text-gray-600">
            <a href="#features" class="hover:text-violet-600 transition-colors">Features</a>
            <a href="#stats" class="hover:text-violet-600 transition-colors">Statistics</a>
            <a href="#about" class="hover:text-violet-600 transition-colors">About</a>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('login') }}" class="btn-hero bg-white border-2 border-violet-200 text-violet-700 px-5 py-2.5 text-[13.5px]">Sign In</a>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn-hero g-violet text-white shadow-lg shadow-violet-300/50 px-5 py-2.5 text-[13.5px]">
                {{ auth()->check() ? 'View Dashboard →' : 'Sign In to Dashboard →' }}
            </a>
        </div>
    </div>
</nav>

{{-- HERO --}}
<section class="g-hero min-h-screen flex items-center pt-16 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle,rgba(255,255,255,.8) 1px,transparent 1px);background-size:40px 40px"></div>
    <div class="absolute top-20 right-20 text-8xl float-1 opacity-20 select-none">🥭</div>
    <div class="absolute top-40 right-60 text-6xl float-2 opacity-20 select-none">🍍</div>
    <div class="absolute bottom-32 right-32 text-7xl float-3 opacity-20 select-none">🍌</div>
    <div class="absolute top-60 left-20 text-5xl float-2 opacity-15 select-none">🍈</div>
    <div class="absolute bottom-40 left-40 text-6xl float-1 opacity-15 select-none" style="animation-delay:2s">🍊</div>
    <div class="absolute top-32 left-1/2 text-4xl float-3 opacity-10 select-none">🫐</div>

    <div class="max-w-7xl mx-auto px-6 py-20 w-full">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div>
                <div class="inline-flex items-center gap-2 bg-white/15 backdrop-blur-sm border border-white/20 rounded-full px-4 py-2 mb-6">
                    <span class="w-2 h-2 hero-dot rounded-full pulse-dot" style="animation:pulseDot 2.2s infinite"></span>
                    <span class="text-white/90 text-[12.5px] font-semibold">AI-Powered · Davao City · 2026</span>
                </div>
                <h1 class="text-5xl lg:text-6xl font-black text-white leading-tight mb-6">
                    Sales Forecasting<br>
                    <span class="hero-accent">&amp; Inventory</span><br>
                    Management
                </h1>
                <p class="text-white/80 text-[17px] leading-relaxed mb-8 max-w-lg">
                    AI-powered decision support system for fruit vendors in Davao City. Predict spoilage, forecast demand, and optimize profits with real-time intelligence.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('login') }}" class="btn-hero bg-white text-violet-700 shadow-xl shadow-violet-900/30">
                        Get Started Free
                    </a>
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn-hero bg-white/15 backdrop-blur-sm border border-white/30 text-white hover:bg-white/25">
                        View Live Demo →
                    </a>
                </div>
                <div class="flex items-center gap-6 mt-10">
                    @foreach([['96%','Forecast Accuracy'],['42+','Fruit Batches'],['₱342K','Monthly Revenue']] as [$v,$l])
                    <div class="text-center">
                        <p class="text-3xl font-black hero-accent">{{ $v }}</p>
                        <p class="text-white/60 text-[11.5px] font-medium mt-0.5">{{ $l }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="hidden lg:block">
                <div class="hero-card p-6 mb-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center"><svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>
                        <div>
                            <p class="text-white font-bold text-sm">AI Sales Forecast</p>
                            <p class="text-white/60 text-[11px]">Next 7 days prediction</p>
                        </div>
                        <span class="ml-auto hero-pill text-[10px] font-bold px-2.5 py-1 rounded-full">96.4% ACC</span>
                    </div>
                    <div class="grid grid-cols-7 gap-1.5">
                        @foreach([65,72,68,85,92,88,79] as $h)
                        <div class="flex flex-col items-center gap-1">
                            <div class="w-full bg-white/10 rounded-lg overflow-hidden" style="height:80px">
                                <div class="w-full hero-bar rounded-lg transition-all" style="height:{{ $h }}%;margin-top:{{ 100-$h }}%"></div>
                            </div>
                            <span class="text-white/50 text-[9px]">D{{ $loop->index+1 }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="hero-card p-4">
                        <p class="text-white/60 text-[11px] font-medium mb-1">Today's Sales</p>
                        <p class="text-white font-black text-2xl">₱18,450</p>
                        <p class="hero-accent text-[11px] font-semibold mt-1">↑ +12.5% vs yesterday</p>
                    </div>
                    <div class="hero-card p-4">
                        <p class="text-white/60 text-[11px] font-medium mb-1">Spoilage Risk</p>
                        <p class="text-white font-black text-2xl">8.4%</p>
                        <p class="hero-accent text-[11px] font-semibold mt-1">↓ -2.1% improvement</p>
                    </div>
                    <div class="hero-card p-4">
                        <p class="text-white/60 text-[11px] font-medium mb-1">Inventory</p>
                        <p class="text-white font-black text-2xl">1,240 kg</p>
                        <p class="text-white/50 text-[11px] mt-1">7 fruit varieties</p>
                    </div>
                    <div class="hero-card p-4">
                        <p class="text-white/60 text-[11px] font-medium mb-1">AI Score</p>
                        <p class="text-white font-black text-2xl">94/100</p>
                        <p class="hero-accent text-[11px] font-semibold mt-1">↑ +3pts this week</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- FEATURES --}}
<section id="features" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
            <span class="inline-flex items-center gap-2 bg-violet-50 text-violet-700 text-[12px] font-bold px-4 py-2 rounded-full border border-violet-200 mb-4">Powered by Artificial Intelligence</span>
            <h2 class="text-4xl font-black text-gray-900 mb-4">Everything You Need to Run<br><span class="text-violet-600">a Smarter Fruit Business</span></h2>
            <p class="text-gray-500 text-[16px] max-w-2xl mx-auto">From real-time inventory tracking to AI-powered sales forecasting — FreshTrack gives Davao fruit vendors an enterprise-grade edge.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @php
            $features = [
                ['M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z','AI Sales Forecast','Predict fruit demand 7 days ahead with 96%+ accuracy using machine learning models trained on local market data.'],
                ['M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4','Smart Inventory','Real-time batch tracking with expiry alerts, stock levels, and automated restock recommendations.'],
                ['M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z','Spoilage Prediction','AI-powered spoilage risk analysis using temperature, humidity, and CO₂ sensor data.'],
                ['M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z','Decision Support','Actionable recommendations: sell now, apply discounts, reorder, and optimize pricing for maximum profit.'],
                ['M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z','Analytics Dashboard','Executive-grade charts: revenue trends, forecast accuracy, waste reduction, and profit margins.'],
            ];
            @endphp
            @foreach($features as [$iconPath,$title,$desc])
            <div class="feature-card p-7">
                <div class="w-12 h-12 bg-gray-100 border border-gray-200 rounded-2xl flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
                    </svg>
                </div>
                <h3 class="text-[17px] font-bold text-gray-900 mb-2">{{ $title }}</h3>
                <p class="text-gray-500 text-[13.5px] leading-relaxed">{{ $desc }}</p>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-violet-600 text-[12.5px] font-semibold mt-4 hover:gap-2 transition-all">
                    Explore feature
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- STATS --}}
<section id="stats" class="py-24 g-hero relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle,rgba(255,255,255,.6) 1px,transparent 1px);background-size:32px 32px"></div>
    <div class="max-w-7xl mx-auto px-6 relative">
        <div class="text-center mb-14">
            <h2 class="text-4xl font-black text-white mb-3">Real Results for Davao Fruit Vendors</h2>
            <p class="text-white/70 text-[15px]">Measurable impact backed by AI precision</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach([['96.4%','Forecast Accuracy','Across all fruit categories'],['↓62%','Spoilage Reduction','AI-guided sell priorities'],['₱342K','Monthly Revenue','Tracked & optimized'],['8 hrs','Time Saved Daily','Automated decisions']] as [$v,$l,$d])
            <div class="stat-card text-center">
                <p class="text-4xl font-black hero-accent mb-2">{{ $v }}</p>
                <p class="text-white font-bold text-[15px]">{{ $l }}</p>
                <p class="text-white/50 text-[12px] mt-1">{{ $d }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- FRUIT SHOWCASE --}}
<section class="py-20 bg-[#F5F3FF]">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-black text-gray-900 mb-3">Fruits We <span class="text-violet-600">Track &amp; Analyze</span></h2>
            <p class="text-gray-500 text-[14px]">Local Davao City varieties with full AI analysis support</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4">
            @foreach([['🥭','Mango','₱125/kg','96%'],['🍑','Durian','₱320/kg','94%'],['🍈','Pomelo','₱70/kg','98%'],['🍊','Mangosteen','₱170/kg','97%'],['🫐','Lanzones','₱85/kg','95%'],['🍌','Banana','₱42/kg','96%'],['🍍','Pineapple','₱75/kg','93%']] as [$e,$n,$p,$a])
            <div class="bg-white rounded-2xl p-5 text-center border border-violet-100 hover:border-violet-300 hover:shadow-lg hover:shadow-violet-100 transition-all cursor-pointer group">
                <div class="w-12 h-12 bg-gray-50 border border-gray-100 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:bg-violet-50 group-hover:border-violet-200 transition-all"><svg class="w-6 h-6 text-gray-400 group-hover:text-violet-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>
                <p class="font-bold text-gray-900 text-[13px]">{{ $n }}</p>
                <p class="text-violet-600 text-[11.5px] font-semibold">{{ $p }}</p>
                <div class="mt-2 bg-violet-100 text-violet-700 text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $a }} acc.</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section id="about" class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-6 text-center">
        <div class="bg-gradient-to-br from-[#F5F3FF] to-[#EDE9FE] rounded-3xl p-12 border border-violet-200">
            <div class="w-16 h-16 g-violet rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-violet-300/40"><svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>
            <h2 class="text-4xl font-black text-gray-900 mb-4">Ready to Transform Your<br><span class="text-violet-600">Fruit Business?</span></h2>
            <p class="text-gray-500 text-[15px] mb-8 max-w-xl mx-auto">Join the future of fruit retail. AI-powered forecasting, real-time spoilage alerts, and smart inventory management — all in one platform.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('login') }}" class="btn-hero g-violet text-white shadow-xl shadow-violet-300/50 text-[15px] px-8 py-4">Start Free Trial</a>
                <a href="{{ route('dashboard') }}" class="btn-hero bg-white border-2 border-violet-200 text-violet-700 text-[15px] px-8 py-4">📊 Explore Demo</a>
            </div>
        </div>
    </div>
</section>

{{-- FOOTER --}}
<footer class="bg-gray-900 text-white py-12">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-10">
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 g-violet rounded-xl flex items-center justify-center"><svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>
                    <div>
                        <p class="font-black text-[15px]" style="font-family:Poppins,sans-serif">FreshTrack</p>
                        <p class="text-violet-400 text-[10px] font-semibold">AI-Powered Platform</p>
                    </div>
                </div>
                <p class="text-gray-400 text-[13px] leading-relaxed">Capstone project — Sales Forecasting & Inventory Management System for Fruit Vendors in Davao City.</p>
            </div>
            @foreach([['Platform',['Dashboard','Inventory','Sales Forecasting','Spoilage','Decision Support']],['Analytics',['Sales Reports','Analytics','Notifications','Settings','About']]] as [$g,$links])
            <div>
                <h4 class="font-bold text-[13px] text-gray-300 uppercase tracking-wider mb-4">{{ $g }}</h4>
                <ul class="space-y-2">
                    @foreach($links as $l)
                    <li><a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-violet-400 text-[13px] transition-colors">{{ $l }}</a></li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
        <div class="border-t border-gray-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-gray-500 text-[12.5px]">© 2026 FreshTrack — Capstone Project · All rights reserved · Davao City, Philippines</p>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 hero-dot rounded-full" style="animation:pulseDot 2.2s infinite"></span>
                <span class="hero-accent text-[12px] font-semibold">System Online</span>
            </div>
        </div>
    </div>
</footer>

<style>
@keyframes pulseDot{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(1.3)}}
</style>
</body>
</html>
