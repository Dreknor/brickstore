            <aside :class="{ 'w-full md:w-64': sidebarOpen, 'w-0 md:w-16 hidden md:block': !sidebarOpen }"
                class="bg-sidebar text-sidebar-foreground border-r border-gray-200 dark:border-gray-700 sidebar-transition overflow-hidden">
                <!-- Sidebar Content -->
                <div class="h-full flex flex-col">
                    <!-- Sidebar Menu -->
                    <nav class="flex-1 overflow-y-auto custom-scrollbar py-4">
                        <ul class="space-y-1 px-2">
                            <!-- Dashboard -->
                            <x-layouts.sidebar-link href="{{ route('dashboard') }}" icon='fas-house'
                                :active="request()->routeIs('dashboard*')">Dashboard</x-layouts.sidebar-link>

                            <!-- Orders -->
                            <x-layouts.sidebar-link href="{{ route('orders.index') }}" icon='fas-cart-shopping'
                                :active="request()->routeIs('orders.*')">Bestellungen</x-layouts.sidebar-link>

                            <!-- Inventory -->
                            <x-layouts.sidebar-link href="{{ route('inventory.index') }}" icon='fas-boxes-stacked'
                                :active="request()->routeIs('inventory.*')">Inventar</x-layouts.sidebar-link>

                            <!-- Invoices -->
                            <x-layouts.sidebar-link href="{{ route('invoices.index') }}" icon='fas-file-invoice'
                                :active="request()->routeIs('invoices.*')">Rechnungen</x-layouts.sidebar-link>

                            <!-- Settings -->
                            <x-layouts.sidebar-two-level-link-parent title="Einstellungen" icon="fas-cog"
                                :active="request()->routeIs('settings.*') || request()->routeIs('store.settings*')">
                                <x-layouts.sidebar-two-level-link href="{{ route('settings.profile.edit') }}" icon='fas-user'
                                    :active="request()->routeIs('settings.profile.*')">Profil</x-layouts.sidebar-two-level-link>
                                <x-layouts.sidebar-two-level-link href="{{ route('settings.password.edit') }}" icon='fas-key'
                                    :active="request()->routeIs('settings.password.*')">Passwort</x-layouts.sidebar-two-level-link>
                                <x-layouts.sidebar-two-level-link href="{{ route('settings.appearance.edit') }}" icon='fas-palette'
                                    :active="request()->routeIs('settings.appearance.*')">Aussehen</x-layouts.sidebar-two-level-link>
                                <x-layouts.sidebar-two-level-link href="{{ route('store.settings') }}" icon='fas-store'
                                    :active="request()->routeIs('store.settings*')">Shop-Einstellungen</x-layouts.sidebar-two-level-link>
                            </x-layouts.sidebar-two-level-link-parent>

                            @if(auth()->user()->isAdmin())
                                <!-- Admin Section -->
                                <x-layouts.sidebar-two-level-link-parent title="Administration" icon="fas-shield-halved"
                                    :active="request()->routeIs('admin.*')">
                                    <x-layouts.sidebar-two-level-link href="{{ route('admin.dashboard') }}" icon='fas-gauge'
                                        :active="request()->routeIs('admin.dashboard')">Dashboard</x-layouts.sidebar-two-level-link>
                                    <x-layouts.sidebar-two-level-link href="{{ route('admin.activity-logs') }}" icon='fas-list'
                                        :active="request()->routeIs('admin.activity-logs*')">Activity Logs</x-layouts.sidebar-two-level-link>
                                </x-layouts.sidebar-two-level-link-parent>
                            @endif
                        </ul>
                    </nav>
                </div>
            </aside>
