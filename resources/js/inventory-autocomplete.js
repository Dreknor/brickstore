/**
 * Inventory Auto-Complete
 * Lädt automatisch Daten von BrickLink wenn Teilenummer und Farbe eingegeben werden
 */

document.addEventListener('DOMContentLoaded', function() {
    const itemNoInput = document.getElementById('item_no');
    const itemTypeSelect = document.getElementById('item_type');
    const colorSelect = document.getElementById('color_id');

    // Loader fields (für "Von BrickLink laden" Bereich)
    const loaderItemNoInput = document.getElementById('loader_item_no');
    const loaderItemTypeSelect = document.getElementById('loader_item_type');
    const loaderColorSelect = document.getElementById('loader_color_id');

    const unitPriceInput = document.getElementById('unit_price');

    // Container für Preview und Price Guide
    let previewContainer = document.getElementById('item-preview-container');
    let priceGuideContainer = document.getElementById('price-guide-container');

    // Erstelle Container falls nicht vorhanden
    if (!previewContainer) {
        previewContainer = document.createElement('div');
        previewContainer.id = 'item-preview-container';
        previewContainer.className = 'mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hidden';

        // Füge nach dem Item-Info Bereich ein
        const itemInfoSection = document.querySelector('form > div:first-child');
        if (itemInfoSection) {
            itemInfoSection.appendChild(previewContainer);
        }
    }

    if (!priceGuideContainer) {
        priceGuideContainer = document.createElement('div');
        priceGuideContainer.id = 'price-guide-container';
        priceGuideContainer.className = 'mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 hidden';

        // Füge nach dem Preview Container ein
        if (previewContainer.parentNode) {
            previewContainer.parentNode.insertBefore(priceGuideContainer, previewContainer.nextSibling);
        }
    }

    // Debounce-Funktion
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Lade Item-Daten von BrickLink
    async function loadItemData() {
        // Priorisiere Loader-Felder, falls vorhanden und ausgefüllt
        const itemNo = (loaderItemNoInput?.value.trim() || itemNoInput?.value.trim());
        const itemType = (loaderItemTypeSelect?.value || itemTypeSelect?.value);
        const colorId = (loaderColorSelect?.value || colorSelect?.value);
        const newOrUsed = document.querySelector('input[name="new_or_used"]:checked')?.value || 'N';

        if (!itemNo || !itemType || !colorId) {
            hidePreview();
            hidePriceGuide();
            return;
        }

        showLoading();

        try {
            // Lade Item-Daten und Price Guide parallel
            const [itemResponse, priceGuideResponse] = await Promise.all([
                fetch(`/api/bricklink/item-info?item_no=${encodeURIComponent(itemNo)}&item_type=${itemType}&color_id=${colorId}`),
                fetch(`/api/bricklink/price-guide?item_no=${encodeURIComponent(itemNo)}&item_type=${itemType}&color_id=${colorId}&new_or_used=${newOrUsed}`)
            ]);

            if (itemResponse.ok) {
                const itemData = await itemResponse.json();
                displayPreview(itemData);
            }

            if (priceGuideResponse.ok) {
                const priceGuide = await priceGuideResponse.json();
                displayPriceGuide(priceGuide);

                // Setze Preis-Vorschlag wenn Feld leer ist
                if (priceGuide.avg_price && (!unitPriceInput?.value || unitPriceInput.value === '0.000')) {
                    unitPriceInput.value = parseFloat(priceGuide.avg_price).toFixed(3);
                }
            }

        } catch (error) {
            console.error('Fehler beim Laden der BrickLink-Daten:', error);
            showError('Fehler beim Laden der Daten von BrickLink');
        }
    }

    // Zeige Ladeindikator
    function showLoading() {
        previewContainer.innerHTML = '<div class="text-center"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Lade Daten...</div>';
        previewContainer.classList.remove('hidden');

        priceGuideContainer.innerHTML = '<div class="text-center"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Lade Preisempfehlungen...</div>';
        priceGuideContainer.classList.remove('hidden');
    }

    // Zeige Fehler
    function showError(message) {
        previewContainer.innerHTML = `<div class="text-red-600 dark:text-red-400"><i class="fa-solid fa-exclamation-triangle mr-2"></i> ${message}</div>`;
        priceGuideContainer.classList.add('hidden');
    }

    // Verstecke Preview
    function hidePreview() {
        previewContainer.classList.add('hidden');
        previewContainer.innerHTML = '';
    }

    // Verstecke Price Guide
    function hidePriceGuide() {
        priceGuideContainer.classList.add('hidden');
        priceGuideContainer.innerHTML = '';
    }

    // Zeige Item-Preview
    function displayPreview(data) {
        const imageUrl = data.image_url || data.thumbnail_url || '';
        const itemName = data.name || 'Unbekanntes Teil';
        const categoryName = data.category_name || '';

        previewContainer.innerHTML = `
            <div class="flex items-start gap-4">
                ${imageUrl ? `
                    <div class="flex-shrink-0">
                        <img src="${imageUrl}"
                             alt="${itemName}"
                             class="w-32 h-32 object-contain bg-white rounded border border-gray-200 dark:border-gray-600">
                    </div>
                ` : ''}
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        <i class="fa-solid fa-cube mr-2 text-blue-500"></i>
                        ${itemName}
                    </h3>
                    ${categoryName ? `
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                            <i class="fa-solid fa-folder mr-2"></i>
                            Kategorie: ${categoryName}
                        </p>
                    ` : ''}
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <i class="fa-solid fa-hashtag mr-2"></i>
                        ${data.item_no || ''} (${data.item_type || ''})
                    </p>
                </div>
            </div>
        `;
        previewContainer.classList.remove('hidden');
    }

    // Zeige Price Guide
    function displayPriceGuide(data) {
        if (!data || !data.avg_price) {
            priceGuideContainer.innerHTML = `
                <div class="text-gray-600 dark:text-gray-400">
                    <i class="fa-solid fa-info-circle mr-2"></i>
                    Keine Preisempfehlungen verfügbar
                </div>
            `;
            return;
        }

        const condition = data.new_or_used === 'N' ? 'Neu' : 'Gebraucht';

        priceGuideContainer.innerHTML = `
            <div>
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">
                    <i class="fa-solid fa-chart-line mr-2"></i>
                    Preisempfehlungen (${condition})
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Durchschnittspreis -->
                    <div class="bg-white dark:bg-gray-800 p-3 rounded border border-blue-200 dark:border-blue-700">
                        <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Durchschnitt</div>
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            ${parseFloat(data.avg_price).toFixed(3)}€
                        </div>
                    </div>

                    <!-- Minimalpreis -->
                    ${data.min_price ? `
                        <div class="bg-white dark:bg-gray-800 p-3 rounded border border-green-200 dark:border-green-700">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Minimum</div>
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                ${parseFloat(data.min_price).toFixed(3)}€
                            </div>
                        </div>
                    ` : ''}

                    <!-- Maximalpreis -->
                    ${data.max_price ? `
                        <div class="bg-white dark:bg-gray-800 p-3 rounded border border-orange-200 dark:border-orange-700">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Maximum</div>
                            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                ${parseFloat(data.max_price).toFixed(3)}€
                            </div>
                        </div>
                    ` : ''}

                    <!-- Verkaufte Menge -->
                    ${data.qty_sold ? `
                        <div class="bg-white dark:bg-gray-800 p-3 rounded border border-purple-200 dark:border-purple-700">
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Verkauft</div>
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                ${data.qty_sold}×
                            </div>
                        </div>
                    ` : ''}
                </div>

                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    <i class="fa-solid fa-clock mr-1"></i>
                    Basierend auf BrickLink Marktdaten
                </div>
            </div>
        `;
        priceGuideContainer.classList.remove('hidden');
    }

    // Event Listeners mit Debounce
    const debouncedLoad = debounce(loadItemData, 500);

    // Normale Formular-Felder
    if (itemNoInput) {
        itemNoInput.addEventListener('input', debouncedLoad);
    }

    if (itemTypeSelect) {
        itemTypeSelect.addEventListener('change', loadItemData);
    }

    if (colorSelect) {
        colorSelect.addEventListener('change', loadItemData);
    }

    // Loader-Felder (aus "Von BrickLink laden" Bereich)
    if (loaderItemNoInput) {
        loaderItemNoInput.addEventListener('input', debouncedLoad);
    }

    if (loaderItemTypeSelect) {
        loaderItemTypeSelect.addEventListener('change', loadItemData);
    }

    if (loaderColorSelect) {
        loaderColorSelect.addEventListener('change', loadItemData);
    }

    // Auch auf new_or_used Änderungen reagieren
    document.querySelectorAll('input[name="new_or_used"]').forEach(radio => {
        radio.addEventListener('change', loadItemData);
    });
});

