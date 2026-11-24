<div
    class="study-coach"
    data-url="{{ route('student.study-coach') }}"
    x-cloak
    x-data="{
        reply: null,
        thinking: false,
        failed: false,

        openCoach(event) {
            const panel = this.$refs.panel;
            panel.toggleAttribute('data-pointer-open', event.detail > 0);
            panel.showModal();

            if (this.reply === null) {
                this.loadRecommendation();
            }
        },

        async loadRecommendation() {
            if (this.thinking) return;

            this.thinking = true;
            this.failed = false;

            const controller = new AbortController();
            const timeout = window.setTimeout(() => controller.abort(), 8000);

            try {
                const response = await fetch(this.$root.dataset.url, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });

                if (!response.ok) throw new Error('Recommendation unavailable');

                this.reply = await response.json();
            } catch {
                this.failed = true;
            } finally {
                window.clearTimeout(timeout);
                this.thinking = false;
            }
        },
    }"
>
    <button
        type="button"
        class="study-coach-trigger rounded-3xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary"
        aria-label="Open CitiRx Study Coach"
        aria-haspopup="dialog"
        aria-controls="study-coach-panel"
        @click="openCoach($event)"
    >
        <span class="study-coach-art relative block h-full w-full" aria-hidden="true">
            <x-study-coach-mascot instance="launcher" />
        </span>
    </button>

    <dialog
        id="study-coach-panel"
        x-ref="panel"
        class="study-coach-panel rounded-3xl border border-line bg-surface text-ink shadow-2xl"
        aria-labelledby="study-coach-title"
    >
        <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3">
            <h2 id="study-coach-title" class="font-display font-black">
                CitiRx Study Coach
            </h2>

            <form method="dialog">
                <x-ui.button type="submit" variant="ghost" autofocus>
                    Close
                </x-ui.button>
            </form>
        </div>

        <div class="flex items-start gap-3 p-4">
            <span class="study-coach-avatar relative block h-20 w-20 shrink-0" :data-thinking="thinking" aria-hidden="true">
                <x-study-coach-mascot instance="chat" />
            </span>
            <div class="min-w-0 flex-1 space-y-3">
                <p
                    role="status"
                    class="rounded-2xl rounded-tl-sm bg-surface-2 px-4 py-3 text-sm leading-relaxed"
                    x-text="thinking
                        ? 'Finding your next study topic…'
                        : failed
                            ? 'I couldn’t load your recommendation. Try again in a moment.'
                            : reply?.message ?? ''"
                ></p>

                <template x-if="reply">
                    <a
                        class="ui-button ui-button-secondary w-full whitespace-normal text-center"
                        :href="reply.url"
                        x-text="reply.label"
                    ></a>
                </template>

                <button
                    x-show="failed"
                    type="button"
                    class="ui-button ui-button-secondary"
                    @click="loadRecommendation()"
                >
                    Try again
                </button>
            </div>
        </div>
    </dialog>
</div>
