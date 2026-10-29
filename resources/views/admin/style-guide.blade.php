<x-app-layout surface="admin">
    <x-slot name="header">
        <x-ui.page-header
            title="Interface style guide"
            description="Internal verification of shared states, surfaces, themes, and comfort modes."
        />
    </x-slot>

    @php
        $surfaces = [
            'student',
            'faculty',
            'admin',
        ];

        $themes = [
            'theme-standard',
            'theme-forest',
            'theme-sakura',
            'theme-midnight',
            'theme-ocean',
            'theme-solar',
        ];

        $comforts = [
            'Light' => '',
            'Dark' => 'ui-dark-preview',
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-10 px-4 py-8 sm:px-6 lg:px-8">
        @foreach ($comforts as $comfort => $comfortClass)
            <section class="space-y-5">
                <h2 class="font-display text-xl font-extrabold text-ink">
                    {{ $comfort }}
                </h2>

                @foreach ($surfaces as $surface)
                    <div
                        data-surface="{{ $surface }}"
                        class="{{ $comfortClass }} space-y-5 rounded-card
                            bg-canvas p-5 text-ink"
                    >
                        <h3 class="font-display text-lg font-extrabold">
                            {{ ucfirst($surface) }}
                        </h3>

                        <div class="grid gap-5 xl:grid-cols-2">
                            @foreach ($themes as $theme)
                                @php
                                    $key = "{$comfort}-{$surface}-{$theme}";
                                @endphp

                                <x-ui.card
                                    class="{{ $theme }} space-y-5"
                                >
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <h4 class="font-bold text-ink">
                                            {{ str($theme)
                                                ->after('theme-')
                                                ->headline() }}
                                        </h4>

                                        <x-ui.badge variant="primary">
                                            {{ ucfirst($surface) }}
                                        </x-ui.badge>
                                    </div>

                                    <div class="flex flex-wrap gap-3">
                                        <x-ui.button>
                                            Save changes
                                        </x-ui.button>

                                        <x-ui.button variant="secondary">
                                            Cancel
                                        </x-ui.button>

                                        <x-ui.button variant="danger">
                                            Delete
                                        </x-ui.button>

                                        <x-ui.button disabled>
                                            Disabled
                                        </x-ui.button>
                                    </div>

                                    <x-ui.field
                                        :id="'field-'.$key"
                                        :name="'field_'.$key"
                                        label="Example field"
                                        hint="This hint explains what to enter."
                                        placeholder="Enter a value"
                                    />

                                    <div class="space-y-3">
                                        <x-ui.alert variant="success">
                                            Changes were saved.
                                        </x-ui.alert>

                                        <x-ui.alert variant="danger">
                                            The value could not be saved. Check the field and try again.
                                        </x-ui.alert>

                                        <x-ui.alert variant="warning">
                                            Review this information before continuing.
                                        </x-ui.alert>

                                        <x-ui.alert variant="info">
                                            This information is available for review.
                                        </x-ui.alert>
                                    </div>

                                    <x-ui.progress
                                        :value="64"
                                        label="Review progress"
                                    />
                                </x-ui.card>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </section>
        @endforeach
    </div>
</x-app-layout>
