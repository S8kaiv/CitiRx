<div class="admin-nav-group-label">
    Cohort
</div>

<x-admin.nav-link
    :href="route('faculty.dashboard')"
    label="Dashboard"
    icon="dashboard"
    :active="request()->routeIs('faculty.dashboard')"
/>

<x-admin.nav-link
    :href="route('faculty.interventions.index')"
    label="Interventions"
    icon="participants"
    :active="request()->routeIs('faculty.interventions.*')"
/>

<div class="admin-nav-group-label">
    Learning Content
</div>

<x-admin.nav-link
    :href="route('pearls.index')"
    label="Pearl Library"
    icon="pearls"
    :active="request()->routeIs('pearls.*')"
/>

<x-admin.nav-link
    :href="route('content.pearls.index')"
    label="Manage Pearls"
    icon="questions"
    :active="request()->routeIs('content.pearls.*')"
/>
