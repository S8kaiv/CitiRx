@if (session('status'))
    <x-ui.alert
        variant="success"
        aria-live="polite"
    >
        {{ session('status') }}
    </x-ui.alert>
@endif

@if (session('error'))
    <x-ui.alert variant="danger">
        {{ session('error') }}
    </x-ui.alert>
@endif
