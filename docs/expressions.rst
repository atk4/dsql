.. _expr:

.. php:class:: Expression

===========
Expressions
===========

Expression class implements a flexible way for you to define any custom expression
then execute it as-is or as a part of another query or expression. Expression
is supported anywhere in DSQL to allow you to express SQL syntax properly.

Quick Example:

.. code-block:: php

    $query -> where('time', $query->expr(
        'between "[]" and "[]"',
        [$from_time, $to_time]
    ));

    // Produces: .. where `time` between :a and :b

Another use of expression is to supply field instead of value and vice versa.

.. code-block:: php

    $query -> where($query->expr(
        '[] between time_from and time_to',
        [$time]
    ));

    // Produces: where :a between time_from and time_to

Yet another curious use for the DSQL library is if you have certain object
in your ORM implementing :php:class:`Expressionable` interface. Then you can
also use it within expressions:

.. code-block:: php

    $query -> where($query->expr(
        '[] between [] and []',
        [$time, $model->getElement('time_form'), $model->getElement('time_to')]
    ));

    // Produces: where :a between `time_from` and `time_to`

.. todo::
    add more info or more precise example of Expressionable interface usage.


Another uses for expressions could be:

 - Sub-Queries
 - SQL functions, e.g. IF, CASE
 - nested AND / OR clauses
 - vendor-specific queries - "describe table"
 - non-traditional constructions , UNIONS or SELECT INTO

Properties, Arguments and Parameters
====================================

Be careful when using those similar terms as they refer to different things:

 - Properties refer to object properties, e.g. `$expr->template`, see :ref:`properties`
 - Arguments refer to template arguments, e.g. `select * from [table]`, see :ref:`expression-template`
 - Parameters refer to the way of passing user values within a query `where id=:a`

Creating Expression
===================

.. code-block:: php

    use atk4\dsql;

    $expr = new Expression("NOW()");

You can also use :php:meth:`expr()` method to create expression, in
which case you do not have to define "use" block:

.. code-block:: php

    $query -> where('time', '>', $query->expr('NOW()'));

    // Produces: .. where `time` > NOW()

You can specify some of the expression properties through first argument
of the constructor:

.. code-block:: php

    $expr = new Expression(["NOW()", 'escapeChar' => '*']);

:ref:`Scroll down <properties>` for full list of properties.

Expression Template
===================

When you create a template the first argument is the template. It will be stored
in :php:attr:`$template` property. Template string can contain
arguments in a square brackets:

 - `coalesce([], [])` is same as `coalesce([0], [1])`
 - `coalesce([one], [two])`

Arguments can be specified immediatelly through an array as a second argument
into constructor or you can specify arguments later

.. code-block:: php

    $expr = new Expression(
        "coalesce([name], [surname])",
        ['name' => $name, 'surname' => $surname]
    );

    // is the same as

    $expr = new Expression("coalesce([name], [surname])");
    $expr['name'] = $name;
    $expr['surname'] = $surname;

Nested expressions
==================

Expressions can be nested several times

.. code-block:: php

    $age = new Expression("coalesce([age], [default_age])");
    $age['age'] = new Expression("year(now()) - year(birth_date)");
    $age['default_age'] = 18;

    $query -> table('user') -> field($age, 'calculated_age');

    // select coalesce(year(now()) - year(birth_date), :a) `calculated_age` from `user`

When you include one query into another query, it will automatically take care
of all user-defined parameters (such as value `18` above) which will make sure
that SQL injections could not be introduced at any stage.

Public Methods
==============

.. php:method:: execute($connection)

    Executes expression using current database connection.

.. php:method:: expr($properties, $arguments)

    Creates new :php:class:`Expression` which inherits current
    :php:attr:`$connection` property.

.. php:method:: get()

    Executes expression and return whole result-set.

.. php:method:: getRow()

    Executes expression and returns first row of data from result-set.

.. php:method:: getOne()

    Executes expression and return first value of first row of data from result-set.

.. php:method:: getDebugQuery()

    Outputs query as a string by placing parameters into their respective
    places. The parameters will be escaped, but you should still avoid using
    generated query as it can potentially make you vulnerable to SQL injection.

    This method will use HTML tags to highlight parameters.

.. php:method:: render()

    Converts :php:class:`Expression` object to a string. Parameters are
    replaced with :a, :b, etc.


Internal Methods
================

You probably won't have to use those methods, unless you're working with
DSQL internally.

.. php:method:: _consume($sql_code)

  Makes `$sql_code` part of `$this` expression. Argument may be either
  a string (which will be escaped) or another :php:class:`Expression` or
  :php:class:`Query`.
  If specified :php:class:`Query` is in "select" mode, then it's
  automatically placed inside brackets.

  .. code-block:: php

      $query->_consume('first_name');  // `first_name`
      $query->_consume($other_query);  // will merge parameters and return string

.. php:method:: _escape($sql_code)

  Surrounds `$sql code` with :php:attr:`$escapeChar`.
  If escapeChar is `null` will do nothing.

  Will also do nothing if it finds "*", "." or "(" character in `$sql_code`.

  .. code-block:: php

      $query->_escape('first_name');  // `first_name`
      $query->_escape('first.name');  // first.name
      $query->_escape('(2+2)');       // (2+2)
      $query->_escape('*');           // *

.. php:method:: _param($value)

    Converts value into parameter and returns reference. Used only during
    query rendering. Consider using :php:meth:`_consume()` instead, which
    will also handle nested expressions properly.




.. _properties:

Properties
==========

.. php:attr:: template

    Template which is used when rendering.
    You can set this with either `new Expression("show tables")`
    or `new Expression(["show tables"])`
    or `new Expression(["template" => "show tables"])`.

.. php:attr:: connection

    PDO connection object or any other DB connection object.

.. php:attr:: escapeChar

    Field and table names are escaped using escapeChar which by default is: *`*.

.. php:attr:: paramBase

    Normally parameters are named :a, :b, :c. You can specify a different
    param base such as :param_00 and it will be automatically increased
    into :param_01 etc.

.. php:attr:: params

    This public property will contain the actual values of all the parameters. When
    multiple queries are merged together, their parameters are interlinked.
