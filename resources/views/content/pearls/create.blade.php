<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Create Micro Pearl"
            description="Create a governed lesson draft for faculty review before publication."
        >
            <x-slot:actions>
                <x-ui.button
                    variant="secondary"
                    :href="route('content.pearls.index')"
                >
                    Back to Pearl Management
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="pt-6 pb-6 sm:pb-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('error'))
                <x-ui.alert variant="danger">
                    {{ session('error') }}
                </x-ui.alert>
            @endif

            <x-ui.card>
                <form
                    method="POST"
                    action="{{ route('content.pearls.store') }}"
                >
                    @csrf

                    @include('content.pearls._form', [
                        'pearl' => null,
                    ])
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
