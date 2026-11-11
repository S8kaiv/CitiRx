@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'labelledby' => null,
    'describedby' => null,
    'focusable' => false,
])

@php
    $widthClass = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
    ][$maxWidth] ?? 'sm:max-w-2xl';

    $titleId = $labelledby ?? $name.'-title';
@endphp

<dialog
    x-data="{
        previouslyFocused: null,
        pageWasLocked: false,
        ownsScrollLock: false,

        openDialog() {
            if (this.$el.open) return;

            this.previouslyFocused = document.activeElement;
            this.pageWasLocked =
                document.body.classList.contains('overflow-y-hidden');

            this.$el.showModal();

            document.body.classList.add('overflow-y-hidden');
            this.ownsScrollLock = true;

            if (@js($focusable)) {
                this.$nextTick(() => {
                    const target = this.$el.querySelector(
                        '[aria-invalid=true], [autofocus], input:not([type=hidden]), textarea, select, button, a[href]'
                    );

                    target?.focus();
                });
            }
        },

        closeDialog() {
            if (this.$el.open) {
                this.$el.close();
            }
        },

        releaseScrollLock() {
            if (this.ownsScrollLock && !this.pageWasLocked) {
                document.body.classList.remove('overflow-y-hidden');
            }

            this.ownsScrollLock = false;
        },

        finishClose() {
            this.releaseScrollLock();

            const target = this.previouslyFocused;
            this.previouslyFocused = null;

            this.$nextTick(() => {
                if (target?.isConnected) {
                    target.focus();
                }
            });
        },

        destroy() {
            if (this.$el.open) {
                this.$el.close();
            }

            this.releaseScrollLock();
        }
    }"
    x-init="if (@js($show)) $nextTick(() => openDialog())"
    x-on:open-modal.window="
        if ($event.detail === @js($name)) openDialog()
    "
    x-on:close-modal.window="
        if ($event.detail === @js($name)) closeDialog()
    "
    x-on:cancel.prevent="closeDialog()"
    x-on:close="finishClose()"
    x-on:click.self="closeDialog()"
    aria-modal="true"
    aria-labelledby="{{ $titleId }}"
    @if ($describedby)
        aria-describedby="{{ $describedby }}"
    @endif
    class="fixed inset-0 m-0 h-dvh max-h-none w-full max-w-none overflow-y-auto border-0 bg-transparent px-4 py-6 text-ink backdrop:bg-gray-500/75 sm:px-0"
>
    <div
        class="mb-6 rounded-lg bg-surface shadow-xl sm:mx-auto sm:w-full {{ $widthClass }}"
    >
        {{ $slot }}
    </div>
</dialog>
