@php
$route = request()->route()->getName();
$user = auth()->user();

// Default to owner if no user is logged in (for testing)
if (!$user) {
    $user = (object)['role' => 'owner', 'name' => 'Guest'];
}

// Define navigation groups with role access
$navGroups = [
    'Overview' => [
        ['dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'Dashboard', ['owner']],
    ],
    'Commerce' => [
        ['pos', 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z', 'Point of Sale', ['owner', 'manager', 'cashier']],
        ['sales', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'Sales', ['owner', 'manager']],
        ['inventory', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'Inventory', ['owner', 'manager']],
        ['reports', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'Reports', ['owner', 'manager']],
    ],
    'AI & Analytics' => [
        ['forecast', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'Sales Forecasting', ['owner', 'manager']],
        ['spoilage', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'Spoilage', ['owner', 'manager']],
        ['analytics', 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'Analytics', ['owner', 'manager']],
    ],
    'System' => [
        ['users', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'User Management', ['owner']],
        ['settings', 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', 'Settings', ['owner', 'manager', 'cashier']],
        ['notifications', 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', 'Notifications', ['owner', 'manager', 'cashier']],
    ],
];

// Get user role display name
$roleNames = [
    'owner' => 'Owner · Administrator',
    'manager' => 'Manager · Operations',
    'cashier' => 'Cashier · Sales',
];
$userRole = $roleNames[$user->role ?? 'owner'] ?? 'User';
@endphp

<aside x-bind:class="sidebarOpen ? 'w-[260px]' : 'w-[72px]'"
       class="sidebar bg-white border-r border-[rgba(124,58,237,.08)] shadow-[2px_0_20px_rgba(124,58,237,.06)] flex flex-col h-screen flex-shrink-0 z-40 overflow-hidden">

    {{-- Logo --}}
    <div class="flex items-center h-[68px] px-4 border-b border-[rgba(124,58,237,.06)] flex-shrink-0">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 g-violet rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-violet-300/40">
                {{-- Leaf / nature icon for fruit brand --}}
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2z
                             M8.5 15.5c1-3 3-5 6-6.5
                             M12 20v-8"/>
                </svg>
            </div>
            <div x-show="sidebarOpen"
                 x-transition:enter="transition-opacity duration-200 delay-100"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity duration-100"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="min-w-0">
                <p class="font-black text-[15px] text-gray-900 leading-tight" style="font-family:Poppins,sans-serif">FreshTrack</p>
                <p class="text-[11px] text-violet-500 font-medium truncate">Davao City · AI Platform</p>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">
        @foreach($navGroups as $groupName => $items)
            @php
                // Check if group has any items accessible to current user
                $hasAccessibleItems = false;
                foreach($items as $item) {
                    $allowedRoles = $item[3] ?? ['owner', 'manager', 'cashier'];
                    if(in_array($user->role ?? 'owner', $allowedRoles)) {
                        $hasAccessibleItems = true;
                        break;
                    }
                }
            @endphp
            
            @if($hasAccessibleItems)
                <div x-show="sidebarOpen" x-transition class="nav-group">{{ $groupName }}</div>
                <div x-show="!sidebarOpen" class="h-3"></div>

                @foreach($items as [$r, $iconPath, $label, $allowedRoles])
                    @if(in_array($user->role ?? 'owner', $allowedRoles))
                        @php $active = $route === $r; @endphp
                        <a href="{{ route($r) }}" data-tip="{{ $label }}"
                           class="nav-link {{ $active ? 'active' : '' }}">
                            <svg class="w-[18px] h-[18px] flex-shrink-0 {{ $active ? 'text-white' : 'text-gray-400 group-hover:text-violet-600' }} transition-colors"
                                 fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" />
                            </svg>
                            <span x-show="sidebarOpen"
                                  x-transition:enter="transition-opacity duration-200 delay-75"
                                  x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                  x-transition:leave="transition-opacity duration-75"
                                  x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                  class="truncate text-[13.5px]">{{ $label }}</span>
                            @if($active)
                                <span x-show="sidebarOpen" class="ml-auto w-1.5 h-1.5 bg-white/60 rounded-full flex-shrink-0"></span>
                            @endif
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        <div class="pt-3 mt-2 border-t border-[rgba(124,58,237,.08)]">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" data-tip="Logout" class="nav-link text-red-500 hover:bg-red-50 hover:text-red-600 w-full">
                    <svg class="w-[18px] h-[18px] flex-shrink-0 text-red-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="truncate text-[13.5px]">Logout</span>
                </button>
            </form>
        </div>
    </nav>

    {{-- User card --}}
    <div x-show="sidebarOpen" x-transition class="border-t border-[rgba(124,58,237,.06)] p-3 flex-shrink-0">
        <div class="flex items-center gap-3 p-3 bg-[#F5F3FF] rounded-2xl cursor-pointer hover:bg-violet-100/60 transition-colors">
            <div class="w-9 h-9 g-violet rounded-full flex items-center justify-center text-white text-xs font-black flex-shrink-0 shadow-md shadow-violet-200">
                {{ strtoupper(substr($user->name ?? 'User', 0, 2)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[12.5px] font-semibold text-gray-900 truncate">{{ $user->name ?? 'User' }}</p>
                <p class="text-[11px] text-violet-500 truncate font-medium">{{ $userRole }}</p>
            </div>
            <div class="w-2 h-2 bg-green-400 rounded-full flex-shrink-0 pulse-dot ring-2 ring-white"></div>
        </div>
    </div>
    <div x-show="!sidebarOpen" class="border-t border-[rgba(124,58,237,.06)] p-3 flex justify-center flex-shrink-0">
        <div class="w-9 h-9 g-violet rounded-full flex items-center justify-center text-white text-xs font-black shadow-md shadow-violet-200">
            {{ strtoupper(substr($user->name ?? 'User', 0, 2)) }}
        </div>
    </div>
</aside>
