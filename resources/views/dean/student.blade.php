<x-app-layout>
    <x-slot name="header"><x-ui.page-header :title="$student->fullName()" description="Official student identity and current competency readiness." /></x-slot>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6">
        <x-ui.button variant="secondary" :href="route('dean.dashboard')">Back to workspace</x-ui.button>
        @if(session('status'))<x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>@endif
        <x-ui.card>
            @if(!$breakdown || empty($breakdown['domains']))<p class="text-ink">Diagnostic not completed</p>@else
                <h2 class="text-xl font-bold text-ink">Estimated readiness: {{ number_format($breakdown['total'], 2) }}%</h2><p class="mt-2 text-sm text-ink-muted">An estimated learning indicator, not a probability of passing the PhLE.</p>
                <ul class="mt-4 divide-y divide-line">@foreach($breakdown['domains'] as $domain)<li class="flex justify-between gap-4 py-3 text-ink"><span>{{ $domain['domain_name'] }}</span><span>{{ number_format($domain['mastery'] * 100, 1) }}%</span></li>@endforeach</ul>
            @endif
        </x-ui.card>
        <x-ui.card id="assign-intervention" class="space-y-4">
            <h2 class="text-lg font-bold text-ink">Assign intervention</h2>

            @if ($faculty->isEmpty())
                <p class="text-sm text-ink-muted">
                    @if ($student->cohort_id)
                        This student's cohort has no faculty available for assignment.
                    @else
                        This student needs a cohort before an intervention can be assigned.
                    @endif
                </p>
            @elseif ($formOptions->isEmpty())
                <p class="text-sm text-ink-muted">No domains are available for intervention assignment.</p>
            @else
                <p class="text-sm text-ink-muted">Choose who will follow up with this student.</p>

                @error('target_user_id')
                    <x-ui.alert variant="warning">{{ $message }}</x-ui.alert>
                @enderror

                <form method="POST" action="{{ route('dean.students.interventions.store', $student) }}"
                    x-data="{ selectedDomain: @js((string) old('domain_id', '')) }"
                    class="space-y-4">
                    @csrf

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field name="faculty_id" label="Assigned faculty" type="select" required>
                            <option value="">Select faculty</option>
                            @foreach ($faculty as $member)
                                <option value="{{ $member->user_id }}"
                                    @selected(old('faculty_id', $faculty->count() === 1 ? $faculty->first()->user_id : null) === $member->user_id)>
                                    {{ $member->fullName() }}
                                </option>
                            @endforeach
                        </x-ui.field>

                        <x-ui.field name="intervention_type" label="Type" type="select" required>
                            @foreach (\App\Models\FacultyIntervention::TYPE_LABELS as $value => $label)
                                <option value="{{ $value }}" @selected(old('intervention_type', 'advisory') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </x-ui.field>

                        <x-ui.field name="domain_id" label="Domain" type="select" required
                            x-model="selectedDomain" x-on:change="$refs.competency.value = ''">
                            <option value="">Select a domain</option>
                            @foreach ($formOptions as $domain)
                                <option value="{{ $domain->domain_id }}" @selected((string) old('domain_id') === (string) $domain->domain_id)>
                                    {{ $domain->domain_name }}
                                </option>
                            @endforeach
                        </x-ui.field>

                        <x-ui.field name="competency_id" label="Competency (optional)" type="select" x-ref="competency">
                            <option value="">Domain-wide intervention</option>
                            @foreach ($formOptions as $domain)
                                <optgroup label="{{ $domain->domain_name }}"
                                    x-bind:disabled="selectedDomain !== @js((string) $domain->domain_id)">
                                    @foreach ($domain->competencies as $competency)
                                        <option value="{{ $competency->competency_id }}"
                                            @selected(old('competency_id') === $competency->competency_id)>
                                            {{ $competency->competency_code }} {{ $competency->title }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </x-ui.field>
                    </div>

                    <x-ui.field name="custom_message" label="Message (optional)" type="textarea" :rows="3" maxlength="1000" />
                    <x-ui.button type="submit">Assign intervention</x-ui.button>
                </form>
            @endif
        </x-ui.card>
        <x-ui.card><h2 class="mb-4 text-lg font-bold text-ink">Edit official name</h2>
            <form method="POST" action="{{ route('dean.students.name', $student) }}" data-confirm="Update this student's official name? Any pending name request will be superseded." class="space-y-4">
                @csrf @method('PATCH')
                <x-ui.field name="first_name" label="First name" :value="$student->first_name" required />
                <x-ui.field name="middle_name" label="Middle name" :value="$student->middle_name" />
                <x-ui.field name="last_name" label="Last name" :value="$student->last_name" required />
                <x-ui.button type="submit">Update official name</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
