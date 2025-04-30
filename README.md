# Elementor JetThemeCore Fix

## Beschreibung

Elementor JetThemeCore Fix behebt Konflikte zwischen Elementor und JetThemeCore (Crocoblock), die verhindern, dass Templates normal in Elementor bearbeitet werden können. Es löst insbesondere das Problem "Auf der Seite wurde kein Inhaltsbereich gefunden" im abgesicherten Modus und stellt sicher, dass Templates trotz aktiviertem JetThemeCore bearbeitet werden können.

![JetThemeCore Status in der Template-Liste](https://example.com/screenshot.png)

## Hauptfunktionen

- **Konfliktlösung**: Beseitigt Konflikte zwischen Elementor und JetThemeCore
- **Template-Steuerung**: Ermöglicht das selektive Deaktivieren von JetThemeCore für bestimmte Templates
- **Listenansicht-Integration**: Zeigt JetThemeCore-Status in der Template-Liste an
- **Massenaktionen**: Ermöglicht das gleichzeitige Aktivieren/Deaktivieren für mehrere Templates
- **Filterung**: Filtert Templates nach JetThemeCore-Status
- **Editor-Fix**: Behebt das "Kein Inhaltsbereich gefunden"-Problem im Elementor-Editor

## Installation

### Automatische Installation
1. Gehen Sie in Ihrem WordPress-Dashboard zu **Plugins > Installieren**
2. Klicken Sie auf **Plugin hochladen**
3. Wählen Sie die Zip-Datei aus und klicken Sie auf **Jetzt installieren**
4. Aktivieren Sie das Plugin

### Manuelle Installation
1. Laden Sie das Plugin herunter und entpacken Sie es
2. Laden Sie den Ordner `elementor-jetthemecore-fix` in das Verzeichnis `/wp-content/plugins/` hoch
3. Aktivieren Sie das Plugin über den Menüpunkt **Plugins** in WordPress

## Verwendung

### JetThemeCore für ein einzelnes Template deaktivieren

1. Öffnen Sie ein Template oder eine Seite im WordPress-Editor
2. Suchen Sie in der rechten Seitenleiste nach der Box **JetThemeCore für Elementor deaktivieren**
3. Aktivieren Sie die Checkbox
4. Klicken Sie auf **Aktualisieren** oder **Veröffentlichen**

### JetThemeCore-Status in der Template-Liste verwalten

1. Gehen Sie zu **Templates > Gespeicherte Templates**
2. In der Spalte **JetThemeCore Status** sehen Sie den aktuellen Status jedes Templates
3. Klicken Sie auf den **JetThemeCore deaktivieren**-Button, um den Status direkt zu ändern

### Templates nach JetThemeCore-Status filtern

1. Gehen Sie zu **Templates > Gespeicherte Templates**
2. Verwenden Sie das Dropdown **JetThemeCore Status filtern**
3. Wählen Sie **JetThemeCore deaktiviert** oder **JetThemeCore aktiv**
4. Klicken Sie auf **Filter anwenden**

### Massenaktionen verwenden

1. Wählen Sie mehrere Templates durch Anklicken der Checkboxen aus
2. Wählen Sie **JetThemeCore deaktivieren** oder **JetThemeCore aktivieren** aus dem Dropdown **Bulk-Aktionen**
3. Klicken Sie auf **Anwenden**

### Frontend-Bearbeitung

Auf der Frontend-Seite finden Sie in der Admin-Symbolleiste einen neuen Button **Mit Elementor bearbeiten (Fix)**, der eine spezielle URL verwendet, um JetThemeCore-Konflikte zu vermeiden.

## Häufig gestellte Fragen

### Warum erscheint die Meldung "Auf der Seite wurde kein Inhaltsbereich gefunden"?
Diese Meldung erscheint, wenn JetThemeCore mit der normalen Funktionsweise von Elementor in Konflikt gerät. Unser Plugin behebt dieses Problem, indem es die problematischen Hooks und Filter deaktiviert.

### Werden durch die Deaktivierung von JetThemeCore für ein Template andere Funktionen beeinträchtigt?
Nein, die Deaktivierung gilt nur für die Interaktion zwischen JetThemeCore und dem spezifischen Template. Andere Funktionen von JetThemeCore bleiben erhalten.

### Funktioniert das Plugin mit allen Versionen von Elementor und JetThemeCore?
Das Plugin wurde mit den neuesten Versionen von Elementor und JetThemeCore getestet. Es sollte mit den meisten Versionen kompatibel sein, aber bei sehr alten Versionen können Probleme auftreten.

### Kann ich JetThemeCore für alle Templates auf einmal deaktivieren?
Ja, gehen Sie zu **Templates > Gespeicherte Templates**, wählen Sie alle Templates aus und verwenden Sie die Bulk-Aktion **JetThemeCore deaktivieren**.

## Fehlerbehebung

### Das Template lässt sich immer noch nicht bearbeiten
1. Stellen Sie sicher, dass Sie die Option **JetThemeCore für Elementor deaktivieren** aktiviert haben
2. Leeren Sie den Cache Ihres Browsers und von WordPress
3. Versuchen Sie, den Button **Mit Elementor bearbeiten (Fix)** aus der Admin-Symbolleiste zu verwenden

### Der JetThemeCore-Status ändert sich nicht
1. Prüfen Sie, ob Sie über ausreichende Berechtigungen verfügen (Administrator-Rechte)
2. Deaktivieren und reaktivieren Sie das Plugin
3. Prüfen Sie, ob andere Plugins mit der Metabox interferieren

## Systemanforderungen

- WordPress 5.0 oder höher
- Elementor (Free oder Pro)
- JetThemeCore (Teil von Crocoblock)

## Entwickler

### Hooks und Filter

Das Plugin bietet folgende Hooks für Entwickler:

- `elementor_jetthemecore_fix/disable_hooks` - Filter zum Anpassen der zu deaktivierenden Hooks
- `elementor_jetthemecore_fix/post_types` - Filter zum Ändern der unterstützten Post-Types

### Beispiel: Benutzerdefinierte Post-Types hinzufügen

```php
add_filter('elementor_jetthemecore_fix/post_types', function($post_types) {
    $post_types[] = 'product'; // WooCommerce-Produkte hinzufügen
    return $post_types;
});
```

## Mitmachen und Beitragen

Wir freuen uns über Beiträge zum Plugin! Besuchen Sie unser [GitHub-Repository](https://github.com/your-username/elementor-jetthemecore-fix), um:

- Fehler zu melden
- Funktionen vorzuschlagen
- Pull Requests einzureichen

## Änderungsprotokoll

### 1.0.0
- Erstveröffentlichung
- Konfliktlösung zwischen Elementor und JetThemeCore
- Implementierung der Template-Steuerung
- Integration in die Template-Listenansicht
- Unterstützung für Massenaktionen
- Frontend-Bearbeitungsfix