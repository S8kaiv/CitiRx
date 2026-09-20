@props([
    'levelUp' => session('level_up'),
])

@if (
    is_array($levelUp)
    && isset(
        $levelUp['level_number'],
        $levelUp['tier_name']
    )
)
    <div
        x-data
        x-init="
            if (typeof confetti === 'function') {
                confetti({
                    particleCount: 50,
                    spread: 60,
                    origin: { y: 0.3 },
                    colors: ['#6D4AFF', '#0EA5A4', '#F5A623', '#22C55E']
                });
            }
        "
        role="status"
        class="animate-pop mb-4 flex items-center gap-3.5 rounded-2xl border-2 border-b-4 border-[#4A2FC4] bg-[#6D4AFF] p-4 text-white shadow-sm"
    >
        {{-- Load canvas-confetti via lightweight CDN only when alert triggers --}}
        <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>

        <span class="text-3xl shrink-0" aria-hidden="true">
            &#127881;
        </span>

        <div>
            <p class="font-display text-xs font-bold uppercase tracking-wider text-white/85">
                Level Up
            </p>

            <p class="font-display text-lg font-extrabold text-white">
                Level {{ $levelUp['level_number'] }}
                &mdash;
                {{ $levelUp['tier_name'] }}
            </p>
        </div>
    </div>
@endif