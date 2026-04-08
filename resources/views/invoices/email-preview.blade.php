<x-layouts.app title="E-Mail-Vorschau – {{ $invoice->invoice_number }}">
    <div class="p-6 max-w-5xl mx-auto">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    <i class="fa-solid fa-envelope-open-text text-blue-600"></i>
                    E-Mail-Vorschau
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Rechnung {{ $invoice->invoice_number }} – Überprüfen Sie die E-Mail vor dem Versand
                </p>
            </div>
            <a href="{{ route('invoices.show', $invoice) }}"
               class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fa-solid fa-arrow-left"></i> Zurück zur Rechnung
            </a>
        </div>

        <!-- Flash Messages -->
        @if(session('error'))
            <div class="mb-6 p-4 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded-lg flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()"><i class="fa-solid fa-times"></i></button>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- E-Mail-Vorschau -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <!-- E-Mail Header Info -->
                    <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                        <dl class="space-y-2 text-sm">
                            <div class="flex items-start gap-3">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium w-16 shrink-0">Von:</dt>
                                <dd class="text-gray-900 dark:text-white">
                                    {{ $fromName }} &lt;{{ $fromAddress }}&gt;
                                </dd>
                            </div>
                            <div class="flex items-start gap-3">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium w-16 shrink-0">An:</dt>
                                <dd class="text-gray-900 dark:text-white">
                                    {{ $invoice->customer_name }} &lt;{{ $invoice->customer_email }}&gt;
                                </dd>
                            </div>
                            <div class="flex items-start gap-3">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium w-16 shrink-0">Betreff:</dt>
                                <dd class="text-gray-900 dark:text-white font-semibold">{{ $subject }}</dd>
                            </div>
                            <div class="flex items-start gap-3">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium w-16 shrink-0">Anhang:</dt>
                                <dd class="text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded text-xs font-medium">
                                        <i class="fa-solid fa-file-pdf"></i>
                                        {{ $invoice->invoice_number }}.pdf
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- E-Mail Body -->
                    <div class="p-6">
                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden bg-white">
                            <iframe id="email-preview-frame"
                                    class="w-full border-0"
                                    style="min-height: 500px;">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar: Versand-Aktionen -->
            <div class="space-y-6">
                <!-- Empfänger-Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <i class="fa-solid fa-user text-blue-600"></i> Empfänger
                    </h2>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Name</dt>
                            <dd class="text-gray-900 dark:text-white font-medium mt-1">{{ $invoice->customer_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">E-Mail</dt>
                            <dd class="text-gray-900 dark:text-white font-medium mt-1">{{ $invoice->customer_email }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Betrag</dt>
                            <dd class="text-gray-900 dark:text-white font-bold text-lg mt-1">
                                {{ number_format($invoice->total, 2, ',', '.') }} {{ $invoice->currency }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Status-Warnung wenn bereits versendet -->
                @if($invoice->sent_via_email)
                    <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">Bereits versendet</p>
                                <p class="text-xs text-amber-700 dark:text-amber-400 mt-1">
                                    Diese Rechnung wurde bereits am {{ $invoice->email_sent_at?->format('d.m.Y H:i') }} per E-Mail versendet.
                                    Ein erneuter Versand ist möglich.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- SMTP-Check -->
                @if(!$invoice->store->hasSmtpCredentials())
                    <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-xmark text-red-600 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-semibold text-red-800 dark:text-red-300">SMTP nicht konfiguriert</p>
                                <p class="text-xs text-red-700 dark:text-red-400 mt-1">
                                    Bitte konfigurieren Sie zuerst die
                                    <a href="{{ route('store.settings') }}" class="underline font-medium">E-Mail-Einstellungen</a>.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Versand-Button -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6" x-data="{ confirming: false }">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <i class="fa-solid fa-paper-plane text-green-600"></i> Versand
                    </h2>

                    <div x-show="!confirming" class="space-y-3">
                        <button @click="confirming = true"
                                @if(!$invoice->store->hasSmtpCredentials()) disabled @endif
                                class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-paper-plane"></i> E-Mail jetzt senden
                        </button>
                        <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                            Die Rechnung wird als PDF-Anhang versendet.
                        </p>
                    </div>

                    <div x-show="confirming" x-cloak class="space-y-3">
                        <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-lg p-3">
                            <p class="text-sm text-amber-800 dark:text-amber-300 font-medium">
                                <i class="fa-solid fa-question-circle"></i>
                                Rechnung wirklich an <strong>{{ $invoice->customer_email }}</strong> senden?
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button @click="confirming = false"
                                    class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                Abbrechen
                            </button>
                            <form action="{{ route('invoices.send-email', $invoice) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit"
                                        class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                                    <i class="fa-solid fa-check"></i> Bestätigen
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- PDF-Vorschau Link -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <i class="fa-solid fa-file-pdf text-red-600"></i> Rechnung
                    </h2>
                    <div class="space-y-2">
                        <a href="{{ route('invoices.stream-pdf', $invoice) }}" target="_blank"
                           class="block w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-center">
                            <i class="fa-solid fa-eye"></i> PDF ansehen
                        </a>
                        <a href="{{ route('invoices.download-pdf', $invoice) }}"
                           class="block w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-center">
                            <i class="fa-solid fa-download"></i> PDF herunterladen
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const iframe = document.getElementById('email-preview-frame');
            if (iframe) {
                // Assign via JS to avoid Blade double-escaping the srcdoc attribute.
                // @json() JSON-encodes the string safely for use in JavaScript.
                iframe.srcdoc = @json($emailHtml);

                iframe.addEventListener('load', function() {
                    try {
                        const height = iframe.contentDocument.documentElement.scrollHeight;
                        iframe.style.height = Math.max(height + 40, 500) + 'px';
                    } catch (e) {
                        // Cross-origin fallback
                        iframe.style.height = '600px';
                    }
                });
            }
        });
    </script>
</x-layouts.app>

