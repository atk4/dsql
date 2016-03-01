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
            'select now()  ',
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
     * @covers ::dump
     */
    public function testgetDebugQurey()
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
}
