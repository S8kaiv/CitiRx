@if (session('level_up') || session('level_up_alert'))
    @php
        $levelData = session('level_up') ?? session('level_up_alert');
    @endphp

    <div role="alert" class="flex items-center gap-3.5 rounded-2xl border-2 border-b-4 border-[#F5A623]/40 bg-gold-tint p-4 font-display text-gold-ink shadow-sm">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-xl shadow-xs">
            🎉
        </div>
        <div class="min-w-0 flex-1">
            <h4 class="text-sm font-extrabold tracking-tight text-slate-900">
                Level Up!
            </h4>
            <p class="text-xs font-semibold text-slate-700">
                {{ is_array($levelData) ? ($levelData['message'] ?? 'Congratulations on advancing to the next level!') : $levelData }}
            </p>
        </div>
    </div>
@endif