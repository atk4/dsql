========
Overview
========

Purpose and design goals of DSQL
================================
When writing PHP software you have to rely on data storage engines such as SQL.
Recently more NoSQL vendors have also picked up SQL language (or some subset)
making SQL even more demanded.

If you have used SQL with PHP you would have used PDO in the past, but
designing all your SQL queries leaves a lot of space for error and inflexibility.

If you have to modify your SQL query depending on your application logic,
you also are forced working with query-"string".

DSQL represents your query as an object, allowing you to extensibly extend
various parts of your query with great efficiency and safety. DSQL takes care
of escaping parameters of your query, adding quotations and handling nested
expressions.

Goals of DSQL
-------------

- Provide Object-oriented library for designing SQL queries of any complexity.
- Offer simple and readable syntax
- Offer great integration for higher-level ORM libraries
- Support PDO out of the box
- Allow developers to extend DSQL into supporting non-relational SQL databases


DSQL by example
===============
The simplest way to explain DSQL is by example::


    use atk4\dsql;

    $query = new Query(['connection' => $pdo]);

    $query
        ->table('employees')
        ->where('birth_date', '1961-05-02')
        ->field('count(*)')
        ;

    $count = $query->getOne();

The above code will execute the following query:

.. code-block:: sql

    select count(*) from `salary` where `birth_date` = :a
        :a = "1961-05-02"

DSQL can also execute queries with multiple sub-queries, joins, expressions
grouping, ordering, unions as well as queries on result-set.

 - See :ref:`quickstart` if you would like to learn more about basics.
 - https://github.com/atk4/dsql-primer project contains various working
   examples of using DSQL with a real data-set.

DSQL in ORM
===========
Frankly, not many developers are keen to write queries today and prefer
use of ORM (Object Relational Mapper). DSQL is designed in such a way
so that a higher-level ORM library could use it in it's foundation.

Agile ORM (https://github.com/atk4/orm) is a Functional-ORM library for PHP,
that combines database mapping with query-building to create one of the most
powerful and flexible database manipalation libraries available today.

.. warning::
    Before you start using DSQL, look into Agile ORM. It may be a more appropriate
    library tool for your application that retains full power of DSQL.

    If you want to learn more about Agile ORM you need to understand how
    DSQL functions work, so continue reading.

Requirements
============

#. PHP 5.5 and above

.. _installation:

Installation
============

The recommended way to install DSQL is with
`Composer <http://getcomposer.org>`_. Composer is a dependency management tool
for PHP that allows you to declare the dependencies your project has and it
automatically installs them into your project.


.. code-block:: bash

    # Install Composer
    curl -sS https://getcomposer.org/installer | php
    php composer.phar require atk4/dsql

You can specify DSQL as a project or module dependency in composer.json:

.. code-block:: js

    {
      "require": {
         "atk4/dsql": "*"
      }
    }

After installing, you need to require Composer's autoloader in your PHP file::

    require 'vendor/autoload.php';

You can find out more on how to install Composer, configure autoloading, and
other best-practices for defining dependencies at
`getcomposer.org <http://getcomposer.org>`_.


Getting Started
===============

Continue reading :ref:`quickstart` where you will learn about basics of
DSQL and how to use it to it's full potential.

Contributing
============

Guidelines
----------

1. DSQL utilizes PSR-1, PSR-2, PSR-4, and PSR-7.
2. DSQL is meant to be lean and fast with very few dependencies. This means
   that not every feature request will be accepted.
3. All pull requests must include unit tests to ensure the change works as
   expected and to prevent regressions.
4. All pull requests must include relevant documentation or amend the existing
   documentaion if necessary.

Review and Approval
-------------------

1. All code must be submitted through pull requests on Github
2. Any of the project managers may Merge your pull request, but it must not be
   the same person who initiated the pull request.


Running the tests
-----------------

In order to contribute, you'll need to checkout the source from GitHub and
install DSQL dependencies using Composer:

.. code-block:: bash

    git clone https://github.com/atk4/dsql.git
    cd dsql && curl -s http://getcomposer.org/installer | php && ./composer.phar install --dev

DSQL is unit tested with PHPUnit. Run the tests using the Makefile:

.. code-block:: bash

    make tests

There are also vendor-specific test-scripts which will require you to
set database. To run them:

.. code-block:: bash

    # All unit tests including SQLite database engine tests
    phpunit --config phpunit.xml

    # MySQL database engine tests
    phpunit --config phpunit-mysql.xml

Look inside these the .xml files for further information and connection details.

License
=======

Licensed using the `MIT license <http://opensource.org/licenses/MIT>`_:

    Copyright (c) 2015 Michael Dowling <https://github.com/mtdowling>

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in
    all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
    THE SOFTWARE.


Reporting a security vulnerability
==================================

We want to ensure that DSQL is a secure library for everyone. If
you've discovered a security vulnerability in DSQL, we appreciate your help
in disclosing it to us in a `responsible manner <http://en.wikipedia.org/wiki/Responsible_disclosure>`_.

Publicly disclosing a vulnerability can put the entire community at risk. If
you've discovered a security concern, please email us at
security@agiletoolkit.org. We'll work with you to make sure that we understand the
scope of the issue, and that we fully address your concern. We consider
correspondence sent to security@agiletoolkit.org our highest priority, and work to
address any issues that arise as quickly as possible.

After a security vulnerability has been corrected, a security hotfix release will
be deployed as soon as possible.
