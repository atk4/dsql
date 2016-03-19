.. _quickstart:

==========
Quickstart
==========

When working with DSQL you need to understand the following basic concepts:


Basic Concepts
==============

Expression
    Expression object, represents a part of a SQL query. It can be used to express
    advanced logic, which Query itself migth not support. Expressions take extra care
    of parameters, making sure they are either escaped or passed as parameters.

Query
    Object of a Query class is using for building and executing valid SQL statement
    as SELECT, INSERT, UPDATE, etc. After creating Query object you can call various
    methods to add "table", "where", "from" parts of your query.

Connection (optional)
    Represents a connection to the database. A standard PDO class can be
    used, but if database vendor does not support PDO (e.g. RestAPI)
    you can create a custom connection class.

ResultSet (optional)
    Represents a result set returned in response to your query. When
    used with PDO, PDOStatement will be used.

Getting Started
===============

We will start by looking at the Query building, becasue you do not need
a database to create a query::

    use atk4\dsql;

    $query = new dsql\Query(['connection' => $pdo]);

Once you have a query object, you can add parameters by calling some of
it's methods::

    $query
        ->table('employees')
        ->where('birth_date','1961-05-02')
        ->field('count(*)')
        ;

Finally you can get the data::

    $count = $query->getOne();

While DSQL is simple to use for basic queries, it also gives a huge
power and consistency when you are building complex queries. The
unique trait of DSQL builder, is its object-oriented design and 
incredible consistency when you need to **optionally** modify
your current query.

This is hugely benificial for frameworks and large applications, where
various classes need to interact and inject more code into your 
SQL query.

The next example might be a bit too complex for you, but still read
through and try to understand what each section does to your base
query::

    // Estabish a query looking for a maximum salary
    $salary = new dsql\Query(['connection'=>$pdo]);

    // Create few expressions objects
    $e_ms = $salary->expr('max(salary)');
    $e_df = $salary->expr('TimeStampDiff(month, from_date, to_date)');

    // Configure our basic query
    $salary
        ->table('salary')
        ->field(['emp_no', 'max_salary'=>$e_ms, 'months'=>$e_df])
        ->group('emp_no')
        ->order('-max_salary')

    // Define sub-query for employee "id" with certain birth-date
    $employees = $salary->dsql()
        ->table('employees')
        ->where('birth_date','1961-05-02')
        ->field('emp_no')
        ;

    // use sub-select to condition salaries
    $salary->where('emp_no', $employees);

    // Join with another table for more data
    $salary
        ->join('employees.emp_id','emp_id')
        ->field('employees.first_name');


    // finally, fetch result
    foreach ($salary as $row) {
        echo "Data: ".json_encode($row)."\n";
    }

The above query resulting code will look like this:

.. code-block:: sql

    select 
        `emp_no`,
        max(salary) `max_salary`,
        TimeStampDiff(month, from_date, to_date) `months`
    from 
        `salary`
    join
        `employees` on `employees`.`emp_id` = `salary`.`emp_id`
    where 
        `salary`.`emp_no` in (select `id` from `employees` where `birth_date` = :a)
    group by `emp_no`
    order by max_salary desc

    :a = "1961-05-02"

Using DSQL in higher level ORM libraries and frameworks allows them to
focus on defining the database logic, while DSQL can perform the heavy-lifting
of query building and execution.

Creating Objects and PDO
========================
DSQL class does not need database connection for most of it's work. Once
you create new instance of Expression or Query you can perform operation
and finally call :php:meth:`Expression::render()` to get the
final query string.

When used in application you would typically geerate queries with the
purpose of executing them, which makes it very useful to specify
"connection" to DSQL objects during initialization::

    $expr = new Expression('show tables', ['connection'=>$pdo]);
    $tables = $expr->getAll();

(You can also pass connection to the :php:meth:`Expression::execute()`)

To save you some time, you can re-use existing *connection* from
existing object, by calling :php:meth:`Expression::dsql()` and 
:php:meth:`Expression::expr()`. 

.. note::
    Even though code reads Expression::expr, you can call this method
    on any $query, because Query class extends Expression class and
    anything said about Expression also applies on Query.

In the above example, I have used those methods on multiple occassions::

    $e_ms = $salary->expr('max(salary)');
    $e_df = $salary->expr('TimeStampDiff(month, from_date, to_date)');

.. note::
    DSQL classes are mindful about your SQL vendor and it's quirks,
    so when you're building sub-queries with dsql(), you can avoid
    some nasty problems.


Query Buliding
==============
Calling methods such as :php:meth:`Query::table` or :php:meth:`Query::where`
affect part of the query you're making. To learn more about all the 
methods and their arguments, continue to :php:class:`Query` documentation.

Query class can be further extended and you can introduce new ways
to extend queries.

Query Mode
==========

When you create a new Query, it is going to be a *SELECT* query by default.
You can, however, perform other operations by callind :php:meth:`Query::update`,
:php:meth:`Query::delete` (etc). For more information see :ref:`query-modes`::

    $query->table('employee')->where('emp_no', 1234)->delete();

A good practice is to re-use your condition where possible.


Fetching Result
===============

When you are using default "select" mode for Query, there are several
ways how you can go over the resulting data-set.

DSQL does not implement any additional overheads or iterating, instead
it simply uses PDOStatement if you try to iterate over it::


    foreach ($q->table('employee')->where('dep_no',123) as $employee) {
        echo $employee['first_name']."\n";
    }

If you want to do more stuff to PDO before fetching data, you can use
:php:meth:`Expression::execute` directly which returns PDOStatement object
back to you.

When you expect only one row of results or just a single value you can use
:php:meth:`Expression::getRow` and :php:meth:`Expression::getOne`.

Finally - there is :php:meth:`Expression::get` which will give you array
with all of results, however it's alwasy a better idea to iterate over
results where possible instead of storing them all in an array.
