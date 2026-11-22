<dialog id="workflow-confirm" aria-labelledby="workflow-confirm-title" aria-describedby="workflow-confirm-message" class="w-full max-w-md rounded-2xl border border-line bg-surface p-6 text-ink backdrop:bg-black/50">
    <h2 id="workflow-confirm-title" class="text-lg font-bold">Confirm action</h2>
    <p id="workflow-confirm-message" class="mt-3 whitespace-pre-line text-sm"></p>
    <div class="mt-6 flex justify-end gap-3">
        <x-ui.button type="button" variant="secondary" id="workflow-confirm-cancel">Cancel</x-ui.button>
        <x-ui.button type="button" id="workflow-confirm-accept">Confirm</x-ui.button>
    </div>
</dialog>
