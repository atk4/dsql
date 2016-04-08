
.. php:class:: Query

==============================
Introduction to Query Building
==============================

Query class represents your SQL query in-the-making. Once you create object of the Query
class, call some of the methods listed below to modify your query. To actually execute
your query and start retrieving data, see :ref:`fetching` section.

You should use Query to build a specific statements that are understood by
your SQL database, such as SELECT or INSERT.

Once you create a query you can execute modifier methods such as `field()` or
`table()` which will change the way how your query will act.

Once the query is defined, you can either use it inside another query or
expression or you can execute it in exchange for result set.

Quick Example::

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

.. _query-modes:

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

This method can be invoked using different combinations of arguments. Follow
the principle of specifying the table first, and then
optionally provide an alias. You can specify multiple tables at the same
time by using comma or array (although you won't be able to use the
alias there). Using keys in your array will also
specify the aliases::

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

Inside your query tables and aliases will always be surrounded by backticks.
If you want to use a more complex expression, use :php:class:`Expression`::

    $query->table($query->expr(
        '(SELECT id FROM user UNION select id from document) tbl'
    ));
        // SELECT * FROM (SELECT id FROM user UNION
        //  SELECT id FROM document ) tbl

Finally, you can also specify a different query instead of table, by simply
passing another :php:class:`Query` object::

    $sub_q = new Query();
    $sub_q -> table('emplyeee');
    $sub_q -> where('name','John');

    $q = new Query();
    $t -> field('surname');
    $t -> table($sub_q);

Method table() can be executed several times on the same query object.

  .. php:method:: field($fields, $table = null, $alias = null)

      Adds additional field that you would like to query. If never called,
      will default do `defaultField`, which normally is `*`.

      This method has several call options. $field can be array of fields
      and can also can be an expression. If you specify expression in $field
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

If the first argument to field contains non-alphanumeric values such as spaces
or brackets, then field() will assume that you're passing an expression::

    $query->field('now()');

    $query->field('now()', 'time_now');

You may also pass array as first argument, keys will be used as alias (if they are
specified)::

    $query->field(['time_now'=>'now()', 'time_created']);

Obviously you can call field() multiple times.

  .. php:method:: where($field, $operation, $value)


      Specify a table to be used in a query.

      :param mixed $field: field such as "name"
      :param mixed $operation: comparison operation such as ">" (optional)
      :param mixed $value: value or expression
      :returns: $this

This method can be invoked with different arguments, as long as you specify
them in the correct order.

Pass string (field), Expression (or even Query) as first argument. If you
are using string, you may end it with operation, such as "age>"  or "parent_id is not"
DSQL will recognize <,>,=,!=,<>,is,is not. 

If you havent specified parameter as a part of field, specify it through a second
parameter - $operation. If unspecified, will default to '='.

Last argument is value. You can specify number, string, array or even null.
This argument will always be parameterised. If you specify array, it's
elements will be parametrised.

Starting with the basic examples::

    $q->where('id',1);

    $q->where('id>', 1);
    $q->where('id', '>', 1); //  same as above

    $q->where('id', 'is', null); 
    $q->where('id', null);   // same as above

    $q->where('now()',1);    // will not use backticks.
    $q->where(new Expression('now()'),1);  // same as above

    $q->where('id',[1,2]);   // renders as id in (1,2)

You may call where() multiple times, and conditions are always additive (uses AND)
The easiest way to supply OR condition is if you specify multiple condition
through array::

    $q->where([['name','like','%john%'], ['surname','like','%john%']);

You can also mix and match with expressions and strings::

    $q->where([['name','like','%john%'], 'surname is null');

    $q->where([['name','like','%john%'], new Expression('surname is null')]);

.. todo::
    strict mode

Joining with other tables
-------------------------

  .. php:method:: join($field)

      Join results with additional table using "JOIN" statement in your query.

      :param string $foreign_table: table to join (may include field and alias)
      :param mixed  $master_field:  main field (and table) to join on or Expression
      :param string $join_kind:     'left' (default), 'inner', etc - which type of join.

When joinin with a different table, the results will be stacked by the SQL server
so that fields from both tables are available.

    $q->group('gender');

    $q->group('gender,age');

    $q->group(['gender', 'age']);

    $q->group('gender')->group('age');

    $q->group(new Expression('year(date)'));

You may call group() multiple times.


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
