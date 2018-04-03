
DSQL is a composable SQL query builder. You can write multi-vendor queries in PHP profiting from
better security, clean syntax and avoid human errors.


## Hold on! Why yet another query builder?

Obviously because existing ones are not good enough. DSQL tries to do things differently:

1. Composability. Unlike other libraries, we render queries recursively allowing many levels of sub-selects.
2. Small footprint. We don't duplicate query code for all vendors, instead we use clever templating system.
3. Extensibility. We have 3 different ways to extend DSQL as well as 3rd party vendor driver support.
4. **Any Query** - any query with any complexity can be expressed through DSQL.
5. Almost no dependencies. Use DSQL in any PHP application or framework.
6. NoSQL support. In addition to supporting PDO, DSQL can be extended to deal with SQL-compatible NoSQL servers.

[See our "Awesome Queries" gallery](https://github.com/atk4/dsql/wiki/Awesome-Queries)


## DSQL Is Stable!

DSQL has been in production since 2006, initially included in [AModules2](https://sourceforge.net/projects/amodules3/) and later [Agile Toolkit](https://github.com/atk4/atk4/blob/release-4.0.1/lib/DBlite/dsql.php). We simply forked it and cleaned it up for you:

[![Gitter](https://img.shields.io/gitter/room/atk4/data.svg?maxAge=2592000)](https://gitter.im/atk4/dataset?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Documentation Status](https://readthedocs.org/projects/dsql/badge/?version=latest)](http://dsql.readthedocs.io/en/latest/?badge=latest)
[![Build Status](https://travis-ci.org/atk4/dsql.png?branch=develop)](https://travis-ci.org/atk4/dsql)
[![Code Climate](https://codeclimate.com/github/atk4/dsql/badges/gpa.svg)](https://codeclimate.com/github/atk4/dsql)
[![Test Coverage](https://codeclimate.com/github/atk4/dsql/badges/coverage.svg)](https://codeclimate.com/github/atk4/dsql/coverage)
[![GitHub release](https://img.shields.io/github/release/atk4/dsql.svg)](CHANGELOG.md)

## DSQL Is Simple and Powerful

``` php
$query = new atk4\dsql\Query();
$query  ->table('employees')
        ->where('birth_date','1961-05-02')
        ->field('count(*)')
        ;
echo "Employees born on May 2, 1961: ".$query->getOne();
```

If the basic query is not fun, how about more complex one?

``` php
// Establish a query looking for a maximum salary
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

``` sql
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

## DSQL is part of Agile Data

Building SQL queries might be fun, but why not take it to the next level?

### Domain Model

[Agile Data](https://github.com/atk4/data) is my other project, which implements Domain Model
Persistence on top of DSQL. You still maintain control over your queries while also benefiting
from database abstraction.

Next example uses Agile Data's method "[action](http://agile-data.readthedocs.io/en/develop/quickstart.html?highlight=action#actions)()"
to pre-populate DSQL object:

``` php
$m = new Client($db);
echo $m->addCondition('vip', true)
  ->ref('Order')
  ->ref('Line')
  ->action('fx', ['sum', 'total'])
  ->getDebugQuery(true);
```

```sql
select sum(`price`*`qty`) from `order_line` `O_L` where `order_id` in (
  select `id` from `order` `O` where `client_id` in (
    select `id` from `client` where `vip` = :a
  )
)
```

### User Inerface

[Agile UI](https://github.com/atk4/ui) is my other project that focuses on data visualization.

![image](https://github.com/atk4/ui/raw/develop/docs/images/grid.png)

If you wonder what's the most efficient way to display table like that on your page, with
Agile UI, Agile Data and DSQL you can do it in **less than 10 lines**:

``` php
require 'vendor/autoload.php';

$db = new \atk4\data\Persistence_SQL('mysql:dbname=atkui;host=localhost','root','root');

$app = new \atk4\ui\App('My First App');
$app->initLayout('Centered');

$g = $layout->add(new \atk4\ui\Grid());
$g->setModel(new Employee($db), false);
```

## Limitations of DSQL

Our team intentionally keeps DSQL simple. The following features are deliberately excluded:

- no knowledge of your database schema (see https://github.com/atk4/schema).
- no reliance on any usage pattern in your database or presence of specific tables.
- no decision making based on supplied data values.
- no active record or object relational mapping

If you need features above, I strongly advise you to look into [Agile Data](https://github.com/atk4/data).

## Documentation cheat-sheet

DSQL has extensive documentation at http://dsql.readthedocs.org, but below we have linked some
of the frequent topics:


- querying data from [table()](http://dsql.readthedocs.org/en/latest/queries.html#modifying-your-query) or sub-select with [join()](http://dsql.readthedocs.org/en/develop/queries.html#joining-with-other-tables), [where()](http://dsql.readthedocs.io/en/develop/queries.html?highlight=delete#Query::where), [order()](http://dsql.readthedocs.io/en/develop/queries.html?highlight=order#ordering-result-set), [group()](http://dsql.readthedocs.org/en/develop/queries.html#grouping-results-by-field), [limit()](http://dsql.readthedocs.io/en/develop/queries.html?highlight=limit#limiting-result-set), [having()](http://dsql.readthedocs.io/en/develop/queries.html?highlight=having#Query::having) and [option()](http://dsql.readthedocs.io/en/develop/queries.html?highlight=option#Query::option)
- [update](http://dsql.readthedocs.io/en/develop/queries.html?highlight=update#Query::update)/[replace](http://dsql.readthedocs.io/en/develop/queries.html?highlight=replace#Query::replace) single or multiple records with [set()](http://dsql.readthedocs.io/en/develop/queries.html?highlight=set#set-value-to-a-field), [where()](http://dsql.readthedocs.io/en/develop/queries.html?highlight=delete#Query::where) and [option()](http://dsql.readthedocs.io/en/develop/queries.html?highlight=option#Query::option)
- [insert](http://dsql.readthedocs.io/en/develop/queries.html?highlight=insert#Query::insert) one or multiple records with [set()](http://dsql.readthedocs.io/en/develop/queries.html?highlight=set#set-value-to-a-field) or setAll() and [option()](http://dsql.readthedocs.io/en/develop/queries.html?highlight=option#Query::option)
- [delete](http://dsql.readthedocs.io/en/develop/queries.html?highlight=delete#Query::delete) records with [where()](http://dsql.readthedocs.io/en/develop/queries.html?highlight=delete#Query::where)
- [iterate](http://dsql.readthedocs.org/en/latest/quickstart.html#fetching-result) through [result-set](http://dsql.readthedocs.io/en/develop/results.html#results) or [get()](http://dsql.readthedocs.io/en/develop/expressions.html?highlight=get#Expression::get) all data
- supporting [sub-queries](http://dsql.readthedocs.org/en/latest/queries.html#using-query-as-expression) and [expressions](http://dsql.readthedocs.org/en/latest/expressions.html#expressions) anywhere


## License and Thanks

The code of DSQL is available under MIT license with community and
[commercial support](http://www.agiletoolkit.org/enterprise) available.

Our team wishes to thanks [Reddit PHP community](https://www.reddit.com/r/PHP/)
for feedback and help in creation of this project.
