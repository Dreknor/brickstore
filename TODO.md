# BrickStore - Projekt TODO-Liste

**Projekt:** Multi-Tenant Laravel-Anwendung zur Verwaltung von BrickLink-Stores  
**Start:** 2025-12-04  
**Status:** In Entwicklung

---

## 📋 Projektübersicht

### Anforderungen Zusammenfassung
- **Multi-User:** Jeder Benutzer kann sich selbst registrieren und hat EINEN BrickLink-Store
- **Rollen:** Nur System-Admin (keine Store-spezifischen Rollen)
- **API:** BrickLink API (OAuth 1.0) + Brickognize API
- **Features:** Order-Management, Rechnungserstellung (PDF), E-Mail-Versand, Nextcloud-Integration
- **Tech-Stack:** Laravel 12, MySQL, Blade + Tailwind v4, Alpine.js

---

## ✅ Phase 1: Grundlagen & Datenbank-Schema

### 1.1 Projekt-Setup
- [x] .env auf MySQL umgestellt
- [x] App-Name auf "BrickStore" geändert
- [x] Locale auf Deutsch (de) gesetzt
- [x] MySQL-Datenbank "brickstore" erstellt
- [ ] Composer-Packages installieren
- [ ] NPM-Packages installieren

### 1.2 Datenbank-Migrationen
- [x] `stores` Tabelle Migration erstellt
- [x] `orders` Tabelle Migration
- [x] `order_items` Tabelle Migration
- [x] `invoices` Tabelle Migration
- [x] `users` Tabelle erweitern (is_admin Flag)
- [x] Migrationen ausgeführt

### 1.3 Models & Relationships
- [x] Store Model erstellt
- [x] Order Model erstellt
- [x] OrderItem Model erstellt
- [x] Invoice Model erstellt
- [x] User Model erweitert
- [x] Relationships definiert:
  - User hasOne Store
  - Store hasMany Orders
  - Order hasMany OrderItems
  - Order hasOne Invoice
  - Store hasMany Invoices

### 1.4 Model Casts & Encryption
- [x] Store Model: Verschlüsselte Felder (API-Keys, Passwörter)
- [x] Date-Casting für timestamps
- [x] Boolean-Casting

### 1.5 Factories & Seeders
- [x] StoreFactory erstellt (mit States: withBrickLinkCredentials, withSmtpCredentials, withNextcloudCredentials)
- [x] OrderFactory erstellt (mit States: paid, shipped, completed)
- [x] OrderItemFactory erstellt (mit States: part, minifig, set)
- [x] InvoiceFactory erstellt (mit States: paid, sent, smallBusiness)
- [x] DatabaseSeeder aktualisiert

---

## ✅ Phase 2: Authentication & Authorization

### 2.1 User-Registrierung
- [ ] Registrierungsformular anpassen
- [ ] E-Mail-Verifizierung (optional)
- [ ] Nach Registrierung → Store-Setup-Wizard

### 2.2 Policies
- [x] StorePolicy erstellt (User kann nur eigenen Store bearbeiten)
- [x] OrderPolicy erstellt
- [x] InvoicePolicy erstellt
- [x] Admin-Gate für System-Administration (via isAdmin() Methode)

### 2.3 Middleware
- [ ] EnsureUserHasStore Middleware (Prüfung ob Store-Setup abgeschlossen)
- [ ] Admin-Middleware

---

## ✅ Phase 3: Store-Management

### 3.1 Store CRUD
- [ ] Store-Controller erstellen
- [ ] Store-Setup-Wizard (nach Registrierung)
  - Schritt 1: Grunddaten (Name, BrickLink Store-Name)
  - Schritt 2: BrickLink API-Credentials
  - Schritt 3: Firmenadresse für Rechnungen
  - Schritt 4: Bank-Daten
  - Schritt 5: SMTP-Einstellungen
  - Schritt 6: Nextcloud WebDAV (optional)
- [ ] Store-Einstellungen bearbeiten
- [ ] Store aktivieren/deaktivieren

### 3.2 Store-Settings UI (Blade + Tailwind)
- [ ] Layout-Template erstellen
- [ ] Navigation mit Store-Switcher (falls später Multi-Store)
- [ ] Settings-Seiten:
  - [ ] Allgemeine Einstellungen
  - [ ] BrickLink API
  - [ ] Rechnungseinstellungen
  - [ ] E-Mail (SMTP)
  - [ ] Nextcloud
  - [ ] Firmen-/Bankdaten

### 3.3 Form Requests
- [ ] StoreStoreRequest (Validierung)
- [ ] UpdateStoreRequest
- [ ] UpdateBrickLinkCredentialsRequest
- [ ] UpdateInvoiceSettingsRequest
- [ ] UpdateSmtpSettingsRequest
- [ ] UpdateNextcloudSettingsRequest

---

## ✅ Phase 4: BrickLink API Integration

### 4.1 OAuth 1.0 Client
- [x] `composer require guzzlehttp/guzzle guzzlehttp/oauth-subscriber`
- [x] BrickLinkService erstellen (`app/Services/BrickLink/`)
- [x] OAuth-Authentifizierung implementieren
- [x] Base-Request-Methode mit Rate-Limiting

### 4.2 Orders API
- [x] `getOrders()` - Liste aller Orders abrufen
- [x] `getOrder($orderId)` - Einzelne Order abrufen
- [x] `getOrderItems($orderId)` - Order-Items abrufen
- [x] `updateOrderStatus($orderId, $status)` - Order-Status ändern
- [x] `getOrderMessages($orderId)` - Nachrichten abrufen
- [x] `sendOrderMessage($orderId, $message)` - Nachricht senden

### 4.3 Weitere Endpoints (später)
- [ ] Inventory API
- [ ] Catalog API
- [ ] Member API

### 4.4 Sync-Commands
- [x] `php artisan bricklink:sync-orders` Command
- [x] Scheduled Task für automatisches Sync (stündlich)
- [x] Queue-Job für Sync (asynchron)
- [x] BrickLink-Image-URLs Integration
- [x] "Alle synchronisieren"-Button in UI

---

## ✅ Phase 5: Order-Management

### 5.1 Order-Datenspeicherung
- [x] Orders von BrickLink abrufen und in DB speichern
- [x] Order-Items speichern
- [x] Status-Mapping (BrickLink → DB)
- [x] Duplikate vermeiden (Check auf bricklink_order_id)

### 5.2 Order-Controller & Routes
- [x] OrderController erstellen
- [x] Routes:
  - GET /orders - Liste
  - GET /orders/{order} - Details
  - GET /orders/{order}/pack - Pack-Ansicht
  - POST /orders/{order}/sync - Mit BrickLink synchronisieren
  - POST /orders/{order}/status - Status ändern
  - POST /orders/{order}/ship - Als versendet markieren
  - POST /orders/{order}/pack-item - Item als gepackt markieren
  - POST /orders/{order}/unpack-item - Item als ungepackt markieren

### 5.3 Order-UI (Blade + Tailwind)
- [x] Orders-Übersicht (Tabelle)
  - [x] Filter: Status, Datum, Käufer
  - [x] Suche: Order-ID, Käufer-Name
  - [x] Sortierung
  - [x] Pagination
- [x] Order-Details-Seite
  - [x] Käuferinformationen
  - [x] Versandadresse
  - [x] Bestellte Items (Tabelle)
  - [x] Zahlungsinformationen
  - [x] Status-Änderungen
- [x] Pack-Ansicht
  - [x] Items nach Store Location gruppiert
  - [x] Vorschaubilder mit lokalem Caching
  - [x] Checkbox zum Abhaken
  - [x] Fortschrittsbalken
  - [x] Alpine.js Integration
- [x] Aktions-Buttons:
  - [x] "Rechnung erstellen" (vorbereitet)
  - [x] "Als versendet markieren"
  - [x] "Mit BrickLink synchronisieren"

### 5.4 Order-Status-Management
- [x] Status-Badges implementiert (Tailwind)
- [ ] Status-Enum erstellen (optional)
- [ ] Status-History (optional)

### 5.5 Image-Caching-System
- [x] ImageCacheService erstellt
- [x] Lokales Caching von BrickLink-Bildern
- [x] Automatisches Caching beim Abruf
- [x] Artisan-Command zum manuellen Cachen aller Bilder
- [x] Cache-Statistiken
- [x] Integration in OrderItem Model
- [x] Dokumentation erstellt (docs/IMAGE_CACHING.md)

---

## ✅ Phase 6: Rechnungserstellung

### 6.1 Invoice-System
- [x] `composer require barryvdh/laravel-dompdf`
- [x] Invoice Model & Migration
- [x] InvoiceService erstellen
- [x] Rechnungsnummern-Generator (mit konfigurierbarem Format)
  - Format: `RE-{year}-{number}`, `{year}/{number}`, etc.
  - Counter pro Store
  - Atomare Inkrementierung (DB-Lock)

### 6.2 PDF-Generierung
- [x] Blade-Template für Rechnung (`resources/views/invoices/pdf.blade.php`)
- [x] Deutsche Rechnungsanforderungen:
  - [x] Vollständiger Name und Anschrift des leistenden Unternehmers
  - [x] Name und Anschrift des Leistungsempfängers
  - [x] Steuernummer oder USt-IdNr.
  - [x] Ausstellungsdatum
  - [x] Fortlaufende Rechnungsnummer
  - [x] Menge und Art der gelieferten Gegenstände
  - [x] Zeitpunkt der Lieferung
  - [x] Entgelt und Steuersatz
  - [x] Steuerbetrag (oder Hinweis auf Kleinunternehmerregelung §19 UStG)
- [x] Kleinunternehmer-Template (ohne MwSt.)
- [x] Normales Template (mit 19% MwSt.)
- [ ] Logo-Upload (optional - später)

### 6.3 Invoice-Controller
- [x] InvoiceController erstellen
- [x] Routes:
  - GET /invoices - Liste
  - GET /invoices/{invoice} - Anzeigen
  - GET /invoices/{invoice}/pdf - PDF herunterladen
  - POST /orders/{order}/invoice - Rechnung für Order erstellen
  - POST /invoices/{invoice}/send - Per E-Mail senden
  - POST /invoices/{invoice}/mark-paid - Als bezahlt markieren

### 6.4 Invoice-UI
- [x] Rechnungs-Übersicht
- [x] Rechnungs-Vorschau (PDF-Viewer inline)
- [x] Rechnungs-Download
- [ ] Massen-Aktionen (mehrere Rechnungen herunterladen/senden)

---

## ✅ Phase 7: E-Mail-System

### 7.1 Multi-SMTP Setup
- [x] Dynamischer SMTP-Service erstellt (in SendInvoiceEmailJob)
- [x] SMTP-Config zur Laufzeit laden (aus Store-Daten)
- [x] Mailer pro Store dynamisch konfigurieren
- [ ] Test-E-Mail-Funktion (Settings-Seite)

### 7.2 Mailables
- [x] InvoiceMail (Rechnung als PDF-Anhang)
- [ ] ShippingNotificationMail (Versandbestätigung)
- [ ] GeneralMessageMail (Allgemeine Nachrichten)
- [ ] TestMail (für SMTP-Test)

### 7.3 Mail-Templates (Blade)
- [x] E-Mail-Layout (Laravel Markdown)
- [x] Rechnung-Template
- [ ] Versandbestätigung-Template
- [ ] Allgemeine Nachricht-Template

### 7.4 Queue-Jobs
- [x] SendInvoiceEmailJob
- [ ] SendShippingNotificationJob
- [x] Retry-Logik bei Fehlern (3 Versuche)
- [ ] Failed-Jobs-Handling UI

---

## ✅ Phase 8: Nextcloud WebDAV Integration

### 8.1 WebDAV Client
- [ ] `composer require league/flysystem-webdav`
- [ ] NextcloudService erstellen
- [ ] Connection-Test-Methode
- [ ] Upload-Methode mit Pfad-Platzhaltern ({year}, {month}, {store})

### 8.2 Invoice-Upload
- [ ] Nach PDF-Erstellung automatisch hochladen (optional, Checkbox)
- [ ] Queue-Job: UploadInvoiceToNextcloudJob
- [ ] Fehlerbehandlung (Connection-Fehler, Ordner nicht vorhanden)
- [ ] Ordner automatisch erstellen falls nicht vorhanden

### 8.3 Settings & UI
- [ ] Nextcloud-Settings-Formular
- [ ] Verbindungstest-Button
- [ ] Upload-Status in Rechnung anzeigen (hochgeladen, fehlgeschlagen)

---

## ✅ Phase 9: Dashboard & Statistiken

### 9.1 Dashboard
- [ ] Dashboard-Controller
- [ ] Statistik-Widgets:
  - [ ] Neue Orders (heute/diese Woche)
  - [ ] Offene Zahlungen
  - [ ] Zu versendende Orders
  - [ ] Umsatz (Monat/Jahr)
- [ ] Charts (Chart.js oder ähnlich):
  - [ ] Umsatz-Verlauf
  - [ ] Order-Status-Verteilung
- [ ] Quick-Actions:
  - [ ] "Neue Orders synchronisieren"
  - [ ] "Alle unbezahlten Orders"
  - [ ] "Offene Versandbestätigungen"

### 9.2 Dashboard-UI
- [ ] Dashboard-Blade-Template
- [ ] Tailwind-Cards für Widgets
- [ ] Responsive Design

---

## ✅ Phase 10: Brickognize Integration

### 10.1 API-Integration
- [ ] BrickognizeService erstellen
- [ ] `recognizeItem($imageData)` - Bild hochladen & erkennen
- [ ] Result-Parsing
- [ ] Error-Handling

### 10.2 Inventory-Management UI
- [ ] Inventory-Controller
- [ ] Kamera/Upload-Interface (Alpine.js)
- [ ] Live-Preview
- [ ] Erkannte Teile anzeigen
- [ ] Teil zu Inventory hinzufügen (BrickLink API)

### 10.3 Routes & Views
- [ ] GET /inventory/recognize - Erkennungs-Seite
- [ ] POST /inventory/recognize - Bild hochladen
- [ ] Blade-View mit Kamera-Interface

---

## ✅ Phase 11: UI/UX & Design

### 11.1 Layout & Components
- [ ] App-Layout (Blade)
- [ ] Navigation (Header, Sidebar)
- [ ] Dark Mode Support (Tailwind v4 dark:)
- [ ] Responsive Design (Mobile, Tablet, Desktop)
- [ ] Loading-States (Alpine.js)
- [ ] Toast-Notifications (Success, Error)



### 11.3 Icons & Assets
- [ ] FontAwesome bereits vorhanden (blade-fontawesome)
- [ ] Logo hochladen/einbinden
- [ ] Favicon

---

## ✅ Phase 12: Testing

### 12.1 Feature Tests
- [ ] Store-Management Tests
  - [ ] Store erstellen
  - [ ] Store bearbeiten
  - [ ] Store-Policy (User kann nur eigenen Store sehen)
- [ ] Order-Management Tests
  - [ ] Orders synchronisieren
  - [ ] Order-Details anzeigen
  - [ ] Order-Status ändern
- [ ] Invoice Tests
  - [ ] Rechnung erstellen
  - [ ] PDF generieren
  - [ ] Rechnungsnummer-Generator
- [ ] E-Mail Tests (mit Mail::fake())
  - [ ] Rechnung senden
  - [ ] Versandbestätigung senden

### 12.2 Unit Tests
- [ ] BrickLinkService Tests (mit Mock)
- [ ] InvoiceNumberGenerator Test
- [ ] NextcloudService Tests (mit Mock)
- [ ] DynamicMailService Tests



---

## ✅ Phase 13: Security & Performance

### 13.1 Security
- [ ] API-Keys verschlüsselt in DB (Laravel Encryption)
- [ ] CSRF-Protection (bereits in Laravel)
- [ ] XSS-Prevention (Blade {{ }} bereits escaped)
- [ ] SQL-Injection-Prevention (Eloquent verwendet prepared statements)
- [ ] Rate-Limiting für API-Calls (BrickLink: max 5000/Tag)
- [ ] Rate-Limiting für Login/Registrierung

### 13.2 Performance
- [ ] Eager Loading für Relations (N+1-Problem vermeiden)
- [ ] Cache für BrickLink API-Responses (Orders, Catalog)
- [ ] Queue für zeitaufwändige Tasks:
  - [ ] PDF-Generierung
  - [ ] Nextcloud-Upload
  - [ ] E-Mail-Versand
  - [ ] BrickLink-Sync
- [ ] Database-Indexierung:
  - [ ] stores.bricklink_store_name
  - [ ] orders.bricklink_order_id
  - [ ] orders.status
  - [ ] invoices.invoice_number

### 13.3 Monitoring & Logging
- [x] ActivityLog Model & Migration
- [x] ActivityLogger Service
- [x] Error-Logging (Log::error())
- [x] Activity-Logging in Controllers
- [x] Database Logging mit UI für Admin
- [ ] Laravel Telescope installieren (optional)

---

## ✅ Phase 14: Admin-Bereich

### 14.1 Admin-Dashboard
- [x] Admin-Middleware
- [x] Admin-Routes (Prefix: /admin)
- [x] Admin-Dashboard (Statistiken)
- [x] Activity-Logs-Ansicht
- [ ] User-Verwaltung (aktivieren/deaktivieren)
- [ ] Store-Übersicht (alle Stores)

### 14.2 System-Settings
- [ ] Global-Settings (optional)
- [ ] Wartungsmodus
- [ ] System-Logs anzeigen

---

## ✅ Phase 15: Dokumentation

### 15.1 Code-Dokumentation
- [ ] PHPDoc für alle Methoden
- [ ] README.md aktualisieren
- [ ] API-Dokumentation (BrickLink-Service)

### 15.2 User-Dokumentation
- [ ] Setup-Anleitung
- [ ] BrickLink API-Keys generieren
- [ ] SMTP-Konfiguration
- [ ] Nextcloud-Setup

---

## ✅ Phase 16: Deployment

### 16.1 Production-Ready
- [ ] .env.example aktualisieren
- [ ] Migrations testen
- [ ] Seeders testen
- [ ] Queue-Worker konfigurieren
- [ ] Cron-Jobs einrichten (für Scheduler)
- [ ] SSL/HTTPS erzwingen

### 16.2 Backup & Restore
- [ ] Backup-Strategy für DB
- [ ] Backup für hochgeladene Dateien

---

## 📦 Benötigte Composer-Packages

```bash
composer require guzzlehttp/guzzle
composer require guzzlehttp/oauth-subscriber
composer require barryvdh/laravel-dompdf
composer require league/flysystem-webdav
```

**Optional:**
```bash
composer require spatie/laravel-permission  # Falls komplexere Rollen später benötigt
composer require laravel/telescope --dev    # Monitoring/Debugging
```

---

## 🎨 Benötigte NPM-Packages

Bereits vorhanden: Alpine.js v3, Tailwind v4

**Optional:**
```bash
npm install chart.js  # Für Dashboard-Charts
```

---

## 🚀 Nächste Schritte (AKTUELL)

**Phase 1-5 größtenteils abgeschlossen! ✅**

**Aktuelle Fortschritte:**
- ✅ Order-Management komplett funktionsfähig
- ✅ BrickLink API Integration (Orders, Items, Status, Shipping)
- ✅ Order-Status Update mit BrickLink-Synchronisation
- ✅ Tracking-Nummer Verwaltung
- ✅ Pack-Ansicht mit Image-Caching
- ✅ Tests für Status- und Shipping-Updates

**Nächste Prioritäten:**

1. **Phase 6: Rechnungserstellung** (teilweise erledigt, PDF & E-Mail)
   - [ ] Logo-Upload (optional)
   - [ ] Massen-Aktionen für Rechnungen

2. **Phase 7: E-Mail-System** (teilweise erledigt)
   - [ ] Test-E-Mail-Funktion in Settings
   - [ ] Versandbestätigung-Template
   - [ ] Failed-Jobs-Handling UI

3. **Phase 8: Nextcloud WebDAV Integration**
   - [ ] WebDAV Client implementieren
   - [ ] Invoice-Upload zu Nextcloud
   - [ ] Settings & Verbindungstest

4. **Phase 9: Dashboard & Statistiken**
   - [ ] Dashboard mit Widgets
   - [ ] Umsatz-Charts
   - [ ] Quick-Actions

5. **Phase 3: Store-Management UI**
   - [ ] Store-Setup-Wizard
   - [ ] Store-Einstellungen UI

---

**Stand:** 2025-12-04 (abends)  
**Aktueller Meilenstein:** Phase 5 (Order-Management) ✅ nahezu komplett - Phase 6-9 als nächstes

