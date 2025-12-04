<x-layouts.app title="Store Setup">
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center p-6">
        <div class="max-w-4xl w-full">
            <!-- Progress Bar -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Setup-Fortschritt</span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="currentStep + '/6'"></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                    <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                         :style="'width: ' + ((currentStep / 6) * 100) + '%'"></div>
                </div>
            </div>

            <!-- Setup Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8" x-data="setupWizard()">
                <!-- Welcome Screen -->
                <div x-show="step === 'welcome'" x-transition>
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                            Willkommen bei BrickStore! 🎉
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400 text-lg">
                            Lassen Sie uns Ihren Store in wenigen Schritten einrichten.
                        </p>
                    </div>
                    <div class="flex justify-center">
                        <button @click="nextStep()"
                                class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-lg font-semibold">
                            <i class="fa-solid fa-arrow-right mr-2"></i> Los geht's
                        </button>
                    </div>
                </div>

                <!-- Step 1: Basic Info -->
                <div x-show="step === 'basic'" x-transition>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                        <i class="fa-solid fa-store text-blue-600 mr-2"></i> Grundinformationen
                    </h2>
                    <form @submit.prevent="submitStep('basic')" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Store-Name *
                                </label>
                                <input type="text" x-model="formData.basic.name" required
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Firmenname *
                                </label>
                                <input type="text" x-model="formData.basic.company_name" required
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Inhaber *
                            </label>
                            <input type="text" x-model="formData.basic.owner_name" required
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Straße & Hausnummer *
                            </label>
                            <input type="text" x-model="formData.basic.street" required
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    PLZ *
                                </label>
                                <input type="text" x-model="formData.basic.postal_code" required
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Stadt *
                                </label>
                                <input type="text" x-model="formData.basic.city" required
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Land *
                                </label>
                                <input type="text" x-model="formData.basic.country" required value="Deutschland"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Steuernummer
                                </label>
                                <input type="text" x-model="formData.basic.tax_number"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    USt-IdNr.
                                </label>
                                <input type="text" x-model="formData.basic.vat_id"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" x-model="formData.basic.is_small_business" id="is_small_business"
                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                            <label for="is_small_business" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                Kleinunternehmer gemäß § 19 UStG (keine MwSt.)
                            </label>
                        </div>

                        <div class="flex justify-between pt-6">
                            <button type="button" @click="step = 'welcome'"
                                    class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                                Zurück
                            </button>
                            <button type="submit" :disabled="loading"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                                <span x-show="!loading">Weiter</span>
                                <span x-show="loading">Wird gespeichert...</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 2: Bank Info -->
                <div x-show="step === 'bank'" x-transition>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                        <i class="fa-solid fa-building-columns text-blue-600 mr-2"></i> Bankverbindung
                    </h2>
                    <form @submit.prevent="submitStep('bank')" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Bank *
                                </label>
                                <input type="text" x-model="formData.bank.bank_name" required
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Kontoinhaber *
                                </label>
                                <input type="text" x-model="formData.bank.bank_account_holder" required
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                IBAN *
                            </label>
                            <input type="text" x-model="formData.bank.iban" required maxlength="34"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                BIC *
                            </label>
                            <input type="text" x-model="formData.bank.bic" required maxlength="11"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono">
                        </div>

                        <div class="flex justify-between pt-6">
                            <button type="button" @click="step = 'basic'"
                                    class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                                Zurück
                            </button>
                            <button type="submit" :disabled="loading"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                                <span x-show="!loading">Weiter</span>
                                <span x-show="loading">Wird gespeichert...</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 3: BrickLink API -->
                <div x-show="step === 'bricklink'" x-transition>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                        <i class="fa-solid fa-key text-blue-600 mr-2"></i> BrickLink API
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Verbinden Sie Ihren BrickLink Store, um Bestellungen automatisch zu synchronisieren.
                    </p>
                    <form @submit.prevent="submitStep('bricklink')" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Consumer Key *
                                </label>
                                <input type="text" x-model="formData.bricklink.bl_consumer_key" required
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Consumer Secret *
                                </label>
                                <input type="password" x-model="formData.bricklink.bl_consumer_secret" required
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Token *
                                </label>
                                <input type="text" x-model="formData.bricklink.bl_token" required
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Token Secret *
                                </label>
                                <input type="password" x-model="formData.bricklink.bl_token_secret" required
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono">
                            </div>
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                <i class="fa-solid fa-info-circle mr-2"></i>
                                Erstellen Sie Ihre API-Credentials in Ihrem BrickLink Store unter Settings → API.
                            </p>
                        </div>

                        <div class="flex justify-between pt-6">
                            <button type="button" @click="step = 'bank'"
                                    class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                                Zurück
                            </button>
                            <button type="submit" :disabled="loading"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                                <span x-show="!loading">Weiter</span>
                                <span x-show="loading">Wird gespeichert...</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 4: SMTP / Email -->
                <div x-show="step === 'smtp'" x-transition>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                        <i class="fa-solid fa-envelope text-blue-600 mr-2"></i> E-Mail Einstellungen
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Konfigurieren Sie Ihre SMTP-Einstellungen für den Versand von Rechnungen per E-Mail.
                    </p>
                    <form @submit.prevent="submitStep('smtp')" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    SMTP Host *
                                </label>
                                <input type="text" x-model="formData.smtp.smtp_host" required
                                       placeholder="smtp.gmail.com"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    SMTP Port *
                                </label>
                                <input type="number" x-model="formData.smtp.smtp_port" required
                                       placeholder="587"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    SMTP Benutzername *
                                </label>
                                <input type="text" x-model="formData.smtp.smtp_username" required
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    SMTP Passwort *
                                </label>
                                <input type="password" x-model="formData.smtp.smtp_password" required
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Verschlüsselung *
                            </label>
                            <select x-model="formData.smtp.smtp_encryption" required
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Absender E-Mail *
                                </label>
                                <input type="email" x-model="formData.smtp.smtp_from_address" required
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Absender Name *
                                </label>
                                <input type="text" x-model="formData.smtp.smtp_from_name" required
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div class="flex justify-between pt-6">
                            <button type="button" @click="step = 'bricklink'"
                                    class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                                Zurück
                            </button>
                            <button type="submit" :disabled="loading"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                                <span x-show="!loading">Weiter</span>
                                <span x-show="loading">Wird gespeichert...</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 5: Nextcloud -->
                <div x-show="step === 'nextcloud'" x-transition>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                        <i class="fa-solid fa-cloud text-blue-600 mr-2"></i> Nextcloud (Optional)
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Verbinden Sie Ihre Nextcloud-Instanz, um Rechnungen automatisch hochzuladen.
                    </p>
                    <form @submit.prevent="submitStep('nextcloud')" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nextcloud URL
                            </label>
                            <input type="url" x-model="formData.nextcloud.nextcloud_url"
                                   placeholder="https://cloud.example.com"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Benutzername
                                </label>
                                <input type="text" x-model="formData.nextcloud.nextcloud_username"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Passwort / App-Passwort
                                </label>
                                <input type="password" x-model="formData.nextcloud.nextcloud_password"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Rechnungspfad
                            </label>
                            <input type="text" x-model="formData.nextcloud.nextcloud_invoice_path"
                                   placeholder="/Invoices"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Verfügbare Platzhalter: {year}, {month}
                            </p>
                        </div>

                        <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                <i class="fa-solid fa-info-circle mr-2"></i>
                                Dieser Schritt ist optional. Sie können ihn überspringen.
                            </p>
                        </div>

                        <div class="flex justify-between pt-6">
                            <button type="button" @click="step = 'smtp'"
                                    class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                                Zurück
                            </button>
                            <button type="submit" :disabled="loading"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                                <span x-show="!loading">Weiter</span>
                                <span x-show="loading">Wird gespeichert...</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 6: Complete -->
                <div x-show="step === 'complete'" x-transition>
                    <div class="text-center">
                        <div class="mb-6">
                            <i class="fa-solid fa-check-circle text-6xl text-green-500"></i>
                        </div>
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                            Setup abgeschlossen! 🎉
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400 text-lg mb-8">
                            Ihr Store ist jetzt bereit. Sie können nun mit der Verwaltung Ihrer Bestellungen und Rechnungen beginnen.
                        </p>
                        <button @click="submitStep('complete')" :disabled="loading"
                                class="px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 text-lg font-semibold disabled:opacity-50">
                            <span x-show="!loading">
                                <i class="fa-solid fa-arrow-right mr-2"></i> Zum Dashboard
                            </span>
                            <span x-show="loading">Wird abgeschlossen...</span>
                        </button>
                    </div>
                </div>

                @push('scripts')
                <script>
                function setupWizard() {
                    return {
                        step: 'welcome',
                        currentStep: 0,
                        loading: false,
                        formData: {
                            basic: {
                                name: '{{ $store->name ?? '' }}',
                                company_name: '',
                                owner_name: '',
                                street: '',
                                postal_code: '',
                                city: '',
                                country: 'Deutschland',
                                tax_number: '',
                                vat_id: '',
                                is_small_business: false
                            },
                            bank: {
                                bank_name: '',
                                bank_account_holder: '',
                                iban: '',
                                bic: ''
                            },
                            bricklink: {
                                bl_consumer_key: '',
                                bl_consumer_secret: '',
                                bl_token: '',
                                bl_token_secret: ''
                            },
                            smtp: {
                                smtp_host: '',
                                smtp_port: 587,
                                smtp_username: '',
                                smtp_password: '',
                                smtp_encryption: 'tls',
                                smtp_from_address: '',
                                smtp_from_name: ''
                            },
                            nextcloud: {
                                nextcloud_url: '',
                                nextcloud_username: '',
                                nextcloud_password: '',
                                nextcloud_invoice_path: '/Invoices'
                            }
                        },

                        nextStep() {
                            const steps = ['welcome', 'basic', 'bank', 'bricklink', 'smtp', 'nextcloud', 'complete'];
                            const currentIndex = steps.indexOf(this.step);
                            if (currentIndex < steps.length - 1) {
                                this.step = steps[currentIndex + 1];
                                this.currentStep = currentIndex + 1;
                            }
                        },

                        submitStep(stepName) {
                            this.loading = true;

                            fetch('{{ route('store.setup-step') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify({
                                    step: stepName,
                                    ...this.formData[stepName]
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                this.loading = false;
                                if (data.success) {
                                    if (data.redirect) {
                                        window.location.href = data.redirect;
                                    } else {
                                        this.step = data.next_step;
                                        this.currentStep++;
                                    }
                                }
                            })
                            .catch(error => {
                                this.loading = false;
                                alert('Fehler: ' + error.message);
                            });
                        }
                    }
                }
                </script>
                @endpush
            </div>
        </div>
    </div>
</x-layouts.app>

