## Changes from DB_dsql

### Renamed internal methods and properties
 - `consume()` renamed into `_consume()`
 - `render_*` methods renamed into `_render_*`
 - `bt()` renamed into `_escape()`
 - `$bt` property renamed to `$escapeChar`
 - `$sql_templates` renamed to `$template_select`, `$template_insert` etc.
 - `options()`, `options_insert()`, `options_replace` renamed to `option($option, $mode)`


### Other changes
 - `_consume()` does not have 2nd argument anymore ($tick). Will always escape unless expression is passed.
