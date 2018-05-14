# 1.2

In this verison we are adding support for Oracle and PostgreSQL. 

**Closed issues:**

- where\(\) should throw exception when passed incompatible object. [\#121](https://github.com/atk4/dsql/issues/121)

**Merged pull requests:**

- Feature/oracle support fixes [\#133](https://github.com/atk4/dsql/pull/133) ([DarkSide666](https://github.com/DarkSide666))
- Feature/connection oracle [\#132](https://github.com/atk4/dsql/pull/132) ([DarkSide666](https://github.com/DarkSide666))
- Fix: SQL does not guarantee the order of retreived rows unless explic… [\#131](https://github.com/atk4/dsql/pull/131) ([gartner](https://github.com/gartner))
- Create a driver for postgresql [\#130](https://github.com/atk4/dsql/pull/130) ([gartner](https://github.com/gartner))
- spellcheck and wrapping :\) [\#125](https://github.com/atk4/dsql/pull/125) ([DarkSide666](https://github.com/DarkSide666))
- Feature/oracle support [\#124](https://github.com/atk4/dsql/pull/124) ([romaninsh](https://github.com/romaninsh))
- Add verification for foreign objects inside where\(\) values [\#122](https://github.com/atk4/dsql/pull/122) ([romaninsh](https://github.com/romaninsh))
- Add support for URI-style DNS: mysql://user:pass@host/db [\#120](https://github.com/atk4/dsql/pull/120) ([romaninsh](https://github.com/romaninsh))

## 1.2.1

Resolve minor issues where expression class wasn't set correctly or some minor incompatibilities
appeared between SQL vendor syntaxes.

**Closed issues:**

- ATK Data, action\('fx'\) not working [\#136](https://github.com/atk4/dsql/issues/136)
- Passing a \PDO to connect\(\) assumes mysql [\#134](https://github.com/atk4/dsql/issues/134)
- \[epic\] Add support for PostgreSQL [\#129](https://github.com/atk4/dsql/issues/129)
- Use PDO::quote\(\) to escape values and identifiers [\#127](https://github.com/atk4/dsql/issues/127)
- Add PostgreSQL test-scripts [\#27](https://github.com/atk4/dsql/issues/27)

**Merged pull requests:**

- resolve \#136 [\#137](https://github.com/atk4/dsql/pull/137) ([romaninsh](https://github.com/romaninsh))
- Fix pdo [\#135](https://github.com/atk4/dsql/pull/135) ([gartner](https://github.com/gartner))

## 1.2.2

**Implemented enhancements:**

- Add support for CASE expression [\#74](https://github.com/atk4/dsql/issues/74)
- We should properly support tables and fields with spaces e.g. `my table`. [\#60](https://github.com/atk4/dsql/issues/60)

**Merged pull requests:**

- Implement normalizeDSN\(\) [\#139](https://github.com/atk4/dsql/pull/139) ([DarkSide666](https://github.com/DarkSide666))
- Implement CASE WHEN/THEN [\#138](https://github.com/atk4/dsql/pull/138) ([DarkSide666](https://github.com/DarkSide666))

## 1.2.3

Refactored, code cleanup

**Closed issues:**

- Put together a basic website or Release Notes [\#24](https://github.com/atk4/dsql/issues/24)

## 1.2.4

**Closed issues:**

- Model with properties: when iterating over several loaded Items, changes to property are done on all Items [\#318](https://github.com/atk4/data/issues/318)

**Merged pull requests:**

- validate if methods exist in persistence before calling them [\#324](https://github.com/atk4/data/pull/324) ([DarkSide666](https://github.com/DarkSide666))
- validate if persistence have action\(\) method [\#323](https://github.com/atk4/data/pull/323) ([DarkSide666](https://github.com/DarkSide666))
- if it's array then it's treated as defaults. [\#322](https://github.com/atk4/data/pull/322) ([DarkSide666](https://github.com/DarkSide666))
- Support LOB fields loading [\#321](https://github.com/atk4/data/pull/321) ([DarkSide666](https://github.com/DarkSide666))
- fix newInstance\(\) [\#320](https://github.com/atk4/data/pull/320) ([DarkSide666](https://github.com/DarkSide666))

# Older releases

## 1.1.5

- Migrate to Agile Core 1.3

## 1.1.4

- Migrate to Agile Core 1.2

## 1.1.3

- Added release script

## 1.1.2

 - Fix how getDebugQuery() works without sql-formatter
 - Fix Query::dsql will return instance of same class if you extend. #116

## 1.1.1

Bugfix and README.md update.

## 1.1.0

This version now officially supports PHP 7.1, as well as adding
optional dependency for SQL formatter (credit to
[https://github.com/jdorn/sql-formatter](jdorn)). When running
getDebugQuery(true) you'll be getting a well formatted and
justified SQL query.

If after upgrading to 1.1.* branch your application complains
about missing class "SqlFormatter", then your autoloader is
not configured correctly and as a work-around you would need to:

 > composer require jdorn/sql-formatter

## 1.0.10

Clean code, instead of relying on error suppression.

## 1.0.9

Exception class has been having the same functionality as the one
from atk4/core, so we have finally added dependency for atk4/core
and exndended their exception.

To celebrate this fact, we have added additional infromation
through params to all the places where we use Exceptions.


## 1.0.8

Calling `$dsql->set('foo', 4)->set('foo', 10);` now works differently.
It used to record "foo=10" in insert/update record, but now it will
simply add both statement. With MySQL at least the first value is
used.

Calling set() multiple times should behave similarly how adding
multiple conditions on the same field. It's not up to DSQL to
select which value to use.

* Field in set() can now be an expression
* Improved formatting a bit

## 1.0.7

* Fix test-suite bugs introduced in 1.0.6

## 1.0.6

* Fix problem when more than 28 parameters are specified
* Permit use of JOIN and table alias in MySQL update

## 1.0.5

* added transaction support
* made getDebugQuery() use false by default
* Style Improvement

## 1.0.4

* added getDebugQuery(false) which will output in TEXT instead of HTML
* bugfixes

## 1.0.3

* fix bug preventing from passing float as parameters

## 1.0.2

Minor cleanups and improvements to code coverage

* improve __toString() handling, will not attempt to catch exception

## 1.0.1

This is now our first stable release. It features clean-ups from 1.0.0:

* selectTemplate() is replaced with mode()
* upadted docs to use $c->query() instead of "new Query()" (better use pattern)
* added examples for having()
* added method reset()
* added method option()
* Expressionable now receives parent $expression as argument
* documented orExpr(), andExpr()
* documented template_* properties
* improved PSR compatibility
* escapeChar is dropped (too generic)
* introduced softEscape and made escape more strict
* improved and cleaned up documentation
* updated REDAME highlighting our USP

## 1.0.0-alpha2

Mainly clean up the code and added more tests.

## 1.0.0-alpha

Massive release that delivers all the major functionality we will need
for 1.0. We have now the full functionality implemented and a very
extensive test-suite consisting of almost 100 tests.

* Implemented all the basic functionality to start using DSQL.
* new Expression class. Define, build and render any SQL expression.
* refactored Query class. Added field(), where() and other methods.
* Added all documentation: http://dsql.readthedocs.org/
* implemented query rendering
* implemented query execution and fetching
* https://github.com/atk4/dsql/compare/0.1.1...release/1.0.0-alpha
* https://github.com/atk4/dsql/issues?utf8=✓&q=milestone%3A1.0-alpha+is%3Aclosed+is%3Aissue


## 0.1.1

* Added first sample Query.php class
* Added first sample TestQuery.php class
* Integrated with Travis for running tests
* Integrated with codeclimate for code analysis and code coverage
* Integrated badges into README
* Integrated http://dsql.readthedocs.org/

## 0.1.0

* Initial Release
* Bootstraped Documentation (sphinx-doc)
