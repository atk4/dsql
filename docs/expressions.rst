.. _expr:

===========
Expressions
===========

Query class implements a flexible way for you to define any custom expression
then execute it as-is or as a part of another query or expression. Here are
some examples how expressions can be used:

 - Sub-Queries
 - SQL functions, e.g. IF, CASE
 - nested AND / OR clauses
 - non-traditional constructions , UNIONS or SELECT INTO

Basically - Expression will be able to cover functionality that your unique
database vendor might support.


Creating Expression
===================

.. code-block:: php

    use atk4\dsql;

    $expr = new dsql\Expression("NOW()");

    $query -> where('time', '>', $expr);

There is also a shorter way to create an expression:

    $query -> where('time', '>', $query->expr('NOW()'));
