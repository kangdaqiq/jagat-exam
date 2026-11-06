@php
    $metrics = $this->getMetrics();
@endphp

<x-filament-widgets::widget class="col-span-full" style="grid-column: 1 / -1;">
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-6 w-full">
    @foreach ($metrics as $metric)
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-[#1a2231] md:p-6 shadow-theme-xs transition-transform duration-200 hover:-translate-y-1 hover:shadow-theme-md">
            <div class="flex items-center justify-between">
                {{-- Circular / Rounded Icon Badge --}}
                <div class="flex items-center justify-center w-12 h-12 rounded-xl {{ $metric['icon_bg'] }}">
                    @if ($metric['icon'] === 'user')
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    @elseif ($metric['icon'] === 'calendar')
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    @elseif ($metric['icon'] === 'document')
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    @else
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                    @endif
                </div>

                {{-- Trend / Status Badge --}}
                @if ($metric['badge_type'] === 'success')
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        {{ $metric['trend'] }}
                    </span>
                @elseif ($metric['badge_type'] === 'info')
                    <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2.5 py-0.5 text-xs font-semibold text-sky-700 dark:bg-sky-500/15 dark:text-sky-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                        {{ $metric['trend'] }}
                    </span>
                @elseif ($metric['badge_type'] === 'warning')
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        {{ $metric['trend'] }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 rounded-full bg-[#ecf3ff] px-2.5 py-0.5 text-xs font-semibold text-[#465fff] dark:bg-[#465fff]/15 dark:text-[#7592ff]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#465fff]"></span>
                        {{ $metric['trend'] }}
                    </span>
                @endif
            </div>

            <div class="mt-4">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400" style="font-family: 'Outfit', sans-serif;">
                    {{ $metric['label'] }}
                </span>
                <h3 class="mt-1 font-extrabold text-gray-900 text-2xl lg:text-3xl dark:text-white" style="font-family: 'Outfit', sans-serif; letter-spacing: -0.02em;">
                    {{ $metric['value'] }}
                </h3>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 font-medium">
                    {{ $metric['sub'] }}
                </p>
            </div>
        </div>
    @endforeach
</div>
</x-filament-widgets::widget>

