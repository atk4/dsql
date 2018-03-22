<?php

namespace atk4\dsql\tests;

use atk4\dsql\Expression;
use atk4\dsql\Query;

/**
 * @coversDefaultClass \atk4\dsql\Query
 */
class QueryTest extends \atk4\core\PHPUnit_AgileTestCase
{
    public function q()
    {
        $args = func_get_args();
        switch (count($args)) {
            case 1:
                return new Query($args[0]);
            case 2:
                return new Query($args[0], $args[1]);
        }

        return new Query();
    }

    /**
     * Test constructor.
     *
     * @covers ::__construct
     */
    public function testConstruct()
    {
        // passing properties in constructor
        $this->assertEquals(
            '"q"',
            $this->callProtected($this->q(), '_escape', ['q'])
        );
    }

    /**
     * dsql() should return new Query object and inherit connection from it.
     *
     * @covers ::dsql
     */
    public function testDsql()
    {
        $q = $this->q(['connection' => new \stdClass()]);
        $this->assertEquals(true, $q->dsql()->connection instanceof \stdClass);
    }

    /**
     * field() should return $this Query for chaining.
     *
     * @covers ::field
     */
    public function testFieldReturnValue()
    {
        $q = $this->q();
        $this->assertEquals($q, $q->field('first_name'));
    }

    /**
     * Testing field - basic cases.
     *
     * @covers ::field
     * @covers ::_render_field
     */
    public function testFieldBasic()
    {
        $this->assertEquals(
            '"first_name"',
            $this->callProtected($this->q()->field('first_name'), '_render_field')
        );
        $this->assertEquals(
            '"first_name","last_name"',
            $this->callProtected($this->q()->field('first_name,last_name'), '_render_field')
        );
        $this->assertEquals(
            '"first_name","last_name"',
            $this->callProtected($this->q()->field('first_name')->field('last_name'), '_render_field')
        );
        $this->assertEquals(
            '"last_name"',
            $this->callProtected($this->q()->field('first_name')->reset('field')->field('last_name'), '_render_field')
        );
        $this->assertEquals(
            '*',
            $this->callProtected($this->q()->field('first_name')->reset('field'), '_render_field')
        );
        $this->assertEquals(
            '*',
            $this->callProtected($this->q()->field('first_name')->reset(), '_render_field')
        );
        $this->assertEquals(
            '"employee"."first_name"',
            $this->callProtected($this->q()->field('employee.first_name'), '_render_field')
        );
        $this->assertEquals(
            '"first_name" "name"',
            $this->callProtected($this->q()->field('first_name', 'name'), '_render_field')
        );
        $this->assertEquals(
            '"first_name" "name"',
            $this->callProtected($this->q()->field(['name' => 'first_name']), '_render_field')
        );
        $this->assertEquals(
            '"name"',
            $this->callProtected($this->q()->field(['name' => 'name']), '_render_field')
        );
        $this->assertEquals(
            '"employee"."first_name" "name"',
            $this->callProtected($this->q()->field(['name' => 'employee.first_name']), '_render_field')
        );
        $this->assertEquals(
            '*',
            $this->callProtected($this->q()->field('*'), '_render_field')
        );
        $this->assertEquals(
            '"employee"."first_name"',
            $this->callProtected($this->q()->field('employee.first_name'), '_render_field')
        );
    }

    /**
     * Testing field - defaultField.
     *
     * @covers ::field
     * @covers ::_render_field
     */
    public function testFieldDefaultField()
    {
        // default defaultField
        $this->assertEquals(
            '*',
            $this->callProtected($this->q(), '_render_field')
        );
        // defaultField as custom string - not escaped
        $this->assertEquals(
            'id',
            $this->callProtected($this->q(['defaultField' => 'id']), '_render_field')
        );
        // defaultField as custom string with dot - not escaped
        $this->assertEquals(
            'all.values',
            $this->callProtected($this->q(['defaultField' => 'all.values']), '_render_field')
        );
        // defaultField as Expression object - not escaped
        $this->assertEquals(
            'values()',
            $this->callProtected($this->q(['defaultField' => new Expression('values()')]), '_render_field')
        );
    }

    /**
     * Testing field - basic cases.
     *
     * @covers ::field
     * @covers ::_render_field
     */
    public function testFieldExpression()
    {
        $this->assertEquals(
            '"name"',
            $this->q('[field]')->field('name')->render()
        );
        $this->assertEquals(
            '"first name"',
            $this->q('[field]')->field('first name')->render()
        );
        $this->assertEquals(
            '"first"."name"',
            $this->q('[field]')->field('first.name')->render()
        );
        $this->assertEquals(
            'now()',
            $this->q('[field]')->field('now()')->render()
        );
        $this->assertEquals(
            'now()',
            $this->q('[field]')->field(new Expression('now()'))->render()
        );
        // Usage of field aliases
        $this->assertEquals(
            'now() "time"',
            $this->q('[field]')->field('now()', 'time')->render()
        );
        $this->assertEquals(// alias can be passed as 2nd argument
            'now() "time"',
            $this->q('[field]')->field(new Expression('now()'), 'time')->render()
        );
        $this->assertEquals(// alias can be passed as 3nd argument
            'now() "time"',
            $this->q('[field]')->field(['time' => new Expression('now()')])->render()
        );
    }

    /**
     * Duplicate alias of field.
     *
     * @covers ::field
     * @covers ::_set_args
     * @expectedException Exception
     */
    public function testFieldException1()
    {
        $this->q()->field('name', 'a')->field('surname', 'a');
    }

    /**
     * There shouldn't be alias when passing fields as array.
     *
     * @covers ::field
     * @expectedException Exception
     */
    public function testFieldException2()
    {
        $this->q()->field(['name', 'surname'], 'a');
    }

    /**
     * There shouldn't be alias when passing multiple tables.
     *
     * @covers ::table
     * @expectedException Exception
     */
    public function testTableException1()
    {
        $this->q()->table('employee,jobs', 'u');
    }

    /**
     * There shouldn't be alias when passing multiple tables.
     *
     * @covers ::table
     * @expectedException Exception
     */
    public function testTableException2()
    {
        $this->q()->table(['employee', 'jobs'], 'u');
    }

    /**
     * Alias is NOT mandatory when pass table as Expression.
     *
     * @covers ::table
     */
    public function testTableException3()
    {
        $this->q()->table($this->q()->expr('test'));
    }

    /**
     * Alias is IS mandatory when pass table as Query.
     *
     * @covers ::table
     * @expectedException Exception
     */
    public function testTableException4()
    {
        $this->q()->table($this->q()->table('test'));
    }

    /**
     * Table aliases should be unique.
     *
     * @covers ::table
     * @covers ::_set_args
     * @expectedException Exception
     */
    public function testTableException5()
    {
        $this->q()
            ->table('foo', 'a')
            ->table('bar', 'a');
    }

    /**
     * Table aliases should be unique.
     *
     * @covers ::table
     * @covers ::_set_args
     * @expectedException Exception
     */
    public function testTableException6()
    {
        $this->q()
            ->table('foo', 'bar')
            ->table('bar');
    }

    /**
     * Table aliases should be unique.
     *
     * @covers ::table
     * @covers ::_set_args
     * @expectedException Exception
     */
    public function testTableException7()
    {
        $this->q()
            ->table('foo')
            ->table('foo');
    }

    /**
     * Table aliases should be unique.
     *
     * @covers ::table
     * @covers ::_set_args
     * @expectedException Exception
     */
    public function testTableException8()
    {
        $this->q()
            ->table($this->q()->table('test'), 'foo')
            ->table('foo');
    }

    /**
     * Table aliases should be unique.
     *
     * @covers ::table
     * @covers ::_set_args
     * @expectedException Exception
     */
    public function testTableException9()
    {
        $this->q()
            ->table('foo')
            ->table($this->q()->table('test'), 'foo');
    }

    /**
     * Table can't be set as sub-Query in Update query mode.
     *
     * @covers ::table
     * @expectedException Exception
     */
    public function testTableException10()
    {
        $this->q()
            ->mode('update')
            ->table($this->q()->table('test'), 'foo')
            ->field('name')->set('name', 1)
            ->render();
    }

    /**
     * Table can't be set as sub-Query in Insert query mode.
     *
     * @covers ::table
     * @expectedException Exception
     */
    public function testTableException11()
    {
        $this->q()
            ->mode('insert')
            ->table($this->q()->table('test'), 'foo')
            ->field('name')->set('name', 1)
            ->render();
    }

    /**
     * Requesting non-existant query mode should throw exception.
     *
     * @covers ::mode
     * @expectedException Exception
     */
    public function testModeException1()
    {
        $this->q()->mode('non_existant_mode');
    }

    /**
     * table() should return $this Query for chaining.
     *
     * @covers ::table
     */
    public function testTableReturnValue()
    {
        $q = $this->q();
        $this->assertEquals($q, $q->table('employee'));
    }

    /**
     * @covers ::table
     * @covers ::_render_table
     * @covers ::_render_table_noalias
     */
    public function testTableRender1()
    {
        // no table defined
        $this->assertEquals(
            'select now()',
            $this->q()
                ->field(new Expression('now()'))
                ->render()
        );

        // one table
        $this->assertEquals(
            'select "name" from "employee"',
            $this->q()
                ->field('name')->table('employee')
                ->render()
        );

        $this->assertEquals(
            'select "na#me" from "employee"',
            $this->q()
                ->field('"na#me"')->table('employee')
                ->render()
        );
        $this->assertEquals(
            'select "na""me" from "employee"',
            $this->q()
                ->field(new Expression('{}', ['na"me']))->table('employee')
                ->render()
        );
        $this->assertEquals(
            'select "жук" from "employee"',
            $this->q()
                ->field(new Expression('{}', ['жук']))->table('employee')
                ->render()
        );
        $this->assertEquals(
            'select "this is 💩" from "employee"',
            $this->q()
                ->field(new Expression('{}', ['this is 💩']))->table('employee')
                ->render()
        );

        $this->assertEquals(
            'select "name" from "employee" "e"',
            $this->q()
                ->field('name')->table('employee', 'e')
                ->render()
        );
        $this->assertEquals(
            'select * from "employee" "e"',
            $this->q()
                ->table('employee', 'e')
                ->render()
        );

        // multiple tables
        $this->assertEquals(
            'select "employee"."name" from "employee","jobs"',
            $this->q()
                ->field('employee.name')->table('employee')->table('jobs')
                ->render()
        );
        $this->assertEquals(
            'select "name" from "employee","jobs"',
            $this->q()
                ->field('name')->table('employee,jobs')
                ->render()
        );
        $this->assertEquals(
            'select "name" from "employee","jobs"',
            $this->q()
                ->field('name')->table('  employee ,   jobs  ')
                ->render()
        );
        $this->assertEquals(
            'select "name" from "employee","jobs"',
            $this->q()
                ->field('name')->table(['employee', 'jobs'])
                ->render()
        );
        $this->assertEquals(
            'select "name" from "employee","jobs"',
            $this->q()
                ->field('name')->table(['employee  ', '  jobs'])
                ->render()
        );

        // multiple tables with aliases
        $this->assertEquals(
            'select "name" from "employee","jobs" "j"',
            $this->q()
                ->field('name')->table(['employee', 'j' => 'jobs'])
                ->render()
        );
        $this->assertEquals(
            'select "name" from "employee" "e","jobs" "j"',
            $this->q()
                ->field('name')->table(['e' => 'employee', 'j' => 'jobs'])
                ->render()
        );
        // testing _render_table_noalias, shouldn't render table alias 'emp'
        $this->assertEquals(
            'insert into "employee" ("name") values (:a)',
            $this->q()
                ->field('name')->table('employee', 'emp')->set('name', 1)
                ->mode('insert')
                ->render()
        );
        $this->assertEquals(
            'update "employee" set "name"=:a',
            $this->q()
                ->field('name')->table('employee', 'emp')->set('name', 1)
                ->mode('update')
                ->render()
        );
    }

    /**
     * @covers ::table
     * @covers ::_render_table
     */
    public function testTableRender2()
    {
        // pass table as expression or query
        $q = $this->q()->table('employee');

        $this->assertEquals(
            'select "name" from (select * from "employee") "e"',
            $this->q()
                ->field('name')->table($q, 'e')
                ->render()
        );

        $this->assertEquals(
            'select "name" from "myt""able"',
            $this->q()
                ->field('name')->table(new Expression('{}', ['myt"able']))
                ->render()
        );

        // test with multiple sub-queries as tables
        $q1 = $this->q()->table('employee');
        $q2 = $this->q()->table('customer');

        $this->assertEquals(
            //this way it would be more correct: 'select "e"."name","c"."name" from (select * from "employee") "e",(select * from "customer") "c" where "e"."last_name" = "c"."last_name"',
            'select "e"."name","c"."name" from (select * from "employee") "e",(select * from "customer") "c" where "e"."last_name" = c.last_name',
            $this->q()
                ->field('e.name')
                ->field('c.name')
                ->table($q1, 'e')
                ->table($q2, 'c')
                ->where('e.last_name', $this->q()->expr('c.last_name'))
                ->render()
        );
    }

    /**
     * @covers ::render
     * @covers \atk4\dsql\Expression::_consume
     * @covers \atk4\dsql\Expression::render
     */
    public function testBasicRenderSubquery()
    {
        $age = new Expression('coalesce([age], [default_age])');
        $age['age'] = new Expression('year(now()) - year(birth_date)');
        $age['default_age'] = 18;

        $q = $this->q()->table('user')->field($age, 'calculated_age');

        $this->assertEquals(
            'select coalesce(year(now()) - year(birth_date), :a) "calculated_age" from "user"',
            $q->render()
        );
    }

    /**
     * @covers atk4\dsql\Expression::getDebugQuery
     */
    public function testgetDebugQuery()
    {
        $age = new Expression('coalesce([age], [default_age], [foo], [bar])');
        $age['age'] = new Expression('year(now()) - year(birth_date)');
        $age['default_age'] = 18;
        $age['foo'] = 'foo';
        $age['bar'] = null;

        $q = $this->q()->table('user')->field($age, 'calculated_age');

        $this->assertEquals(
            "select coalesce(year(now()) - year(birth_date), 18, 'foo', NULL) \"calculated_age\" from \"user\"",
            strip_tags($q->getDebugQuery())
        );
    }

    /**
     * @requires PHP 5.6
     * @covers ::__debugInfo
     */
    public function testVarDump()
    {
        $this->expectOutputRegex('/.*select \* from "user".*/');
        var_dump($this->q()->table('user'));
    }

    /**
     * @requires PHP 5.6
     * @covers ::__debugInfo
     */
    public function testVarDump2()
    {
        $this->expectOutputRegex('/.*Expression could not render tag.*/');
        var_dump(new Expression('Hello [world]'));
    }

    /**
     * @requires PHP 5.6
     * @covers ::__debugInfo
     */
    public function testVarDump3()
    {
        $this->expectOutputRegex('/.*Hello \'php\'.*/');
        var_dump(new Expression('Hello [world]', ['world' => 'php']));
    }

    /**
     * @requires PHP 5.6
     * @covers ::__debugInfo
     */
    public function testVarDump4()
    {
        $this->expectOutputRegex('/.*Table cannot be Query.*/');
        // should throw exception "Table cannot be Query in UPDATE, INSERT etc. query modes"
        var_dump(
            $this->q()
                ->mode('update')
                ->table($this->q()->table('test'), 'foo')
        );
    }

    /**
     * @covers ::field
     * @covers ::_render_field
     * @covers ::table
     * @covers ::_render_table
     * @covers ::render
     */
    public function testUnionQuery()
    {
        // 1st query
        $q1 = $this->q()
            ->table('sales')
            ->field('date')
            ->field('amount', 'debit')
            ->field($this->q()->expr('0'), 'credit') // simply 0
;
        $this->assertEquals(
            'select "date","amount" "debit",0 "credit" from "sales"',
            $q1->render()
        );

        // 2nd query
        $q2 = $this->q()
            ->table('purchases')
            ->field('date')
            ->field($this->q()->expr('0'), 'debit') // simply 0
            ->field('amount', 'credit');
        $this->assertEquals(
            'select "date",0 "debit","amount" "credit" from "purchases"',
            $q2->render()
        );

        // $q1 union $q2
        $u = new Expression('[] union []', [$q1, $q2]);
        $this->assertEquals(
            '(select "date","amount" "debit",0 "credit" from "sales") union (select "date",0 "debit","amount" "credit" from "purchases")',
            $u->render()
        );

        // SELECT date,debit,credit FROM ($q1 union $q2)
        $q = $this->q()
            ->field('date,debit,credit')
            ->table($u, 'derrivedTable');
        /*
         * @see https://github.com/atk4/dsql/issues/33
         * @see https://github.com/atk4/dsql/issues/34
         */
        /*
        $this->assertEquals(
            'select "date","debit","credit" from ((select "date","amount" "debit",0 "credit" from "sales") union (select "date",0 "debit","amount" "credit" from "purchases")) "derrivedTable"',
            $q->render()
        );
        */
    }

    /**
     * where() should return $this Query for chaining.
     *
     * @covers ::where
     */
    public function testWhereReturnValue()
    {
        $q = $this->q();
        $this->assertEquals($q, $q->where('id', 1));
    }

    /**
     * having() should return $this Query for chaining.
     *
     * @covers ::field
     */
    public function testHavingReturnValue()
    {
        $q = $this->q();
        $this->assertEquals($q, $q->having('id', 1));
    }

    /**
     * Basic where() tests.
     *
     * @covers ::where
     * @covers ::_render_where
     * @covers ::__render_where
     */
    public function testWhereBasic()
    {
        // one parameter as a string - treat as expression
        $this->assertEquals(
            'where now()',
            $this->q('[where]')->where('now()')->render()
        );
        $this->assertEquals(
            'where foo >=    bar',
            $this->q('[where]')->where('foo >=    bar')->render()
        );

        // two parameters - field, value
        $this->assertEquals(
            'where "id" = :a',
            $this->q('[where]')->where('id', 1)->render()
        );
        $this->assertEquals(
            'where "user"."id" = :a',
            $this->q('[where]')->where('user.id', 1)->render()
        );
        $this->assertEquals(
            'where "db"."user"."id" = :a',
            $this->q('[where]')->where('db.user.id', 1)->render()
        );
        $this->assertEquals(
            'where "id" is :a',
            $this->q('[where]')->where('id', null)->render()
        );
        $this->assertEquals(
            'where "id" is :a',
            $this->q('[where]')->where('id', null)->render()
        );

        // three parameters - field, condition, value
        $this->assertEquals(
            'where "id" > :a',
            $this->q('[where]')->where('id', '>', 1)->render()
        );
        $this->assertEquals(
            'where "id" < :a',
            $this->q('[where]')->where('id', '<', 1)->render()
        );
        $this->assertEquals(
            'where "id" = :a',
            $this->q('[where]')->where('id', '=', 1)->render()
        );
        $this->assertEquals(
            'where "id" in (:a,:b)',
            $this->q('[where]')->where('id', '=', [1, 2])->render()
        );
        $this->assertEquals(
            'where "id" in (:a,:b)',
            $this->q('[where]')->where('id', [1, 2])->render()
        );
        $this->assertEquals(
            'where "id" in (select * from "user")',
            $this->q('[where]')->where('id', $this->q()->table('user'))->render()
        );

        // two parameters - more_than_just_a_field, value
        $this->assertEquals(
            'where "id" = :a',
            $this->q('[where]')->where('id=', 1)->render()
        );
        $this->assertEquals(
            'where "id" != :a',
            $this->q('[where]')->where('id!=', 1)->render()
        );
        $this->assertEquals(
            'where "id" <> :a',
            $this->q('[where]')->where('id<>', 1)->render()
        );

        // field name with special symbols - not escape
        $this->assertEquals(
            'where now() = :a',
            $this->q('[where]')->where('now()', 1)->render()
        );

        // field name as expression
        $this->assertEquals(
            'where now = :a',
            $this->q('[where]')->where(new Expression('now'), 1)->render()
        );

        // more than one where condition - join with AND keyword
        $this->assertEquals(
            'where "a" = :a and "b" is :b',
            $this->q('[where]')->where('a', 1)->where('b', null)->render()
        );
    }

    /**
     * Verify that passing garbage to where throw exception.
     *
     * @covers ::order
     * @expectedException Exception
     */
    public function testWhereIncompatibleObject1()
    {
        $this->q('[where]')->where('a', new \DateTime())->render();
    }

    /**
     * Verify that passing garbage to where throw exception.
     *
     * @covers ::order
     * @expectedException Exception
     */
    public function testWhereIncompatibleObject2()
    {
        $this->q('[where]')->where('a', new \DateTime());
    }

    /**
     * Verify that passing garbage to where throw exception.
     *
     * @covers ::order
     * @expectedException Exception
     */
    public function testWhereIncompatibleObject3()
    {
        $this->q('[where]')->where('a', '<>', new \DateTime());
    }

    /**
     * Testing where() with special values - null, array, like.
     *
     * @covers ::where
     * @covers ::_render_where
     * @covers ::__render_where
     */
    public function testWhereSpecialValues()
    {
        // in | not in
        $this->assertEquals(
            'where "id" in (:a,:b)',
            $this->q('[where]')->where('id', 'in', [1, 2])->render()
        );
        $this->assertEquals(
            'where "id" not in (:a,:b)',
            $this->q('[where]')->where('id', 'not in', [1, 2])->render()
        );
        $this->assertEquals(
            'where "id" not in (:a,:b)',
            $this->q('[where]')->where('id', 'not', [1, 2])->render()
        );
        $this->assertEquals(
            'where "id" in (:a,:b)',
            $this->q('[where]')->where('id', '=', [1, 2])->render()
        );
        $this->assertEquals(
            'where "id" not in (:a,:b)',
            $this->q('[where]')->where('id', '<>', [1, 2])->render()
        );
        $this->assertEquals(
            'where "id" not in (:a,:b)',
            $this->q('[where]')->where('id', '!=', [1, 2])->render()
        );
        // pass array as CSV
        $this->assertEquals(
            'where "id" in (:a,:b)',
            $this->q('[where]')->where('id', 'in', '1,2')->render()
        );
        $this->assertEquals(
            'where "id" not in (:a,:b)',
            $this->q('[where]')->where('id', 'not in', '1,    2')->render()
        );
        $this->assertEquals(
            'where "id" not in (:a,:b)',
            $this->q('[where]')->where('id', 'not', '1,2')->render()
        );

        // is | is not
        $this->assertEquals(
            'where "id" is :a',
            $this->q('[where]')->where('id', 'is', null)->render()
        );
        $this->assertEquals(
            'where "id" is not :a',
            $this->q('[where]')->where('id', 'is not', null)->render()
        );
        $this->assertEquals(
            'where "id" is not :a',
            $this->q('[where]')->where('id', 'not', null)->render()
        );
        $this->assertEquals(
            'where "id" is :a',
            $this->q('[where]')->where('id', '=', null)->render()
        );
        $this->assertEquals(
            'where "id" is not :a',
            $this->q('[where]')->where('id', '<>', null)->render()
        );
        $this->assertEquals(
            'where "id" is not :a',
            $this->q('[where]')->where('id', '!=', null)->render()
        );

        // like | not like
        $this->assertEquals(
            'where "name" like :a',
            $this->q('[where]')->where('name', 'like', 'foo')->render()
        );
        $this->assertEquals(
            'where "name" not like :a',
            $this->q('[where]')->where('name', 'not like', 'foo')->render()
        );

        // two parameters - more_than_just_a_field, value
        // is | is not
        $this->assertEquals(
            'where "id" is :a',
            $this->q('[where]')->where('id=', null)->render()
        );
        $this->assertEquals(
            'where "id" is not :a',
            $this->q('[where]')->where('id!=', null)->render()
        );
        $this->assertEquals(
            'where "id" is not :a',
            $this->q('[where]')->where('id<>', null)->render()
        );

        // in | not in
        $this->assertEquals(
            'where "id" in (:a,:b)',
            $this->q('[where]')->where('id=', [1, 2])->render()
        );
        $this->assertEquals(
            'where "id" not in (:a,:b)',
            $this->q('[where]')->where('id!=', [1, 2])->render()
        );
        $this->assertEquals(
            'where "id" not in (:a,:b)',
            $this->q('[where]')->where('id<>', [1, 2])->render()
        );
    }

    /**
     * Having basically is the same as where, so we can relax and trouhly test where() instead.
     *
     * @covers ::having
     * @covers ::_render_having
     */
    public function testBasicHaving()
    {
        $this->assertEquals(
            'having "id" = :a',
            $this->q('[having]')->having('id', 1)->render()
        );
        $this->assertEquals(
            'having "id" > :a',
            $this->q('[having]')->having('id', '>', 1)->render()
        );
        $this->assertEquals(
            'where "id" = :a having "id" > :b',
            $this->q('[where][having]')->where('id', 1)->having('id>', 1)->render()
        );
    }

    /**
     * Test Limit.
     *
     * @covers ::limit
     * @covers ::_render_limit
     */
    public function testLimit()
    {
        $this->assertEquals(
            'limit 0, 100',
            $this->q('[limit]')->limit(100)->render()
        );
        $this->assertEquals(
            'limit 200, 100',
            $this->q('[limit]')->limit(100, 200)->render()
        );
    }

    /**
     * Test Order.
     *
     * @covers ::order
     * @covers ::_render_order
     */
    public function testOrder()
    {
        $this->assertEquals(
            'order by "name"',
            $this->q('[order]')->order('name')->render()
        );
        $this->assertEquals(
            'order by "name", "surname"',
            $this->q('[order]')->order('name,surname')->render()
        );
        $this->assertEquals(
            'order by "name" desc, "surname" desc',
            $this->q('[order]')->order('name desc,surname desc')->render()
        );
        $this->assertEquals(
            'order by "name" desc, "surname"',
            $this->q('[order]')->order(['name desc', 'surname'])->render()
        );
        $this->assertEquals(
            'order by "name" desc, "surname"',
            $this->q('[order]')->order('surname')->order('name desc')->render()
        );
        $this->assertEquals(
            'order by "name" desc, "surname"',
            $this->q('[order]')->order('surname', false)->order('name', true)->render()
        );
        // table name|alias included
        $this->assertEquals(
            'order by "users"."name"',
            $this->q('[order]')->order('users.name')->render()
        );
        // strange field names
        $this->assertEquals(
            'order by "my name" desc',
            $this->q('[order]')->order('"my name" desc')->render()
        );
        $this->assertEquals(
            'order by "жук"',
            $this->q('[order]')->order('жук asc')->render()
        );
        $this->assertEquals(
            'order by "this is 💩"',
            $this->q('[order]')->order('this is 💩')->render()
        );
        $this->assertEquals(
            'order by "this is жук" desc',
            $this->q('[order]')->order('this is жук desc')->render()
        );
        $this->assertEquals(
            'order by * desc',
            $this->q('[order]')->order(['* desc'])->render()
        );
        $this->assertEquals(
            'order by "{}" desc',
            $this->q('[order]')->order(['{} desc'])->render()
        );
        $this->assertEquals(
            'order by "* desc"',
            $this->q('[order]')->order(new Expression('"* desc"'))->render()
        );
        $this->assertEquals(
            'order by "* desc"',
            $this->q('[order]')->order($this->q()->escape('* desc'))->render()
        );
        $this->assertEquals(
            'order by "* desc {}"',
            $this->q('[order]')->order($this->q()->escape('* desc {}'))->render()
        );
    }

    /**
     * If first argument is array, second argument must not be used.
     *
     * @covers ::order
     * @expectedException Exception
     */
    public function testOrderException1()
    {
        $this->q('[order]')->order(['name', 'surname'], 'desc');
    }

    /**
     * Incorrect ordering keyword.
     *
     * @covers ::order
     * @expectedException Exception
     */
    public function testOrderException2()
    {
        $this->q('[order]')->order('name', 'random_order');
    }

    /**
     * Test Group.
     *
     * @covers ::group
     * @covers ::_render_group
     */
    public function testGroup()
    {
        $this->assertEquals(
            'group by "gender"',
            $this->q('[group]')->group('gender')->render()
        );
        $this->assertEquals(
            'group by "gender", "age"',
            $this->q('[group]')->group('gender,age')->render()
        );
        $this->assertEquals(
            'group by "gender", "age"',
            $this->q('[group]')->group(['gender', 'age'])->render()
        );
        $this->assertEquals(
            'group by "gender", "age"',
            $this->q('[group]')->group('gender')->group('age')->render()
        );
        // table name|alias included
        $this->assertEquals(
            'group by "users"."gender"',
            $this->q('[group]')->group('users.gender')->render()
        );
        // strange field names
        $this->assertEquals(
            'group by "my name"',
            $this->q('[group]')->group('"my name"')->render()
        );
        $this->assertEquals(
            'group by "жук"',
            $this->q('[group]')->group('жук')->render()
        );
        $this->assertEquals(
            'group by "this is 💩"',
            $this->q('[group]')->group('this is 💩')->render()
        );
        $this->assertEquals(
            'group by "this is жук"',
            $this->q('[group]')->group('this is жук')->render()
        );
        $this->assertEquals(
            'group by date_format(dat, "%Y")',
            $this->q('[group]')->group(new Expression('date_format(dat, "%Y")'))->render()
        );
        $this->assertEquals(
            'group by date_format(dat, "%Y")',
            $this->q('[group]')->group('date_format(dat, "%Y")')->render()
        );
    }

    /**
     * Test Join.
     *
     * @covers ::join
     * @covers ::_render_join
     */
    public function testJoin()
    {
        $this->assertEquals(
            'left join "address" on "address"."id" = "address_id"',
            $this->q('[join]')->join('address')->render()
        );
        $this->assertEquals(
            'left join "address" as "a" on "a"."id" = "address_id"',
            $this->q('[join]')->join('address a')->render()
        );
        $this->assertEquals(
            'left join "address" as "a" on "a"."id" = "user"."address_id"',
            $this->q('[join]')->table('user')->join('address a')->render()
        );
        $this->assertEquals(
            'left join "address" as "a" on "a"."id" = "user"."my_address_id"',
            $this->q('[join]')->table('user')->join('address a', 'my_address_id')->render()
        );
        $this->assertEquals(
            'left join "address" as "a" on "a"."id" = "u"."address_id"',
            $this->q('[join]')->table('user', 'u')->join('address a')->render()
        );
        $this->assertEquals(
            'left join "address" as "a" on "a"."user_id" = "u"."id"',
            $this->q('[join]')->table('user', 'u')->join('address.user_id a')->render()
        );
        $this->assertEquals(
            'left join "address" as "a" on "a"."user_id" = "u"."id" '.
            'left join "bank" as "b" on "b"."id" = "u"."bank_id"',
            $this->q('[join]')->table('user', 'u')
                ->join(['a' => 'address.user_id', 'b' => 'bank'])->render()
        );
        $this->assertEquals(
            'left join "address" on "address"."user_id" = "u"."id" '.
            'left join "bank" on "bank"."id" = "u"."bank_id"',
            $this->q('[join]')->table('user', 'u')
                ->join(['address.user_id', 'bank'])->render()
        );
        $this->assertEquals(
            'left join "address" as "a" on "a"."user_id" = "u"."id" '.
            'left join "bank" as "b" on "b"."id" = "u"."bank_id" '.
            'left join "bank_details" on "bank_details"."id" = "bank"."details_id"',
            $this->q('[join]')->table('user', 'u')
                ->join(['a' => 'address.user_id', 'b' => 'bank'])
                ->join('bank_details', 'bank.details_id')->render()
        );

        $this->assertEquals(
            'left join "address" as "a" on a.name like u.pattern',
            $this->q('[join]')->table('user', 'u')
                ->join('address a', new Expression('a.name like u.pattern'))->render()
        );
    }

    /**
     * Combined execution of where() clauses.
     *
     * @covers ::where
     * @covers ::_render_where
     * @covers ::mode
     */
    public function testCombinedWhere()
    {
        $this->assertEquals(
            'select "name" from "employee" where "a" = :a',
            $this->q()
                ->field('name')->table('employee')->where('a', 1)
                ->render()
        );

        $this->assertEquals(
            'select "name" from "employee" where "employee"."a" = :a',
            $this->q()
                ->field('name')->table('employee')->where('employee.a', 1)
                ->render()
        );

        /*
        $this->assertEquals(
            'select "name" from "db"."employee" where "db"."employee"."a" = :a',
            $this->q()
                ->field('name')->table('db.employee')->where('db.employee.a',1)
                ->render()
        );
         */

        $this->assertEquals(
            'delete from "employee" where "employee"."a" = :a',
            $this->q()
                ->mode('delete')
                ->field('name')->table('employee')->where('employee.a', 1)
                ->render()
        );

        $user_ids = $this->q()->table('expired_users')->field('user_id');

        $this->assertEquals(
            'update "user" set "active"=:a  where "id" in (select "user_id" from "expired_users")',
            $this->q()
                ->table('user')
                ->where('id', 'in', $user_ids)
                ->set('active', 0)
                ->mode('update')
                ->render()
        );
    }

    /**
     * Test where() when $field is passed as array. Should create OR conditions.
     *
     * @covers ::where
     * @covers ::orExpr
     * @covers ::_render_where
     * @covers ::_render_orwhere
     */
    public function testOrWhere()
    {
        $this->assertEquals(
            'select "name" from "employee" where ("a" = :a or "b" = :b)',
            $this->q()
                ->field('name')->table('employee')->where([['a', 1], ['b', 1]])
                ->render()
        );

        $this->assertEquals(
            'select "name" from "employee" where ("a" = :a or a=b)',
            $this->q()
                ->field('name')->table('employee')->where([['a', 1], 'a=b'])
                ->render()
        );
    }

    /**
     * Test OrWhere and AndWhere without where condition. Should ignore them.
     *
     * @covers ::where
     * @covers ::orExpr
     * @covers ::andExpr
     * @covers ::_render_where
     * @covers ::_render_orwhere
     * @covers ::_render_andwhere
     */
    public function testEmptyOrAndWhere()
    {
        $this->assertEquals(
            '',
            $this->q()->orExpr()->render()
        );

        $this->assertEquals(
            '',
            $this->q()->andExpr()->render()
        );
    }

    /**
     * Test insert, update and delete templates.
     *
     * @covers ::mode
     * @covers ::where
     * @covers ::set
     * @covers ::_render_set
     * @covers ::_render_set_fields
     * @covers ::_render_set_values
     */
    public function testInsertDeleteUpdate()
    {
        // delete template
        $this->assertEquals(
            'delete from "employee" where "name" = :a',
            $this->q()
                ->field('name')->table('employee')->where('name', 1)
                ->mode('delete')
                ->render()
        );

        // update template
        $this->assertEquals(
            'update "employee" set "name"=:a',
            $this->q()
                ->field('name')->table('employee')->set('name', 1)
                ->mode('update')
                ->render()
        );

        $this->assertEquals(
            'update "employee" set "name"="name"+1',
            $this->q()
                ->field('name')->table('employee')->set('name', new Expression('"name"+1'))
                ->mode('update')
                ->render()
        );

        // insert template
        $this->assertEquals(
            'insert into "employee" ("name") values (:a)',
            $this->q()
                ->field('name')->table('employee')->set('name', 1)
                ->mode('insert')
                ->render()
        );

        // set multiple fields
        $this->assertEquals(
            'insert into "employee" ("time","name") values (now(),:a)',
            $this->q()
                ->field('time')->field('name')->table('employee')
                ->set('time', new Expression('now()'))
                ->set('name', 'unknown')
                ->mode('insert')
                ->render()
        );

        // set as array
        $this->assertEquals(
            'insert into "employee" ("time","name") values (now(),:a)',
            $this->q()
                ->field('time')->field('name')->table('employee')
                ->set(['time' => new Expression('now()'), 'name' => 'unknown'])
                ->mode('insert')
                ->render()
        );
    }

    /**
     * set() should return $this Query for chaining.
     *
     * @covers ::set
     */
    public function testSetReturnValue()
    {
        $q = $this->q();
        $this->assertEquals($q, $q->set('id', 1));
    }

    /**
     * Value [false] is not supported by SQL.
     *
     * @covers ::set
     * @expectedException Exception
     */
    public function testSetException1()
    {
        $this->q()->set('name', false);
    }

    /**
     * Field name can be expression.
     *
     * @covers ::set
     */
    public function testSetException2()
    {
        $this->q()->set((new Expression('foo')), 1);
    }

    /**
     * Test nested OR and AND expressions.
     *
     * @covers ::where
     * @covers ::orExpr
     * @covers ::andExpr
     * @covers ::_render_orwhere
     * @covers ::_render_andwhere
     */
    public function testNestedOrAnd()
    {
        // test 1
        $q = $this->q();
        $q->table('employee')->field('name');
        $q->where(
            $q
                ->orExpr()
                ->where('a', 1)
                ->where('b', 1)
        );
        $this->assertEquals(
            'select "name" from "employee" where ("a" = :a or "b" = :b)',
            $q->render()
        );

        // test 2
        $q = $this->q();
        $q->table('employee')->field('name');
        $q->where(
            $q
                ->orExpr()
                ->where('a', 1)
                ->where('b', 1)
                ->where(
                    $q->andExpr()
                        ->where('true')
                        ->where('false')
                )
        );
        $this->assertEquals(
            'select "name" from "employee" where ("a" = :a or "b" = :b or (true and false))',
            $q->render()
        );
    }

    /**
     * Test reset().
     *
     * @covers \atk4\dsql\Expression::reset
     */
    public function testReset()
    {
        // reset everything
        $q = $this->q()->table('user')->where('name', 'John');
        $q->reset();
        $this->assertEquals('select *', $q->render());

        // reset particular tag
        $q = $this->q()
            ->table('user')
            ->where('name', 'John')
            ->reset('where')
            ->where('surname', 'Doe');
        $this->assertEquals('select * from "user" where "surname" = :a', $q->render());
    }

    /**
     * Test [option].
     *
     * @covers ::option
     * @covers ::_render_option
     */
    public function testOption()
    {
        // single option
        $this->assertEquals(
            'select calc_found_rows * from "test"',
            $this->q()->table('test')->option('calc_found_rows')->render()
        );
        // multiple options
        $this->assertEquals(
            'select calc_found_rows ignore * from "test"',
            $this->q()->table('test')->option('calc_found_rows,ignore')->render()
        );
        $this->assertEquals(
            'select calc_found_rows ignore * from "test"',
            $this->q()->table('test')->option(['calc_found_rows', 'ignore'])->render()
        );
        // options for specific modes
        $q = $this->q()
                ->table('test')
                ->field('name')
                ->set('name', 1)
                ->option('calc_found_rows', 'select') // for default select mode
                ->option('ignore', 'insert') // for insert mode
;

        $this->assertEquals(
            'select calc_found_rows "name" from "test"',
            $q->mode('select')->render()
        );
        $this->assertEquals(
            'insert ignore into "test" ("name") values (:a)',
            $q->mode('insert')->render()
        );
        $this->assertEquals(
            'update "test" set "name"=:a',
            $q->mode('update')->render()
        );
    }

    /**
     * Test caseExpr (normal).
     *
     * @covers ::caseExpr
     * @covers ::when
     * @covers ::else
     * @covers ::_render_case
     */
    public function testCaseExprNormal()
    {
        // Test normal form
        $s = $this->q()->caseExpr()
                ->when(['status', 'New'], 't2.expose_new')
                ->when(['status', 'like', '%Used%'], 't2.expose_used')
                ->otherwise(null)
                ->render();
        $this->assertEquals('case when "status" = :a then :b when "status" like :c then :d else :e end', $s);

        // with subqueries
        $age = new Expression('year(now()) - year(birth_date)');
        $q = $this->q()->table('user')->field($age, 'calc_age');

        $s = $this->q()->caseExpr()
                ->when(['age', '>', $q], 'Older')
                ->otherwise('Younger')
                ->render();
        $this->assertEquals('case when "age" > (select year(now()) - year(birth_date) "calc_age" from "user") then :a else :b end', $s);
    }

    /**
     * Test caseExpr (short form).
     *
     * @covers ::caseExpr
     * @covers ::when
     * @covers ::else
     * @covers ::_render_case
     */
    public function testCaseExprShortForm()
    {
        $s = $this->q()->caseExpr('status')
                ->when('New', 't2.expose_new')
                ->when('Used', 't2.expose_used')
                ->otherwise(null)
                ->render();
        $this->assertEquals('case "status" when :a then :b when :c then :d else :e end', $s);

        // with subqueries
        $age = new Expression('year(now()) - year(birth_date)');
        $q = $this->q()->table('user')->field($age, 'calc_age');

        $s = $this->q()->caseExpr($q)
                ->when(100, 'Very old')
                ->otherwise('Younger')
                ->render();
        $this->assertEquals('case (select year(now()) - year(birth_date) "calc_age" from "user") when :a then :b else :c end', $s);
    }

    /**
     * Incorrect use of "when" method parameters.
     *
     * @expected Exception Exception
     */
    public function testCaseExprException1()
    {
        $this->q()->caseExpr()
            ->when(['status'], 't2.expose_new');
    }

    /**
     * When using short form CASE statement, then you should not set array as when() method 1st parameter.
     *
     * @expected Exception Exception
     */
    public function testCaseExprException2()
    {
        $this->q()->caseExpr('status')
            ->when(['status', 'New'], 't2.expose_new');
    }
}
