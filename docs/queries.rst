.. _query:

.. php:class:: Query

=======
Queries
=======

Query class represents your SQL query in-the-making. Once you create object of the Query
class, call some of the methods listed below to modify your query. To actually execute
your query and start retrieving data, see :ref:`fetching-result` section.

You should use :ref:`Query <queries>` to build a specific statements that are understood
by your SQL database, such as SELECT or INSERT.

Once you create a query you can execute modifier methods such as :php:meth:`field()` or
:php:meth:`table()` which will change the way how your query will act.

Once the query is defined, you can either use it inside another query or
expression or you can execute it in exchange for result set.

Quick Example::

    use atk4\dsql;

    $query = new Query();
    $query -> field('name');
    $query -> where('id', 123);
    $name = $query -> getOne();


Method invocation principles
============================

Methods of Query are designed to be safe to use through a higher level
framework. Most of the methods will accept either a string or expression.
If you pass a string, it will be escaped. Using expression you can bypass
the escaping.

There are 2 types of escaping:

 * :php:meth:`Expression::_escape()`. Used for field and table names. Surrounds name with *`*.
 * :php:meth:`Expression::_param()`. Will convert value into parameter and replace with *:a*

By calling `$res = Expression::_consume($sql, 'param')` the query code
makes sure that nested expressions are properly interlinked and that
any strings are converted into parameters.

.. _query-modes:

Query Modes
===========

By default there are 6 query modes::
 * :php:meth:`select()`
 * :php:meth:`insert()`
 * :php:meth:`replace()`
 * :php:meth:`update()`
 * :php:meth:`delete()`
 * :php:meth:`truncate()`
The default mode is 'select'.

With Query object you need to specify arguments first and then perform an operation.
This actually allows you to re-use the same Query object for more than one operation.

.. code-block:: php

    use atk4\dsql;

    $data = ['name'=>'John', 'surname'=>'Smith']

    $query = new Query();
    $query
        -> where('id', 123)
        -> field('id')
        -> table('user')
        -> set($data)
        ;

    $row = $query->getRow();

    if ($row) {
        $query
            ->set('revision', $query->expr('revision + 1'))
            ->update()
            ;
    } else {
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

Majority of methods return `$this` when called, which makes it pretty convenient
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
        ->field('amount', null, 'debit');

    $q2 = (new Query())
        ->table('purchases')
        ->field('date')
        ->field('amount', null, 'credit');

    $u = (new Expression("([] union []) derrivedTable", [$q1, $q2]));

    $q = (new Query())
        ->field('date,debit,credit')
        ->table($u)
        ;

    $q->getData();

This query will perform union between 2 table selects resulting in the following
query:

.. code-block:: sql

    select `date`,`debit`,`credit` from (
        (select `date`,`amount` `debit` from `sales`) union
        (select `date`,`amount` `credit` from `purchases`)
    ) derrivedTable

Modifying your Query
====================

Setting Table
-------------

  .. php:method:: table($table, $alias)

      Specify a table to be used in a query.

      :param mixed $table: table such as "employees"
      :param mixed $alias: alias of table
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

Inside your query table names and aliases will always be surrounded by backticks.
If you want to use a more complex expression, use :php:class:`Expression`::

    $query->table($query->expr(
        '(SELECT id FROM user UNION select id from document) tbl'
    ));
        // SELECT * FROM (SELECT id FROM user UNION SELECT id FROM document ) tbl

Finally, you can also specify a different query instead of table, by simply
passing another :php:class:`Query` object::

    $sub_q = new Query();
    $sub_q -> table('employee');
    $sub_q -> where('name', 'John');

    $q = new Query();
    $t -> field('surname');
    $t -> table($sub_q);

    // SELECT `surname` FROM (SELECT * FROM employee WHERE `name` = :a)

Method table() can be executed several times on the same query object.

Setting Fields
--------------

  .. php:method:: field($fields, $table = null, $alias = null)

      Adds additional field that you would like to query. If never called,
      will default to `defaultField`, which normally is `*`.

      This method has several call options. $field can be array of fields
      and also can be an expression. If you specify expression in $field
      then alias is mandatory.

      :param string|array|object $fields: Specify list of fields to fetch
      :param string $table: Optionally specify a table to query from
      :param string $alias: Optionally specify alias for resulting query
      :returns: $this

Basic Examples::

    $query = new Query();
    $query->table('user');

    $query->field('first_name');
        // SELECT `first_name` from `user`

    $query->field('first_name,last_name');
        // SELECT `first_name`,`last_name` from `user`

    $query->field('first_name','employee')
        // SELECT `employee`.`first_name` from `user`

    $query->field('first_name',null,'name')
        // SELECT `first_name` `name` from `user`

    $query->field(['name'=>'first_name'])
        // SELECT `first_name` `name` from `user`

    $query->field(['name'=>'first_name'],'employee');
        // SELECT `employee`.`first_name` `name` from `user`

If the first parameter of field method contains non-alphanumeric values such as spaces
or brackets, then field() will assume that you're passing an expression::

    $query->field('now()');

    $query->field('now()', 'time_now');

You may also pass array as first argument. In such case array keys will be used as aliases
(if they are specified)::

    $query->field(['time_now'=>'now()', 'time_created']);
        // SELECT now() `time_now`, `time_created` ...

Obviously you can call field() multiple times.

Setting where clauses
---------------------

  .. php:method:: where($field, $operation, $value)

      Specify a table to be used in a query.

      :param mixed $field: field such as "name"
      :param mixed $operation: comparison operation such as ">" (optional)
      :param mixed $value: value or expression
      :returns: $this

This method can be invoked with different arguments, as long as you specify
them in the correct order.

Pass string (field name), :php:class:`Expression` (or even :php:class:`Query`) as
first argument. If you are using string, you may end it with operation, such as "age>"
or "parent_id is not" DSQL will recognize <,>,=,!=,<>,is,is not.

If you havent specified parameter as a part of field, specify it through a second
parameter - $operation. If unspecified, will default to '='.

Last argument is value. You can specify number, string, array or even null.
This argument will always be parameterised. If you specify array, it's
elements will be parametrised.

Starting with the basic examples::

    $q->where('id', 1);
    $q->where('id', '=', 1); // same as above

    $q->where('id>', 1);
    $q->where('id', '>', 1); // same as above

    $q->where('id', 'is', null);
    $q->where('id', null);   // same as above

    $q->where('now()', 1);   // will not use backticks
    $q->where(new Expression('now()'),1);  // same as above

    $q->where('id', [1,2]);  // renders as id in (1,2)

You may call where() multiple times, and conditions are always additive (uses AND).
The easiest way to supply OR condition is if you specify multiple condition
through array::

    $q->where([['name', 'like', '%john%'], ['surname', 'like', '%john%']);
        // .. WHERE `name` like '%john%' OR `surname` like '%john%'

You can also mix and match with expressions and strings::

    $q->where([['name', 'like', '%john%'], 'surname is null');
        // .. WHERE `name` like '%john%' AND `surname` is null

    $q->where([['name', 'like', '%john%'], new Expression('surname is null')]);
        // .. WHERE `name` like '%john%' AND surname is null

.. todo::
    strict mode


Grouping results by field
-------------------------

  .. php:method:: group($field)

      Group results with same values in $field

      :param mixed $field: field such as "name"

The "group by" clause in SQL query accepts one or several fields. It can also
accept expressions. You can call `group()` with one or several comma-separated
fields as a parameter or you can specify them in array. Additionally you can
mix that with :php:class:`Expression` or :php:class:`Expressionable` objects.

Few examples::

    $q->group('gender');

    $q->group('gender,age');

    $q->group(['gender', 'age']);

    $q->group('gender')->group('age');

    $q->group(new Expression('year(date)'));

You may call group() multiple times.


Joining with other tables
-------------------------

  .. php:method:: join($foreign_table, $master_field, $join_kind)

      Join results with additional table using "JOIN" statement in your query.

      :param string|array $foreign_table: table to join (may include field and alias)
      :param mixed  $master_field:  main field (and table) to join on or Expression
      :param string $join_kind:     'left' (default), 'inner', 'right' etc - which join type to use

When joinin with a different table, the results will be stacked by the SQL server
so that fields from both tables are available. The first argument can specify
the table to join, but may contain more information::

    $q->join('address');           // address.id = address_id
        // JOIN `address` ON `address`.`id`=`address_id`

    $q->join('address a');         // specifies alias for the table
        // JOIN `address` `a` ON `address`.`id`=`address_id`

    $q->join('address.user_id');   // address.user_id = id
        // JOIN `address` ON `address`.`user_id`=`id`

You can also pass array as a first argument, to join multiple tables::

    $q->table('user u');
    $q->join(['a'=>'address', 'c'=>'credit_card', 'preferences']);

The above code will join 3 tables using the following query syntax:

.. code-block:: sql

    join
        address as a on a.id = u.address_id
        credit_card as c on c.id = u.credit_card_id
        preferences on preferences.id = u.preferences_id

However normally you would have `user_id` field defined in your suplimentary
tables so you need a different syntax::

    $q->table('user u');
    $q->join(['a'=>'address.user_id', 'c'=>'credit_card.user_id', 'preferences.user_id']);

The second argument to join specifies which existing table/field is
used in `on` condition::

    $q->table('user u');
    $q->join('user boss', 'u.boss_user_id');
        // JOIN `user` `boss` ON `boss`.`id`=`u`.`boss_user_id`

By default the "on" field is defined as `$table."_id"`, as you have seen in the previous
examples where join was done on "address_id", and "credit_card_id". If you
have specified field explicitly in the foreign field, then the "on" field
is set to "id", like in the example above.

You can specify both fields like this::

    $q->table('employees');
    $q->join('salaries.emp_no', 'emp_no');

If you only specify field like this, then it will be automatically prefixed with the name
or alias of your main table. If you have specified multiple tables, this won't work
and you'll have to define name of the table explicitly::

    $q->table('user u');
    $q->join('user boss', 'u.boss_user_id');
    $q->join('user super_boss', 'boss.boss_user_id');

The third argument specifies type of join and defaults to "left" join. You can specify
"inner", "straight" or any other join type that your database support.


Limiting result-set
-------------------

  .. php:method:: limit($cnt, $shift)

      Limits query result-set.

      :param int $cnt: number of rows to return
      :param int $shift: offset, how many rows to skip

Use this to limit your :php:class:`Query` result-set::

    $q->limit(5, 10);
        // .. LIMIT 5, 10


Ordering result-set
-------------------

  .. php:method:: order($order, $desc)

      Orders query result-set.

      :param int $order: one or more field names, expression etc.
      :param int $desc: pass true to sort descending

Use this to order your :php:class:`Query` result-set::

    $q->order('name');              // .. order by name
    $q->order('name desc');         // .. order by name desc
    $q->order('name desc, id asc')  // .. order by name desc, id asc
    $q->order('name',true);         // .. order by name desc


Public Methods
==============

.. php:method:: field($field, $table, $alias)

    Adds new column to resulting select by querying $field. See :ref:`Setting Fields`.

.. php:method:: table($table, $alias)

    Adds table to resulting query. See :ref:`Setting Table`.

.. php:method:: join($foreign_table, $master_field, $join_kind, $_foreign_alias)

    Joins your query with another table. Join will use $main_table to reference
    the main table, unless you specify it explicitly. See :ref:`Joining with other tables`.

.. php:method:: where($field, $cond, $value, $kind, $num_args)

    Adds condition to your query. See :ref:`Setting where clauses`.

.. php:method:: having($field, $cond, $value)

    Adds condition to your query. Same syntax as :php:meth:`where()`.
    See :ref:`Setting where clauses`.

.. php:method:: group($group)

    Group by functionality. Simply pass either field name as string or
    :class:`Expression` object. See :ref:`Grouping results by field`.

.. php:method:: set($field, $value)

    Sets field value for INSERT or UPDATE statements. See :ref:`Query Modes`.

.. php:method:: select()

    Execute `select` statement. See :ref:`Query Modes`.

.. php:method:: insert()

    Execute `insert` statement. See :ref:`Query Modes`.

.. php:method:: update()

    Execute `update` statement. See :ref:`Query Modes`.

.. php:method:: replace()

    Execute `replace` statement. See :ref:`Query Modes`.

.. php:method:: delete()

    Execute `delete` statement. See :ref:`Query Modes`.

.. php:method:: truncate()

    Execute `truncate` statement. See :ref:`Query Modes`.

.. php:method:: limit($cnt, $shift)

    Limit how many rows will be returned. See :ref:`Limiting result-set`.

.. php:method:: order($order, $desc)

    Orders results by field or :class:`Expression`. See :ref:`Ordering result-set`.

.. php:method:: selectTemplate($mode)

    Switch template for this query. Determines what would be done on execute.
    See :ref:`Query Modes`.

.. php:method:: dsql($properties)

    Use this instead of `new Query()` if you want to automatically bind
    query to the same connection as the parent.

.. php:method:: orExpr()

    Returns new Query object of [or] expression.

.. php:method:: andExpr()

    Returns new Query object of [and] expression.

Properties
==========

.. php:attr:: templates

    Array of templates for basic queries. See :ref:`Query Modes`.

.. php:attr:: mode

    Query will use one of the predefined "templates". The mode will contain
    name of template used. Basically it's array key of $templates property.
    See :ref:`Query Modes`.

.. php:attr:: defaultField

    If no fields are defined, this field is used.
