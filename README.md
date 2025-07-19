# Hetzner DynDNS Script für Fritz!Box

Ein einfaches PHP-Script, das als DynDNS-Provider fungiert und Ihre aktuelle IP-Adresse automatisch an Hetzner DNS weitergibt. **Speziell optimiert für Fritz!Box Router** mit sicherer Token-Übertragung über das Kennwort-Feld.

## 🚀 Features

- ✅ **IPv4 und IPv6 Unterstützung**
- ✅ **Multiple Domains** mit einem Request
- ✅ **Automatische Record-Erstellung** falls nicht vorhanden
- ✅ **Intelligente Updates** (nur bei IP-Änderungen)
- ✅ **Detailliertes Logging** (optional)
- ✅ **Sicherer Token-Transfer** über Fritz!Box Kennwort-Feld
- ✅ **Niedrige TTL** (60 Sekunden) für schnelle Updates

## 📋 Voraussetzungen

- PHP 7.0+ mit cURL Unterstützung
- Webserver (Apache, Nginx, etc.)
- Hetzner DNS Account mit API-Zugriff
- Domain bereits als Zone in Hetzner DNS eingerichtet

## 🛠 Installation

1. **Script herunterladen**
   ```bash
   wget https://raw.githubusercontent.com/sakis-tech/hetzner-dyndns/main/hetzner_dyndns.php
   ```

2. **Auf Webserver hochladen**
   - Script in ein Web-zugängliches Verzeichnis kopieren
   - Stellen Sie sicher, dass PHP Schreibrechte für Log-Dateien hat

3. **Hetzner DNS API-Token erstellen**
   - Gehen Sie zu [Hetzner DNS Console](https://dns.hetzner.com/)
   - Navigieren Sie zu "API Tokens"
   - Erstellen Sie einen neuen Token mit **Lese- und Schreibrechten**
   - Notieren Sie sich den Token (wird nur einmal angezeigt!)

## ⚙️ Fritz!Box Konfiguration

### Schritt 1: DynDNS einrichten
1. Fritz!Box Web-Interface öffnen (`http://192.168.178.1`)
2. **Internet → Freigaben → DynDNS**
3. **DynDNS verwenden** aktivieren

### Schritt 2: Anbieter konfigurieren
- **DynDNS-Anbieter:** `Benutzerdefiniert`
- **Update-URL:**
  ```
  https://yourdomain.com/path/hetzner_dyndns.php?pass=<pass>&domain=<domain>&ipv4=<ipaddr>&ipv6=<ip6addr>&log=true
  ```
- **Domainname:** `subdomain.yourdomain.com`
- **Benutzername:** `dummy` (beliebiger Text, da Fritz!Box ein Benutzername benötigt)
- **Kennwort:** `Ihr_Hetzner_API_Token`

### Schritt 3: IPv6 aktivieren (optional)
Für IPv6-Unterstützung in der Update-URL `&ipv6=<ip6addr>` hinzufügen.

### ⚠️ Wichtiger Hinweis
Die Fritz!Box benötigt zwingend einen Benutzernamen, auch wenn das Script diesen nicht verwendet. Geben Sie einfach einen beliebigen Text wie "dummy" oder "user" ein. **Das API-Token gehört ins Kennwort-Feld!**

## 🔧 Verwendung

### URL-Parameter

| Parameter | Erforderlich | Beschreibung |
|-----------|--------------|--------------|
| `pass` | ✅ | Hetzner DNS API Token (Fritz!Box Kennwort) |
| `domain` | ✅ | Domain(s) zu aktualisieren (Komma-getrennt) |
| `ipv4` | ❌ | IPv4-Adresse (automatisch von Fritz!Box) |
| `ipv6` | ❌ | IPv6-Adresse (automatisch von Fritz!Box) |
| `log` | ❌ | Logging aktivieren (`true`/`false`) |

### Beispiele

### Beispiele

**Fritz!Box Update-URL (Standard-Konfiguration):**
```
https://example.com/hetzner_dyndns.php?pass=<pass>&domain=<domain>&ipv4=<ipaddr>&ipv6=<ip6addr>&log=true
```
*Die Platzhalter `<pass>`, `<domain>`, `<ipaddr>` etc. werden automatisch von der Fritz!Box ersetzt.*

**Manuelle Test-URLs (nur zum Debuggen):**
```
# Einzelne Domain testen
https://example.com/hetzner_dyndns.php?pass=YOUR_ACTUAL_TOKEN&domain=home.example.com&ipv4=1.2.3.4&log=true

# Multiple Domains testen  
https://example.com/hetzner_dyndns.php?pass=YOUR_ACTUAL_TOKEN&domain=home.example.com,server.example.com&ipv4=1.2.3.4&log=true

# Nur IPv6 testen
https://example.com/hetzner_dyndns.php?pass=YOUR_ACTUAL_TOKEN&domain=home.example.com&ipv6=2001:db8::1&log=true
```
*Diese URLs nur zum manuellen Testen verwenden. In der Fritz!Box immer `<pass>` verwenden!*

## 📝 Logging

Bei aktiviertem Logging (`log=true`) werden Log-Dateien erstellt:
- **Dateiname:** `log-hetzner-[erste-domain].txt`
- **Format:** `YYYY-MM-DD HH:MM:SS - LEVEL - Nachricht`

**Beispiel Log-Eintrag:**
```
2025-07-19 14:30:15 - INFO - Updated A record successfully: 1.2.3.4
2025-07-19 14:30:16 - INFO - IPv6 record already up-to-date
```

## 🔒 Sicherheit

- ⚠️ **API-Token sicher aufbewahren** - Niemals in öffentlichen Repositories speichern
- 🛡️ **HTTPS verwenden** für alle API-Aufrufe
- 🔒 **Zugriff beschränken** - Script nur von vertrauenswürdigen IPs aufrufen lassen
- 📁 **Log-Dateien schützen** - Webserver-Zugriff auf Log-Dateien verhindern
- 🔐 **Token im Kennwort-Feld** - Sicherer als direkte URL-Parameter

### .htaccess Beispiel (Apache)
```apache
# Log-Dateien vor Web-Zugriff schützen
<Files "log-hetzner-*.txt">
    Deny from all
</Files>
```

## 🐛 Troubleshooting

### Häufige Probleme

**"Hetzner DNS authentication failed"**
- ✅ API-Token im Fritz!Box Kennwort-Feld korrekt eingegeben?
- ✅ Token hat Lese- und Schreibrechte in Hetzner DNS?
- ✅ Internet-Verbindung vorhanden?
- ✅ Update-URL verwendet `pass=<pass>` Parameter?

**"Could not find zone for domain"**
- ✅ Domain als Zone in Hetzner DNS eingerichtet?
- ✅ Domain-Name korrekt geschrieben?

**"HTTP Error 429"**
- ⏱️ Rate Limit erreicht - Warten Sie kurz und versuchen Sie es erneut

**Fritz!Box meldet Fehler**
- ✅ Update-URL verwendet `pass=<pass>` Parameter?
- ✅ Script über Web erreichbar?
- ✅ Benutzername ausgefüllt? (Beliebiger Text wie "dummy" erforderlich)
- ✅ API-Token korrekt im Kennwort-Feld eingegeben?
- ✅ PHP-Fehler im Webserver-Log prüfen

**"Parameter missing or invalid"**
- ✅ URL verwendet `pass=<pass>` nicht `api_token=<pass>`
- ✅ Domain-Parameter in der URL vorhanden?
- ✅ Fritz!Box überträgt das Kennwort korrekt als `<pass>`?

### Debug-Modus
Für detaillierte Fehlerdiagnose:
1. `log=true` in der URL setzen
2. Log-Datei prüfen
3. PHP-Fehlerlog des Webservers prüfen

## 🔄 Migration von Cloudflare

Falls Sie vom ursprünglichen Cloudflare-Script migrieren:

1. **URL-Parameter ändern:** `cf_key=<pass>` → `pass=<pass>`
2. **Proxy-Parameter entfernen:** Hetzner DNS hat keine Proxy-Funktion  
3. **Fritz!Box Konfiguration:**
   - Benutzername: Beliebigen Text eingeben (z.B. "dummy")
   - Kennwort: Ihr Hetzner DNS API-Token
   - Update-URL: `pass=<pass>` verwenden
4. **Domain-Zone in Hetzner DNS erstellen**

## 📈 API-Limits

Hetzner DNS API-Limits (Stand 2025):
- **Rate Limit:** 3600 Requests pro Stunde
- **Burst:** 10 Requests pro Sekunde

Für normale DynDNS-Nutzung sind diese Limits mehr als ausreichend.

## 🤝 Beitragen

Beiträge sind willkommen! Bitte:
1. Issue erstellen für Bug-Reports oder Feature-Requests
2. Pull Requests für Code-Verbesserungen
3. Dokumentation bei Änderungen aktualisieren

## 📄 Lizenz

GNU General Public License v3.0

## 👨‍💻 Credits

- **Original Script:** [1rfsNet](https://github.com/1rfsNet) (Cloudflare Version)
- **Hetzner Adaptation:** Angepasst für Hetzner DNS API
- **Inspiriert von:** Fritz!Box DynDNS Community

## 🔗 Links

- [Hetzner DNS Console](https://dns.hetzner.com/)
- [Hetzner DNS API Dokumentation](https://dns.hetzner.com/api-docs/)
- [Fritz!Box Handbuch](https://avm.de/service/handbuecher/)

---

**⭐ Wenn dieses Script hilfreich war, geben Sie ihm einen Stern!**
