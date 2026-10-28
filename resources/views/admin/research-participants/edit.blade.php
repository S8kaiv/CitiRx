<x-app-layout>
    <x-slot name="header">
        <div>
            <a
                href="{{ route('admin.research-participants.index') }}"
                class="text-sm font-bold text-primary"
            >
                ← Research Participants
            </a>

            <h1 class="mt-1 text-xl font-black text-slate-900">
                {{ $student->fullName() }}
            </h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div
                    role="status"
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800"
                >
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div
                    role="alert"
                    class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800"
                >
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div
                    role="alert"
                    class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800"
                >
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="grid gap-4 rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm md:grid-cols-2">
                <div>
                    <p class="text-xs font-black uppercase tracking-wider text-slate-500">
                        Student ID
                    </p>
                    <p class="mt-1 font-bold text-slate-900">
                        {{ $student->student_id ?? 'Not assigned' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-black uppercase tracking-wider text-slate-500">
                        Cohort
                    </p>
                    <p class="mt-1 font-bold text-slate-900">
                        {{ $student->cohort?->cohort_name ?? 'Unassigned' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-black uppercase tracking-wider text-slate-500">
                        Email status
                    </p>
                    <p class="mt-1 font-bold text-slate-900">
                        {{ $student->email_verified_at ? 'Verified' : 'Unverified' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-black uppercase tracking-wider text-slate-500">
                        Account status
                    </p>
                    <p class="mt-1 font-bold text-slate-900">
                        {{ $student->trashed() ? 'Soft-deleted' : 'Active' }}
                    </p>
                </div>
            </section>

            @if ($student->trashed())
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    This account is soft-deleted. That does not automatically
                    mean the participant withdrew consent. Record the research
                    status separately according to the approved protocol.
                </div>
            @endif

            <section class="rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-900">
                    Change participant status
                </h2>

                <form
                    method="POST"
                    action="{{ route('admin.research-participants.update', ['userId' => $student->user_id]) }}"
                    class="mt-5 space-y-4"
                >
                    @csrf
                    @method('PATCH')

                    <input
                        type="hidden"
                        name="expected_lock_version"
                        value="{{ $participant?->lock_version ?? 0 }}"
                    >

                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">
                            Research status
                        </span>

                        <select
                            name="status"
                            required
                            class="mt-1 w-full rounded-xl border-slate-300"
                        >
                            <option value="" disabled @selected(old('status', $participant?->status) === null)>
                                Select a documented status
                            </option>

                            @foreach ($statuses as $status)
                                <option
                                    value="{{ $status }}"
                                    @selected(old('status', $participant?->status) === $status)
                                >
                                    {{ \App\Models\ResearchParticipant::label($status) }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">
                            Documented reason
                        </span>

                        <textarea
                            name="reason"
                            required
                            minlength="10"
                            maxlength="500"
                            rows="4"
                            class="mt-1 w-full rounded-xl border-slate-300"
                            placeholder="Record the protocol-based decision without unnecessary medical or personal details."
                        >{{ old('reason', $participant?->reason) }}</textarea>
                    </label>

                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">
                            Current administrator password
                        </span>

                        <input
                            type="password"
                            name="current_password"
                            required
                            autocomplete="current-password"
                            class="mt-1 w-full rounded-xl border-slate-300"
                        >
                    </label>

                    <button
                        type="submit"
                        class="rounded-xl bg-primary px-5 py-3 font-black text-white focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                    >
                        Save and Audit Status
                    </button>
                </form>
            </section>

            <section class="rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-900">
                    Recent status history
                </h2>

                <div class="mt-4 space-y-3">
                    @forelse ($recentAudits as $audit)
                        <article class="rounded-2xl bg-slate-50 p-4 text-sm">
                            <p class="font-bold text-slate-900">
                                {{ $audit->admin?->fullName() ?? 'Unavailable administrator' }}
                            </p>
                            <p class="text-slate-600">
                                {{ $audit->created_at->format('M j, Y g:i A') }} ·
                                {{ $audit->reason }}
                            </p>
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">
                            No participant-status changes have been recorded.
                        </p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
