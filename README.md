# Elementor JetThemeCore Manager

## Beschreibung

Elementor JetThemeCore Manager ist ein WordPress-Plugin, das Ihnen ermöglicht, bei Elementor-Templates zu entscheiden, ob sie von Crocoblock JetThemeCore verwaltet werden sollen oder nicht. Dies löst Kompatibilitätsprobleme zwischen Elementor-Templates und JetThemeCore.

Das Plugin fügt eine einfache Checkbox zu Elementor-Templates und anderen unterstützten Post-Types hinzu, mit der Sie einzelne Templates von der JetThemeCore-Verwaltung ausschließen können.

## Hauptfunktionen

- Checkbox zum Ausschließen einzelner Templates von JetThemeCore
- Unterstützung für Elementor-Templates, Sektionen, Seiten und Beiträge
- Kompatibilität mit benutzerdefinierten Post-Types, die Elementor unterstützen
- Einfache Benutzeroberfläche im WordPress-Backend
- Leichtgewichtig und performant

## Installation

### Als Plugin (empfohlen)

1. **Manuell über FTP**
   - Laden Sie den Ordner `elementor-jetthemecore-manager` in das Verzeichnis `/wp-content/plugins/` Ihrer WordPress-Installation hoch
   - Aktivieren Sie das Plugin über den Menüpunkt "Plugins" in WordPress

2. **Über WordPress-Administrationsoberfläche**
   - Laden Sie die ZIP-Datei des Plugins herunter
   - Gehen Sie zu "Plugins" > "Installieren" > "Plugin hochladen"
   - Wählen Sie die ZIP-Datei aus und klicken Sie auf "Jetzt installieren"
   - Aktivieren Sie das Plugin

### Als Snippet (Alternative)

1. Öffnen Sie die `functions.php` Ihres aktiven Child-Themes
2. Fügen Sie den Snippet-Code am Ende der Datei ein
3. Speichern Sie die Datei

**Hinweis:** Die Verwendung als Snippet wird nur empfohlen, wenn Sie ein Child-Theme verwenden, da Änderungen bei Theme-Updates verloren gehen können.

## Voraussetzungen

- WordPress 5.0 oder höher
- Elementor (kostenlos oder Pro)
- JetThemeCore von Crocoblock

## Verwendung

1. Installieren und aktivieren Sie das Plugin
2. Bearbeiten Sie ein Elementor-Template oder eine Seite
3. Suchen Sie im rechten Bereich nach der Box "JetThemeCore Verwaltung"
4. Aktivieren Sie die Option "Von JetThemeCore ausschließen", wenn das Template nicht von JetThemeCore verwaltet werden soll
5. Speichern Sie das Template

## Häufig gestellte Fragen

### Warum sollte ich bestimmte Templates von JetThemeCore ausschließen?

In manchen Fällen kann JetThemeCore die normale Funktionalität von Elementor-Templates beeinträchtigen. Dieses Plugin ermöglicht es Ihnen, selektiv zu entscheiden, welche Templates von JetThemeCore verwaltet werden sollen und welche nicht.

### Funktioniert das Plugin mit benutzerdefinierten Post-Types?

Ja, das Plugin unterstützt standardmäßig Elementor-Templates, Sektionen, Seiten und Beiträge sowie alle benutzerdefinierten Post-Types, die Elementor-Unterstützung deklariert haben.

### Was passiert, wenn ich JetThemeCore oder Elementor deaktiviere?

Das Plugin prüft, ob beide Plugins aktiv sind. Falls eines oder beide deaktiviert werden, wird eine Benachrichtigung angezeigt, aber keine Funktionalität ist beeinträchtigt. Sie können das Plugin problemlos wieder aktivieren, sobald Elementor und JetThemeCore aktiv sind.

## Fehlerbehebung

### Die Checkbox erscheint nicht

- Stellen Sie sicher, dass Elementor und JetThemeCore aktiv sind
- Prüfen Sie, ob Sie den richtigen Post-Type bearbeiten (Elementor-Template, Seite, Beitrag etc.)
- Schauen Sie im rechten Metabox-Bereich nach der Box "JetThemeCore Verwaltung"

### Ausgeschlossene Templates werden trotzdem von JetThemeCore verwaltet

- Stellen Sie sicher, dass Sie das Template nach dem Aktivieren der Checkbox gespeichert haben
- Leeren Sie den Cache Ihres Browsers und von WordPress
- Prüfen Sie, ob JetThemeCore auf die neueste Version aktualisiert ist

## Lizenz

GPL v2 oder später

## Autor

[Ihr Name]

## Unterstützung

Bei Fragen oder Problemen erstellen Sie bitte ein Issue auf GitHub oder kontaktieren Sie den Autor direkt.

## Änderungsprotokoll

### 1.0.0
- Erstveröffentlichung