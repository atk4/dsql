========
Overview
========

Requirements
============

#. PHP 5.3
#. One of the supported SQL Vendors
#. RECOMMENDED: Use along with Agile ORM

.. _installation:

Installation
============

The recommended way to install DSQL is with
`Composer <http://getcomposer.org>`_. Composer is a dependency management tool
for PHP that allows you to declare the dependencies your project needs and
installs them into your project.


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

After installing, you need to require Composer's autoloader in your PHP file:

.. code-block:: php

    require 'vendor/autoload.php';

You can find out more on how to install Composer, configure autoloading, and
other best-practices for defining dependencies at `getcomposer.org <http://getcomposer.org>`_.


Testing your installation
=========================

If you are looking to perform a quick-test, use the following code:

.. code-block:: php

    $dsql = new atk4\dsql\DSQL\MySQL();
    echo $dsql
        ->table('user')
        ->where('foo',123)
        ->field('hello')
        ->getDebugQuery();

Continue to :ref:`quickstart` for further examples.

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
3.


Running the tests
-----------------

In order to contribute, you'll need to checkout the source from GitHub and
install DSQL dependencies using Composer:

.. code-block:: bash

    git clone https://github.com/atk4/dsql.git
    cd dsql && curl -s http://getcomposer.org/installer | php && ./composer.phar install --dev

DSQL is unit tested with PHPUnit. Run the tests using the Makefile:

.. code-block:: bash

    make test

.. note::

    In order to execute tests against a specific database verdor, you may need to
    set environment variable, e.g. DSN="mysql://root:root@127.0.0.1/employees"


License
=======

Licensed using the `MIT license <http://opensource.org/licenses/MIT>`_.

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
correspondence sent to security@guzzlephp.org our highest priority, and work to
address any issues that arise as quickly as possible.

After a security vulnerability has been corrected, a security hotfix release will
be deployed as soon as possible.
