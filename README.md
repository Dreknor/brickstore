# BrickStore - BrickLink Store Management System

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Tailwind](https://img.shields.io/badge/Tailwind-4-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)

**Multi-Tenant Laravel-Anwendung zur Verwaltung von BrickLink-Stores**

[Features](#-features) • [Installation](#-installation) • [Dokumentation](#-dokumentation) • [Roadmap](#-roadmap)

</div>

---

## 📋 Übersicht

**BrickStore** ist eine umfassende Verwaltungslösung für BrickLink-Händler, die es ermöglicht:

- 📦 **BrickLink-Orders** zentral zu verwalten
- 🧾 **Professionelle Rechnungen** nach deutschen Standards zu erstellen
- 📧 **Automatisierten E-Mail-Versand** über store-spezifische SMTP-Konten
- ☁️ **Nextcloud-Integration** für automatisches Rechnungs-Backup
- 📸 **Brickognize** für Kamera-basierte LEGO-Teile-Erkennung (geplant)

### Multi-Tenant-Architektur

Jeder registrierte Benutzer kann seinen eigenen BrickLink-Store verwalten mit:
- Separaten API-Credentials
- Eigenen E-Mail-Einstellungen
- Individuellen Rechnungsvorlagen
- Store-spezifischer Nextcloud-Anbindung

---

## ✨ Features

### ✅ Aktuell implementiert (Phase 1)

- **Benutzer-Verwaltung**
  - Selbstregistrierung
  - Admin-Dashboard
  - Store-spezifische Berechtigungen

- **Store-Management**
  - BrickLink API-Integration (OAuth 1.0)
  - Verschlüsselte Credential-Speicherung
  - SMTP-Konfiguration pro Store
  - Nextcloud WebDAV-Anbindung

- **Order-Management** ✅
  - BrickLink API Sync (Echtzeit)
  - Order-Status-Updates
  - Pack-Ansicht mit Bilder-Caching
  - Versandverfolgung

- **Rechnungssystem** ✅
  - Automatische Rechnungsnummern
  - Deutsche Compliance (§19 UStG)
  - PDF-Generierung (DomPDF)
  - Kleinunternehmerregelung

- **Nextcloud-Integration** ✅ **NEU!**
  - Automatisches Rechnungs-Backup zu Nextcloud
  - WebDAV-Anbindung mit Pfad-Platzhaltern
  - Asynchrone Queue-Verarbeitung
  - Fehlerbehandlung mit Retries

- **Datenbank-Schema**
  - Orders & Order-Items mit Relationships
  - Invoices mit Upload-Status
  - Automatische Rechnungsnummern
  - Unique-Constraint: eine Rechnung pro Bestellung

### 🚧 In Entwicklung (Phase 2-3)

- Store-Setup-Wizard
- Store-Settings UI
- E-Mail-Versand-UI
- Dashboard mit Statistiken

### 🔄 Kürzlich implementiert (Phase 3)

- **Inventarverwaltung**
  - BrickLink Inventar-Synchronisation
  - Automatisches Image-Caching für optimale Performance
  - Lazy Loading der Bilder
  - Filter- und Suchfunktionen
  - Artisan-Commands für Batch-Operations

- **Brickognize-Integration** ✅ **NEU!**
  - 📸 Kamera-basierte LEGO-Teil-Identifikation
  - Direkter Zugriff auf Geräte-Kamera oder Datei-Upload
  - Automatische Inventar-Suche
  - Quick-Add: Teile zu bestehendem Bestand hinzufügen
  - Neuen Artikel aus erkannten Daten erstellen
  - Identifikations-Historie

### 📅 Geplant (Phase 4+)

- **Brickognize Erweiterte Features**
  - Batch-Upload (mehrere Bilder)
  - Erweiterte Statistiken
  - PWA-Funktionalität
- Shipping-Label-Generator
- Erweiterte Statistiken & Reports

---

## 🚀 Installation

### Voraussetzungen

- **PHP:** 8.2 oder höher
- **MySQL/MariaDB:** 8.0+
- **Composer:** 2.x
- **Node.js:** 18+ & NPM
- **Web-Server:** Apache/Nginx oder `php artisan serve`

### Schnellstart

```bash
# 1. Repository klonen
git clone https://github.com/your-username/brickstore.git
cd brickstore

# 2. Dependencies installieren
composer install
npm install

# 3. Umgebung konfigurieren
cp .env.example .env
# .env bearbeiten: Datenbank-Zugangsdaten eintragen

# 4. App Key generieren
php artisan key:generate

# 5. Datenbank erstellen
mysql -u root -p -e "CREATE DATABASE brickstore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6. Migrationen ausführen & Test-Daten laden
php artisan migrate --seed

# 7. Frontend kompilieren
npm run build

# 8. Development-Server starten
php artisan serve
```

Die Anwendung ist nun unter `http://localhost:8000` erreichbar.

### Login-Daten (Entwicklung)

Nach dem Seeding stehen folgende Test-Accounts zur Verfügung:

- **Admin:** `admin@brickstore.local` / `password`
- **Test-User:** `test@brickstore.local` / `password`

---

## 🔧 Konfiguration

### Umgebungsvariablen (.env)

```env
# App
APP_NAME=BrickStore
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_LOCALE=de

# Datenbank
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=brickstore
DB_USERNAME=root
DB_PASSWORD=

# Mail (global, kann pro Store überschrieben werden)
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
```

### BrickLink API-Credentials

Jeder Store benötigt eigene BrickLink API-Credentials:

1. Besuche https://www.bricklink.com/v2/api/register_consumer.page
2. Erstelle eine neue Consumer-Registrierung
3. Speichere Consumer Key, Consumer Secret, Token und Token Secret
4. Trage die Credentials in den Store-Einstellungen ein

---

## 📚 Dokumentation

### Implementierte Features

- **[NEXTCLOUD_INTEGRATION.md](docs/NEXTCLOUD_INTEGRATION.md)** - Nextcloud WebDAV Setup & Verwendung
- **[IMPLEMENTATION_SUMMARY.md](docs/IMPLEMENTATION_SUMMARY.md)** - Detaillierte Implementierungsdokumentation
- **[IMAGE_CACHING.md](docs/IMAGE_CACHING.md)** - Bilder-Caching-System für Order-Items
- **[BRICKLINK_INVENTORY_API.md](docs/BRICKLINK_INVENTORY_API.md)** - BrickLink Inventory API-Integration
- **[BRICKOGNIZE_IMPLEMENTATION_REPORT.md](docs/BRICKOGNIZE_IMPLEMENTATION_REPORT.md)** - ✅ Brickognize Implementierungsbericht
- **[BRICKOGNIZE_QUICK_START.md](docs/BRICKOGNIZE_QUICK_START.md)** - 🚀 Quick Start Guide für Entwickler

### Geplante Features

- **[BRICKOGNIZE_INTEGRATION.md](docs/BRICKOGNIZE_INTEGRATION.md)** - ✅ Kamera-basierte Teil-Identifikation (IMPLEMENTIERT!)

### Datenbankstruktur

Siehe [TODO.md](TODO.md) für Details zum Datenbank-Schema.

**Haupttabellen:**
- `users` - Benutzerkonten
- `stores` - BrickLink-Stores (1:1 zu User)
- `orders` - Bestellungen aus BrickLink
- `order_items` - Bestellpositionen
- `invoices` - Generierte Rechnungen (unique pro Order)
- `activity_logs` - Audit-Trail

### Entwickler-Dokumentation

```bash
# Tests ausführen
php artisan test

# Nextcloud-Verbindung testen
php artisan nextcloud:test-connection

# Code-Formatierung (Laravel Pint)
vendor/bin/pint

# Static Analysis (Larastan)
vendor/bin/phpstan analyse

# Queue-Worker starten
php artisan queue:work
```

---

## 🗺️ Roadmap

Siehe [TODO.md](TODO.md) für die vollständige Projekt-Roadmap.

**Kurzübersicht:**

- [x] **Phase 1:** Datenbank-Schema & Models ✅
- [x] **Phase 2:** Authentication & Policies ✅
- [x] **Phase 3:** Store-Management Backend ✅
- [x] **Phase 4:** BrickLink API-Integration ✅
- [x] **Phase 5:** Order-Management ✅
- [x] **Phase 6:** Rechnungserstellung (PDF) ✅
- [x] **Phase 7:** Nextcloud WebDAV Integration ✅
- [ ] **Phase 8:** Dashboard & Statistiken (UI ausstehend)
- [ ] **Phase 9:** E-Mail-System UI
- [ ] **Phase 10:** Brickognize-Integration

---

## 🛠️ Tech-Stack

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** Blade, Tailwind CSS v4, Alpine.js v3
- **Datenbank:** MySQL 8.0+
- **Testing:** Pest v3
- **Code-Quality:** Laravel Pint, Larastan
- **APIs:** 
  - BrickLink API (OAuth 1.0) ✅
  - Nextcloud WebDAV ✅
  - Brickognize API (geplant)
- **Dateisystem:** 
  - Lokal: `storage/app/private/`
  - Remote: Nextcloud WebDAV

---

## 📄 Lizenz

Dieses Projekt ist unter der [MIT-Lizenz](LICENSE) lizenziert.

---

## 🤝 Beitragen

Contributions sind willkommen! Bitte erstelle einen Pull Request oder öffne ein Issue für Vorschläge.

---

## 📧 Support

Bei Fragen oder Problemen öffne ein [GitHub Issue](https://github.com/your-username/brickstore/issues).

---

**Made with ❤️ for the LEGO & BrickLink community**
bash
laravel new --using=laraveldaily/starter-kit
```

From there, you can modify the kit to your needs.

---

## Design Elements

If you want to see examples of what design elements we have, you can [visit the Wiki](<https://github.com/LaravelDaily/starter-kit/wiki/Design-Examples-(Raw-Files)>) and see the raw HTML files.

---

## Licence

Starter kit is open-sourced software licensed under the MIT license.
