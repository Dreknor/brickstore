@php
    $store = auth()->user()->store ?? null;

    $recentActivities = [];

    if ($store) {
        // Hole die letzten 10 Inventory-bezogenen Activities
        $recentActivities = \App\Models\ActivityLog::where('store_id', $store->id)
            ->where('event', 'like', 'inventory.%')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }
@endphp

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fa-solid fa-clock-rotate-left mr-2 text-gray-500"></i>
                Inventar-Aktivitäten
            </h3>
            @if($store && auth()->user()->is_admin)
                <a href="{{ route('admin.activity-logs') }}"
                   class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                    Alle Logs →
                </a>
            @endif
        </div>
    </div>

    @if(!$store)
        <div class="p-6">
            <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                Kein Store konfiguriert
            </p>
        </div>
    @elseif($recentActivities->isEmpty())
        <div class="p-6">
            <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                <i class="fa-solid fa-inbox mr-2"></i>
                Noch keine Inventar-Aktivitäten
            </p>
        </div>
    @else
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($recentActivities as $activity)
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="flex items-start gap-3">
                        <!-- Icon based on log level -->
                        <div class="flex-shrink-0 mt-1">
                            @if($activity->log_level === 'error')
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/30">
                                    <i class="fa-solid fa-circle-exclamation text-red-600 dark:text-red-400"></i>
                                </span>
                            @elseif($activity->log_level === 'warning')
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-orange-100 dark:bg-orange-900/30">
                                    <i class="fa-solid fa-triangle-exclamation text-orange-600 dark:text-orange-400"></i>
                                </span>
                            @elseif($activity->log_level === 'info')
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30">
                                    <i class="fa-solid fa-info-circle text-blue-600 dark:text-blue-400"></i>
                                </span>
                            @else
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700">
                                    <i class="fa-solid fa-circle text-gray-600 dark:text-gray-400"></i>
                                </span>
                            @endif
                        </div>

                        <!-- Activity Details -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $activity->description ?? $activity->event }}
                            </p>

                            @if($activity->properties)
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    @if(isset($activity->properties['item_no']))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700">
                                            <i class="fa-solid fa-hashtag mr-1"></i>
                                            {{ $activity->properties['item_no'] }}
                                        </span>
                                    @endif
                                    @if(isset($activity->properties['quantity']))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 ml-1">
                                            <i class="fa-solid fa-cubes mr-1"></i>
                                            {{ $activity->properties['quantity'] }} Stk.
                                        </span>
                                    @endif
                                </div>
                            @endif

                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                <i class="fa-solid fa-clock mr-1"></i>
                                {{ $activity->created_at->diffForHumans() }}
                            </p>
                        </div>

                        <!-- Event Badge -->
                        <div class="flex-shrink-0">
                            @php
                                $eventBadge = match(true) {
                                    str_contains($activity->event, 'created') => ['bg' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-700 dark:text-green-300', 'label' => 'Erstellt'],
                                    str_contains($activity->event, 'updated') => ['bg' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-blue-700 dark:text-blue-300', 'label' => 'Aktualisiert'],
                                    str_contains($activity->event, 'deleted') => ['bg' => 'bg-red-100 dark:bg-red-900/30', 'text' => 'text-red-700 dark:text-red-300', 'label' => 'Gelöscht'],
                                    str_contains($activity->event, 'sync') => ['bg' => 'bg-purple-100 dark:bg-purple-900/30', 'text' => 'text-purple-700 dark:text-purple-300', 'label' => 'Sync'],
                                    default => ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-700 dark:text-gray-300', 'label' => 'Info'],
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $eventBadge['bg'] }} {{ $eventBadge['text'] }}">
                                {{ $eventBadge['label'] }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

