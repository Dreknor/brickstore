<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>BrickStore - Professional BrickLink Store Management</title>
    <meta name="description" content="Verwalten Sie Ihren BrickLink-Store professionell mit automatischer Rechnungserstellung, Nextcloud-Integration und Brickognize-Teile-Erkennung.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800">

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-gray-900/80 backdrop-blur-lg border-b border-gray-200 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-orange-500 rounded-lg flex items-center justify-center shadow-lg">
                        <i class="fas fa-cube text-white text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold bg-gradient-to-r from-red-600 to-orange-500 bg-clip-text text-transparent">
                        BrickStore
                    </span>
                </div>

                <!-- Auth Links -->
                @if (Route::has('login'))
                    <div class="flex items-center space-x-4">
                        @auth
                            <a href="{{ url('/dashboard') }}"
                               class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                                Login
                            </a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="px-6 py-2 text-sm font-semibold text-white bg-gradient-to-r from-red-600 to-orange-500 rounded-lg hover:from-red-700 hover:to-orange-600 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                                    Jetzt starten
                                </a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold text-gray-900 dark:text-white mb-6 leading-tight">
                    Verwalten Sie Ihren<br>
                    <span class="bg-gradient-to-r from-red-600 via-orange-500 to-amber-500 bg-clip-text text-transparent">
                        BrickLink-Store
                    </span><br>
                    professionell
                </h1>
                <p class="text-xl sm:text-2xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto mb-10">
                    Die umfassende Management-Lösung für BrickLink-Händler.<br>
                    Bestellungen, Rechnungen, Inventar – alles an einem Ort.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                           class="px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-red-600 to-orange-500 rounded-xl hover:from-red-700 hover:to-orange-600 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-200">
                            <i class="fas fa-rocket mr-2"></i> Kostenlos starten
                        </a>
                    @endif
                    <a href="#features"
                       class="px-8 py-4 text-lg font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
                        <i class="fas fa-info-circle mr-2"></i> Mehr erfahren
                    </a>
                </div>
            </div>

            <!-- Hero Image / Preview -->
            <div class="relative max-w-5xl mx-auto">
                <div class="absolute inset-0 bg-gradient-to-r from-red-500 to-orange-500 rounded-3xl blur-3xl opacity-20"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="aspect-video bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-xl overflow-hidden">
                        <img src="{{ asset('dashboard-preview.svg') }}"
                             alt="BrickStore Dashboard Preview"
                             class="w-full h-full object-cover">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-900">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                    Leistungsstarke Features
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-400">
                    Alles, was Sie für Ihren erfolgreichen BrickLink-Store benötigen
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1: Order Management -->
                <div class="group relative bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl p-8 shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 border border-gray-200 dark:border-gray-700">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg">
                        <i class="fas fa-shopping-cart text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        Order Management
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                        Synchronisieren Sie Ihre BrickLink-Bestellungen in Echtzeit. Pack-Ansicht mit automatischem Bilder-Caching für optimalen Workflow.
                    </p>
                </div>

                <!-- Feature 2: Invoice System -->
                <div class="group relative bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl p-8 shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 border border-gray-200 dark:border-gray-700">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg">
                        <i class="fas fa-file-invoice text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        Rechnungssystem
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                        Automatische Rechnungserstellung nach deutschen Standards (§19 UStG). PDF-Generierung und E-Mail-Versand inklusive.
                    </p>
                </div>

                <!-- Feature 3: Nextcloud Integration -->
                <div class="group relative bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl p-8 shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 border border-gray-200 dark:border-gray-700">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg">
                        <i class="fas fa-cloud text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        Nextcloud Integration
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                        Automatisches Backup Ihrer Rechnungen in Ihre Nextcloud. WebDAV-Anbindung mit flexiblen Pfad-Platzhaltern.
                    </p>
                </div>

                <!-- Feature 4: Inventory Management -->
                <div class="group relative bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl p-8 shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 border border-gray-200 dark:border-gray-700">
                    <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg">
                        <i class="fas fa-boxes text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        Inventarverwaltung
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                        Vollständige BrickLink Inventar-Synchronisation mit automatischem Image-Caching. Filter- und Suchfunktionen für schnellen Zugriff.
                    </p>
                </div>

                <!-- Feature 5: Brickognize -->
                <div class="group relative bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl p-8 shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 border border-gray-200 dark:border-gray-700">
                    <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-orange-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg">
                        <i class="fas fa-camera text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        Brickognize AI
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                        Kamera-basierte LEGO-Teil-Identifikation mit KI. Scannen, erkennen und direkt zu Ihrem Inventar hinzufügen.
                    </p>
                </div>

                <!-- Feature 6: Multi-Tenant -->
                <div class="group relative bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl p-8 shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 border border-gray-200 dark:border-gray-700">
                    <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        Multi-Tenant Ready
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                        Jeder Store mit eigenen API-Credentials, E-Mail-Einstellungen und individuellen Rechnungsvorlagen. Datensicherheit garantiert.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-r from-red-600 to-orange-500">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                <div class="p-8">
                    <div class="text-5xl font-bold text-white mb-2">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3 class="text-4xl font-bold text-white mb-2">Schnell</h3>
                    <p class="text-red-100">Automatisierte Workflows sparen Zeit</p>
                </div>
                <div class="p-8">
                    <div class="text-5xl font-bold text-white mb-2">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="text-4xl font-bold text-white mb-2">Sicher</h3>
                    <p class="text-red-100">Verschlüsselte Credentials & Daten</p>
                </div>
                <div class="p-8">
                    <div class="text-5xl font-bold text-white mb-2">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3 class="text-4xl font-bold text-white mb-2">Einfach</h3>
                    <p class="text-red-100">Intuitive Bedienung, keine Einarbeitung</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Technology Stack Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-800">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                    Moderne Technologie
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-400">
                    Gebaut mit bewährten und zukunftssicheren Technologien
                </p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="text-center p-6 bg-white dark:bg-gray-900 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <i class="fab fa-laravel text-5xl text-red-500 mb-4"></i>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Laravel 12</h4>
                </div>
                <div class="text-center p-6 bg-white dark:bg-gray-900 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <i class="fab fa-php text-5xl text-indigo-500 mb-4"></i>
                    <h4 class="font-semibold text-gray-900 dark:text-white">PHP 8.2+</h4>
                </div>
                <div class="text-center p-6 bg-white dark:bg-gray-900 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <div class="text-5xl mb-4">🎨</div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Tailwind CSS 4</h4>
                </div>
                <div class="text-center p-6 bg-white dark:bg-gray-900 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <i class="fas fa-mountain text-5xl text-teal-500 mb-4"></i>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Alpine.js</h4>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-900">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white mb-6">
                Bereit zu starten?
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-400 mb-10">
                Registrieren Sie sich jetzt kostenlos und bringen Sie Ihren BrickLink-Store auf das nächste Level.
            </p>
            @if (Route::has('register'))
                <a href="{{ route('register') }}"
                   class="inline-block px-10 py-5 text-lg font-semibold text-white bg-gradient-to-r from-red-600 to-orange-500 rounded-xl hover:from-red-700 hover:to-orange-600 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-200">
                    <i class="fas fa-user-plus mr-2"></i> Jetzt kostenlos registrieren
                </a>
            @endif
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 px-4 sm:px-6 lg:px-8 bg-gray-900 text-gray-400">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center space-x-3 mb-4 md:mb-0">
                    <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-orange-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-cube text-white text-xl"></i>
                    </div>
                    <span class="text-xl font-bold text-white">BrickStore</span>
                </div>
                <div class="text-center md:text-right">
                    <p class="mb-2">© {{ date('Y') }} BrickStore. Gebaut mit <i class="fas fa-heart text-red-500"></i> für die LEGO-Community.</p>
                    <p class="text-sm">Made with Laravel 12 & Tailwind CSS 4</p>
                </div>
            </div>
        </div>
    </footer>

</body>

</html>
