<dialog
    open
    data-assessment-gate
    aria-labelledby="assessment-gate-title"
    aria-describedby="assessment-gate-message assessment-gate-rules"
    aria-modal="true"
    class="assessment-focus-gate rounded-2xl border border-line bg-surface p-5 text-ink sm:p-6"
>
    <h2 id="assessment-gate-title" data-assessment-gate-title class="text-xl font-bold">
        Before you answer
    </h2>
    <p id="assessment-gate-message" data-assessment-gate-message class="mt-3 text-sm leading-relaxed text-ink-muted">
        Enter fullscreen to answer questions. You can return to the dashboard at any time.
    </p>
    <p id="assessment-gate-rules" class="mt-3 text-sm leading-relaxed text-ink-muted">
        Copy, cut, paste, and dragging question content are disabled while answering.
        Leaving fullscreen or switching tabs hides the questions until you resume.
    </p>
    <p class="mt-3 text-sm leading-relaxed text-warning-ink">
        Any existing assessment timer keeps running on this screen. Leaving fullscreen does not submit your answers.
    </p>
    <p data-assessment-fullscreen-unavailable hidden class="mt-3 text-sm leading-relaxed text-ink-muted">
        Fullscreen is unavailable in this browser. You can continue in focus mode with clipboard restrictions still active.
    </p>
    <p data-assessment-fullscreen-error hidden role="alert" class="mt-3 text-sm text-danger-ink"></p>
    <div class="mt-6 flex flex-wrap gap-3">
        <x-ui.button type="button" data-assessment-enter :disabled="true" autofocus>
            Enter fullscreen
        </x-ui.button>
        <x-ui.button variant="secondary" :href="route('dashboard')">
            Return to dashboard
        </x-ui.button>
    </div>
</dialog>

<p
    data-assessment-clipboard-notice
    hidden
    role="status"
    aria-live="polite"
    class="assessment-clipboard-notice rounded-xl border border-warning-strong/30 bg-warning-tint p-4 text-sm font-semibold text-warning-ink"
>
    Copy, cut, paste, and dragging question content are disabled during this assessment.
</p>

<noscript>
    <section class="assessment-script-notice bg-canvas p-6 text-ink">
        <h2 class="text-xl font-bold">JavaScript is required for assessments</h2>
        <p class="mt-3">Enable JavaScript and reload this page to enter the assessment.</p>
        <x-ui.button variant="secondary" :href="route('dashboard')" class="mt-4">Return to dashboard</x-ui.button>
    </section>
</noscript>
