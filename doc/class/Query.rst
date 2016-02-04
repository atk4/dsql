Query Class
===========

.. php:class:: Query

  Query class

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

  .. php:method:: field($fields)

      Specify which fields to use in SELECT query.

      :param string|array $fields: Specify list of fields to fetch
      :returns: $this

  .. php:const:: ATOM

      Y-m-d\TH:i:sP
