===========
Query Class
===========

.. php:class:: Query

Query class represents your SQL query-in-making.

  .. php:method:: table($year)

      Specify a table to be used in a query.

      :param mixed $table: table such as "employees"
      :returns: $this

There are many possible scenarios how to specify a table::

    $query->table('user');
        // SELECT * from `user`

    $query->table('user','u');
        // aliases table with "u"
        // SELECT * from `user` `u`

    $query->table('user')->table('salary');
        // specify multiple tables. Don't forget to link with "where"
        // SELECT * from `user`, `salary`

    $query->table(['user','salary']);
        // identical to previous example
        // SELECT * from `user`, `salary`

    $query->table(['u'=>'user','s'=>'salary']);
        // specify aliases for multiple tables
        // SELECT * from `user` `u`, `salary` `s`

All tables and aliases will always be surrounded by backticks. Additionally
you may use expression as a table::

    $query->table($query->expr(
        '(SELECT id FROM user UNION select id from document) tbl'
    ));
        // SELECT * FROM (SELECT id FROM user UNION
        //  SELECT id FROM document ) tbl

Refer to :ref:`expr` for more information on how to create them.

  .. php:method:: field($fields, $table = null, $alias = null)

      Adds additional field that you would like to query. If never called,
      will default do `defaultField`.

      This method has several call options. $field can be array of fields
      and can also be an expression. If you specify expression in $field
      then alias is mandatory.

      :param string|array|object $fields: Specify list of fields to fetch
      :param string $table: Optionally secify a table to query from
      :param string $alias: Optionally secify alias for resulting query
      :returns: $this

Basic Examples::

    $query = new dsql/Query();
    $query->table('user');

    $query->field('first_name');
        // SELECT `first_name` from `user`

    $query->field('first_name,last_name');
        // SELECT `first_name`,`last_name` from `user`

    $query->field('first_name','emplayee')
        // SELECT `emplayee`.`first_name` from `user`

    $query->field(first_name',null,'name')
        // SELECT `first_name` `name` from `user`

    $query->field(['name'=>'first_name'])
        // SELECT `first_name` `name` from `user`

    $query->field(['name'=>'first_name'],'employee');
        // SELECT `employee`.`first_name` `name` from `user`

See also field() usage with :ref:`expr`.


Internal Methods
================

You probably won't have to use those methods, unless you're working with
DSQL internally.

  .. php:method:: _consume($sql_code)

      Internal method.

      Makes $sql_code part of $this query. Argument may be either
      a string (which will be escaped) or another Query. If
      specified query implements a "select", then it's automatically
      placed inside brackets.

      $query->consume('first_name');  // `first_name`
      $query->consume($other_query);  // will merge parameters and return string

  .. php:method:: _escape($sql_code)

      Internal method.

      Surrounds $sql code with $escapeChar. If escapeChar is null
      will do nothing.

      Will also do nothing if it finds "*", "." or "(" character in $sql_code

      $query->_escape('first_name');  // `first_name`
      $query->_escape('first.name');  // first.name
      $query->_escape('(2+2)');       // (2+2)
      $query->_escape('*');           // *
