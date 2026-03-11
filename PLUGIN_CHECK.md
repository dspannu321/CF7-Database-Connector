# Plugin Check Report

**Plugin:** CF7 Database Connector
**Generated at:** 2026-03-11 05:10:45


## `C:\Users\dspan\Local Sites\cf7\app\public\wp-content\plugins\CF7-Database-Connector\includes\class-admin.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 539 | 87 | ERROR | WordPress.WP.I18n.MissingTranslatorsComment | A function call to __() with texts containing placeholders was found, but was not accompanied by a "translators:" comment on the line above to clarify the meaning of the placeholders. | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#descriptions) |
| 540 | 98 | ERROR | WordPress.WP.I18n.MissingTranslatorsComment | A function call to __() with texts containing placeholders was found, but was not accompanied by a "translators:" comment on the line above to clarify the meaning of the placeholders. | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#descriptions) |

## `cf7-database-connector.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | ERROR | textdomain_invalid_format | The "Text Domain" header in the plugin file should only contain lowercase letters, numbers, and hyphens. Found "CF7-Database-Connector". | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#text-domains) |

## `C:\Users\dspan\Local Sites\cf7\app\public\wp-content\plugins\CF7-Database-Connector\admin\views\logs-page.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 88 | 68 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$cf7db_created'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 89 | 70 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$cf7db_source'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 91 | 75 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$cf7db_dest'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 93 | 71 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$cf7db_message'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |

## `C:\Users\dspan\Local Sites\cf7\app\public\wp-content\plugins\CF7-Database-Connector\cf7-database-connector.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 53 | 5 | ERROR | PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound | load_plugin_textdomain() has been discouraged since WordPress version 4.6. When your plugin is hosted on WordPress.org, you no longer need to manually include this function call for translations under your plugin slug. WordPress will automatically load the translations for you as needed. | [Docs](https://make.wordpress.org/core/2016/07/06/i18n-improvements-in-4-6/) |
