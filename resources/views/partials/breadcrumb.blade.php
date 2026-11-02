@php
$route = request()->route()->getName();
$map = [
    'dashboard'        => [['Dashboard',null]],
    'sales'            => [['Dashboard','dashboard'],['Sales',null]],
    'inventory'        => [['Dashboard','dashboard'],['Inventory',null]],
    'forecast'         => [['Dashboard','dashboard'],['AI Sales Forecast',null]],
    'spoilage'         => [['Dashboard','dashboard'],['Spoilage Prediction',null]],

    'decision-support' => [['Dashboard','dashboard'],['Decision Support',null]],
    'reports'          => [['Dashboard','dashboard'],['Reports',null]],
    'analytics'        => [['Dashboard','dashboard'],['Analytics',null]],
    'users'            => [['Dashboard','dashboard'],['User Management',null]],
    'settings'         => [['Dashboard','dashboard'],['Settings',null]],
    'notifications'    => [['Dashboard','dashboard'],['Notifications',null]],
];
$crumbs = $map[$route] ?? [['Dashboard',null]];
@endphp
<nav class="flex items-center gap-1.5 text-[12.5px] mb-6 select-none">
    <svg class="w-4 h-4 text-violet-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
    @foreach($crumbs as $i => [$label, $r])
        @if($i > 0)
        <svg class="w-3.5 h-3.5 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        @endif
        @if($r)
            <a href="{{ route($r) }}" class="text-gray-400 hover:text-violet-600 font-medium transition-colors hover:underline underline-offset-2">{{ $label }}</a>
        @else
            <span class="text-gray-800 font-semibold">{{ $label }}</span>
        @endif
    @endforeach
</nav>
