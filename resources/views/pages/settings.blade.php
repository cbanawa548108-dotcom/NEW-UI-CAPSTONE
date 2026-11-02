<x-app-layout>
<x-slot name="title">Settings — FreshTrack</x-slot>

<div class="flex items-center justify-between mb-7 fade-up">
    <div><h1 class="text-[26px] font-black text-gray-900">Settings</h1><p class="text-[13.5px] text-gray-500 mt-0.5">Manage your account, system preferences, and notifications</p></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 fade-up delay-1"
     x-data="{ tab:'profile', notifEmail:true, notifSMS:false, notifPush:true, notifLow:true, notifSpoil:true, notifForecast:true, darkMode:false }">

    {{-- Sidebar nav --}}
    <div class="card p-3 h-fit lg:sticky lg:top-6">
    @foreach([['profile','user','Profile Settings'],['notifications','bell','Notifications'],['appearance','palette','Appearance'],['language','globe','Language & Region'],['backup','save','Data Backup'],['system','cog','System Info']] as [$t,$i,$l])
        <button @click="tab='{{ $t }}'"
                :class="tab==='{{ $t }}' ? 'g-violet text-white shadow-md' : 'text-gray-600 hover:bg-[#F5F3FF] hover:text-violet-700'"
                class="w-full flex items-center gap-3 text-left px-4 py-3 rounded-xl text-[13.5px] font-semibold mb-1 transition-all">
            @if($i==='user')<svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            @elseif($i==='bell')<svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            @elseif($i==='palette')<svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
            @elseif($i==='globe')<svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @elseif($i==='save')<svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
            @elseif($i==='cog')<svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            @endif
            {{ $l }}
        </button>
        @endforeach
    </div>

    <div class="lg:col-span-3 space-y-5">

        {{-- Profile --}}
        <div x-show="tab==='profile'" class="card overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100"><h3 class="font-bold text-gray-900 text-[16px]">Profile Settings</h3><p class="text-[12px] text-gray-400 mt-0.5">Update your personal information and account details</p></div>
            <div class="p-6">
                <div class="flex items-center gap-6 mb-8">
                    <div class="relative">
                        <div class="w-20 h-20 g-violet rounded-2xl flex items-center justify-center text-white text-2xl font-black shadow-xl shadow-violet-200">JD</div>
                        <button class="absolute -bottom-2 -right-2 w-7 h-7 g-blue rounded-full flex items-center justify-center text-white shadow-md">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                    </div>
                    <div>
                        <h4 class="font-black text-gray-900 text-[18px]">Criss Banawa</h4>
                        <p class="text-gray-500 text-[13px] mt-0.5">Owner · FreshTrack Davao City</p>
                        <span class="badge badge-violet text-[11px] mt-2">Administrator</span>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach([['First Name','text','Criss',''],['Last Name','text','Banawa',''],['Email Address','email','juan@FreshTrack.ph','md:col-span-2'],['Phone Number','tel','+63 912 345 6789',''],['Business Name','text','FreshTrack Davao',''],['Location','text','Davao City, Philippines',''],['Role','text','Owner · Administrator',''],['Account Created','text','January 15, 2026','md:col-span-2']] as [$l,$t,$v,$c])
                    <div class="{{ $c }}"><label class="inp-label">{{ $l }}</label><input type="{{ $t }}" value="{{ $v }}" class="inp {{ in_array($l,['Role','Account Created']) ? 'bg-gray-100 cursor-not-allowed' : '' }}" {{ in_array($l,['Role','Account Created']) ? 'readonly' : '' }}></div>
                    @endforeach
                </div>
                <div class="flex justify-end mt-6"><button class="btn btn-violet btn-md text-[13px]"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Save Changes</button></div>
            </div>
        </div>

        {{-- Notifications --}}
        <div x-show="tab==='notifications'" class="card overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100"><h3 class="font-bold text-gray-900 text-[16px]">Notification Preferences</h3><p class="text-[12px] text-gray-400 mt-0.5">Choose how and when you receive alerts</p></div>
            <div class="p-6 space-y-6">
                <div>
                    <h4 class="text-[12px] font-bold text-gray-500 uppercase tracking-wider mb-3">Notification Channels</h4>
                    <div class="space-y-3">
                        @foreach([['notifEmail','Email Notifications','Receive alerts and reports via email','notifEmail'],['notifSMS','SMS Notifications','Receive urgent alerts via SMS','notifSMS'],['notifPush','Push Notifications','In-app push notifications','notifPush']] as $n)
                        <div class="flex items-center justify-between p-4 bg-[#F9F8FF] rounded-2xl border border-violet-100">
                            <div><p class="text-[13.5px] font-semibold text-gray-800">{{ $n[1] }}</p><p class="text-[12px] text-gray-400 mt-0.5">{{ $n[2] }}</p></div>
                            <button @click="{{ $n[3] }} = !{{ $n[3] }}" :class="{{ $n[3] }} ? 'g-violet' : 'bg-gray-300'" class="relative w-12 h-6 rounded-full transition-all duration-200 flex-shrink-0 shadow-inner">
                                <span :class="{{ $n[3] }} ? 'translate-x-6' : 'translate-x-1'" class="absolute top-1 w-4 h-4 bg-white rounded-full shadow-md transition-transform duration-200"></span>
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h4 class="text-[12px] font-bold text-gray-500 uppercase tracking-wider mb-3">Alert Types</h4>
                    <div class="space-y-3">
                        @foreach([['notifLow','Low Stock Alerts','Alert when fruit stock falls below minimum level','notifLow'],['notifSpoil','Spoilage Risk Alerts','Alert when spoilage risk exceeds threshold','notifSpoil'],['notifForecast','Sales Forecast Updates','Alert when new sales forecast is ready','notifForecast']] as $n)
                        <div class="flex items-center justify-between p-4 bg-[#F9F8FF] rounded-2xl border border-violet-100">
                            <div><p class="text-[13.5px] font-semibold text-gray-800">{{ $n[1] }}</p><p class="text-[12px] text-gray-400 mt-0.5">{{ $n[2] }}</p></div>
                            <button @click="{{ $n[3] }} = !{{ $n[3] }}" :class="{{ $n[3] }} ? 'g-violet' : 'bg-gray-300'" class="relative w-12 h-6 rounded-full transition-all duration-200 flex-shrink-0 shadow-inner">
                                <span :class="{{ $n[3] }} ? 'translate-x-6' : 'translate-x-1'" class="absolute top-1 w-4 h-4 bg-white rounded-full shadow-md transition-transform duration-200"></span>
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Appearance --}}
        <div x-show="tab==='appearance'" class="card overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100"><h3 class="font-bold text-gray-900 text-[16px]">Appearance</h3><p class="text-[12px] text-gray-400 mt-0.5">Customize the look and feel of FreshTrack</p></div>
            <div class="p-6 space-y-6">
                <div class="flex items-center justify-between p-4 bg-[#F9F8FF] rounded-2xl border border-violet-100">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <div><p class="font-semibold text-gray-800 text-[13.5px]">Dark Mode</p><p class="text-[12px] text-gray-400">Switch to dark theme</p></div>
                    </div>
                    <button @click="darkMode=!darkMode" :class="darkMode ? 'g-violet' : 'bg-gray-300'" class="relative w-12 h-6 rounded-full transition-all duration-200 shadow-inner"><span :class="darkMode ? 'translate-x-6' : 'translate-x-1'" class="absolute top-1 w-4 h-4 bg-white rounded-full shadow-md transition-transform duration-200"></span></button>
                </div>
                <div>
                    <p class="text-[13px] font-bold text-gray-700 mb-3">Color Theme</p>
                    <div class="grid grid-cols-5 gap-3">
                        @foreach([['bg-violet-600','Violet',true],['bg-blue-600','Blue',false],['bg-green-600','Green',false],['bg-orange-500','Orange',false],['bg-teal-600','Teal',false]] as [$bg,$l,$sel])
                        <button class="flex flex-col items-center gap-2 group">
                            <div class="w-12 h-12 {{ $bg }} rounded-2xl shadow-md group-hover:scale-110 transition-transform {{ $sel ? 'ring-3 ring-offset-2 ring-violet-500' : '' }}"></div>
                            <span class="text-[11.5px] text-gray-500 font-medium">{{ $l }}</span>
                        </button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="text-[13px] font-bold text-gray-700 mb-3">Font Size</p>
                    <div class="flex gap-3">
                        @foreach(['Small','Medium','Large'] as $i => $s)
                        <button class="flex-1 py-2.5 rounded-xl border text-[13px] font-semibold transition-all {{ $i===1 ? 'g-violet text-white border-transparent shadow-md' : 'border-gray-200 text-gray-600 hover:bg-[#F5F3FF] hover:border-violet-200' }}">{{ $s }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Language --}}
        <div x-show="tab==='language'" class="card overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100"><h3 class="font-bold text-gray-900 text-[16px]">Language & Region</h3><p class="text-[12px] text-gray-400 mt-0.5">Set your preferred language and regional formats</p></div>
            <div class="p-6 space-y-4">
                @foreach([['System Language','globe'],['Date Format','calendar'],['Currency','money'],['Time Zone','clock']] as $i => [$l,$icon])
                <div>
                    <label class="inp-label">{{ $l }}</label>
                    <select class="inp">
                        @if($i===0)
                            <option selected>Filipino (Philippines)</option>
                            <option>English (United States)</option>
                        @elseif($i===1)
                            <option selected>MM/DD/YYYY</option><option>DD/MM/YYYY</option><option>YYYY-MM-DD</option>
                        @elseif($i===2)
                            <option selected>₱ Philippine Peso (PHP)</option><option>$ US Dollar (USD)</option>
                        @else
                            <option selected>Asia/Manila (UTC+8)</option><option>UTC (GMT+0)</option>
                        @endif
                    </select>
                </div>
                @endforeach
                <div class="flex justify-end pt-2"><button class="btn btn-violet btn-md text-[13px]">Save Language Settings</button></div>
            </div>
        </div>

        {{-- Backup --}}
        <div x-show="tab==='backup'" class="card overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100"><h3 class="font-bold text-gray-900 text-[16px]">Data Backup</h3><p class="text-[12px] text-gray-400 mt-0.5">Manage data backup and restore operations</p></div>
            <div class="p-6 space-y-4">
                <div class="bg-green-50 border border-green-200 rounded-2xl p-5">
                    <div class="flex items-start gap-4">
                        <div class="icon-ring flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        </div>
                        <div class="flex-1"><h4 class="font-bold text-gray-900">Last Backup</h4><p class="text-[13px] text-gray-600 mt-0.5">June 23, 2026 at 12:00 AM · Automatic</p><p class="text-[12px] text-green-600 font-semibold mt-1">✓ Backup successful · 4.2 MB</p></div>
                        <button class="btn btn-green btn-sm text-[12px]">Download</button>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <button class="flex items-center justify-center gap-2.5 p-4 bg-[#F5F3FF] rounded-2xl border border-violet-100 hover:bg-violet-100 transition-all text-[13px] font-semibold text-violet-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        Backup Now
                    </button>
                    <button class="flex items-center justify-center gap-2.5 p-4 bg-[#F5F3FF] rounded-2xl border border-violet-100 hover:bg-violet-100 transition-all text-[13px] font-semibold text-violet-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Restore Backup
                    </button>
                </div>
                <div>
                    <p class="text-[12px] font-bold text-gray-500 uppercase tracking-wider mb-3">Auto Backup Schedule</p>
                    <div class="space-y-2">
                        @foreach([['Daily at 12:00 AM','Enabled','badge-green'],['Weekly on Sunday','Enabled','badge-green'],['Monthly on 1st','Disabled','badge-gray']] as [$s,$e,$b])
                        <div class="flex items-center justify-between p-3.5 bg-gray-50 rounded-xl border border-gray-100">
                            <span class="text-[13px] text-gray-700 font-medium">{{ $s }}</span>
                            <span class="badge {{ $b }} text-[11px]">{{ $e }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- System Info --}}
        <div x-show="tab==='system'" class="card overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100"><h3 class="font-bold text-gray-900 text-[16px]">System Information</h3><p class="text-[12px] text-gray-400 mt-0.5">Technical details about your FreshTrack installation</p></div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    @foreach([['System Name','FreshTrack — Fruit Inventory System'],['Version','v2.0.0-beta (Build 2026.06.23)'],['Framework','Laravel 12.x'],['PHP Version','8.2.x'],['Database','SQLite 3.x'],['AI Model Version','FreshTrack-ML v2.4.1'],['Frontend','Tailwind CSS v4 + Alpine.js v3'],['License','Academic / Capstone Project']] as [$l,$v])
                    <div class="bg-[#F5F3FF] rounded-2xl p-4 border border-violet-100">
                        <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ $l }}</p>
                        <p class="text-[13.5px] font-bold text-gray-900 mt-1">{{ $v }}</p>
                    </div>
                    @endforeach
                </div>
                <div class="bg-green-50 border border-green-200 rounded-2xl p-5">
                    <div class="flex items-center gap-3 mb-4"><span class="w-3 h-3 bg-green-500 rounded-full pulse-dot"></span><h4 class="font-bold text-gray-900 text-[15px]">System Health: Excellent</h4></div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach([['CPU Usage','12%','g-green'],['Memory','34%','g-blue'],['Storage','28%','g-violet'],['Uptime','99.9%','g-green']] as [$l,$v,$g])
                        <div class="text-center bg-white rounded-2xl p-3.5 border border-green-100">
                            <p class="text-[22px] font-black text-green-600">{{ $v }}</p>
                            <p class="text-[11.5px] text-gray-500 mt-0.5">{{ $l }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
