.. _expr:

.. php:class:: Expressions

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
in your ORM define "Expressionable" interface. Then you can also use it within
expressions:

.. code-block:: php

    $query -> where($query->expr(
        '[] between [] and []',
        [$time, $model->getElement('time_form'), $model->getElement('time_to')]
    ));

    // Produces: where :a between `time_from` and `time_to`

Another uses for expressions could be:

 - Sub-Queries
 - SQL functions, e.g. IF, CASE
 - nested AND / OR clauses
 - vendor-specific queries - "describe table"
 - non-traditional constructions , UNIONS or SELECT INTO

Properties, Arguments and Parameters
====================================

Be careful when using those similar terms as they refer to different things:

 - Properties refer to object properties, e.g. `$expr->template`
 - Arguments refer to template arguments, e.g. `select * from [table]`
 - Parameters refer to the way of passing user values within a query `where id=:a`

Creating Expression
===================

.. code-block:: php

    use atk4\dsql;

    $expr = new dsql\Expression("NOW()");

You can also use `dsql()` method to create expression, in which case
you do not have to define "use" block:

.. code-block:: php

    $query -> where('time', '>', $query->expr('NOW()'));

You can specify some of the expression properties through first argument
of the constructor:

.. code-block:: php

    $expr = new dsql\Expression(["NOW()", 'escapeChar'=>'*']);

Scroll down for full list of properties.

Query Template
==============

When you create a template the first argument is the template. It will
be stored in Query::$template property. The query string can contain
arguments in a square brackers:

 - `coalesce([], [])` is same as `coalesce([0], [1])`
 - `coalesce([one], [two])`

Arguments can be specified immediatelly through an array as a second argument
into constructor or you can specify parameters later

.. code-block:: php


    $expr = new dsql\Expression("coalesce([name], [surname])");
    $expr['name'] = $name;
    $expr['surname'] = $surname;

Nested expressions
==================

Expressions can be nested several times

.. code-block:: php

    $age = new dsql\Expression("coalesce([age], [default_age])");
    $age['age'] = new dsql\Expression("year(now()) - year(birth_date)");
    $age['default_age'] = 18;

    $query -> field($age, 'calculated_age');

    // select coalesce(year(now()) - year(birth_date), :a) `calculated_age` from `user`

When you specify one query to another query, it will automatically take care
of all user-defined parameters (such as 18 above) which will make sure
that SQL injections could not be introduced at any stage.

Expression Rendering
====================


.. php:method:: render()

    Converts expression into a string. Parameters are replaced with :a, :b, etc.


.. php:method:: getDebugQuery()

    Outputs debug-query by placing parameters into their respective places. The
    parameters will be escaped, but you should still avoid using generated
    qurey as it can potentially make you vulnerable to SQL injection.

    This method will use HTML tags to highlight parameters.

Properties
==========

.. php:attr:: template

    Template which is used when rendering. You can set this with either
    `new Expression("show tables")` or `new Expression(["show tables"])`
    or `new Expression(["template" => "show tables"])`.

.. php:attr:: escapeChar

    Field and table names are escaped using escapeChar which by default is: *`*.

.. php:attr:: paramBase

    Normally parameters are named :a, :b, :c. You can specify a different
    param base such as :param_00 and it will be automatically increased
    into :param_01 etc.

.. php:attr:: params

    This public property will contain the actual values of all the parameters. When
    multiple queries are merged together, their parameters are interlinked.
