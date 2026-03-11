# Changelog

All notable changes to CF7 Database Connector are documented here.

## [1.0.0] – Initial release (MVP)

- **Connections:** Add, edit, delete, and test external MySQL connection profiles.
- **Mappings:** Select a Contact Form 7 form, connection, and destination table; map form fields to database columns; save one active mapping per form.
- **Runtime:** Capture CF7 submissions via `wpcf7_mail_sent`; normalize payload; find active mapping; transform with mapping engine; insert into external MySQL via PDO prepared statements; log success, failed, or skipped.
- **Logs:** View latest 50 sync attempts with date, source, form ID, destination table, status, message; expandable payload for each row.
- **Security:** Nonces and capability checks on all admin actions; no credentials in logs; validated table/column names for inserts.
- **Requirements:** WordPress 5.9+, PHP 8.1+, Contact Form 7 active.
