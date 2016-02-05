## Changes from DB_dsql

 - bt() renamed into escape()
 - $bt property renamed to escapeChar
 - sql_templates renamed to templates
 - consume() does not have 2nd argument anymore ($tick). Will always escape unless expression is passed.
