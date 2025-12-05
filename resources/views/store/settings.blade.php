<x-layouts.app title="Store-Einstellungen">
    <div class="p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
            <i class="fa-solid fa-cog mr-2"></i> Store-Einstellungen
        </h1>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <!-- Tabs -->
        <div class="mb-6" x-data="{ tab: 'basic' }">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex space-x-8">
                    <button @click="tab = 'basic'" :class="tab === 'basic' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'"
                            class="py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fa-solid fa-store mr-2"></i> Grunddaten
                    </button>
                    <button @click="tab = 'bank'" :class="tab === 'bank' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'"
                            class="py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fa-solid fa-building-columns mr-2"></i> Bank
                    </button>
                    <button @click="tab = 'bricklink'" :class="tab === 'bricklink' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'"
                            class="py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fa-brands fa-lego mr-2"></i> BrickLink
                    </button>
                    <button @click="tab = 'smtp'" :class="tab === 'smtp' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'"
                            class="py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fa-solid fa-envelope mr-2"></i> E-Mail
                    </button>
                    <button @click="tab = 'nextcloud'" :class="tab === 'nextcloud' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'"
                            class="py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fa-solid fa-cloud mr-2"></i> Nextcloud
                    </button>
                </nav>
            </div>

            <!-- Tab: Grunddaten -->
            <div x-show="tab === 'basic'" class="mt-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <form action="{{ route('store.settings.basic') }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Store-Name</label>
                                <input type="text" name="name" value="{{ $store->name }}" required
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Firmenname</label>
                                <input type="text" name="company_name" value="{{ $store->company_name }}" required
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Inhaber</label>
                            <input type="text" name="owner_name" value="{{ $store->owner_name }}" required
                                   class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Straße & Hausnummer</label>
                            <input type="text" name="street" value="{{ $store->street }}" required
                                   class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">PLZ</label>
                                <input type="text" name="postal_code" value="{{ $store->postal_code }}" required
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Stadt</label>
                                <input type="text" name="city" value="{{ $store->city }}" required
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Land</label>
                                <input type="text" name="country" value="{{ $store->country }}" required
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Steuernummer</label>
                                <input type="text" name="tax_number" value="{{ $store->tax_number }}"
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">USt-IdNr.</label>
                                <input type="text" name="vat_id" value="{{ $store->vat_id }}"
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rechnungsnummern-Format</label>
                            <input type="text" name="invoice_number_format" value="{{ $store->invoice_number_format }}"
                                   class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono"
                                   placeholder="RE-{year}-{number}">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Verfügbare Platzhalter: {year}, {month}, {number}
                            </p>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="is_small_business" value="1" {{ $store->is_small_business ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 rounded">
                            <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                Kleinunternehmer gemäß § 19 UStG (keine MwSt.)
                            </label>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Speichern
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab: Bank -->
            <div x-show="tab === 'bank'" class="mt-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <form action="{{ route('store.settings.bank') }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bank</label>
                                <input type="text" name="bank_name" value="{{ $store->bank_name }}" required
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kontoinhaber</label>
                                <input type="text" name="bank_account_holder" value="{{ $store->bank_account_holder }}" required
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">IBAN</label>
                            <input type="text" name="iban" value="{{ $store->iban }}" required maxlength="34"
                                   class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">BIC</label>
                            <input type="text" name="bic" value="{{ $store->bic }}" required maxlength="11"
                                   class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono">
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Speichern
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab: BrickLink -->
            <div x-show="tab === 'bricklink'" class="mt-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <form action="{{ route('store.settings.bricklink') }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg mb-4">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                <i class="fa-solid fa-info-circle mr-2"></i>
                                Ihre API-Credentials werden verschlüsselt gespeichert.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Consumer Key</label>
                                <input type="text" name="bl_consumer_key" value="{{ $store->bl_consumer_key ? '••••••••' : '' }}"
                                       placeholder="{{ $store->bl_consumer_key ? 'Aktualisieren (leer lassen für keine Änderung)' : 'Consumer Key eingeben' }}"
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Consumer Secret</label>
                                <input type="password" name="bl_consumer_secret"
                                       placeholder="{{ $store->bl_consumer_secret ? 'Aktualisieren (leer lassen für keine Änderung)' : 'Consumer Secret eingeben' }}"
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Token</label>
                                <input type="text" name="bl_token" value="{{ $store->bl_token ? '••••••••' : '' }}"
                                       placeholder="{{ $store->bl_token ? 'Aktualisieren (leer lassen für keine Änderung)' : 'Token eingeben' }}"
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Token Secret</label>
                                <input type="password" name="bl_token_secret"
                                       placeholder="{{ $store->bl_token_secret ? 'Aktualisieren (leer lassen für keine Änderung)' : 'Token Secret eingeben' }}"
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono">
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Speichern
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab: SMTP -->
            <div x-show="tab === 'smtp'" class="mt-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <form action="{{ route('store.settings.smtp') }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMTP Host</label>
                                <input type="text" name="smtp_host" value="{{ $store->smtp_host }}"
                                       placeholder="smtp.gmail.com"
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMTP Port</label>
                                <input type="number" name="smtp_port" value="{{ $store->smtp_port }}"
                                       placeholder="587"
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMTP Benutzername</label>
                                <input type="text" name="smtp_username" value="{{ $store->smtp_username }}"
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMTP Passwort</label>
                                <input type="password" name="smtp_password"
                                       placeholder="{{ $store->smtp_password ? 'Aktualisieren (leer lassen für keine Änderung)' : 'Passwort eingeben' }}"
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Verschlüsselung</label>
                            <select name="smtp_encryption"
                                    class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <option value="tls" {{ $store->smtp_encryption === 'tls' ? 'selected' : '' }}>TLS</option>
                                <option value="ssl" {{ $store->smtp_encryption === 'ssl' ? 'selected' : '' }}>SSL</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Absender E-Mail</label>
                                <input type="email" name="smtp_from_address" value="{{ $store->smtp_from_address }}"
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Absender Name</label>
                                <input type="text" name="smtp_from_name" value="{{ $store->smtp_from_name }}"
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-4">
                            <button type="button" id="smtp-test-btn" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                                <i class="fa-solid fa-paper-plane mr-2"></i> Test-E-Mail senden
                            </button>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Speichern
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab: Nextcloud -->
            <div x-show="tab === 'nextcloud'" class="mt-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <form action="{{ route('store.settings.nextcloud') }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nextcloud URL</label>
                            <input type="url" name="nextcloud_url" value="{{ $store->nextcloud_url }}"
                                   placeholder="https://cloud.example.com"
                                   class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Benutzername</label>
                                <input type="text" name="nextcloud_username" value="{{ $store->nextcloud_username }}"
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Passwort / App-Passwort</label>
                                <input type="password" name="nextcloud_password"
                                       placeholder="{{ $store->nextcloud_password ? 'Aktualisieren (leer lassen für keine Änderung)' : 'Passwort eingeben' }}"
                                       class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rechnungspfad</label>
                            <input type="text" name="nextcloud_invoice_path" value="{{ $store->nextcloud_invoice_path }}"
                                   placeholder="/Rechnungen/{year}/{month}"
                                   class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Verfügbare Platzhalter: {year}, {month}, {day}, {store_name}
                            </p>
                        </div>

                        <div class="flex justify-between items-center pt-4">
                            <button type="button" id="nextcloud-test-btn" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                                <i class="fa-brands fa-nextcloud mr-2"></i> Verbindung testen
                            </button>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Speichern
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Weitere Tabs folgen analog... -->
        </div>
    </div>

    <script>
        // Handle test buttons with AJAX
        document.addEventListener('DOMContentLoaded', function() {
            // SMTP Test Button
            const smtpTestBtn = document.getElementById('smtp-test-btn');
            if (smtpTestBtn) {
                smtpTestBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    testConnection('{{ route("store.settings.smtp.test") }}', this);
                });
            }

            // Nextcloud Test Button
            const nextcloudTestBtn = document.getElementById('nextcloud-test-btn');
            if (nextcloudTestBtn) {
                nextcloudTestBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    testConnection('{{ route("store.settings.nextcloud.test") }}', this);
                });
            }
        });

        function testConnection(url, btn) {
            const originalText = btn.innerHTML;

            // Show loading state
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Wird getestet...';

            // Get CSRF token
            const csrfToken = document.querySelector('input[name="_token"]');
            const token = csrfToken ? csrfToken.value : '';

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                // Create notification
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 p-4 rounded-lg text-white z-50 transition-all duration-300 shadow-lg ${
                    data.success
                        ? 'bg-green-500 dark:bg-green-600'
                        : 'bg-red-500 dark:bg-red-600'
                }`;
                notification.innerHTML = `
                    <div class="flex items-center gap-2">
                        <i class="fa-solid ${data.success ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                        <span>${data.message}</span>
                    </div>
                `;
                document.body.appendChild(notification);

                // Auto-remove notification after 5 seconds
                setTimeout(() => {
                    notification.style.opacity = '0';
                    setTimeout(() => notification.remove(), 300);
                }, 5000);
            })
            .catch(error => {
                console.error('Test error:', error);
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 p-4 rounded-lg text-white bg-red-500 dark:bg-red-600 z-50 transition-all duration-300 shadow-lg';
                notification.innerHTML = `
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-exclamation-circle"></i>
                        <span>Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.</span>
                    </div>
                `;
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.style.opacity = '0';
                    setTimeout(() => notification.remove(), 300);
                }, 5000);
            })
            .finally(() => {
                // Restore button state
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        }
    </script>
</x-layouts.app>

