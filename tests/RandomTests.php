<?php

namespace atk4\dsql\tests;

use atk4\dsql\Query;

/**
 * @coversDefaultClass \atk4\dsql\Query
 */
class RandomTests extends \atk4\core\PHPUnit_AgileTestCase
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

    public function testMiscInsert()
    {
        $data = [
            'id'                    => null,
            'system_id'             => '3576',
            'system'                => null,
            'created_dts'           => 123,
            'contractor_from'       => null,
            'contractor_to'         => null,
            'vat_rate_id'           => null,
            'currency_id'           => null,
            'vat_period_id'         => null,
            'journal_spec_id'       => '147735',
            'job_id'                => '9341',
            'nominal_id'            => null,
            'root_nominal_code'     => null,
            'doc_type'              => null,
            'is_cn'                 => 'N',
            'doc_date'              => null,
            'ref_no'                => '940 testingqq11111',
            'po_ref'                => null,
            'total_gross'           => '100.00',
            'total_net'             => null,
            'total_vat'             => null,
            'exchange_rate'         => null,
            'note'                  => null,
            'archive'               => 'N',
            'fx_document_id'        => null,
            'exchanged_total_net'   => null,
            'exchanged_total_gross' => null,
            'exchanged_total_vat'   => null,
            'exchanged_total_a'     => null,
            'exchanged_total_b'     => null,
        ];
        $q = $this->q();
        $q->mode('insert');
        foreach ($data as $key => $val) {
            $q->set($data);
        }
        $this->assertEquals("insert into  (`id`,`system_id`,`system`,`created_dts`,`contractor_from`,`contractor_to`,`vat_rate_id`,`currency_id`,`vat_period_id`,`journal_spec_id`,`job_id`,`nominal_id`,`root_nominal_code`,`doc_type`,`is_cn`,`doc_date`,`ref_no`,`po_ref`,`total_gross`,`total_net`,`total_vat`,`exchange_rate`,`note`,`archive`,`fx_document_id`,`exchanged_total_net`,`exchanged_total_gross`,`exchanged_total_vat`,`exchanged_total_a`,`exchanged_total_b`) values (NULL,'3576',NULL,123,NULL,NULL,NULL,NULL,NULL,'147735','9341',NULL,NULL,NULL,'N',NULL,'940 testingqq11111',NULL,'100.00',NULL,NULL,NULL,NULL,'N',NULL,NULL,NULL,NULL,NULL,NULL) [:ad, :ac, :ab, :aa, :z, :y, :x, :w, :v, :u, :t, :s, :r, :q, :p, :o, :n, :m, :l, :k, :j, :i, :h, :g, :f, :e, :d, :c, :b, :a]", $q->getDebugQuery());
    }
}
