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
     * @covers ::field
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
            PHPUnitUtil::callProtectedMethod($this->q(), '_render_field')
        );
        $this->assertEquals(
            'id',
            PHPUnitUtil::callProtectedMethod($this->q(['defaultField' => 'id']), '_render_field')
        );
        $this->assertEquals(
            '*',
            PHPUnitUtil::callProtectedMethod($this->q()->field('*'), '_render_field')
        );
        $this->assertEquals(
            'first_name.employee',
            PHPUnitUtil::callProtectedMethod($this->q()->field('first_name.employee'), '_render_field')
        );
    }

    public function testFieldExpression()
    {
        $this->assertEquals(
            '`name`',
            $this->q(['template'=>'[field]'])->field('name')->render()
        );
        $this->assertEquals(
            '`first name`',
            $this->q(['template'=>'[field]'])->field('first name')->render()
        );
        $this->assertEquals(
            'first.name',
            $this->q(['template'=>'[field]'])->field('first.name')->render()
        );
        $this->assertEquals(
            'now()',
            $this->q(['template'=>'[field]'])->field('now()')->render()
        );
        $this->assertEquals(
            'now()',
            $this->q(['template'=>'[field]'])->field(new Expression('now()'))->render()
        );
        // Next two require review of $field() second argument logic
        //$this->assertEquals(
        //    'now() `time`',
        //    $this->q(['template'=>'[field]'])->field('now()',null,'time')->render()
        //);
        //$this->assertEquals(
        //    'now() `time`',
        //    $this->q(['template'=>'[field]'])->field(new Expression('now()'),null,'time')->render()
        //);

    }

    /**
     * @covers ::table
     */
    public function testTable()
    {
        $q = $this->q();
        $this->assertEquals($q, $q->table('employee'));

    }

    /**
     * @expectedException Exception
     */
    public function testTableFailure1()
    {
        $this->q()->table('employee,jobs', 'u');
    }

    /**
     * @expectedException Exception
     */
    public function testTableFailure2()
    {
        $this->q()->table(['employee','jobs'], 'u');
    }

    /**
     * @covers ::render
     */
    public function testTableRender()
    {
        $this->assertEquals(
            'select `name` from `employee`',
            $this->q()
                ->field('name')->table('employee')
                ->render()
        );

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
                ->field('name')->table(['employee','jobs'])
                ->render()
        );

        $this->assertEquals(
            'select `name` from `employee`,`jobs` `j`',
            $this->q()
                ->field('name')->table(['employee','j'=>'jobs'])
                ->render()
        );

        $this->assertEquals(
            'select now()',
            $this->q()
                ->field(new Expression('now()'))
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
    }

    /**
     * @covers ::render
     */
    public function testBasicRenderSubquery()
    {
        $q = $this->q()->table('employee');

        $this->assertEquals(
            'select `name` from (select * from `employee`)',
            $this->q()
                ->field('name')->table($q)
                ->render()
        );

        $query = $this->q();
        $query->table('user');

        $age = new Expression("coalesce([age], [default_age])");
        $age['age'] = new Expression("year(now()) - year(birth_date)");
        $age['default_age'] = 18;

        $query->field($age, 'calculated_age');

        $this->assertEquals(
            'select coalesce(year(now()) - year(birth_date), :a) `calculated_age` from `user`',
            $query->render()
        );


    }

    public function testUnionQuery()
    {
        $q1 = $this->q()
            ->table('sales')
            ->field('date')
            ->field('amount', null, 'debit');

        $this->assertEquals(
            'select `date`,`amount` `debit` from `sales`',
            $q1->render()
        );

        $q2 = $this->q()
            ->table('purchases')
            ->field('date')
            ->field('amount', null, 'credit');

        $this->assertEquals(
            'select `date`,`amount` `credit` from `purchases`',
            $q2->render()
        );

        $u = (new Expression("([] union []) derrivedTable", [$q1, $q2]));

        $this->assertEquals(
            '((select `date`,`amount` `debit` from `sales`) union (select `date`,`amount` `credit` from `purchases`)) derrivedTable',
            $u->render()
        );

        $q = $this->q()
            ->field('date,debit,credit')
            ->table($u);

        $this->assertEquals(
            'select `date`,`debit`,`credit` from ((select `date`,`amount` `debit` from `sales`) union (select `date`,`amount` `credit` from `purchases`)) derrivedTable',
            $q->render()
        );
    }

    /**
     * @covers ::getDebugQuery
     */
    public function testgetDebugQuery()
    {
        $query = $this->q();
        $query->table('user');

        $age = new Expression("coalesce([age], [default_age])");
        $age['age'] = new Expression("year(now()) - year(birth_date)");
        $age['default_age'] = 18;

        $query -> field($age, 'calculated_age');

        $this->assertEquals(
            'select coalesce(year(now()) - year(birth_date), 18) `calculated_age` from `user` [:a]',
            strip_tags($query->getDebugQuery())
        );
    }

    /**
     * @covers ::where
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
     * @covers ::having
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
