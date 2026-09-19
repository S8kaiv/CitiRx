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
        role="status"
        class="mb-4 flex items-center gap-3.5 rounded-2xl border-2 border-b-4 border-[#4A2FC4] bg-[#6D4AFF] p-4 text-white shadow-sm"
    >
        <span
            class="text-3xl shrink-0"
            aria-hidden="true"
        >
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