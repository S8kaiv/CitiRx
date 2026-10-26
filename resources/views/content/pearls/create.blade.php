<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-xl font-bold">
            Create Micro Pearl
        </h1>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4">
            <form
                method="POST"
                action="{{ route('content.pearls.store') }}"
                class="rounded-3xl border-2 border-slate-200 bg-white p-6"
            >
                @csrf

                @include('content.pearls._form', [
                    'pearl' => null,
                ])
            </form>
        </div>
    </div>
</x-app-layout>
