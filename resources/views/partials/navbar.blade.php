<header class="h-[68px] bg-white border-b border-[rgba(124,58,237,.07)] flex items-center justify-between px-6 flex-shrink-0 z-30 shadow-[0_1px_0_rgba(124,58,237,.06)]">

    {{-- Left: hamburger + search --}}
    <div class="flex items-center gap-4">
        <button @click="sidebarOpen = !sidebarOpen"
                class="p-2.5 rounded-xl hover:bg-[#F5F3FF] text-gray-500 hover:text-violet-600 transition-all active:scale-95">
            <svg class="w-5 h-5 transition-transform duration-300" :class="sidebarOpen ? '' : 'rotate-90'"
                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <div x-data="{ focused: false }" class="relative hidden md:flex items-center">
            <div :class="focused ? 'w-80 border-violet-400 bg-white shadow-md shadow-violet-100' : 'w-64 border-transparent bg-gray-100'"
                 class="flex items-center gap-2.5 rounded-2xl px-4 py-2.5 border-2 transition-all duration-300">
                <svg :class="focused ? 'text-violet-500' : 'text-gray-400'"
                     class="w-4 h-4 flex-shrink-0 transition-colors"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" placeholder="Search fruits, transactions, reports…"
                       @focus="focused=true" @blur="focused=false"
                       class="bg-transparent text-[13.5px] text-gray-700 outline-none w-full placeholder-gray-400">
                <kbd class="hidden lg:block text-[10px] text-gray-400 bg-gray-200 rounded-md px-1.5 py-0.5 font-mono flex-shrink-0">⌘K</kbd>
            </div>
        </div>
    </div>

    {{-- Right: actions --}}
    <div class="flex items-center gap-2" x-data="{ notif: false, profile: false, msgOpen: false }">

        {{-- Live Date --}}
        <div class="hidden xl:flex items-center gap-2 text-[12px] text-gray-500 bg-gray-100 rounded-xl px-3 py-2 font-medium select-none">
            <svg class="w-3.5 h-3.5 text-violet-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span x-data x-text="new Date().toLocaleDateString('en-PH',{month:'long',day:'numeric',year:'numeric'})"></span>
        </div>

        {{-- Dark mode --}}
        <button @click="darkMode = !darkMode"
                class="p-2.5 rounded-xl hover:bg-[#F5F3FF] text-gray-400 hover:text-violet-600 transition-all active:scale-95"
                :title="darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
            <svg x-show="!darkMode" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
            <svg x-show="darkMode" class="w-5 h-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        </button>

        {{-- Messages --}}
        <button @click="msgOpen = !msgOpen; notif = false; profile = false"
                class="relative p-2.5 rounded-xl hover:bg-[#F5F3FF] text-gray-400 hover:text-violet-600 transition-all active:scale-95"
                title="Messages">
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-violet-500 rounded-full ring-2 ring-white"></span>
        </button>

        {{-- Notifications --}}
        <div class="relative">
            <button @click="notif = !notif; profile = false; msgOpen = false"
                    class="relative p-2.5 rounded-xl hover:bg-[#F5F3FF] text-gray-400 hover:text-violet-600 transition-all active:scale-95"
                    title="Notifications">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white pulse-dot"></span>
            </button>

            <div x-show="notif" @click.outside="notif = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                 class="absolute right-0 top-14 w-88 card z-50 overflow-hidden" style="width:340px" x-cloak>

                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-gray-900 text-sm" style="font-family:Poppins,sans-serif">Notifications</h3>
                        <span class="badge badge-violet text-[10px]">5 new</span>
                    </div>
                    <button class="text-[11px] text-violet-600 font-semibold hover:underline">Mark all read</button>
                </div>

                <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                    @php
                    $notifs = [
                        ['bg-red-100 text-red-600',   'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',   'Critical',         'Pomelo stock critically low — only 8 kg remaining.',     '2 min ago',  'badge-red'],
                        ['bg-amber-100 text-amber-600','M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',   'Spoilage Alert',   'Durian batch DUR-112 has 78% spoilage risk.',             '15 min ago', 'badge-orange'],
                        ['bg-violet-100 text-violet-600','M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'Sales Forecast Ready', 'New 7-day demand forecast is available.',                '1 hr ago',   'badge-violet'],
                        ['bg-green-100 text-green-600','M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',                                                                         'Restock Complete',  'Mango batch MNG-003 (200kg) received.',                  '2 hrs ago',  'badge-green'],
                        ['bg-blue-100 text-blue-600',  'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'Sales Milestone', "Today's sales reached ₱18,000 target.",                 '3 hrs ago',  'badge-blue'],
                    ];
                    @endphp
                    @foreach($notifs as [$iconClass, $iconPath, $title, $body, $time, $badge])
                    <div class="flex items-start gap-3 px-5 py-3.5 hover:bg-[#FAFAFE] transition-colors cursor-pointer">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5 {{ $iconClass }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <p class="text-[12.5px] font-semibold text-gray-900">{{ $title }}</p>
                                <span class="badge {{ $badge }} text-[9px] px-1.5 py-0.5">new</span>
                            </div>
                            <p class="text-[12px] text-gray-500 leading-relaxed">{{ $body }}</p>
                            <p class="text-[11px] text-gray-400 mt-1 font-medium">{{ $time }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50 text-center">
                    <a href="{{ route('notifications') }}" class="text-[12px] text-violet-600 font-semibold hover:text-violet-700 transition-colors">
                        View all notifications →
                    </a>
                </div>
            </div>
        </div>

        <div class="w-px h-6 bg-gray-200 mx-1"></div>

        {{-- Profile --}}
        <div class="relative">
            <button @click="profile = !profile; notif = false; msgOpen = false"
                    class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-2xl hover:bg-[#F5F3FF] transition-all active:scale-95 group">
                <div class="w-8 h-8 g-violet rounded-full flex items-center justify-center text-white text-xs font-black shadow-md shadow-violet-200">JD</div>
                <div class="hidden lg:block text-left">
                    <p class="text-[12.5px] font-semibold text-gray-900 leading-tight">Criss Banawa</p>
                    <p class="text-[11px] text-violet-500 leading-tight">Owner</p>
                </div>
                <svg :class="profile ? 'rotate-180' : ''"
                     class="w-4 h-4 text-gray-400 transition-transform hidden lg:block"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="profile" @click.outside="profile = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                 class="absolute right-0 top-14 w-60 card z-50 overflow-hidden" x-cloak>

                {{-- Profile header --}}
                <div class="p-4 bg-gradient-to-br from-[#F5F3FF] to-[#EDE9FE] border-b border-violet-100">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 g-violet rounded-2xl flex items-center justify-center text-white font-black text-sm shadow-lg shadow-violet-200">JD</div>
                        <div>
                            <p class="font-bold text-gray-900 text-sm" style="font-family:Poppins,sans-serif">Criss Banawa</p>
                            <p class="text-[11.5px] text-gray-500">juan@FreshTrack.ph</p>
                            <div class="flex items-center gap-1.5 mt-1">
                                <svg class="w-3 h-3 text-violet-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 1a1 1 0 01.894.553l2.236 4.472 4.944.719a1 1 0 01.555 1.706l-3.576 3.486.844 4.92a1 1 0 01-1.45 1.054L10 15.347l-4.447 2.563a1 1 0 01-1.45-1.054l.844-4.92L1.371 8.45a1 1 0 01.555-1.706l4.944-.719L8.106 1.553A1 1 0 0110 1z" clip-rule="evenodd"/>
                                </svg>
                                <span class="badge badge-violet text-[10px] px-2 py-0.5">Owner</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Menu items --}}
                <div class="p-2">
                    @php
                    $profileMenu = [
                        ['settings',  'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'Account Settings', false],
                        ['users',     'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',                                                                                                                                                                                                                                                                                                                                                                                                                                                     'My Profile',       false],
                        ['notifications','M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',                                                                                                                                                                                                                                                                                                                    'Notifications',    false],
                    ];
                    @endphp
                    @foreach($profileMenu as [$r, $path, $label, $danger])
                    <a href="{{ route($r) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium transition-colors {{ $danger ? 'text-red-500 hover:bg-red-50 hover:text-red-600' : 'text-gray-600 hover:bg-[#F5F3FF] hover:text-violet-700' }}">
                        <svg class="w-4 h-4 flex-shrink-0 {{ $danger ? 'text-red-400' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/>
                        </svg>
                        {{ $label }}
                    </a>
                    @endforeach
                    <div class="my-1.5 border-t border-gray-100"></div>
                    <a href="{{ route('login') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium text-red-500 hover:bg-red-50 hover:text-red-600 transition-colors">
                        <svg class="w-4 h-4 flex-shrink-0 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Sign Out
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
