==========
Quickstart
==========

This page provides a quick introduction to DSQL and introductory examples.
If you have not already installed, Guzzle, head over to the :ref:`installation`
page.

.. _installation:

Basic Principles
===================

DSQL library consists of 3 parts:

Query
    Implements a class for building a query and managing arguments. Base
    class can be extended to achieve vendor-specific functionality.

Connection
    Represents a connection to the database. Connection can be a PDO or
    ReST depending on the vendor.

Execution
    Represents a result set returned in response to your query.

We will start by looking at the Query building.

.. code-block:: php

    $query = new atk4/dsql/Query();

Once you have a query object, you can add parameters by calling some of
it's methods:

.. code-block:: php

    $query
        ->table('employees')
        ->where('birth_date','1961-05-02')
        ->field('count(*)')
        ;
