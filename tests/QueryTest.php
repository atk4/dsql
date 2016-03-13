<?php
namespace atk4\dsql\tests;
use atk4\dsql\Query;
use atk4\dsql\Expression;



/**
 * @coversDefaultClass \atk4\dsql\Query
 */
class QueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Name of class which we're testing with all these tests
     */
    public function q($args = [])
    {
        return new Query($args);
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        // passing arguments in constructor
        $this->assertEquals('#q#', PHPUnitUtil::callProtectedMethod($this->q(['escapeChar' => '#']), '_escape',['q']));
    }


    /**
     * @covers ::field
     */
    public function testFieldBasic()
    {
        $this->assertEquals('`first_name`',             PHPUnitUtil::callProtectedMethod($this->q()->field('first_name'), '_render_field'));
        $this->assertEquals('`first_name`,`last_name`', PHPUnitUtil::callProtectedMethod($this->q()->field('first_name,last_name'), '_render_field'));
        $this->assertEquals('`employee`.`first_name`',  PHPUnitUtil::callProtectedMethod($this->q()->field('first_name', 'employee'), '_render_field'));
        $this->assertEquals('`first_name` `name`',      PHPUnitUtil::callProtectedMethod($this->q()->field('first_name', null, 'name'), '_render_field'));
        $this->assertEquals('`first_name` `name`',      PHPUnitUtil::callProtectedMethod($this->q()->field(['name' => 'first_name']), '_render_field'));
        $this->assertEquals(
            '`employee`.`first_name` `name`',
            PHPUnitUtil::callProtectedMethod($this->q()->field(['name'=>'first_name'],'employee'), '_render_field')
        );
        $this->assertEquals('*',                        PHPUnitUtil::callProtectedMethod($this->q(), '_render_field'));
        $this->assertEquals('id',                       PHPUnitUtil::callProtectedMethod($this->q(['defaultField' => 'id']), '_render_field'));
        $this->assertEquals('*',                        PHPUnitUtil::callProtectedMethod($this->q()->field('*'), '_render_field'));
        $this->assertEquals('first_name.employee',      PHPUnitUtil::callProtectedMethod($this->q()->field('first_name.employee'), '_render_field'));
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
     * @covers ::render
     */
    public function testBasicRender()
    {
        $this->assertEquals(
            'select `name` from `employee`',
            (new Query())
            ->field('name')->table('employee')
            ->render()
        );
        $this->assertEquals(
            'select `employee`.`name` from `employee`,`jobs`',
            (new Query())
            ->field('name','employee')->table('employee')->table('jobs')
            ->render()
        );

        $this->assertEquals(
            'select now()',
            (new Query())
            ->field(new Expression('now()'))
            ->render()
        );

        $this->assertEquals(
            'select `name` from `employee` `e`',
            (new Query())
            ->field('name')->table('employee', 'e')
            ->render()
        );
        $this->assertEquals(
            'select * from `employee` `e`',
            (new Query())
            ->table('employee', 'e')
            ->render()
        );
    }

    /**
     * @covers ::render
     */
    public function testBasicRenderSubquery()
    {
        $q = (new Query())->table('employee');

        $this->assertEquals(
            'select `name` from (select * from `employee`)',
            (new Query())
            ->field('name')->table($q)
            ->render()
        );

        $query = new Query();
        $query->table('user');

        $age = new Expression("coalesce([age], [default_age])");
        $age['age'] = new Expression("year(now()) - year(birth_date)");
        $age['default_age'] = 18;

        $query -> field($age, 'calculated_age');

        $this->assertEquals(
            'select coalesce(year(now()) - year(birth_date), :a) `calculated_age` from `user`',
            $query->render()
        );


    }

    public function testUnionQuery()
    {
        $q1 = (new Query())
            ->table('sales')
            ->field('date')
            ->field('amount',null,'debit');

        $this->assertEquals(
            'select `date`,`amount` `debit` from `sales`',
            $q1->render()
        );

        $q2 = (new Query())
            ->table('purchases')
            ->field('date')
            ->field('amount',null,'credit');

        $this->assertEquals(
            'select `date`,`amount` `credit` from `purchases`',
            $q2->render()
        );

        $u = (new Expression("([] union []) derrivedTable", [$q1, $q2]));

        $this->assertEquals(
            '((select `date`,`amount` `debit` from `sales`) union (select `date`,`amount` `credit` from `purchases`)) derrivedTable',
            $u->render()
        );

        $q = (new Query())
            ->field('date,debit,credit')
            ->table($u)
            ;

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

        $query = new Query();
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
        $this->assertEquals('where `id` = :a',            $this->q(['template'=>'[where]'])->where('id',1)->render());
        $this->assertEquals('where `user`.`id` = :a',     $this->q(['template'=>'[where]'])->where('user.id',1)->render());
        $this->assertEquals('where `db`.`user`.`id` = :a',$this->q(['template'=>'[where]'])->where('db.user.id',1)->render());
        $this->assertEquals('where `id` > :a',            $this->q(['template'=>'[where]'])->where('id','>',1)->render());
        $this->assertEquals('where `id` in (:a,:b)',      $this->q(['template'=>'[where]'])->where('id','in',[1,2])->render());
        $this->assertEquals('where `id` is :a',           $this->q(['template'=>'[where]'])->where('id','is',null)->render());
        $this->assertEquals('where `id` is not :a',       $this->q(['template'=>'[where]'])->where('id!=',null)->render());
        $this->assertEquals('where `id` is not :a',       $this->q(['template'=>'[where]'])->where('id<>',null)->render());
        $this->assertEquals('where now()',                $this->q(['template'=>'[where]'])->where('now()')->render());
        $this->assertEquals('where now() = :a',           $this->q(['template'=>'[where]'])->where('now()',1)->render());
        $this->assertEquals('where now = :a',             $this->q(['template'=>'[where]'])->where(new Expression('now'),1)->render());
        $this->assertEquals('where `a` = :a and `b` = :b',$this->q(['template'=>'[where]'])->where('a',1)->where('b',2)->render());
    }

    /**
     * @covers ::having
     */
    public function testBasicHaving()
    {
        $this->assertEquals('having `id` = :a',            $this->q(['template'=>'[having]'])->having('id',1)->render());
        $this->assertEquals('having `id` > :a',            $this->q(['template'=>'[having]'])->having('id','>',1)->render());
        $this->assertEquals('where `id` = :a having `id` > :b',
            $this->q(['template'=>'[where][having]'])->where('id',1)->having('id>',1)->render());
    }

    /**
     * Combined execution of where() clauses
     */
    public function testCombinedWhere()
    {
        $this->assertEquals(
            'select `name` from `employee` where `a` = :a',
            (new Query())
            ->field('name')->table('employee')->where('a',1)
            ->render()
        );

        $this->assertEquals(
            'select `name` from `employee` where `employee`.`a` = :a',
            (new Query())
            ->field('name')->table('employee')->where('employee.a',1)
            ->render()
        );

        /*
        $this->assertEquals(
            'select `name` from `db`.`employee` where `db`.`employee`.`a` = :a',
            (new Query())
            ->field('name')->table('db.employee')->where('db.employee.a',1)
            ->render()
        );
         */

        $this->assertEquals(
            'delete from `employee` where `employee`.`a` = :a',
            (new Query())
            ->selectTemplate('delete')
            ->field('name')->table('employee')->where('employee.a',1)
            ->render()
        );
    }

    public function testOrWhere()
    {
        $this->assertEquals(
            'select `name` from `employee` where (`a` = :a or `b` = :b)',
            (new Query())
            ->field('name')->table('employee')->where([['a',1],['b',1]])
            ->render()
        );

        $this->assertEquals(
            'select `name` from `employee` where (`a` = :a or a=b)',
            (new Query())
            ->field('name')->table('employee')->where([['a',1],'a=b'])
            ->render()
        );
    }

    public function testNestedOrAnd()
    {

        $q = new Query();
        $q->table('employee')->field('name');
        $q->where(
            $q
                ->orExpr()
                ->where('a',1)
                ->where('b',1)
        );

        $this->assertEquals(
            'select `name` from `employee` where (`a` = :a or `b` = :b)',
            $q->render()
        );

        $q = new Query();
        $q->table('employee')->field('name');
        $q->where(
            $q
                ->orExpr()
                ->where('a',1)
                ->where('b',1)
                ->where($q->andExpr()
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
