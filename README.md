# DSQL

[![Join the chat at https://gitter.im/atk4/data](https://badges.gitter.im/atk4/data.svg)](https://gitter.im/atk4/data?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

DSQL is a composable SQL query builder. You can write multi-vendor queries in PHP profiting from better security, clean syntax and avoid human errors.


## Hold on! Why yet another query builder?

Obviously because existing ones are not good enough. DSQL tries to do things differently:

1. Composability. Unlike other libraries, we render queries recursively allowing many levels of sub-selects.
2. Small footprint. We don't duplicate query code for all vendors, instead we use clever templating system.
3. Extensibility. We have 3 different ways to extend DSQL as well as 3rd party vendor driver support.
4. **Any Query** - any query with any complexity can be expressed through DSQL.
5. Zero dependencies. Use DSQL in any PHP application or framework.
6. NoSQL support. In addition to supporting PDO, DSQL can be extended to deal with SQL-compatible NoSQL servers.

[See our "Awesome Queries" gallery](https://github.com/atk4/dsql/wiki/Awesome-Queries)


## DSQL Is Stable!

DSQL has been in production since 2006, initially included in [AModules2](https://sourceforge.net/projects/amodules3/) and later [Agile Toolkit](https://github.com/atk4/atk4/blob/release-4.0.1/lib/DBlite/dsql.php). We simply forked it and cleaned it up for you:

[![Build Status](https://travis-ci.org/atk4/dsql.png?branch=develop)](https://travis-ci.org/atk4/dsql)
[![Latest Stable](https://poser.pugx.org/atk4/dsql/version)](https://packagist.org/packages/atk4/dsql)
[![Code Climate](https://codeclimate.com/github/atk4/dsql/badges/gpa.svg)](https://codeclimate.com/github/atk4/dsql)
[![Test Coverage](https://codeclimate.com/github/atk4/dsql/badges/coverage.svg)](https://codeclimate.com/github/atk4/dsql/coverage)
[![Issue Count](https://codeclimate.com/github/atk4/dsql/badges/issue_count.svg)](https://codeclimate.com/github/atk4/dsql)
[![Total Downloads](https://poser.pugx.org/atk4/dsql/downloads.png)](https://packagist.org/packages/atk4/dsql)
![DailyDownloads](https://img.shields.io/packagist/dd/atk4/dsql.svg)
[![PHP 7 ready](http://php7ready.timesplinter.ch/atk4/dsql/badge.svg)](https://travis-ci.org/atk4/dsql)

## DSQL Is Simple and Powerful

```
$query = new atk4\dsql\Query();
$query  ->table('employees')
        ->where('birth_date','1961-05-02')
        ->field('count(*)')
        ;
echo "Employees born on May 2, 1961: ".$query->getOne();
```

If the basic query is not fun, how about more complex one?

```
// Estabish a query looking for a maximum salary
$salary = new atk4\dsql\Query(['connection'=>$pdo]);

// Create few expression objects
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
```

This builds and executes a single query that looks like this:

```
SELECT
    `emp_no`,
    max(salary) `max_salary`,
    TimeStampDiff(month, from_date, to_date) `months`
FROM
    `salary`
JOIN
    `employees` on `employees`.`emp_id` = `salary`.`emp_id`
WHERE
    `salary`.`emp_no` in (select `id` from `employees` where `birth_date` = :a)
GROUP BY `emp_no`
ORDER BY max_salary desc

:a = "1961-05-02"
```

## DSQL is part of Full Stack Web UI Framework

![image](docs/files/agiletoolkit.png)

DSQL is nibble enough to be used in your current project, but if you are looking to start a new web
project, why not look into [Agile Toolkit](http://agiletoolkit.org/)? It's a free to use full-stack
framework that will blow your mind form the same team who brought you DSQL.

Our team is also committed to fork [Agile Models](https://github.com/atk4/models) from Agile Toolkit,
so that you could replace your ORM with ours. (Planned summer 2016)

## Limitations of DSQL

Our team intentionally keeps DSQL simple. The following features are deliberatly excluded:

 - no knowledge of your database schema
 - no reliance on any usage pattern in your database or presence of specific tables
 - no decision making based on supplied data values
 - no active record or object relational mapping

If you need those features, then they are implemented by [Agile Models](https://github.com/atk4/models)
by extending DSQL.

## Documentation cheat-sheet

DSQL has extensives documentation at http://dsql.readthedocs.org, but below we have linked some of the
more demanded topics:

 - querying data from [table()](http://dsql.readthedocs.org/en/latest/queries.html#modifying-your-query) or sub-select with [join()](http://dsql.readthedocs.org/en/develop/queries.html#joining-with-other-tables), where(), order(), [group()](http://dsql.readthedocs.org/en/develop/queries.html#grouping-results-by-field), limit(), having() and option()
 - update/replace single or multiple records with set(), where() and option()
 - insert one or multiple records with set() or setAll() and option()
 - delete records with where()
 - [iterate](http://dsql.readthedocs.org/en/latest/quickstart.html#fetching-result) through result-set or get() all data
 - supporting [sub-queries](http://dsql.readthedocs.org/en/latest/queries.html#using-query-as-expression) and [expressions](http://dsql.readthedocs.org/en/latest/expressions.html#expressions) anywhere

