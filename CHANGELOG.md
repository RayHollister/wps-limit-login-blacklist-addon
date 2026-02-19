# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-18

### Added
- **Quick Blacklist Button**: One-click IP blacklisting from the WPS Limit Login lockout log table
- **Automatic IP Sorting**: Optional auto-sort for both Blacklist and Whitelist pages
  - Numeric IPv4 sorting via `ip2long()` for correct ordering (e.g., `2.0.0.1` → `10.0.0.1` → `192.168.1.1`)
  - Alphabetical IPv6 sorting
  - Alphabetical CIDR range sorting
  - Grouped output: IPv4 → IPv6 → CIDR Ranges
- **Enhanced Email Notifications**: Direct link to lockout log appended to WPS Limit Login alert emails
- **Duplicate Detection**: Prevents adding an IP that is already on the blacklist
- **Log Cleanup**: Automatically removes log entries for the blacklisted IP after one-click blacklisting
- **Persistent Sort Preference**: Auto-sort checkbox state is saved per-list (blacklist/whitelist) in WordPress options
- **Seamless UI Integration**: Blacklist button styled to match native WPS Limit Login interface
- Singleton pattern for safe initialization
- AJAX handler secured with nonce verification and `manage_options` capability check
