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
        class="mb-4 rounded-lg bg-[#6D4AFF] p-4 text-white shadow-lg"
    >
        <div class="flex items-center gap-3">
            <span
                class="text-3xl"
                aria-hidden="true"
            >
                &#127881;
            </span>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-white/80">
                    Level Up
                </p>

                <p class="text-lg font-bold">
                    Level {{ $levelUp['level_number'] }}
                    &mdash;
                    {{ $levelUp['tier_name'] }}
                </p>
            </div>
        </div>
    </div>
@endif