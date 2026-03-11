# CF7 Database Connector

**CF7 Database Connector** sends Contact Form 7 submissions to an external MySQL database with field mapping—no code required.

## What it does

- Connect WordPress to an external MySQL database (host, port, database, user, password).
- Map each Contact Form 7 form to a table on that database.
- Map form fields (e.g. `your-name`, `your-email`) to database columns (e.g. `full_name`, `email`).
- On form submit, the plugin inserts the mapped data into the external table using prepared statements.
- Log every attempt (success, failed, skipped) and view logs in the admin.

## Requirements

- WordPress 5.9 or later
- PHP 8.1 or later
- Contact Form 7 installed and active
- An external MySQL (or MariaDB) database and credentials

## Installation

1. Upload the `formbridge` folder to `wp-content/plugins/`.
2. Activate **CF7 Database Connector** in **Plugins**.
3. Go to **CF7 Database Connector** in the admin menu.

## Setup and usage

1. **Connections**  
   Add an external database connection: name, host, port (default 3306), database name, username, password. Use **Test** to verify. Edit or delete as needed.

2. **Mappings**  
   Choose a Contact Form 7 form and a saved connection. Click **Load tables**, then choose the destination table and **Load mapping**. For each form field, choose a database column (or "Do not map"). Click **Save mapping**. Only one active mapping per CF7 form; saving again updates it.

3. **Logs**  
   View the latest 50 sync attempts (date, source, form ID, table, status, message). Use **View payload** to see the mapped data for any row.

## V1 limitations

- **Source:** Contact Form 7 only. No WPForms, Gravity Forms, or other form plugins.
- **Destination:** External MySQL only. No webhooks, Google Sheets, or PostgreSQL.
- **One mapping per form:** One active mapping per CF7 form; saving a new mapping for the same form overwrites the previous one.
- **Insert only:** Rows are inserted only. No update/upsert or delete.
- **No file uploads:** File fields are not synced as binary; array values are JSON-encoded.
- **No queues or background jobs:** Sync runs when the form is submitted; no retry queue.

## Security

- All admin actions require `manage_options` and use nonces.
- Passwords are stored in the WordPress database but are never shown in plain text after save and are never included in logs.
- External inserts use PDO prepared statements; table and column names are validated against the discovered schema.

## Logo and icon (optional)

To use your own branding in the WordPress admin:

- **Folder:** `formbridge/admin/assets/images/`
- **Menu icon:** Add `icon.png` or `icon.svg` (recommended 20×24 px). Replaces the default icon in the admin sidebar.
- **Page logo:** Add `logo.png` or `logo.svg` (e.g. 200×50 px). Shown on Connections, Mappings, and Logs page headers.

See `admin/assets/images/README.md` in the plugin for details.

## Uninstall

Deactivating or deleting the plugin does not remove saved connections, mappings, or logs. Data is preserved. To remove data, delete the plugin tables and options manually or implement cleanup in `uninstall.php` if desired.

## License

GPL v2 or later.
