<?php

declare(strict_types=1);

namespace Atk4\Dsql\Tests;

use Atk4\Core\AtkPhpunit;
use Atk4\Dsql\Mysql;
use Atk4\Dsql\Oracle;
use Atk4\Dsql\Postgresql;
use Atk4\Dsql\Query;
use Atk4\Dsql\Sqlite;

/**
 * @coversDefaultClass \Atk4\Dsql\Query
 */
class RandomTest extends AtkPhpunit\TestCase
{
    public function q(...$args)
    {
        return new Query(...$args);
    }

    public function testMiscInsert(): void
    {
        $data = [
            'id' => null,
            'system_id' => '3576',
            'system' => null,
            'created_dts' => 123,
            'contractor_from' => null,
            'contractor_to' => null,
            'vat_rate_id' => null,
            'currency_id' => null,
            'vat_period_id' => null,
            'journal_spec_id' => '147735',
            'job_id' => '9341',
            'nominal_id' => null,
            'root_nominal_code' => null,
            'doc_type' => null,
            'is_cn' => 'N',
            'doc_date' => null,
            'ref_no' => '940 testingqq11111',
            'po_ref' => null,
            'total_gross' => '100.00',
            'total_net' => null,
            'total_vat' => null,
            'exchange_rate' => null,
            'note' => null,
            'archive' => 'N',
            'fx_document_id' => null,
            'exchanged_total_net' => null,
            'exchanged_total_gross' => null,
            'exchanged_total_vat' => null,
            'exchanged_total_a' => null,
            'exchanged_total_b' => null,
        ];
        $q = $this->q();
        $q->mode('insert');
        foreach ($data as $key => $val) {
            $q->set($key, $val);
        }
        $this->assertSame('insert into  ("id","system_id","system","created_dts","contractor_from","contractor_to","vat_rate_id","currency_id","vat_period_id","journal_spec_id","job_id","nominal_id","root_nominal_code","doc_type","is_cn","doc_date","ref_no","po_ref","total_gross","total_net","total_vat","exchange_rate","note","archive","fx_document_id","exchanged_total_net","exchanged_total_gross","exchanged_total_vat","exchanged_total_a","exchanged_total_b") values (:a,:b,:c,:d,:e,:f,:g,:h,:i,:j,:k,:l,:m,:n,:o,:p,:q,:r,:s,:t,:u,:v,:w,:x,:y,:z,:aa,:ab,:ac,:ad)', $q->render());
    }

    /**
     * confirms that group concat works for all the SQL vendors we support.
     */
    public function _groupConcatTest($q, $query)
    {
        $q->table('people');
        $q->group('age');

        $q->field('age');
        $q->field($q->groupConcat('name', ','));

        $q->groupConcat('name', ',');

        $this->assertSame($query, $q->render());
    }

    public function testGroupConcat(): void
    {
        $this->_groupConcatTest(
            new Mysql\Query(),
            'select `age`,group_concat(`name` separator :a) from `people` group by `age`'
        );

        $this->_groupConcatTest(
            new Sqlite\Query(),
            'select "age",group_concat("name", :a) from "people" group by "age"'
        );

        $this->_groupConcatTest(
            new Postgresql\Query(),
            'select "age",string_agg("name", :a) from "people" group by "age"'
        );

        $this->_groupConcatTest(
            new Oracle\Query(),
            'select "age",listagg("name", :a) within group (order by "name") from "people" group by "age"'
        );
    }
}
