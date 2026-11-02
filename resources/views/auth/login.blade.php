<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — FreshTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        *,*::before,*::after{box-sizing:border-box}
        body{font-family:'Inter',sans-serif;-webkit-font-smoothing:antialiased}
        h1,h2,h3{font-family:'Poppins',sans-serif}
        [x-cloak]{display:none!important}
        .g-violet{background:linear-gradient(135deg,#7C3AED,#6D28D9)}
        .g-hero{background:linear-gradient(160deg,#7C3AED 0%,#6D28D9 55%,#4C1D95 100%)!important}
        .hero-accent{color:#DDD6FE!important}
        .hero-dot{background:#C4B5FD!important}
        .hero-orb{background:rgba(167,139,250,.28)!important}
        .float-a{animation:fa 7s ease-in-out infinite}
        .float-b{animation:fb 9s ease-in-out infinite}
        .float-c{animation:fc 8s ease-in-out infinite}
        @keyframes fa{0%,100%{transform:translateY(0) rotate(0deg)}50%{transform:translateY(-22px) rotate(6deg)}}
        @keyframes fb{0%,100%{transform:translateY(0)}50%{transform:translateY(-16px) rotate(-5deg)}}
        @keyframes fc{0%,100%{transform:translateY(0)}50%{transform:translateY(-28px) rotate(4deg)}}
        .card-enter{animation:cardIn .6s cubic-bezier(.175,.885,.32,1.275) both}
        @keyframes cardIn{from{opacity:0;transform:translateX(32px) scale(.97)}to{opacity:1;transform:translateX(0) scale(1)}}
        .inp{width:100%;padding:13px 16px 13px 48px;border-radius:16px;border:2px solid #E5E7EB;background:#FAFAFA;font-size:14.5px;color:#1F2937;transition:all .2s ease;outline:none}
        .inp:focus{border-color:#7C3AED;background:#fff;box-shadow:0 0 0 4px rgba(124,58,237,.1)}
        .btn-login{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:16px;border-radius:16px;font-weight:700;font-size:15px;color:#fff;cursor:pointer;border:none;transition:all .2s ease;background:linear-gradient(135deg,#7C3AED,#6D28D9);box-shadow:0 8px 24px rgba(124,58,237,.4);font-family:'Poppins',sans-serif}
        .btn-login:hover{box-shadow:0 12px 32px rgba(124,58,237,.5);transform:translateY(-2px)}
        .btn-login:active{transform:scale(.98)}
        .pulse-dot{animation:pd 2.2s infinite}
        @keyframes pd{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(1.3)}}
        .shimmer{position:relative;overflow:hidden}
        .shimmer::after{content:'';position:absolute;top:0;right:0;bottom:0;left:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.4),transparent);animation:shimmer 3s infinite}
        @keyframes shimmer{0%{transform:translateX(-100%)}100%{transform:translateX(100%)}}
        .role-btn{transition:all .3s cubic-bezier(.4,0,.2,1)}
        .role-btn:hover{transform:scale(1.02)}
        .role-active{animation:roleActive .4s cubic-bezier(.175,.885,.32,1.275)}
        @keyframes roleActive{0%{transform:scale(.95)}50%{transform:scale(1.02)}100%{transform:scale(1)}}
        .fade-up{animation:fadeUp .6s ease both}
        @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
        .glow-sm{box-shadow:0 0 20px rgba(124,58,237,.25)}
        .metric-card{transition:all .3s ease}
        .metric-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(255,255,255,.15)}
    </style>
</head>
<body class="h-full">
<div class="min-h-screen flex">

    {{-- LEFT: Illustration panel --}}
    <div class="hidden lg:flex lg:w-1/2 g-hero relative overflow-hidden flex-col justify-between p-12">
        {{-- Animated background grid --}}
        <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle,rgba(255,255,255,.7) 1px,transparent 1px);background-size:36px 36px"></div>
        
        {{-- Floating orbs --}}
        <div class="absolute top-20 left-20 w-72 h-72 hero-orb rounded-full blur-3xl float-a"></div>
        <div class="absolute bottom-20 right-20 w-96 h-96 bg-violet-400/20 rounded-full blur-3xl float-b"></div>
        <div class="absolute top-1/2 left-1/3 w-64 h-64 bg-white/10 rounded-full blur-2xl float-c"></div>

        {{-- Logo --}}
        <div class="relative z-10 fade-up">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm border border-white/30 shadow-2xl glow-sm">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-white font-black text-2xl" style="font-family:Poppins,sans-serif">FreshTrack</p>
                    <p class="text-white/70 text-xs font-semibold tracking-wide">AI-POWERED PLATFORM</p>
                </div>
            </div>
        </div>

        {{-- Hero content --}}
        <div class="relative z-10 fade-up" style="animation-delay:.2s">
            <h1 class="text-5xl font-black text-white leading-tight mb-5">
                The Smartest Way<br>to Manage Your<br><span class="hero-accent shimmer">Fruit Business</span>
            </h1>
            <p class="text-white/80 text-base leading-relaxed mb-10 max-w-md">AI-powered sales forecasting, real-time spoilage prediction, and intelligent inventory management — built for Davao City fruit vendors.</p>

            {{-- Metrics --}}
            <div class="grid grid-cols-3 gap-4 mb-8">
                @foreach([
                    ['96%', 'Forecast Accuracy', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['↓62%', 'Less Spoilage', 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3'],
                    ['₱342K', 'Revenue/Month', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z']
                ] as $i => [$v, $l, $icon])
                <div class="metric-card bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5 text-center" style="animation-delay:{{ $i * 0.1 }}s">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                        </svg>
                    </div>
                    <p class="hero-accent font-black text-2xl mb-1">{{ $v }}</p>
                    <p class="text-white/70 text-xs font-semibold">{{ $l }}</p>
                </div>
                @endforeach
            </div>

            {{-- Feature highlights --}}
            <div class="space-y-3">
                @foreach([
                    ['Real-time inventory tracking', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                    ['AI demand forecasting', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ['Smart spoilage alerts', 'M13 10V3L4 14h7v7l9-11h-7z']
                ] as $i => [$text, $icon])
                <div class="flex items-center gap-3 fade-up" style="animation-delay:{{ 0.4 + ($i * 0.1) }}s">
                    <div class="w-10 h-10 bg-white/10 backdrop-blur-sm rounded-xl flex items-center justify-center border border-white/20">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                        </svg>
                    </div>
                    <span class="text-white/90 text-sm font-medium">{{ $text }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Footer --}}
        <div class="relative z-10 fade-up" style="animation-delay:.6s">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 hero-dot rounded-full pulse-dot"></span>
                <span class="text-white/70 text-xs font-semibold">System Online · Davao City · 2026</span>
            </div>
        </div>
    </div>

    {{-- RIGHT: Login form --}}
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-gradient-to-br from-gray-50 to-white">
        <div class="w-full max-w-md card-enter">

            {{-- Mobile logo --}}
            <div class="flex lg:hidden items-center gap-3 mb-10 justify-center">
                <div class="w-12 h-12 g-violet rounded-2xl flex items-center justify-center shadow-xl shadow-violet-300/50">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <p class="font-black text-lg text-gray-900" style="font-family:Poppins,sans-serif">FreshTrack</p>
                    <p class="text-violet-600 text-xs font-bold">AI-Powered Platform</p>
                </div>
            </div>

            <div class="mb-8">
                <h2 class="text-4xl font-black text-gray-900 mb-3">Welcome back</h2>
                <p class="text-gray-600 text-base">Sign in to your FreshTrack dashboard</p>
            </div>

            {{-- Login Form --}}
            <form method="POST" action="{{ route('login.post') }}" x-data="{ showPass: false, loading: false, activeRole: 0, email: '{{ old('email', 'owner@FreshTrack.ph') }}' }" @submit="loading=true">
                @csrf

                {{-- Role selector fills the matching demo email --}}
                <div class="grid grid-cols-3 gap-3 mb-8 p-1.5 bg-gray-100 rounded-2xl">
                    @foreach([
                        ['Owner', 'owner@FreshTrack.ph', 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['Manager', 'manager@FreshTrack.ph', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['Cashier', 'cashier@FreshTrack.ph', 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z']
                    ] as $i => [$role, $email, $icon])
                    <button type="button" @click="activeRole={{ $i }}; email='{{ $email }}'" 
                            :class="activeRole==={{ $i }} ? 'bg-white shadow-lg text-violet-700 font-bold scale-105 role-active' : 'text-gray-600 hover:text-gray-900'"
                            class="role-btn flex flex-col items-center gap-2 py-3.5 rounded-xl text-xs font-semibold transition-all">
                        <div :class="activeRole==={{ $i }} ? 'bg-violet-100' : 'bg-gray-200'" class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors">
                            <svg class="w-4 h-4" :class="activeRole==={{ $i }} ? 'text-violet-700' : 'text-gray-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                            </svg>
                        </div>
                        {{ $role }}
                    </button>
                    @endforeach
                </div>

                {{-- Validation Errors --}}
                @if ($errors->any())
                    <div class="mb-5 bg-red-50 border-2 border-red-200 rounded-2xl p-4">
                        <div class="flex items-center gap-2 text-red-700">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm font-bold">{{ $errors->first() }}</span>
                        </div>
                    </div>
                @endif

                {{-- Email --}}
                <div class="mb-5">
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2.5">Email Address</label>
                    <div class="relative group">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-violet-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <input type="email" name="email" x-model="email" placeholder="your@email.com" required class="inp">
                    </div>
                </div>

                {{-- Password --}}
                <div class="mb-6">
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2.5">Password</label>
                    <div class="relative group">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-violet-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <input :type="showPass ? 'text' : 'password'" name="password" value="password" required class="inp" style="padding-right:52px">
                        <button type="button" @click="showPass=!showPass" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-violet-600 transition-colors p-1 rounded-lg hover:bg-violet-50">
                            <svg x-show="!showPass" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPass" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-8">
                    <label class="flex items-center gap-2.5 cursor-pointer group">
                        <input type="checkbox" name="remember" checked class="w-4.5 h-4.5 rounded-lg border-2 border-gray-300 accent-violet-600 cursor-pointer">
                        <span class="text-sm text-gray-700 font-medium group-hover:text-gray-900 transition-colors">Keep me signed in</span>
                    </label>
                    <a href="#" class="text-sm text-violet-600 font-bold hover:text-violet-700 transition-colors hover:underline">Forgot password?</a>
                </div>

                <button type="submit" class="btn-login mb-6">
                    <span x-show="!loading" class="flex items-center gap-2">
                        Sign In to Dashboard
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </span>
                    <span x-show="loading" x-cloak class="flex items-center gap-2">
                        <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                        Authenticating…
                    </span>
                </button>
            </form>

            {{-- Demo credentials --}}
            <div class="bg-gradient-to-br from-violet-50 via-purple-50 to-violet-50 rounded-2xl p-5 border-2 border-violet-100 shadow-lg">
                <div class="flex items-center gap-2.5 mb-4">
                    <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    <p class="text-sm font-black text-gray-800">Demo Credentials</p>
                    <span class="ml-auto text-xs bg-violet-600 text-white font-bold px-3 py-1 rounded-full shadow-sm">Prototype</span>
                </div>
                <div class="grid grid-cols-3 gap-3 text-xs mb-4">
                    @foreach([
                        ['Owner', 'owner@FreshTrack.ph', 'Full access', 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'text-violet-600', 'bg-violet-100'],
                        ['Manager', 'manager@FreshTrack.ph', 'Limited', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'text-blue-600', 'bg-blue-100'],
                        ['Cashier', 'cashier@FreshTrack.ph', 'Sales only', 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'text-violet-600', 'bg-violet-100']
                    ] as [$r, $e, $a, $icon, $color, $bg])
                    <div class="bg-white rounded-xl p-3 border border-violet-200 text-center hover:shadow-md transition-shadow">
                        <div class="w-10 h-10 {{ $bg }} rounded-xl flex items-center justify-center mx-auto mb-2">
                            <svg class="w-5 h-5 {{ $color }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                            </svg>
                        </div>
                        <p class="font-bold text-gray-800 text-xs mb-1">{{ $r }}</p>
                        <p class="text-violet-600 font-mono text-[10px] mb-1 break-all">{{ $e }}</p>
                        <p class="text-gray-500 text-[10px] font-medium">{{ $a }}</p>
                    </div>
                    @endforeach
                </div>
                <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 border border-violet-200">
                    <p class="text-center text-xs text-gray-600 font-medium">
                        All accounts use password: <span class="font-mono font-black text-violet-700 bg-violet-100 px-2 py-0.5 rounded">password</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
