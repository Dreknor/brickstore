<x-layouts.app title="Admin Dashboard">
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                <i class="fa-solid fa-shield-halved text-red-600"></i> Admin Dashboard
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Übersicht über System und Aktivitäten</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Benutzer gesamt</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_users'] }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                        <i class="fa-solid fa-users text-2xl text-blue-600 dark:text-blue-300"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Stores gesamt</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_stores'] }}</p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                        <i class="fa-solid fa-store text-2xl text-green-600 dark:text-green-300"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktive Stores</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['active_stores'] }}</p>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                        <i class="fa-solid fa-circle-check text-2xl text-purple-600 dark:text-purple-300"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Logs (24h)</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['recent_logs'] }}</p>
                    </div>
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
                        <i class="fa-solid fa-list text-2xl text-yellow-600 dark:text-yellow-300"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Letzte Aktivitäten</h2>
                    <a href="{{ route('admin.activity-logs') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                        Alle anzeigen <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @forelse($recentActivity as $log)
                        <div class="flex items-start gap-4 pb-4 border-b border-gray-200 dark:border-gray-700 last:border-0 last:pb-0">
                            <div class="flex-shrink-0 mt-1">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full text-xs font-semibold
                                    @if($log->log_level === 'critical' || $log->log_level === 'error') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                    @elseif($log->log_level === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                    @elseif($log->log_level === 'info') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                    @endif">
                                    @if($log->log_level === 'critical' || $log->log_level === 'error')
                                        <i class="fa-solid fa-exclamation-circle"></i>
                                    @elseif($log->log_level === 'warning')
                                        <i class="fa-solid fa-exclamation-triangle"></i>
                                    @else
                                        <i class="fa-solid fa-info-circle"></i>
                                    @endif
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $log->event }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $log->created_at->diffForHumans() }}</p>
                                </div>
                                @if($log->description)
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($log->description, 100) }}</p>
                                @endif
                                <div class="mt-1 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                    @if($log->user)
                                        <span><i class="fa-solid fa-user"></i> {{ $log->user->name }}</span>
                                    @endif
                                    @if($log->store)
                                        <span><i class="fa-solid fa-store"></i> {{ $log->store->name }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-gray-500 dark:text-gray-400 py-8">Keine Aktivitäten vorhanden</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

