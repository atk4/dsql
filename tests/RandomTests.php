<?php

namespace atk4\dsql\tests;

use atk4\dsql\Query;

/**
 * @coversDefaultClass \atk4\dsql\Query
 */
class RandomTests extends \PHPUnit_Framework_TestCase
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
        $data = array (
            'id'                    => NULL,
            'system_id'             => '3576',
            'system'                => NULL,
            'created_dts'           => 123,
            'contractor_from'       => NULL,
            'contractor_to'         => NULL,
            'vat_rate_id'           => NULL,
            'currency_id'           => NULL,
            'vat_period_id'         => NULL,
            'journal_spec_id'       => '147735',
            'job_id'                => '9341',
            'nominal_id'            => NULL,
            'root_nominal_code'     => NULL,
            'doc_type'              => NULL,
            'is_cn'                 => 'N',
            'doc_date'              => NULL,
            'ref_no'                => '940 testingqq11111',
            'po_ref'                => NULL,
            'total_gross'           => '100.00',
            'total_net'             => NULL,
            'total_vat'             => NULL,
            'exchange_rate'         => NULL,
            'note'                  => NULL,
            'archive'               => 'N',
            'fx_document_id'        => NULL,
            'exchanged_total_net'   => NULL,
            'exchanged_total_gross' => NULL,
            'exchanged_total_vat'   => NULL,
            'exchanged_total_a'     => NULL,
            'exchanged_total_b'     => NULL,
        );
        $q = $this->q();
        $q->mode('insert');
        foreach ($data as $key => $val) {
            $q->set($data);
        }
        $this->assertEquals("insert into  (`id`,`system_id`,`system`,`created_dts`,`contractor_from`,`contractor_to`,`vat_rate_id`,`currency_id`,`vat_period_id`,`journal_spec_id`,`job_id`,`nominal_id`,`root_nominal_code`,`doc_type`,`is_cn`,`doc_date`,`ref_no`,`po_ref`,`total_gross`,`total_net`,`total_vat`,`exchange_rate`,`note`,`archive`,`fx_document_id`,`exchanged_total_net`,`exchanged_total_gross`,`exchanged_total_vat`,`exchanged_total_a`,`exchanged_total_b`) values (NULL,'3576',NULL,123,NULL,NULL,NULL,NULL,NULL,'147735','9341',NULL,NULL,NULL,'N',NULL,'940 testingqq11111',NULL,'100.00',NULL,NULL,NULL,NULL,'N',NULL,NULL,NULL,NULL,NULL,NULL) [:ad, :ac, :ab, :aa, :z, :y, :x, :w, :v, :u, :t, :s, :r, :q, :p, :o, :n, :m, :l, :k, :j, :i, :h, :g, :f, :e, :d, :c, :b, :a]", $q->getDebugQuery());
    }
}
