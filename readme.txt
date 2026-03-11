=== CF7 Database Connector ===

Contributors: dilawar321
Tags: contact form 7, cf7, database, mysql, form submission, external database
Requires at least: 5.9
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send Contact Form 7 submissions to an external MySQL database with field mapping—no code required.

== Description ==

CF7 Database Connector lets you send Contact Form 7 form submissions directly to an external MySQL (or MariaDB) database. Map each form field to a database column via a simple admin UI—no custom code needed.

**Features:**

* **Connections:** Add one or more external database connections (host, port, database name, username, password). Test connections before saving.
* **Mappings:** Choose a Contact Form 7 form, a connection, and a destination table. Map form fields to database columns. One active mapping per form (saving again updates it).
* **Automatic sync:** When a visitor submits a CF7 form that has an active mapping, the plugin inserts the mapped data into the external table using prepared statements.
* **Logs:** View the latest 50 sync attempts (success, failed, or skipped) and inspect the payload for any row.

**Requirements:**

* WordPress 5.9 or later
* PHP 8.1 or later
* Contact Form 7 plugin installed and active
* An external MySQL or MariaDB database and credentials

**Security:**

* All admin actions require the `manage_options` capability and use nonces.
* Passwords are stored in the WordPress database and are never shown in plain text after save or included in logs.
* External inserts use PDO prepared statements; table and column names are validated against the discovered schema.

**Limitations (current version):**

* Source: Contact Form 7 only.
* Destination: External MySQL only (no webhooks or other backends).
* Insert only (no update/upsert or delete).
* File upload fields are not synced as binary; array values are JSON-encoded.

== Installation ==

1. Install and activate Contact Form 7 if you have not already.
2. Upload the plugin folder to `wp-content/plugins/` or install via WordPress admin (Plugins → Add New → Upload Plugin).
3. Activate **CF7 Database Connector** in the Plugins screen.
4. Go to **CF7 Database Connector** in the admin menu.

**Setup:**

1. **Connections:** Add an external database connection. Use **Test connection** to verify before saving.
2. **Mappings:** Select a CF7 form and a connection, click **Load tables**, choose the destination table, then **Load mapping**. Map each form field to a database column (or "Do not map"). Click **Save mapping**.
3. Submissions to that CF7 form will now be inserted into the chosen table. Check **Logs** to see sync attempts.

== Frequently Asked Questions ==

= Do I need Contact Form 7? =

Yes. CF7 Database Connector requires Contact Form 7 to be installed and active. If it is missing, the plugin shows an admin notice with a link to install it.

= Where are passwords stored? =

Connection passwords are stored in your WordPress database (in the plugin’s tables). They are never shown in plain text after save and are never included in log payloads.

= Can I use MariaDB? =

Yes. Any MySQL-compatible server (including MariaDB) should work.

= How many mappings can I have? =

One active mapping per Contact Form 7 form. You can have many forms, each with one mapping to (possibly different) external tables.

== Screenshots ==

1. Connections page – add and test external database connections.
2. Mappings page – select form, connection, table, and map fields to columns.
3. Logs page – view sync attempts and inspect payloads.

== Changelog ==

= 1.0.0 =
* Initial release.
* Connections: Add, edit, delete, and test external MySQL connection profiles.
* Mappings: Select CF7 form, connection, and destination table; map form fields to database columns; one active mapping per form.
* Runtime: Capture CF7 submissions via wpcf7_mail_sent; insert into external MySQL via PDO prepared statements; log success, failed, or skipped.
* Logs: View latest 50 sync attempts with expandable payload.
* Security: Nonces and capability checks on all admin actions; no credentials in logs; validated table/column names.
