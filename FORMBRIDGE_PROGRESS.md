# FormBridge build progress

Plain-English log of what’s done and what’s next. Updated as each batch is completed.

---

## Batch 1 — Done

**What was done**

- **Plugin bootstrap** (`formbridge.php`): Plugin header, version and path constants, loading of core files, activation hook, and startup on `plugins_loaded`.
- **Activator** (`includes/class-activator.php`): On activation, creates three database tables (connections, mappings, logs) with `dbDelta` and saves the plugin version in options.
- **Main plugin class** (`includes/class-plugin.php`): Singleton that starts the plugin and creates the admin object so the menu and pages can run.
- **Admin** (`includes/class-admin.php`): Registers the top-level **FormBridge** menu and three subpages (Connections, Mappings, Logs), loads the right CSS/JS only on those pages, and checks that the user can manage options.
- **Placeholder admin pages**: Simple placeholder views for Connections, Mappings, and Logs so each menu item shows a page.
- **Helpers** (`includes/helpers/helpers.php`): File added; no functions yet.
- **Admin assets**: Empty `admin.css` and `admin.js` added.
- **Uninstall** (`uninstall.php`): Stub only; no data removal.

**What you can do now**

- Activate the plugin and see the FormBridge menu with Connections, Mappings, and Logs. The three tables exist in the database but nothing uses them yet.

---

## Batch 2 — Done

**What was done**

- **Connection repository** (`includes/repositories/class-connection-repository.php`): Full CRUD for saved external MySQL connections (create, get by ID, get all, update, delete). Uses `$wpdb` and prepared statements; never logs or exposes passwords.
- **Mapping repository** (`includes/repositories/class-mapping-repository.php`): Full CRUD for mappings (create, get by ID, get all, update, delete) plus a method to find the **active** mapping for a given source type and form ID (used later when a form is submitted).
- **Log repository** (`includes/repositories/class-log-repository.php`): Append-only logging: insert a log row and fetch recent logs (e.g. latest 50) for the Logs page.
- **Helper** `formbridge_now()`: Returns current time in WordPress MySQL format for `created_at` / `updated_at` in repositories.
- **Bootstrap**: The three repository files are loaded from the main plugin file so they are available for Batch 3 and beyond.

**What you can do now**

- No visible change in the admin UI yet. The Connections, Mappings, and Logs pages are still placeholders. Batch 2 only adds the data layer so that Batch 3 (Connections page CRUD and test connection) can save and load connections.

---

## Batch 3 — Done

**What was done**

- **Connection manager** (`includes/class-connection-manager.php`): Builds PDO connections to external MySQL with the DSN and attributes from the brief. Methods: `connect()`, `test_connection()` (returns success/message with no password in errors), `get_tables()`, `get_columns()`, `table_exists()`, `get_valid_columns()`.
- **Plugin wiring**: The plugin class now creates the connection repository and connection manager and passes them into the admin class.
- **Connections admin page**: Full UI on **FormBridge → Connections**: table of existing connections (name, host, database) with **Edit**, **Test**, and **Delete** actions; add/edit form below (connection name, host, port, database name, username, password — password optional on edit with “leave blank to keep current”); validation (required fields, port numeric, default 3306); success/error admin notices after create, update, delete, and test. Delete uses a JS confirm; all actions use nonces and capability checks.

**What you can do now**

- Add a new external MySQL connection, edit it (including leaving password blank to keep it), delete it, and use **Test** to see a clear success or safe error message. The CRUD and test-connection flow are fully working.

---

## Batch 4 — Done

**What was done**

- **CF7 helpers** (`includes/helpers/helpers.php`): `formbridge_get_cf7_forms()` returns a list of CF7 forms (id, title) using `WPCF7_ContactForm::find()`. `formbridge_get_cf7_fields($form_id)` returns field names from the form template using CF7’s `scan_form_tags()` when available, with a regex fallback for `[type name]` tags.
- **Mapping repository**: Added `get_by_source_and_form()` so the mappings page can upsert (one mapping per CF7 form: update if exists, otherwise insert).
- **Mappings admin page**: Full UI on **FormBridge → Mappings**: select CF7 form and connection, then “Load tables”; select destination table, then “Load mapping”; table of CF7 fields with a “Database column” dropdown each (including “Do not map”); “Save mapping” with nonce. Validation: form, connection, and table required; table must exist on the connection; only discovered columns are accepted. Saving updates the existing mapping for that form or creates a new one.

**What you can do now**

- Pick a Contact Form 7 form and a saved connection, load tables, pick a destination table, map each form field to a column (or “Do not map”), and save. Re-opening the same form shows the saved mapping so you can change it.

---

## Batch 5 — Done

**What was done**

- **Interfaces**: `FormBridge_Source_Adapter` (register_hooks, get_source_key) and `FormBridge_Destination_Writer` (get_key, write).
- **Mapping engine** (`class-mapping-engine.php`): Takes normalized submission and field map; outputs destination payload (column => value). Skips missing or unmapped fields; JSON-encodes array values.
- **Logger** (`class-logger.php`): Accepts a standardized log array (source_type, form_id, mapping_id, destination_type, destination_table, payload, status, message) and writes via the log repository. Payload is JSON-encoded; no credentials.
- **MySQL writer** (`destinations/class-mysql-writer.php`): Validates payload and config; ensures table and all payload columns exist via connection manager; builds prepared INSERT with backtick-escaped identifiers; executes with PDO; returns success/message/insert_id.
- **Router** (`class-router.php`): Receives normalized submission; finds active mapping by source + form_id; loads connection; decodes field_map; runs mapping engine; if payload empty logs skipped; calls writer; logs result (success/failed); returns result. Logs skipped when no mapping or invalid submission.
- **CF7 adapter** (`adapters/class-cf7-adapter.php`): Hooks `wpcf7_mail_sent`; gets submission and posted data; strips internal CF7 keys; builds normalized payload (source, form_id, form_title, submitted_at, fields, meta); passes to router.
- **Helper** `formbridge_json_encode()`: Safe JSON encode for logs/maps; returns `'{}'` on failure.
- **Plugin wiring**: Init runs on every request (not only admin). Repositories, connection manager, mapping engine, logger, MySQL writer, router, and CF7 adapter are created; adapter registers hooks so frontend submissions are captured. Admin is created only when `is_admin()`.

**What you can do now**

- Submit a Contact Form 7 form that has an active mapping. The submission is normalized, mapped to the chosen table/columns, and inserted into the external MySQL database. Success and failures are logged. If there is no mapping, the attempt is logged as skipped and the site does not crash.

---

## Batch 6 — Done

**What was done**

- **Log repository in Admin**: Plugin passes `FormBridge_Log_Repository` into the Admin constructor so the Logs page can fetch recent entries.
- **Logs page** (`admin/views/logs-page.php`): Table with columns Date/Time, Source, Form ID, Destination Table, Status, Message, and a Payload column. Shows the latest 50 logs, newest first. “View payload” button toggles an expandable row with the payload JSON pretty-printed in a scrollable &lt;pre&gt;. Empty state message when there are no logs.
- **admin.js**: Toggle handler for “View payload” / “Hide payload” on the Logs page (aria-expanded and button text updated).
- **admin.css**: Column widths, payload cell and &lt;pre&gt; styling, and status colors (success green, failed red, skipped gray) for the Logs table.

**What you can do now**

- Open **FormBridge → Logs** to see recent sync attempts. Use “View payload” to inspect the mapped data (or error context) for any row. MVP is complete.

---

## MVP complete

All six batches from the development brief are done. FormBridge now:

- Lets you manage external MySQL connections and test them.
- Lets you map CF7 forms to a connection and table with field-to-column mapping.
- Captures CF7 submissions and inserts them into the external DB with prepared statements.
- Logs every attempt (success, failed, skipped) and shows the last 50 in the admin Logs page with optional payload detail.
