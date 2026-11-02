<x-admin.mobile-link
    :href="route('faculty.dashboard')"
    label="Dashboard"
    icon="dashboard"
    :active="request()->routeIs('faculty.dashboard')"
/>

<x-admin.mobile-link
    :href="route('faculty.interventions.index')"
    label="Support"
    icon="participants"
    :active="request()->routeIs('faculty.interventions.*')"
/>

<x-admin.mobile-link
    :href="route('pearls.index')"
    label="Library"
    icon="pearls"
    :active="request()->routeIs('pearls.*')"
/>

<x-admin.mobile-link
    :href="route('content.pearls.index')"
    label="Manage"
    icon="questions"
    :active="request()->routeIs('content.pearls.*')"
/>
