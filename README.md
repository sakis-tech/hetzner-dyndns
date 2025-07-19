# Hetzner DynDNS Script

Ein einfaches PHP-Script, das als DynDNS-Provider fungiert und Ihre aktuelle IP-Adresse automatisch an Hetzner DNS weitergibt. Perfekt für Fritz!Box Router und andere Geräte, die DynDNS-Updates unterstützen.

## 🚀 Features

- ✅ **IPv4 und IPv6 Unterstützung**
- ✅ **Multiple Domains** mit einem Request
- ✅ **Automatische Record-Erstellung** falls nicht vorhanden
- ✅ **Intelligente Updates** (nur bei IP-Änderungen)
- ✅ **Detailliertes Logging** (optional)
- ✅ **Fritz!Box kompatibel**
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
  https://yourdomain.com/path/hetzner_dyndns.php?api_token=<pass>&domain=<domain>&ipv4=<ipaddr>&ipv6=<ip6addr>&log=true
  ```
- **Domainname:** `subdomain.yourdomain.com`
- **Benutzername:** `(leer lassen)`
- **Kennwort:** `Ihr_Hetzner_API_Token`

### Schritt 3: IPv6 aktivieren (optional)
Für IPv6-Unterstützung in der Update-URL `&ipv6=<ip6addr>` hinzufügen.

## 🔧 Verwendung

### URL-Parameter

| Parameter | Erforderlich | Beschreibung |
|-----------|--------------|--------------|
| `api_token` | ✅ | Hetzner DNS API Token |
| `domain` | ✅ | Domain(s) zu aktualisieren (Komma-getrennt) |
| `ipv4` | ❌ | IPv4-Adresse (automatisch von Fritz!Box) |
| `ipv6` | ❌ | IPv6-Adresse (automatisch von Fritz!Box) |
| `log` | ❌ | Logging aktivieren (`true`/`false`) |

### Beispiele

**Einzelne Domain:**
```
https://example.com/hetzner_dyndns.php?api_token=YOUR_TOKEN&domain=home.example.com&ipv4=1.2.3.4&log=true
```

**Multiple Domains:**
```
https://example.com/hetzner_dyndns.php?api_token=YOUR_TOKEN&domain=home.example.com,server.example.com&ipv4=1.2.3.4&ipv6=2001:db8::1&log=true
```

**Nur IPv6:**
```
https://example.com/hetzner_dyndns.php?api_token=YOUR_TOKEN&domain=home.example.com&ipv6=2001:db8::1
```

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
- ✅ API-Token korrekt?
- ✅ Token hat Schreibrechte?
- ✅ Internet-Verbindung vorhanden?

**"Could not find zone for domain"**
- ✅ Domain als Zone in Hetzner DNS eingerichtet?
- ✅ Domain-Name korrekt geschrieben?

**"HTTP Error 429"**
- ⏱️ Rate Limit erreicht - Warten Sie kurz und versuchen Sie es erneut

**Fritz!Box meldet Fehler**
- ✅ Update-URL korrekt konfiguriert?
- ✅ Script über Web erreichbar?
- ✅ PHP-Fehler im Webserver-Log prüfen

### Debug-Modus
Für detaillierte Fehlerdiagnose:
1. `log=true` in der URL setzen
2. Log-Datei prüfen
3. PHP-Fehlerlog des Webservers prüfen

## 🔄 Migration von Cloudflare

Falls Sie vom ursprünglichen Cloudflare-Script migrieren:

1. **API-Parameter ändern:** `cf_key` → `api_token`
2. **Proxy-Parameter entfernen:** Hetzner DNS hat keine Proxy-Funktion
3. **Fritz!Box Update-URL anpassen**
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
