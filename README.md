# WPS Limit Login - Quick Blacklist

A WordPress add-on plugin for [WPS Limit Login](https://wordpress.org/plugins/wps-limit-login/) that adds one-click IP blacklisting and automatic IP address sorting to the admin interface.

> **Requires**: WPS Limit Login plugin (installed and active)

---

## Features

### One-Click Blacklist Button
- Adds a **Blacklist** button column to the lockout log table
- Instantly adds the IP to the blacklist and clears its log entries
- Visual feedback with loading state and smooth row fade-out
- Prevents duplicate entries with an "already blacklisted" notice

### Automatic IP Sorting
- Optional **auto-sort** checkbox on the Blacklist and Whitelist pages
- Sorts IPv4 numerically (`2.0.0.1` → `10.0.0.1` → `192.168.1.1`)
- Sorts IPv6 alphabetically
- Sorts CIDR ranges alphabetically
- Final order: IPv4 → IPv6 → CIDR Ranges
- Preference is saved and persists across sessions

### Enhanced Email Notifications
- Appends a direct link to the lockout log in WPS Limit Login alert emails
- Reduces time from notification to action

---

## Requirements

| Requirement | Version |
|---|---|
| WordPress | 5.0+ |
| PHP | 7.4+ |
| WPS Limit Login | Any |

---

## Installation

### Via WordPress Admin (recommended)

1. Download the latest release ZIP from [Releases](../../releases)
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the ZIP and click **Install Now**
4. Click **Activate**

### Manual

1. Download or clone this repo
2. Upload the `wps-limit-login-blacklist-addon` folder to `/wp-content/plugins/`
3. Activate via **Plugins** in the WordPress admin

---

## Usage

### Quick Blacklist

1. Go to **Settings → WPS Limit Login → Log**
2. Click **Blacklist** next to any IP address
3. The IP is added to the blacklist and removed from the log instantly

### Auto-Sort

1. Go to **Settings → WPS Limit Login → Blacklist** (or **Whitelist**)
2. Check **"Automatically sort IP addresses?"**
3. Click **Save** — IPs are sorted on every save from that point on

---

## Sorting Algorithm

| Type | Method | Example |
|---|---|---|
| IPv4 | Numeric (`ip2long`) | `2.0.0.1` → `10.0.0.1` → `192.168.1.1` |
| IPv6 | Lexical | Standard alphabetical |
| CIDR | Lexical | `10.0.0.0/24` → `192.168.0.0/16` |

---

## FAQ

**Does this replace WPS Limit Login?**
No. This is an add-on. WPS Limit Login must be installed and active.

**Does auto-sort affect performance?**
No. Sorting only runs on save or when using the Quick Blacklist button.

**Can I disable auto-sort after enabling it?**
Yes — uncheck the checkbox and save. IPs stay in their current order until the next sort.

**Does it support IPv6?**
Yes. IPv4, IPv6, and CIDR ranges are all supported.

---

## Roadmap

### v1.1.0 — Bulk Actions
- [ ] Bulk blacklist selected IPs from the log table
- [ ] "Blacklist All" button to blacklist every IP in the log at once
- [ ] Confirmation dialog before bulk operations

### v1.2.0 — Whitelist Quick-Add
- [ ] One-click whitelist button on the log page (mirror of blacklist button)
- [ ] Move-to-whitelist action from the blacklist management page

### v1.3.0 — Import / Export
- [ ] Export blacklist/whitelist as a plain-text or CSV file
- [ ] Import IPs from a file with duplicate detection and merge options
- [ ] Copy-to-clipboard button on list pages

### v1.4.0 — Log Enhancements
- [ ] Sortable columns in the lockout log table (by IP, attempts, last seen)
- [ ] Filter log by date range
- [ ] Persistent column sort preference

### v1.5.0 — WHOIS & Geo Lookup
- [ ] Click-to-view WHOIS data for any IP in the log
- [ ] Country flag / geolocation display in the log table (via free API)
- [ ] Filter log by country

### v2.0.0 — Scheduled Automation
- [ ] Auto-expire blacklist entries after a configurable time period
- [ ] Scheduled cleanup of old log entries
- [ ] WP-Cron integration for maintenance tasks

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for full version history.

---

## License

GPL v2 or later. See [LICENSE](LICENSE).

```
WPS Limit Login - Quick Blacklist
Copyright (C) 2026 Ray Hollister

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

---

## Credits

- **Author**: [Ray Hollister](https://rayhollister.com)
- **Built for**: [WPS Limit Login](https://wordpress.org/plugins/wps-limit-login/) by WP Serveur
