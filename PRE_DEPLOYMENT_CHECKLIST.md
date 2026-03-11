# Pre-deployment checklist – CF7 Database Connector

Use this list before releasing or submitting to WordPress.org.

---

## Done (release-prep pass)

- [x] Plugin header: Plugin Name, Description, Version, Requires at least, Requires PHP, Text Domain, License, License URI
- [x] Version 1.0.0 consistent: plugin header, `CF7DB_VERSION`, README.md, CHANGELOG.md, readme.txt Stable tag
- [x] Text domain `cf7-database-connector` used in all user-facing strings
- [x] `load_plugin_textdomain()` added for translations (languages folder created when needed)
- [x] No debug leftovers: no var_dump, print_r, error_log, or console.log in production code
- [x] Admin: all actions gated by `manage_options` and nonces (connection save/delete/test, mapping save, test submission, AJAX test connection)
- [x] External MySQL: inserts use PDO prepared statements; table/column names validated against schema
- [x] Logger: only stores payload (field data); no credentials in logs
- [x] CF7 missing: admin notice with “requires Contact Form 7” and link to install; CF7 adapter only registered when `WPCF7_ContactForm` exists (no frontend crash)
- [x] Uninstall: runs only on plugin delete; removes option `cf7db_version` and tables `cf7db_*`
- [x] readme.txt created (WordPress.org style) with Description, Installation, FAQ, Changelog, Screenshots placeholders
- [x] README.md and CHANGELOG.md in good shape
- [x] LICENSE file added (GPL v2)
- [x] Naming: “CF7 Database Connector” in plugin name and user-facing copy; slug `cf7-database-connector`; main file `cf7-database-connector.php`

---

## Needs manual review

- [ ] **Contributors:** Set your WordPress.org username in `readme.txt` (Contributors: yourname).
- [ ] **Tested up to:** Confirm WordPress version in `readme.txt` (e.g. 6.4 or current) after testing.
- [ ] **Stable tag:** Confirm `readme.txt` Stable tag matches release (1.0.0).
- [ ] **Screenshots:** Add real screenshot assets and list them in `readme.txt` (e.g. 1. Connections page, 2. Mappings page, 3. Logs page). WordPress.org expects files like `screenshot-1.png` in the asset directory or as specified in the repo.
- [ ] **Plugin icon and banner:** If submitting to WordPress.org, prepare icon (256×256) and banner (1544×500 and 772×250) per [Plugin Assets](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/).
- [ ] **Plugin URI / Author URI:** Currently point to `https://wordpress.org/plugins/cf7-database-connector`. Update after approval or use your site URL for self-hosted release.
- [ ] **ZIP for distribution:** Exclude dev-only files (e.g. `formbridge-cursor-development-brief.md`, `FORMBRIDGE_IMPROVEMENTS.md`, `FORMBRIDGE_PROGRESS.md`, `PRE_DEPLOYMENT_CHECKLIST.md`, `.git`) when building the release ZIP if desired. They do not affect runtime.

---

## Not implemented by design

- No license key or update server
- No analytics or telemetry
- No export/import of connections/mappings
- No support portal or in-plugin dashboard
- No integrations other than Contact Form 7
- No webhook or non-MySQL destinations
- No app-builder or charting features
- Logo not displayed in admin UI (helper exists; assets optional for menu icon only)
