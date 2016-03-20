# dsql

DSQL is a lightweight interface to dynamically creating SQL queries in PHP. Goals of DSQL are:

 - provide consistent API for use in higher level ORM implementation
 - offer consise and easy-to-understand syntax
 - integrate with PDO out of the box
 - support Big Data extensions for NoSQL databases such as Clusterpoint, DocumentDB and more


```
$query = new atk4\dsql\Query();
$query  ->table('employees')
        ->where('birth_date','1961-05-02')
        ->field('count(*)')
        ;
echo $query->getOne();
```

This framework can be used as a low-level layer for your ORM or Framework.

[![Build Status](https://travis-ci.org/atk4/dsql.png?branch=develop)](https://travis-ci.org/atk4/dsql)
[![Code Climate](https://codeclimate.com/github/atk4/dsql/badges/gpa.svg)](https://codeclimate.com/github/atk4/dsql)
[![Test Coverage](https://codeclimate.com/github/atk4/dsql/badges/coverage.svg)](https://codeclimate.com/github/atk4/dsql/coverage)
[![Issue Count](https://codeclimate.com/github/atk4/dsql/badges/issue_count.svg)](https://codeclimate.com/github/atk4/dsql)

## Supported SQL features

 - select, insert, update, delete and custom queries (like "show tables")
 - sub-selects, joins, expressions
 - vendor support, MySQL, MSSQL, PostgreSQL, SQLite
 - [planned] Semi-SQL support: DocumentDB, Clusterpoint
 - expression,

## Functional ORM

DSQL is designed to work very well with Agile ORM to offer you object-oriented
yet functional database query interface.

## Documentation

http://dsql.readthedocs.org/en/latest/
