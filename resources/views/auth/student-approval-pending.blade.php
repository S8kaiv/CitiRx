<x-auth-card
    title="Account pending approval"
    subtitle="Your email is confirmed. A Dean or Admin must approve your student account before you can start reviewing."
>
    <div class="flex flex-wrap gap-3">
        <x-ui.button :href="route('verification.notice')">
            Check approval status
        </x-ui.button>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-ui.button type="submit" variant="secondary">Log out</x-ui.button>
        </form>
    </div>
</x-auth-card>
