<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <a href="{{ route('clash.lobby') }}" class="group relative flex flex-col md:flex-row items-center justify-between overflow-hidden rounded-2xl bg-gradient-to-r from-red-600 via-purple-600 to-blue-600 p-6 text-white shadow-xl transition-all hover:scale-[1.01] hover:shadow-2xl">
            <div class="z-10 mb-4 md:mb-0">
                <span class="inline-block rounded-full bg-black/30 px-3 py-1 text-xs font-bold uppercase tracking-wider text-amber-300 backdrop-blur-sm">⚡ Minijuego Multijugador</span>
                <h2 class="mt-2 text-2xl md:text-3xl font-black tracking-tight">Entrar a Reverb Clash</h2>
                <p class="mt-1 text-sm text-neutral-200">Crea una sala o únete a una batalla en tiempo real vía WebSockets.</p>
            </div>
            <div class="z-10 flex items-center gap-3">
                <span class="rounded-xl bg-white px-5 py-2.5 font-bold text-neutral-900 shadow-md transition-transform group-hover:scale-105">
                    Ir al Lobby ⚔️
                </span>
            </div>
            <div class="pointer-events-none absolute -right-6 -bottom-8 select-none text-8xl opacity-20">🥷</div>
        </a>

        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
        </div>
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
        </div>
    </div>
</x-layouts::app>
