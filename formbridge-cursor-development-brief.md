# FormBridge MVP Development Brief for Cursor

## Project Overview

Build a WordPress plugin called **FormBridge**.

The MVP goal is:

- capture **Contact Form 7** submissions
- map CF7 form fields to columns in an **external MySQL database**
- insert the mapped data into a selected table using **safe prepared statements**
- provide an admin UI for:
  - managing external DB connections
  - creating field mappings
  - viewing logs
  - testing the DB connection

This is **not** a generic “catch every form on Earth” plugin in V1.

V1 supports:

- Contact Form 7 as the only source
- MySQL as the only destination
- one active mapping per CF7 form
- row insert only
- logs for success/failure

Out of scope for V1:

- Jotform direct support
- WPForms / Gravity Forms
- Google Sheets
- webhooks
- update/upsert
- file uploads
- queues/background jobs
- conditional logic
- multiple destinations per submission
- drag and drop mapping UI

---

## Product Positioning

FormBridge lets WordPress admins:

1. connect to an external MySQL database
2. choose a table
3. choose a Contact Form 7 form
4. map form fields to DB columns
5. automatically insert submitted form data into the external DB

---

## Technical Requirements

### Platform

- WordPress plugin
- PHP 8.1+
- WordPress coding standards where practical
- Contact Form 7 installed and active for source functionality
- Use WordPress admin pages, nonces, capability checks, sanitization, and escaping

### Security Requirements

Must follow these strictly:

- use **PDO prepared statements** for external DB inserts
- never allow arbitrary SQL query input from the admin
- only allow selection of real discovered tables and columns
- sanitize all incoming admin form input
- escape all admin output
- use `current_user_can('manage_options')` for admin pages/actions
- protect all form submissions and admin actions with nonces
- never expose raw DB passwords after save
- never log DB passwords
- validate CF7 form ID and mapping ownership
- validate destination table/column names against discovered schema

---

## Architecture

Use this pipeline architecture:

**Source Adapter -> Normalizer -> Router -> Mapping Engine -> Destination Writer -> Logger**

### Why

This ensures V2 can later add:

- more sources: WPForms, Gravity Forms, Jotform webhook
- more destinations: webhooks, Google Sheets, PostgreSQL

without rewriting the core.

---

## Normalized Submission Format

Every source adapter must convert submissions into this shape:

```php
[
    'source' => 'cf7',
    'form_id' => 123,
    'form_title' => 'Lead Form',
    'submitted_at' => current_time('mysql'),
    'fields' => [
        'your-name' => 'John Doe',
        'your-email' => 'john@example.com',
        'your-phone' => '6041234567',
        'your-message' => 'Hello there',
    ],
    'meta' => [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ],
]
```

The rest of the plugin must only work with this normalized format.

---

## Plugin Folder Structure

Use this exact structure unless there is a very strong reason to change it.

```text
formbridge/
│
├── formbridge.php
├── uninstall.php
│
├── includes/
│   ├── class-plugin.php
│   ├── class-activator.php
│   ├── class-admin.php
│   ├── class-router.php
│   ├── class-mapping-engine.php
│   ├── class-connection-manager.php
│   ├── class-logger.php
│   │
│   ├── adapters/
│   │   ├── interface-source-adapter.php
│   │   └── class-cf7-adapter.php
│   │
│   ├── destinations/
│   │   ├── interface-destination-writer.php
│   │   └── class-mysql-writer.php
│   │
│   ├── repositories/
│   │   ├── class-connection-repository.php
│   │   ├── class-mapping-repository.php
│   │   └── class-log-repository.php
│   │
│   └── helpers/
│       └── helpers.php
│
├── admin/
│   ├── views/
│   │   ├── connections-page.php
│   │   ├── mappings-page.php
│   │   └── logs-page.php
│   └── assets/
│       ├── admin.css
│       └── admin.js
│
└── assets/
    └── icon.png
```

---

## Core Class Responsibilities

### `formbridge.php`

Main bootstrap file.

Responsibilities:

- plugin header
- define constants
- require core files
- register activation hook
- initialize plugin after plugins_loaded

### `FormBridge_Plugin` in `class-plugin.php`

Responsibilities:

- instantiate repositories/services
- instantiate admin UI
- instantiate router
- instantiate source adapters
- wire dependencies together
- register hooks

### `FormBridge_Activator` in `class-activator.php`

Responsibilities:

- create plugin tables with `dbDelta`
- set plugin version option if needed

### `FormBridge_Admin` in `class-admin.php`

Responsibilities:

- register admin menu
- render admin pages
- enqueue admin assets only on plugin pages
- handle create/update/delete actions for connections/mappings
- handle test connection action

### `FormBridge_Router` in `class-router.php`

Responsibilities:

- receive normalized submission payload from source adapter
- locate active mapping for source + form_id
- run mapping engine
- load destination config
- call destination writer
- log result

### `FormBridge_Mapping_Engine` in `class-mapping-engine.php`

Responsibilities:

- take normalized submission and saved field map
- return destination payload
- ignore unmapped fields
- optionally support static values later, but not required in V1

### `FormBridge_Connection_Manager` in `class-connection-manager.php`

Responsibilities:

- build PDO connection from saved connection profile
- test connection
- fetch available tables
- fetch available columns for selected table
- validate table/column existence for mapping

### `FormBridge_Logger` in `class-logger.php`

Responsibilities:

- save success/failure logs through repository
- standardize log payload format

### `FormBridge_CF7_Adapter` in `class-cf7-adapter.php`

Responsibilities:

- register CF7 hooks
- extract submission data from CF7
- normalize submission into standard array
- send normalized payload to router

### `FormBridge_MySQL_Writer` in `class-mysql-writer.php`

Responsibilities:

- receive destination-ready associative array
- validate target table and columns
- generate safe prepared INSERT statement
- execute insert through PDO
- return structured result array

### Repositories

#### `FormBridge_Connection_Repository`
CRUD for saved external DB connections.

#### `FormBridge_Mapping_Repository`
CRUD for saved mappings.

#### `FormBridge_Log_Repository`
Read/write logs.

---

## Interfaces

### `interface-source-adapter.php`

```php
<?php

interface FormBridge_Source_Adapter {
    public function register_hooks(): void;
    public function get_source_key(): string;
}
```

### `interface-destination-writer.php`

```php
<?php

interface FormBridge_Destination_Writer {
    public function get_key(): string;

    /**
     * @param array $payload Associative array of column => value
     * @param array $config  Destination config including connection and table
     * @return array{success:bool,message:string,insert_id:int|null}
     */
    public function write(array $payload, array $config): array;
}
```

---

## Database Schema for Plugin Tables

Use `$wpdb->prefix`.

### 1. Connections Table

Table name:

`{$wpdb->prefix}formbridge_connections`

Columns:

- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `name` VARCHAR(191) NOT NULL
- `db_host` VARCHAR(191) NOT NULL
- `db_port` INT NOT NULL DEFAULT 3306
- `db_name` VARCHAR(191) NOT NULL
- `db_user` VARCHAR(191) NOT NULL
- `db_pass` TEXT NOT NULL
- `created_at` DATETIME NOT NULL
- `updated_at` DATETIME NOT NULL

Notes:

- For MVP, storing password in DB is acceptable if handled carefully in UI and logs.
- Never print saved password in plain text after save.
- When editing, allow optional blank password field meaning “keep existing password”.

### 2. Mappings Table

Table name:

`{$wpdb->prefix}formbridge_mappings`

Columns:

- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `source_type` VARCHAR(50) NOT NULL
- `form_id` BIGINT UNSIGNED NOT NULL
- `connection_id` BIGINT UNSIGNED NOT NULL
- `destination_type` VARCHAR(50) NOT NULL DEFAULT 'mysql'
- `destination_table` VARCHAR(191) NOT NULL
- `field_map` LONGTEXT NOT NULL
- `is_active` TINYINT(1) NOT NULL DEFAULT 1
- `created_at` DATETIME NOT NULL
- `updated_at` DATETIME NOT NULL

Notes:

- `field_map` will store JSON like:

```json
{
  "your-name": "full_name",
  "your-email": "email",
  "your-phone": "phone_number"
}
```

### 3. Logs Table

Table name:

`{$wpdb->prefix}formbridge_logs`

Columns:

- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `source_type` VARCHAR(50) NOT NULL
- `form_id` BIGINT UNSIGNED NOT NULL
- `mapping_id` BIGINT UNSIGNED NULL
- `destination_type` VARCHAR(50) NOT NULL
- `destination_table` VARCHAR(191) NOT NULL
- `payload` LONGTEXT NULL
- `status` VARCHAR(20) NOT NULL
- `message` TEXT NULL
- `created_at` DATETIME NOT NULL

Recommended `status` values:

- `success`
- `failed`
- `skipped`

---

## Admin Pages Required

Register a top-level admin menu:

- **FormBridge**

Subpages:

- Connections
- Mappings
- Logs

### 1. Connections Page

Purpose:

- create/edit/delete external MySQL connection profiles
- test DB connection

Fields for create/edit:

- Connection Name
- Host
- Port
- Database Name
- Username
- Password

Features:

- list existing connections in a table
- add new connection form
- edit existing connection
- delete with confirmation
- test connection button

Validation:

- all fields required except password on edit if keeping current
- port numeric and default to 3306

Test connection result should display a clear admin notice:

- success: connection successful
- failure: include safe error text, no password exposure

### 2. Mappings Page

Purpose:

- choose a CF7 form
- choose a connection
- choose destination table
- map CF7 fields to DB columns

Required flow:

1. select source type = `cf7`
2. choose CF7 form from dropdown
3. choose saved DB connection
4. click “Load Tables” or auto-load via form submit
5. choose destination table
6. load:
   - CF7 fields
   - DB table columns
7. render visual field mapper
8. save mapping

Mapping UI:

Table-like UI with rows:

- left column = CF7 field name
- right column = dropdown of DB columns plus blank “Do not map” option

Important:

- ignore columns like `id` if needed, but still allow them if user wants, except maybe AUTO_INCREMENT primary keys
- keep UI simple and usable

Only one active mapping per CF7 form in MVP.
If saving a new one for same form, update the existing mapping instead of creating confusing duplicates.

### 3. Logs Page

Purpose:

- show recent form sync attempts
- help with debugging

Columns:

- Date/Time
- Source
- Form ID
- Destination Table
- Status
- Message

Optional details row or expandable view:

- payload JSON pretty printed

Filters nice to have but optional for MVP.
At minimum show latest 50 logs ordered newest first.

---

## Contact Form 7 Integration Details

Use Contact Form 7 hooks to capture successful submissions.

Preferred approach:

- hook after successful submission event
- extract the posted data from `WPCF7_Submission`

Suggested implementation direction:

```php
add_action('wpcf7_mail_sent', [$this, 'handle_submission']);
```

In `handle_submission($contact_form)`:

1. confirm CF7 classes exist
2. get submission instance:

```php
$submission = \WPCF7_Submission::get_instance();
```

3. get posted data:

```php
$posted_data = $submission ? $submission->get_posted_data() : [];
```

4. normalize:

- source = `cf7`
- form_id = `$contact_form->id()`
- form_title = `$contact_form->title()`
- fields = posted data minus internal CF7 keys if needed

Ignore internal keys like:

- `_wpcf7`
- `_wpcf7_version`
- `_wpcf7_locale`
- `_wpcf7_unit_tag`
- `_wpcf7_container_post`
- `_wpnonce`

Then pass normalized submission to router.

### Getting CF7 Forms for Admin Dropdown

Use `WPCF7_ContactForm::find()` if available, or another safe CF7 method to list forms.

Need helper function to return:

```php
[
  ['id' => 123, 'title' => 'Lead Form'],
  ['id' => 456, 'title' => 'Support Form'],
]
```

### Extracting Field Names from CF7 Form Template

Need a helper to parse form tags from the CF7 form template so that mapping page can show known fields before submission.

Use CF7 tag scanning if available. Prefer official/internal parser if practical. Otherwise use a safe regex fallback.

Goal:

From template like:

```text
[text* your-name]
[email* your-email]
[tel your-phone]
[textarea your-message]
```

extract:

```php
['your-name', 'your-email', 'your-phone', 'your-message']
```

This should be used on the mappings page.

---

## Mapping Engine Requirements

Input:

- normalized submission array
- field_map JSON decoded into associative array

Example input map:

```php
[
    'your-name' => 'full_name',
    'your-email' => 'email',
    'your-phone' => 'phone_number',
]
```

Output:

```php
[
    'full_name' => 'John Doe',
    'email' => 'john@example.com',
    'phone_number' => '6041234567',
]
```

Rules:

- if source field missing, skip it
- if destination column blank, skip it
- if field value is array, JSON encode it for now
- do not mutate original submission
- return an associative array ready for insert

---

## MySQL Writer Requirements

This is a very important class.

### Input

- mapped payload associative array
- config array with:
  - connection credentials
  - destination table

### Behavior

1. validate payload is not empty
2. validate destination table exists on remote DB
3. validate every payload key exists as a real column in that table
4. build prepared INSERT statement
5. execute with PDO
6. return structured result

### Example

Payload:

```php
[
    'full_name' => 'John Doe',
    'email' => 'john@example.com',
    'notes' => 'Hello there',
]
```

SQL generated:

```sql
INSERT INTO `leads` (`full_name`, `email`, `notes`) VALUES (?, ?, ?)
```

### Return Format

On success:

```php
[
    'success' => true,
    'message' => 'Insert successful.',
    'insert_id' => 123,
]
```

On failure:

```php
[
    'success' => false,
    'message' => 'Column email_address does not exist in destination table.',
    'insert_id' => null,
]
```

### Validation Notes

- quote identifiers safely with backticks only after validating them against discovered schema
- never trust user-supplied table/column names blindly
- table name must come from admin-selected discovered schema
- columns must come from admin-selected discovered schema

---

## Connection Manager Requirements

Use PDO for external DB connections.

### Method Ideas

- `connect(array $connection): PDO`
- `test_connection(array $connection): array`
- `get_tables(array $connection): array`
- `get_columns(array $connection, string $table): array`
- `table_exists(array $connection, string $table): bool`
- `get_valid_columns(array $connection, string $table): array`

### DSN Example

```php
$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    $connection['db_host'],
    (int) $connection['db_port'],
    $connection['db_name']
);
```

Set PDO attributes:

- `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`
- `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`
- `PDO::ATTR_EMULATE_PREPARES => false`

### Table Discovery

Use:

```sql
SHOW TABLES
```

### Column Discovery

Use:

```sql
SHOW COLUMNS FROM `table_name`
```

Only use validated table names.

---

## Router Flow

The router should do this in order:

1. receive normalized submission
2. find active mapping by:
   - source_type
   - form_id
   - is_active = 1
3. if no mapping found:
   - log status `skipped`
   - message `No active mapping found for this form.`
   - return gracefully
4. load connection by `connection_id`
5. decode `field_map`
6. transform submission through mapping engine
7. if mapped payload empty:
   - log failed or skipped with useful message
8. call MySQL writer
9. log final result
10. return result array

Suggested return shape from router:

```php
[
    'success' => true,
    'message' => 'Insert successful.',
]
```

---

## Logging Requirements

Every submission attempt should create a log.

### Log on success

Store:

- source_type
- form_id
- mapping_id
- destination_type = mysql
- destination_table
- payload JSON
- status = success
- message
- created_at

### Log on failure

Store:

- same fields
- status = failed
- clear error message

### Log on skipped events

Examples:

- no mapping found
- empty mapped payload
- CF7 submission unavailable

Store `status = skipped`.

Payload JSON can be pretty raw, but must not contain credentials.

---

## Admin UX Requirements

Keep UI clean and practical, not overly fancy.

### General

- use standard WordPress admin styles where possible
- use notices for success/failure feedback
- keep pages usable without React or complex JS
- minimal vanilla JS is fine for dynamic dropdown behavior

### Mapping UX

This is the most important admin screen.

Desired flow:

- user picks CF7 form
- user picks connection
- user picks table
- fields and columns load
- mapping rows appear immediately
- user saves

Each mapping row should show:

- source field label/name
- dropdown of DB columns

Add a blank dropdown option like:

- `-- Do not map --`

### Save behavior

When saving mapping:

- validate source form exists
- validate connection exists
- validate destination table exists
- validate selected columns exist
- save JSON field map
- upsert existing active mapping for that form/source

---

## Suggested File-by-File Build Order for Cursor

Tell Cursor to build in this exact order.

### Phase 1: Skeleton

1. `formbridge.php`
2. `includes/class-plugin.php`
3. `includes/class-activator.php`
4. `includes/class-admin.php`
5. empty view files
6. empty helper file

Goal: plugin activates and admin menu appears.

### Phase 2: Database Tables and Repositories

7. create plugin tables with `dbDelta`
8. implement connection repository
9. implement mapping repository
10. implement log repository

Goal: CRUD foundation exists.

### Phase 3: Connection Layer

11. `includes/class-connection-manager.php`
12. Connections admin page
13. create/edit/delete/test connection actions

Goal: admin can save connection and test it.

### Phase 4: Mapping Layer

14. helper to list CF7 forms
15. helper to parse CF7 fields
16. Mappings page UI
17. load tables/columns based on selected connection/table
18. save mapping JSON

Goal: admin can create a usable mapping.

### Phase 5: Runtime Submission Handling

19. source adapter interface
20. CF7 adapter
21. router
22. mapping engine
23. destination writer interface
24. MySQL writer
25. logger service

Goal: form submission writes to external DB.

### Phase 6: Logs Page and Cleanup

26. logs page
27. pretty payload display
28. admin notices and error handling cleanup
29. code cleanup and comments

---

## Coding Standards for Cursor

Cursor should follow these rules while generating code:

- write modular OOP PHP
- avoid giant god classes
- use dependency injection through constructors where reasonable
- add docblocks for public methods
- use strict, readable naming
- no unnecessary abstractions beyond MVP architecture
- do not introduce Composer requirement for MVP unless truly necessary
- avoid React build tooling for admin UI
- prefer simple PHP + WordPress admin forms
- handle errors gracefully with user-friendly admin messages
- no direct SQL string concatenation for inserts except validated identifiers
- all values must be parameterized
- use `wp_nonce_field`, `check_admin_referer`, `sanitize_text_field`, `absint`, etc.
- use `esc_html`, `esc_attr`, `esc_textarea`, `wp_kses_post` appropriately

---

## Example Helper Functions Needed

### `formbridge_get_cf7_forms(): array`

Returns list of CF7 forms for dropdown.

### `formbridge_get_cf7_fields(int $form_id): array`

Returns extracted field names from selected CF7 form.

### `formbridge_json_encode(array $data): string`

Safe wrapper for JSON encoding logs/maps.

### `formbridge_now(): string`

Returns current WordPress time in MySQL format.

---

## Error Handling Expectations

Implement clear messages for these scenarios:

### Connection errors

- invalid credentials
- host unreachable
- DB not found
- access denied

### Mapping errors

- form not found
- connection not found
- table not found
- selected column invalid

### Runtime submission errors

- no mapping found
- no submission instance
- empty payload after mapping
- table missing on remote DB
- remote DB connection failed
- insert failed

Do not crash the frontend if form sync fails.
If DB insert fails, log it quietly and return.
The CF7 submission itself should not white-screen the site.

---

## Test Cases Cursor Should Consider

### Admin / Setup

1. plugin activation creates all tables
2. admin can add a connection
3. admin can edit a connection without retyping password
4. test connection succeeds with valid credentials
5. test connection fails clearly with bad credentials
6. mappings page lists CF7 forms
7. mappings page lists tables for selected connection
8. mappings page lists columns for selected table
9. mapping saves successfully

### Runtime

10. CF7 submission with active mapping inserts row into external DB
11. submission with no mapping logs skipped
12. mapping with invalid column logs failed
13. array field values get JSON encoded
14. empty mapped payload logs skipped or failed appropriately
15. logs page shows newest records first

---

## Sample Runtime Scenario

### Example CF7 form fields

```php
['your-name', 'your-email', 'your-phone', 'your-message']
```

### Example external table columns

```php
['id', 'full_name', 'email', 'phone_number', 'notes', 'created_at']
```

### Saved field mapping

```php
[
    'your-name' => 'full_name',
    'your-email' => 'email',
    'your-phone' => 'phone_number',
    'your-message' => 'notes',
]
```

### Expected insert payload

```php
[
    'full_name' => 'John Doe',
    'email' => 'john@example.com',
    'phone_number' => '6041234567',
    'notes' => 'Hello there',
]
```

### Expected SQL

```sql
INSERT INTO `leads` (`full_name`, `email`, `phone_number`, `notes`) VALUES (?, ?, ?, ?)
```

---

## What Not To Build Yet

Cursor must not bloat MVP with these:

- licensing system
- cloud sync
- cron processing
- retry queues
- background task workers
- custom REST API unless needed later
- Jotform direct integration
- support for every form plugin
- drag-and-drop page builders
- reporting dashboard
- analytics charts
- role management beyond admin-only
- encrypted secrets framework unless trivial
- custom CSS framework

Keep MVP lean.

---

## Deliverables Expected from Cursor

Ask Cursor to generate the code in logical batches.

### Batch 1

Plugin bootstrap + activator + admin menu + empty pages

### Batch 2

Repositories + DB schema

### Batch 3

Connection manager + Connections page CRUD + test connection

### Batch 4

CF7 helper functions + Mappings page + field mapping persistence

### Batch 5

Adapter + router + mapping engine + MySQL writer + logger

### Batch 6

Logs page + cleanup + comments + polish

Do not ask Cursor to dump the whole plugin in one giant answer. Build and review incrementally.

---

## Final Instruction to Cursor

Build a production-minded but MVP-sized WordPress plugin named **FormBridge** that:

- supports Contact Form 7 as the only source in V1
- supports external MySQL as the only destination in V1
- allows admin-managed DB connections
- allows per-form field mapping to a selected remote table
- writes mapped submissions safely using PDO prepared inserts
- logs all sync attempts
- uses clean modular architecture so more adapters/destinations can be added later

The result should be immediately usable, reasonably secure, easy to extend, and should avoid overengineering.

---

## Suggested Next Prompt for Cursor

Use this after sharing the brief:

```text
Read this entire development brief carefully and follow it exactly. Start with Batch 1 only: generate the plugin bootstrap, activator, main plugin class, admin menu, and placeholder admin pages using the specified folder structure. Keep code modular and WordPress-safe. Do not implement future phases yet.
```
