<div x-data="brickognizeScanner" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <x-fas-camera class="w-5 h-5 text-blue-500" />
            LEGO-Teil identifizieren
        </h3>
    </div>

    <!-- Scanner-Interface -->
    <div class="space-y-4">
        <!-- Aktionsbuttons -->
        <div class="grid grid-cols-2 gap-3">
            <button
                @click="openCamera"
                :disabled="loading"
                class="flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg transition-colors"
            >
                <x-fas-video class="w-5 h-5" />
                <span>Kamera öffnen</span>
            </button>

            <label class="flex items-center justify-center gap-2 px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors cursor-pointer">
                <x-fas-upload class="w-5 h-5" />
                <span>Bild hochladen</span>
                <input type="file" accept="image/*" @change="handleFileUpload" class="hidden">
            </label>
        </div>

        <!-- Kamera-Stream (versteckt bis aktiviert) -->
        <div x-show="cameraActive" x-cloak class="relative bg-black rounded-lg overflow-hidden">
            <video x-ref="video" autoplay playsinline class="w-full"></video>
            <canvas x-ref="canvas" class="hidden"></canvas>

            <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-3">
                <button
                    @click="capturePhoto"
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg"
                >
                    <x-fas-camera class="w-5 h-5 inline" />
                    Foto aufnehmen
                </button>
                <button
                    @click="closeCamera"
                    class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg"
                >
                    Abbrechen
                </button>
            </div>
        </div>

        <!-- Bild-Vorschau -->
        <div x-show="preview" x-cloak class="relative">
            <div class="max-w-md mx-auto">
                <img :src="preview" class="w-full h-auto rounded-lg shadow-md" alt="Vorschau">
                <button
                    @click="clearPreview"
                    class="absolute top-4 right-4 p-2 bg-red-600 hover:bg-red-700 text-white rounded-full shadow-lg"
                >
                    <x-fas-times class="w-5 h-5" />
                </button>
            </div>
        </div>

        <!-- Lade-Animation -->
        <div x-show="loading" x-cloak class="flex items-center justify-center py-8">
            <div class="flex flex-col items-center gap-3">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <p class="text-gray-600 dark:text-gray-400">Analysiere Bild...</p>
            </div>
        </div>

        <!-- Identify-Button (nach Preview) -->
        <div x-show="preview && !loading" x-cloak class="space-y-2">
            <button
                @click="identifyItem"
                class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold shadow-md transition-all"
            >
                <x-fas-search class="w-5 h-5 inline mr-2" />
                Teil identifizieren
            </button>
            <button
                @click="clearPreview"
                class="w-full px-6 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm"
            >
                Abbrechen
            </button>
        </div>

        <!-- Fehler-Anzeige -->
        <div x-show="error" x-cloak class="p-4 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 rounded-lg">
            <p class="text-red-700 dark:text-red-300" x-text="error"></p>
        </div>

        <!-- Erfolgs-Anzeige -->
        <div x-show="success" x-cloak class="p-4 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 rounded-lg">
            <p class="text-green-700 dark:text-green-300" x-text="success"></p>
        </div>

        <!-- Letzte Identifikation -->
        <template x-if="lastIdentification">
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Letzte Identifikation:</h4>
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <div class="flex-shrink-0">
                        <img
                            x-show="lastIdentification.thumbnail"
                            :src="lastIdentification.thumbnail"
                            class="w-16 h-16 object-contain bg-white rounded shadow-sm"
                            alt="Teil"
                        />
                        <div x-show="!lastIdentification.thumbnail" class="w-16 h-16 bg-gray-200 dark:bg-gray-800 rounded flex items-center justify-center">
                            <x-fas-camera class="w-6 h-6 text-gray-400" />
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                            Teil #<span x-text="lastIdentification.item_no"></span>
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate" x-text="lastIdentification.item_name"></p>
                        <p class="text-xs text-gray-400 dark:text-gray-500" x-text="lastIdentification.time_ago"></p>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <span x-text="lastIdentification.confidence + '%'"></span>
                        </span>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

@pushOnce('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('brickognizeScanner', () => ({
        cameraActive: false,
        loading: false,
        error: null,
        success: null,
        preview: null,
        imageFile: null,
        stream: null,
        lastIdentification: null,

        init() {
            // Lade letzte Identifikation
            this.loadLastIdentification();
        },

        async openCamera() {
            try {
                this.error = null;
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' }
                });
                this.$refs.video.srcObject = this.stream;
                this.cameraActive = true;
            } catch (err) {
                console.error('Kamera-Fehler:', err);
                this.error = 'Kamera konnte nicht geöffnet werden. Bitte laden Sie stattdessen ein Bild hoch.';
            }
        },

        closeCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
            }
            this.cameraActive = false;
        },

        capturePhoto() {
            const video = this.$refs.video;
            const canvas = this.$refs.canvas;
            const context = canvas.getContext('2d');

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0);

            canvas.toBlob(blob => {
                this.imageFile = new File([blob], 'camera-capture.jpg', { type: 'image/jpeg' });
                this.preview = URL.createObjectURL(blob);
                this.closeCamera();
            }, 'image/jpeg', 0.9);
        },

        handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                this.error = 'Bitte wählen Sie ein Bild aus.';
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                this.error = 'Bild ist zu groß. Maximal 10 MB erlaubt.';
                return;
            }

            this.imageFile = file;
            this.preview = URL.createObjectURL(file);
            this.error = null;
        },

        clearPreview() {
            if (this.preview) {
                URL.revokeObjectURL(this.preview);
            }
            this.preview = null;
            this.imageFile = null;
            this.error = null;
            this.success = null;
        },

        async identifyItem() {
            if (!this.imageFile) {
                this.error = 'Kein Bild ausgewählt.';
                return;
            }

            this.loading = true;
            this.error = null;
            this.success = null;

            const formData = new FormData();
            formData.append('image', this.imageFile);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

            try {
                const response = await fetch('{{ route("brickognize.identify") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();
                console.log('🔍 Brickognize API Response:', data);

                if (data.success) {
                    this.success = 'Teil erfolgreich identifiziert!';

                    const modalData = {
                        identification_id: data.identification_id,
                        top_result: data.top_result,
                        results: data.data
                    };
                    console.log('📤 Sending to Modal:', modalData);

                    window.dispatchEvent(new CustomEvent('open-identification-result', {
                        detail: modalData
                    }));

                    this.loadLastIdentification();

                    setTimeout(() => {
                        this.clearPreview();
                    }, 2000);
                } else {
                    this.error = data.message || 'Identifikation fehlgeschlagen.';
                }
            } catch (err) {
                console.error('Identifikations-Fehler:', err);
                this.error = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
            } finally {
                this.loading = false;
            }
        },

        async loadLastIdentification() {
            try {
                const response = await fetch('{{ route("brickognize.history") }}?limit=1', {
                    headers: {
                        'Accept': 'application/json',
                    }
                });
                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    const item = data.data[0];
                    const apiResponse = item.api_response && item.api_response[0];
                    const imageUrl = apiResponse?.image_url || apiResponse?.thumbnail_url || apiResponse?.img_url || '';

                    this.lastIdentification = {
                        item_no: item.identified_item_no,
                        item_name: item.identified_item_name,
                        confidence: Math.round(item.confidence_score),
                        thumbnail: imageUrl,
                        time_ago: this.formatTimeAgo(item.created_at)
                    };

                    console.log('📸 Last Identification loaded:', {
                        item_no: item.identified_item_no,
                        thumbnail_url: imageUrl,
                        api_response_first: apiResponse
                    });
                }
            } catch (err) {
                console.error('Fehler beim Laden der Historie:', err);
            }
        },

        formatTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);

            if (diff < 60) return 'vor wenigen Sekunden';
            if (diff < 3600) return `vor ${Math.floor(diff / 60)} Minuten`;
            if (diff < 86400) return `vor ${Math.floor(diff / 3600)} Stunden`;
            return `vor ${Math.floor(diff / 86400)} Tagen`;
        }
    }));
});
</script>
@endPushOnce

<style>
    [x-cloak] { display: none !important; }
</style>

