<?php
namespace atk4\dsql\tests;

use atk4\dsql\Query;
use atk4\dsql\Expression;

/**
 * @coversDefaultClass \atk4\dsql\Query
 */
class QueryTest extends \PHPUnit_Framework_TestCase
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
     * Test constructor
     *
     * @covers ::__construct
     */
    public function testConstruct()
    {
        // passing properties in constructor
        $this->assertEquals(
            '#q#',
            PHPUnitUtil::callProtectedMethod($this->q(['escapeChar' => '#']), '_escape', ['q'])
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
     * field() should return $this Query for chaining
     *
     * @covers ::field
     */
    public function testFieldReturnValue()
    {
        $q = $this->q();
        $this->assertEquals($q, $q->field('first_name'));
    }

    /**
     * Testing field - basic cases
     *
     * @covers ::field
     * @covers ::_render_field
     */
    public function testFieldBasic()
    {
        $this->assertEquals(
            '`first_name`',
            PHPUnitUtil::callProtectedMethod($this->q()->field('first_name'), '_render_field')
        );
        $this->assertEquals(
            '`first_name`,`last_name`',
            PHPUnitUtil::callProtectedMethod($this->q()->field('first_name,last_name'), '_render_field')
        );
        $this->assertEquals(
            '`employee`.`first_name`',
            PHPUnitUtil::callProtectedMethod($this->q()->field('first_name', 'employee'), '_render_field')
        );
        $this->assertEquals(
            '`first_name` `name`',
            PHPUnitUtil::callProtectedMethod($this->q()->field('first_name', null, 'name'), '_render_field')
        );
        $this->assertEquals(
            '`first_name` `name`',
            PHPUnitUtil::callProtectedMethod($this->q()->field(['name' => 'first_name']), '_render_field')
        );
        $this->assertEquals(
            '`name`',
            PHPUnitUtil::callProtectedMethod($this->q()->field(['name' => 'name']), '_render_field')
        );
        $this->assertEquals(
            '`employee`.`first_name` `name`',
            PHPUnitUtil::callProtectedMethod($this->q()->field(['name'=>'first_name'], 'employee'), '_render_field')
        );
        $this->assertEquals(
            '*',
            PHPUnitUtil::callProtectedMethod($this->q()->field('*'), '_render_field')
        );
        $this->assertEquals(
            'employee.first_name',
            PHPUnitUtil::callProtectedMethod($this->q()->field('employee.first_name'), '_render_field')
        );
    }

    /**
     * Testing field - defaultField
     *
     * @covers ::field
     * @covers ::_render_field
     */
    public function testFieldDefaultField()
    {
        // default defaultField
        $this->assertEquals(
            '*',
            PHPUnitUtil::callProtectedMethod($this->q(), '_render_field')
        );
        // defaultField as custom string - not escaped
        $this->assertEquals(
            'id',
            PHPUnitUtil::callProtectedMethod($this->q(['defaultField' => 'id']), '_render_field')
        );
        // defaultField as custom string with dot - not escaped
        $this->assertEquals(
            'all.values',
            PHPUnitUtil::callProtectedMethod($this->q(['defaultField' => 'all.values']), '_render_field')
        );
        // defaultField as Expression object - not escaped
        $this->assertEquals(
            'values()',
            PHPUnitUtil::callProtectedMethod($this->q(['defaultField' => new Expression('values()')]), '_render_field')
        );
    }

    /**
     * Testing field - basic cases
     *
     * @covers ::field
     * @covers ::_render_field
     */
    public function testFieldExpression()
    {
        $this->assertEquals(
            '`name`',
            $this->q('[field]')->field('name')->render()
        );
        $this->assertEquals(
            '`first name`',
            $this->q('[field]')->field('first name')->render()
        );
        $this->assertEquals(
            'first.name',
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
            'now() `time`',
            $this->q('[field]')->field('now()', null, 'time')->render()
        );
        $this->assertEquals( // alias can be passed as 2nd argument
            'now() `time`',
            $this->q('[field]')->field(new Expression('now()'), 'time')->render()
        );
        $this->assertEquals( // alias can be passed as 3nd argument
            'now() `time`',
            $this->q('[field]')->field(new Expression('now()'), null, 'time')->render()
        );
    }

    /**
     * There shouldn't be alias when passing multiple tables
     *
     * @covers ::table
     * @expectedException Exception
     */
    public function testTableException1()
    {
        $this->q()->table('employee,jobs', 'u');
    }

    /**
     * There shouldn't be alias when passing multiple tables
     *
     * @covers ::table
     * @expectedException Exception
     */
    public function testTableException2()
    {
        $this->q()->table(['employee','jobs'], 'u');
    }
    /**
     * can't use table with expression
     *
     * @covers ::table
     * @expectedException Exception
     */
    public function testTableExprException1()
    {
        $q = $this->q();
        $q
            ->table($q->expr('test'))
            ->table('user');
    }
    /**
     * can't use table with expression
     *
     * @covers ::table
     * @expectedException Exception
     */
    public function testTableExprException2()
    {
        $q = $this->q();
        $q
            ->table('user')
            ->table($q->expr('test'));
    }

    /**
     * table() should return $this Query for chaining
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
            'select `name` from `employee`',
            $this->q()
                ->field('name')->table('employee')
                ->render()
        );
        $this->assertEquals(
            'select `name` from `employee` `e`',
            $this->q()
                ->field('name')->table('employee', 'e')
                ->render()
        );
        $this->assertEquals(
            'select * from `employee` `e`',
            $this->q()
                ->table('employee', 'e')
                ->render()
        );

        // multiple tables
        $this->assertEquals(
            'select `employee`.`name` from `employee`,`jobs`',
            $this->q()
                ->field('name', 'employee')->table('employee')->table('jobs')
                ->render()
        );
        $this->assertEquals(
            'select `name` from `employee`,`jobs`',
            $this->q()
                ->field('name')->table('employee,jobs')
                ->render()
        );
        $this->assertEquals(
            'select `name` from `employee`,`jobs`',
            $this->q()
                ->field('name')->table('  employee ,   jobs  ')
                ->render()
        );
        $this->assertEquals(
            'select `name` from `employee`,`jobs`',
            $this->q()
                ->field('name')->table(['employee', 'jobs'])
                ->render()
        );
        $this->assertEquals(
            'select `name` from `employee`,`jobs`',
            $this->q()
                ->field('name')->table(['employee  ', '  jobs'])
                ->render()
        );

        // multiple tables with aliases
        $this->assertEquals(
            'select `name` from `employee`,`jobs` `j`',
            $this->q()
                ->field('name')->table(['employee', 'j'=>'jobs'])
                ->render()
        );
        $this->assertEquals(
            'select `name` from `employee` `e`,`jobs` `j`',
            $this->q()
                ->field('name')->table(['e'=>'employee', 'j'=>'jobs'])
                ->render()
        );
        // testing _render_table_noalias, shouldn't render table alias 'emp'
        $this->assertEquals(
            'insert into `employee` (`name`) values (:a)',
            $this->q()
                ->field('name')->table('employee', 'emp')->set('name', 1)
                ->selectTemplate('insert')
                ->render()
        );
        $this->assertEquals(
            'update `employee` set `name`=:a',
            $this->q()
                ->field('name')->table('employee', 'emp')->set('name', 1)
                ->selectTemplate('update')
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
            'select `name` from (select * from `employee`)',
            $this->q()
                ->field('name')->table($q)
                ->render()
        );

        /**
         * @todo Add more tests with multiple tables & subqueries
         * Currently that's restricted, but I believe it should be allowed to create query like this
         * SELECT `e`.`name`, `c`.`name`
         * FROM
         *  (select * from `employee`) `e`,
         *  (select * from `customer`) `c`
         * In such case table alias should better be mandatory.
         */

        /**
         * @todo Add some tests with non-unique table aliases.
         *  They will currently generate: SELECT * FROM `foo` `a`, `bar` `a` which is wrong!
         * We have to check uniquness of table aliases and in such cases throw appropriate exception.
         */
        $q = $this->q()
            ->table('foo', 'a')
            ->table('bar', 'a');
        $this->assertEquals(
            'select * from `foo` `a`,`bar` `a`', // <-- aliases should be unique and THIS SHOULD THROW EXCEPTION
            $q->render()
        );

    }

    /**
     * @covers ::render
     * @covers \atk4\dsql\Expression::_consume
     * @covers \atk4\dsql\Expression::render
     */
    public function testBasicRenderSubquery()
    {
        $age = new Expression("coalesce([age], [default_age])");
        $age['age'] = new Expression("year(now()) - year(birth_date)");
        $age['default_age'] = 18;

        $q = $this->q()->table('user')->field($age, 'calculated_age');

        $this->assertEquals(
            'select coalesce(year(now()) - year(birth_date), :a) `calculated_age` from `user`',
            $q->render()
        );
    }

    /**
     * @covers atk4\dsql\Expression::getDebugQuery
     */
    public function testgetDebugQuery()
    {
        $age = new Expression("coalesce([age], [default_age], [foo], [bar])");
        $age['age'] = new Expression("year(now()) - year(birth_date)");
        $age['default_age'] = 18;
        $age['foo'] = 'foo';
        $age['bar'] = null;

        $q = $this->q()->table('user')->field($age, 'calculated_age');

        $this->assertEquals(
            'select coalesce(year(now()) - year(birth_date), 18, "foo", NULL) `calculated_age` from `user` [:c, :b, :a]',
            strip_tags($q->getDebugQuery())
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
            ->field('amount', null, 'debit')
            ->field($this->q()->expr('0'), null, 'credit') // simply 0
            ;
        $this->assertEquals(
            'select `date`,`amount` `debit`,0 `credit` from `sales`',
            $q1->render()
        );

        // 2nd query
        $q2 = $this->q()
            ->table('purchases')
            ->field('date')
            ->field($this->q()->expr('0'), null, 'debit') // simply 0
            ->field('amount', null, 'credit')
            ;
        $this->assertEquals(
            'select `date`,0 `debit`,`amount` `credit` from `purchases`',
            $q2->render()
        );

        // $q1 union $q2
        $u = new Expression("[] union []", [$q1, $q2]);
        $this->assertEquals(
            '(select `date`,`amount` `debit`,0 `credit` from `sales`) union (select `date`,0 `debit`,`amount` `credit` from `purchases`)',
            $u->render()
        );

        // SELECT date,debit,credit FROM ($q1 union $q2)
        $q = $this->q()
            ->field('date,debit,credit')
            ->table($u, 'derrivedTable')
            ;
        /**
         * @see https://github.com/atk4/dsql/issues/33
         * @see https://github.com/atk4/dsql/issues/34
         */
        /*
        $this->assertEquals(
            'select `date`,`debit`,`credit` from ((select `date`,`amount` `debit`,0 `credit` from `sales`) union (select `date`,0 `debit`,`amount` `credit` from `purchases`)) `derrivedTable`',
            $q->render()
        );
        */
    }

    /**
     * where() should return $this Query for chaining
     *
     * @covers ::where
     */
    public function testWhereReturnValue()
    {
        $q = $this->q();
        $this->assertEquals($q, $q->where('id', 1));
    }

    /**
     * having() should return $this Query for chaining
     *
     * @covers ::field
     */
    public function testHavingReturnValue()
    {
        $q = $this->q();
        $this->assertEquals($q, $q->having('id', 1));
    }

    /**
     * Basic where() tests
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
            'where `id` = :a',
            $this->q('[where]')->where('id', 1)->render()
        );
        $this->assertEquals(
            'where `user`.`id` = :a',
            $this->q('[where]')->where('user.id', 1)->render()
        );
        $this->assertEquals(
            'where `db`.`user`.`id` = :a',
            $this->q('[where]')->where('db.user.id', 1)->render()
        );
        $this->assertEquals(
            'where `id` is :a',
            $this->q('[where]')->where('id', null)->render()
        );

        // three parameters - field, condition, value
        $this->assertEquals(
            'where `id` > :a',
            $this->q('[where]')->where('id', '>', 1)->render()
        );
        $this->assertEquals(
            'where `id` < :a',
            $this->q('[where]')->where('id', '<', 1)->render()
        );
        $this->assertEquals(
            'where `id` = :a',
            $this->q('[where]')->where('id', '=', 1)->render()
        );
        
        // two parameters - more_than_just_a_field, value
        $this->assertEquals(
            'where `id` = :a',
            $this->q('[where]')->where('id=', 1)->render()
        );
        $this->assertEquals(
            'where `id` != :a',
            $this->q('[where]')->where('id!=', 1)->render()
        );
        $this->assertEquals(
            'where `id` <> :a',
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
            'where `a` = :a and `b` is :b',
            $this->q('[where]')->where('a', 1)->where('b', null)->render()
        );
    }

    /**
     * Testing where() with special values - null, array, like
     *
     * @covers ::where
     * @covers ::_render_where
     * @covers ::__render_where
     */
    public function testWhereSpecialValues()
    {
        // in | not in
        $this->assertEquals(
            'where `id` in (:a,:b)',
            $this->q('[where]')->where('id', 'in', [1, 2])->render()
        );
        $this->assertEquals(
            'where `id` not in (:a,:b)',
            $this->q('[where]')->where('id', 'not in', [1, 2])->render()
        );
        $this->assertEquals(
            'where `id` not in (:a,:b)',
            $this->q('[where]')->where('id', 'not', [1, 2])->render()
        );
        $this->assertEquals(
            'where `id` in (:a,:b)',
            $this->q('[where]')->where('id', '=', [1, 2])->render()
        );
        $this->assertEquals(
            'where `id` not in (:a,:b)',
            $this->q('[where]')->where('id', '<>', [1, 2])->render()
        );
        $this->assertEquals(
            'where `id` not in (:a,:b)',
            $this->q('[where]')->where('id', '!=', [1, 2])->render()
        );
        // pass array as CSV
        $this->assertEquals(
            'where `id` in (:a,:b)',
            $this->q('[where]')->where('id', 'in', '1,2')->render()
        );
        $this->assertEquals(
            'where `id` not in (:a,:b)',
            $this->q('[where]')->where('id', 'not in', '1,    2')->render()
        );
        $this->assertEquals(
            'where `id` not in (:a,:b)',
            $this->q('[where]')->where('id', 'not', '1,2')->render()
        );
        
        // is | is not
        $this->assertEquals(
            'where `id` is :a',
            $this->q('[where]')->where('id', 'is', null)->render()
        );
        $this->assertEquals(
            'where `id` is not :a',
            $this->q('[where]')->where('id', 'is not', null)->render()
        );
        $this->assertEquals(
            'where `id` is not :a',
            $this->q('[where]')->where('id', 'not', null)->render()
        );
        $this->assertEquals(
            'where `id` is :a',
            $this->q('[where]')->where('id', '=', null)->render()
        );
        $this->assertEquals(
            'where `id` is not :a',
            $this->q('[where]')->where('id', '<>', null)->render()
        );
        $this->assertEquals(
            'where `id` is not :a',
            $this->q('[where]')->where('id', '!=', null)->render()
        );
        
        // like | not like
        $this->assertEquals(
            'where `name` like :a',
            $this->q('[where]')->where('name', 'like', 'foo')->render()
        );
        $this->assertEquals(
            'where `name` not like :a',
            $this->q('[where]')->where('name', 'not like', 'foo')->render()
        );
        
        // two parameters - more_than_just_a_field, value
        // is | is not
        $this->assertEquals(
            'where `id` is :a',
            $this->q('[where]')->where('id=', null)->render()
        );
        $this->assertEquals(
            'where `id` is not :a',
            $this->q('[where]')->where('id!=', null)->render()
        );
        $this->assertEquals(
            'where `id` is not :a',
            $this->q('[where]')->where('id<>', null)->render()
        );

        // in | not in
        $this->assertEquals(
            'where `id` in (:a,:b)',
            $this->q('[where]')->where('id=', [1, 2])->render()
        );
        $this->assertEquals(
            'where `id` not in (:a,:b)',
            $this->q('[where]')->where('id!=', [1, 2])->render()
        );
        $this->assertEquals(
            'where `id` not in (:a,:b)',
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
            'having `id` = :a',
            $this->q('[having]')->having('id', 1)->render()
        );
        $this->assertEquals(
            'having `id` > :a',
            $this->q('[having]')->having('id', '>', 1)->render()
        );
        $this->assertEquals(
            'where `id` = :a having `id` > :b',
            $this->q('[where][having]')->where('id', 1)->having('id>', 1)->render()
        );
    }

    /**
     * Test Limits
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
            $this->q('[limit]')->limit(100,200)->render()
        );
    }

    /**
     * Test Order
     *
     * @covers ::order
     * @covers ::_render_order
     */
    public function testOrder()
    {
        $this->assertEquals(
            'order by `name`',
            $this->q('[order]')->order('name')->render()
        );
        $this->assertEquals(
            'order by `name`, `surname`',
            $this->q('[order]')->order('name,surname')->render()
        );
        $this->assertEquals(
            'order by `name` desc, `surname` desc',
            $this->q('[order]')->order('name desc,surname desc')->render()
        );
        $this->assertEquals(
            'order by `name` desc, `surname`',
            $this->q('[order]')->order(['name desc','surname'])->render()
        );
        $this->assertEquals(
            'order by `name` desc, `surname`',
            $this->q('[order]')->order('surname')->order('name desc')->render()
        );
    }

    /**
     * Combined execution of where() clauses
     *
     * @covers ::where
     * @covers ::_render_where
     * @covers ::selectTemplate
     */
    public function testCombinedWhere()
    {
        $this->assertEquals(
            'select `name` from `employee` where `a` = :a',
            $this->q()
                ->field('name')->table('employee')->where('a', 1)
                ->render()
        );

        $this->assertEquals(
            'select `name` from `employee` where `employee`.`a` = :a',
            $this->q()
                ->field('name')->table('employee')->where('employee.a', 1)
                ->render()
        );

        /*
        $this->assertEquals(
            'select `name` from `db`.`employee` where `db`.`employee`.`a` = :a',
            $this->q()
                ->field('name')->table('db.employee')->where('db.employee.a',1)
                ->render()
        );
         */

        $this->assertEquals(
            'delete from `employee` where `employee`.`a` = :a',
            $this->q()
                ->selectTemplate('delete')
                ->field('name')->table('employee')->where('employee.a', 1)
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
            'select `name` from `employee` where (`a` = :a or `b` = :b)',
            $this->q()
                ->field('name')->table('employee')->where([['a', 1],['b', 1]])
                ->render()
        );

        $this->assertEquals(
            'select `name` from `employee` where (`a` = :a or a=b)',
            $this->q()
                ->field('name')->table('employee')->where([['a', 1],'a=b'])
                ->render()
        );
    }

    /**
     * Test insert, update and delete templates.
     *
     * @covers ::selectTemplate
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
            'delete from `employee` where `name` = :a',
            $this->q()
                ->field('name')->table('employee')->where('name', 1)
                ->selectTemplate('delete')
                ->render()
        );

        // update template
        $this->assertEquals(
            'update `employee` set `name`=:a',
            $this->q()
                ->field('name')->table('employee')->set('name', 1)
                ->selectTemplate('update')
                ->render()
        );

        $this->assertEquals(
            'update `employee` set `name`=`name`+1',
            $this->q()
                ->field('name')->table('employee')->set('name', new Expression('`name`+1'))
                ->selectTemplate('update')
                ->render()
        );

        // insert template
        $this->assertEquals(
            'insert into `employee` (`name`) values (:a)',
            $this->q()
                ->field('name')->table('employee')->set('name', 1)
                ->selectTemplate('insert')
                ->render()
        );

        // set multiple fields
        $this->assertEquals(
            'insert into `employee` (`time`,`name`) values (now(),:a)',
            $this->q()
                ->field('time')->field('name')->table('employee')
                ->set('time', new Expression('now()'))
                ->set('name', 'unknown')
                ->selectTemplate('insert')
                ->render()
        );
        
        // set as array
        $this->assertEquals(
            'insert into `employee` (`time`,`name`) values (now(),:a)',
            $this->q()
                ->field('time')->field('name')->table('employee')
                ->set(['time' => new Expression('now()'), 'name' => 'unknown'])
                ->selectTemplate('insert')
                ->render()
        );
    }

    /**
     * set() should return $this Query for chaining
     *
     * @covers ::set
     */
    public function testSetReturnValue()
    {
        $q = $this->q();
        $this->assertEquals($q, $q->set('id', 1));
    }

    /**
     * Value [false] is not supported by SQL
     *
     * @covers ::set
     * @expectedException Exception
     */
    public function testSetException1()
    {
        $this->q()->set('name', false);
    }

    /**
     * Field name should be array or string.
     *
     * @covers ::set
     * @expectedException Exception
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
            'select `name` from `employee` where (`a` = :a or `b` = :b)',
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
            'select `name` from `employee` where (`a` = :a or `b` = :b or (true and false))',
            $q->render()
        );
    }
}
