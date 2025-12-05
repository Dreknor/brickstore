<x-layouts.app>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ __('Dashboard')}}</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('Welcome to the dashboard') }}</p>
    </div>


    <!-- Inventory & Nextcloud Widgets Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Inventory Overview -->
        <x-dashboard.inventory-overview-widget />

        <!-- Nextcloud Status -->
        <x-dashboard.nextcloud-widget />
    </div>

    <!-- Inventory Top Items & Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Top Items -->
        <x-dashboard.inventory-top-items-widget />

        <!-- Activity Log -->
        <x-dashboard.inventory-activity-widget />
    </div>

</x-layouts.app>
