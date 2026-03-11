# FormBridge – Improvements checklist

Lean checklist for polish and release prep. Not a full roadmap; no feature creep.

---

## Branding

- [ ] Set real **Plugin URI** and **Author URI** in `formbridge.php` when known (currently placeholder).
- [ ] Add or update **assets/icon.png** (128×128 or 256×256) for the admin menu icon.
- [ ] Use consistent **FormBridge** naming and one-line tagline everywhere.

---

## UI polish

- [ ] Empty states and onboarding hints on Connections, Mappings, and Logs (partially done).
- [ ] Admin notices: clear success and error messages (partially done).
- [ ] Optional: add help tabs (`add_help_tab`) on admin pages with short “How to” text.
- [ ] Keep standard WordPress admin styles; no heavy redesign.

---

## Stability and validation

- [ ] Prevent saving a mapping when no fields are mapped (done).
- [ ] Validate connection, table, and columns before saving mapping (done).
- [ ] Router and writer fail gracefully when connection is missing or invalid; log and do not crash (done).
- [ ] Frontend form submission never crashes the site when sync fails (done).
- [ ] Logs show success / failed / skipped clearly (done).

---

## Documentation

- [ ] **README.md** – What the plugin does, requirements, install, setup, usage, V1 limitations (done).
- [ ] **CHANGELOG.md** – Version and changes per release (done).
- [ ] In-plugin link to docs or help (optional).

---

## Screenshots and release prep

- [ ] Capture screenshots for store or readme: Connections list, Mappings (field mapper), Logs table, Test connection success.
- [ ] Final pass: no debug code, no sensitive data in logs, version and constants consistent.

---

## Future ideas (short)

- Additional form sources (e.g. WPForms) or destinations (e.g. webhooks) in a later version.
- Optional “Delete data on uninstall” setting.
- Optional filters/hooks for power users (e.g. alter payload before insert).

---

*Not in scope for this checklist: licensing, update servers, export/import, telemetry, analytics, cloud features, background jobs, React or new build tooling.*
