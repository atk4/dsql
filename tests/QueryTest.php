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
        // Next two require review of $field() second argument logic
        //$this->assertEquals(
        //    'now() `time`',
        //    $this->q('[field]')->field('now()',null,'time')->render()
        //);
        //$this->assertEquals(
        //    'now() `time`',
        //    $this->q('[field]')->field(new Expression('now()'),null,'time')->render()
        //);
    }

    /**
     * There shouldn't be alias when passing multiple tables
     *
     * @expectedException Exception
     */
    public function testTableException1()
    {
        $this->q()->table('employee,jobs', 'u');
    }

    /**
     * There shouldn't be alias when passing multiple tables
     *
     * @expectedException Exception
     */
    public function testTableException2()
    {
        $this->q()->table(['employee','jobs'], 'u');
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
            'select * from `foo` `a`, `bar` `a`', // <-- testing this !!! table aliases should be unique.
            $q->render()
        );

    }

    /**
     * @covers ::render
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
     * @covers ::getDebugQuery
     */
    public function testgetDebugQuery()
    {
        $age = new Expression("coalesce([age], [default_age])");
        $age['age'] = new Expression("year(now()) - year(birth_date)");
        $age['default_age'] = 18;

        $q = $this->q()->table('user')->field($age, 'calculated_age');

        $this->assertEquals(
            'select coalesce(year(now()) - year(birth_date), 18) `calculated_age` from `user` [:a]',
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
        $u = (new Expression("[] union []", [$q1, $q2]));
        $this->assertEquals(
            '(select `date`,`amount` `debit`,0 `credit` from `sales`) union (select `date`,0 `debit`,`amount` `credit` from `purchases`)',
            $u->render()
        );

        // SELECT date,debit,credit FROM ($q1 union $q2)
        $q = $this->q()
            ->field('date,debit,credit')
            ->table($u, 'derrivedTable')
            ;
        $this->assertEquals(
            'select `date`,`debit`,`credit` from ((select `date`,`amount` `debit`,0 `credit` from `sales`) union (select `date`,0 `debit`,`amount` `credit` from `purchases`)) `derrivedTable`',
            $q->render()
        );
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
     * @covers ::where
     * @covers ::_render_where
     */
    public function testBasicWhere()
    {
        $this->assertEquals(
            'where `id` = :a',
            $this->q(['template'=>'[where]'])->where('id', 1)->render()
        );
        $this->assertEquals(
            'where `user`.`id` = :a',
            $this->q(['template'=>'[where]'])->where('user.id', 1)->render()
        );
        $this->assertEquals(
            'where `db`.`user`.`id` = :a',
            $this->q(['template'=>'[where]'])->where('db.user.id', 1)->render()
        );
        $this->assertEquals(
            'where `id` > :a',
            $this->q(['template'=>'[where]'])->where('id', '>', 1)->render()
        );
        $this->assertEquals(
            'where `id` in (:a,:b)',
            $this->q(['template'=>'[where]'])->where('id', 'in', [1, 2])->render()
        );
        $this->assertEquals(
            'where `id` is :a',
            $this->q(['template'=>'[where]'])->where('id', 'is', null)->render()
        );
        $this->assertEquals(
            'where `id` is not :a',
            $this->q(['template'=>'[where]'])->where('id!=', null)->render()
        );
        $this->assertEquals(
            'where `id` is not :a',
            $this->q(['template'=>'[where]'])->where('id<>', null)->render()
        );
        $this->assertEquals(
            'where now()',
            $this->q(['template'=>'[where]'])->where('now()')->render()
        );
        $this->assertEquals(
            'where now() = :a',
            $this->q(['template'=>'[where]'])->where('now()', 1)->render()
        );
        $this->assertEquals(
            'where now = :a',
            $this->q(['template'=>'[where]'])->where(new Expression('now'), 1)->render()
        );
        $this->assertEquals(
            'where `a` = :a and `b` = :b',
            $this->q(['template'=>'[where]'])->where('a', 1)->where('b', 2)->render()
        );
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
     * @covers ::having
     * @covers ::_render_having
     */
    public function testBasicHaving()
    {
        $this->assertEquals(
            'having `id` = :a',
            $this->q(['template'=>'[having]'])->having('id', 1)->render()
        );
        $this->assertEquals(
            'having `id` > :a',
            $this->q(['template'=>'[having]'])->having('id', '>', 1)->render()
        );
        $this->assertEquals(
            'where `id` = :a having `id` > :b',
            $this->q(['template'=>'[where][having]'])->where('id', 1)->having('id>', 1)->render()
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

    public function testOrWhere()
    {
        $this->assertEquals(
            'select `name` from `employee` where (`a` = :a or `b` = :b)',
            $this->q()
                ->field('name')->table('employee')->where([['a',1],['b',1]])
                ->render()
        );

        $this->assertEquals(
            'select `name` from `employee` where (`a` = :a or a=b)',
            $this->q()
                ->field('name')->table('employee')->where([['a',1],'a=b'])
                ->render()
        );
    }


    public function testInsertDeleteUpdate()
    {
        $this->assertEquals(
            'delete from `employee` where `name` = :a',
            $this->q()
                ->field('name')->table('employee')->where('name', 1)
                ->selectTemplate('delete')
                ->render()
        );

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

        $this->assertEquals(
            'insert into `employee` (`name`) values (:a)',
            $this->q()
                ->field('name')->table('employee')->set('name', 1)
                ->selectTemplate('insert')
                ->render()
        );

        $this->assertEquals(
            'insert into `employee` (`name`) values (now())',
            $this->q()
                ->field('name')->table('employee')->set('name', new Expression('now()'))
                ->selectTemplate('insert')
                ->render()
        );
    }

    public function testNestedOrAnd()
    {

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
