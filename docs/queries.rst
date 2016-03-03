.. php:class:: Query

=========
Basic Queries
=========

Query class represents your SQL query-in-making. Query class extends expression,
therefore inheriting their ability to render(), getDebugQuery() and more.

You should use Query to build a specific statements that are understood by
your SQL database, such as SELECT or INSERT.

Once you create a query you can execute modifier methods such as `field()` or
`table()` which will change the way how your query will act.

Once the query is defined, you can either use it inside another query or
expression or you can execute it in exchange for result set.

Quick Example:

.. code-block:: php

    use dsql;

    $query = new dsql/Query();
    $query -> where('id', 123);
    $query -> field('name');
    $name = $query -> getOne();


Method invocation principles
============================

Methods of Query are designed to be safe to use through a higher level
framework. Most of the methods will accept either a string or expression.
If you pass a string, it will be escaped. Using expression you can bypass
the escaping.

There are 2 types of escaping:

 * escape. Used for field and table names. Surrounds name with *`*.
 * param. Will convert value into param and replace with *:a*

By calling `$res = Expression::_consume($sql, 'param')` the query code
makes sure that nested expressions are properly interlinked and that
any strings are converted into parameters.

Query Modes
===========

By default there are 7 query modes: 'select', 'insert', 'replace', etc. The
default mode is 'select'. With Query object you need to specify arguments
first and then perform an operation. This actually allows you to re-use
the same Query object for more than one operations.

.. code-block:: php

    $data = ['name'=>'John', 'surname'=>'Smith']

    $query = new dsql/Query();
    $query
        -> where('id', 123)
        -> field('id')
        -> table('user')
        -> set($data)
        ;

    $row = $query->getRow();

    if($row){
        $query
            ->set('revision', $query->expr('revision + 1'))
            ->update()
            ;
    }else{
        $query
            ->set('revision', 1)
            ->insert();
    }

The example above will perform a select query first:

 - `select id from user where id=123`

If a single row can be retrieved, then the update will be performed:

 - `update user set name="John", surname="Smith", revision=revision+1 where id=123`

Otherwise an insert operation will be performed:

 - `insert into user (name,surname,revision) values ("John", "Smith", 1)`

Chaining
========

Majority of methods return $this when called, which makes it pretty convenient
for you to chain calls by using `->fx()` multiple times as illustrated in my
last example.

You can also combine creation of the object with method chaining:

.. code-block:: php

    $age = (new Query())->table('user')->where('id',123)->field('age')->getOne();

Using query as expression
=========================

You can use query as expression where applicable. The query will get a special
treatment where it will be surrounded in brackets. Here are few examples:

.. code-block:: php

    $q = (new Query())
        ->table('employee');

    $q2 = (new Query())
        ->field('name')
        ->table($q)
        );

    $q->getData();

This query will perform `select name from (select * from employee)`

.. code-block:: php

    $q1 = (new Query())
        ->table('sales')
        ->field('date')
        ->field('amount',null,'debit');

    $q2 = (new Query())
        ->table('purchases')
        ->field('date')
        ->field('amount',null,'credit');

    $u = (new Expression("([] union []) derrivedTable", [$q1, $q2]));

    $q = (new Query())
        ->field('date,debit,credit')
        ->table($u)
        ;

    $q->getData();

This query will perform union between 2 table selects resulting in the following
qurey:

.. code-block:: sql

    select `date`,`debit`,`credit` from (
        (select `date`,`amount` `debit` from `sales`) union
        (select `date`,`amount` `credit` from `purchases`)
    ) derrivedTable

Modifying your Query
====================

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
        // specify multiple tables. Don't forget to link them by using "where"
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

      $query->_consume('first_name');  // `first_name`
      $query->_consume($other_query);  // will merge parameters and return string

  .. php:method:: _escape($sql_code)

      Internal method.

      Surrounds $sql code with $escapeChar. If escapeChar is null
      will do nothing.

      Will also do nothing if it finds "*", "." or "(" character in $sql_code

      $query->_escape('first_name');  // `first_name`
      $query->_escape('first.name');  // first.name
      $query->_escape('(2+2)');       // (2+2)
      $query->_escape('*');           // *
