# dsql

DSQL is a dynamic SQL query builder. You can write multi-vendor queries in PHP profiting from better security, clean syntax and most importantly – sub-query support. With DSQL you stay in control of when queries are executed and what data is transmitted. DSQL is easily composable – build one query and use it as a part of other query.

Goals of DSQL are:

 - simple and consise syntax
 - consistently scalable (e.g. 5 levels of sub-queries, 10 with joins and 15 parameters? no problem)
 - "One Query" paradigm
 - support for PDO vendors as well as NoSQL databases (with query language smilar to SQL)
 - small code footprint (over 50% less than competing frameworks)
 - free, licensed under MIT
 - no dependencies
 - follows design paradigms:
     - "PHP the Agile way"
     - "[Functional ORM](https://github.com/atk4/dsql/wiki/Functional-ORM)"
     - "[Open to extend](https://github.com/atk4/dsql/wiki/Open-to-Extend)"
     - "[Vendor Transparency](https://github.com/atk4/dsql/wiki/Vendor-Transparency)"

[![Build Status](https://travis-ci.org/atk4/dsql.png?branch=develop)](https://travis-ci.org/atk4/dsql)
[![Code Climate](https://codeclimate.com/github/atk4/dsql/badges/gpa.svg)](https://codeclimate.com/github/atk4/dsql)
[![Test Coverage](https://codeclimate.com/github/atk4/dsql/badges/coverage.svg)](https://codeclimate.com/github/atk4/dsql/coverage)
[![Issue Count](https://codeclimate.com/github/atk4/dsql/badges/issue_count.svg)](https://codeclimate.com/github/atk4/dsql)


## Simple Example

```
$query = new atk4\dsql\Query();
$query  ->table('employees')
        ->where('birth_date','1961-05-02')
        ->field('count(*)')
        ;
echo "Employees born on May 2, 1961: ".$query->getOne();
```

## DSQL is Part of Agile Toolkit

DSQL is a stand-alone and lightweight library with no dependencies and can be used in any PHP project,
big or small. 

![image](docs/files/agiletoolkit.png)

DSQL is also a part of [Agile Toolkit](http://agiletoolkit.org/) framework and works best with [Agile Models](https://github.com/atk4/models). Your project may benefit from a higher-level data abstraction layer, so be sure to look at the rest of the suite.

## Limitations of DSQL

Our team intentionally keeps DSQL simple. The following features are deliberatly excluded:

 - no knowledge of your database schema
 - no reliance on any ussage pattern in your database or presence of specific tables
 - no decision making based on supplied data values
 - no active record or object relational mapping
 
Read more on DSQL Restrictions


## Supported SQL features

Many NoSQL databases are re-introducing SQL support today even if it's a limited subset. DSQL is designed to work with those vendors and is therefore extremely extensive. The features that come out of the box include:

 - querying data from [table()](http://dsql.readthedocs.org/en/latest/queries.html#modifying-your-query) or sub-select with [join()](http://dsql.readthedocs.org/en/develop/queries.html#joining-with-other-tables), where(), order(), [group()](http://dsql.readthedocs.org/en/develop/queries.html#grouping-results-by-field), limit(), having() and option() 
 - update/replace single or multiple records with set(), where() and option()
 - insert one or multiple records with set() or setAll() and option()
 - delete records with where()
 - [iterate](http://dsql.readthedocs.org/en/latest/quickstart.html#fetching-result) through result-set or get() all data
 - supporting [sub-queries](http://dsql.readthedocs.org/en/latest/queries.html#using-query-as-expression) and [expressions](http://dsql.readthedocs.org/en/latest/expressions.html#expressions) anywhere

## Sophisticated Example

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

[Read more or contribute more examples in DSQL-Primer project](https://github.com/atk4/dsql-primer).


## Documentation

http://dsql.readthedocs.org/
