@php
    $btnClass       = $btnClass       ?? 'p-2 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100 active:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition';
    $iconSize       = $iconSize       ?? 'w-5 h-5';
    $iconSizeSm     = $iconSizeSm     ?? 'w-4 h-4';
    $dropdownClass  = $dropdownClass  ?? 'absolute right-0 mt-2 w-44 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50';
@endphp

<div x-data="themeSwitcher()" @click.outside="open = false" class="relative">
    {{-- Trigger button --}}
    <button @click="open = !open"
            type="button"
            aria-label="Переключить тему"
            :aria-expanded="open.toString()"
            class="{{ $btnClass }}">
        <template x-if="currentIcon === 'sun'">
            <svg class="{{ $iconSize }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="5"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
            </svg>
        </template>
        <template x-if="currentIcon === 'moon'">
            <svg class="{{ $iconSize }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        </template>
        <template x-if="currentIcon === 'system'">
            <svg class="{{ $iconSize }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </template>
    </button>

    {{-- Dropdown --}}
    <div x-show="open" x-transition.opacity x-cloak class="{{ $dropdownClass }}">
        {{-- Light --}}
        <button @click="setTheme('light')" type="button"
                class="flex items-center justify-between w-full px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
            <span class="flex items-center gap-2">
                <svg class="{{ $iconSizeSm }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="12" r="5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                </svg>
                Светлая
            </span>
            <svg x-show="theme === 'light'" class="w-4 h-4 text-indigo-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
        </button>

        {{-- Dark --}}
        <button @click="setTheme('dark')" type="button"
                class="flex items-center justify-between w-full px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
            <span class="flex items-center gap-2">
                <svg class="{{ $iconSizeSm }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                Тёмная
            </span>
            <svg x-show="theme === 'dark'" class="w-4 h-4 text-indigo-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
        </button>

        {{-- System --}}
        <button @click="setTheme('system')" type="button"
                class="flex items-center justify-between w-full px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
            <span class="flex items-center gap-2">
                <svg class="{{ $iconSizeSm }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Системная
            </span>
            <svg x-show="theme === 'system'" class="w-4 h-4 text-indigo-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
        </button>
    </div>
</div>
