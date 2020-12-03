<?php

trait DocumentBaseTransactions {
    protected $document_transaction_scheme = [
        [
            'trans_role' => 'total',
            'acc_debit_code' => '0',
            'acc_credit_code' => '0',
            'description_tpl' => 'Template {$doc->doc_num}'
        ]
    ];

    protected function transCommitedChangeRefresh($field, $new_is_commited, $document_properties) {
        //$this->document_properties=$document_properties;
        if ($new_is_commited) {
            $this->transSchemeCreate();
        } else {
            $this->transSchemeDelete();
        }
    }

    protected function transSchemeCreate() {
        $trans_list=$this->transSchemeCalculate($this->document_transaction_scheme);
        $AccountsCore = $this->Hub->load_model("AccountsCore");
        foreach($trans_list as $trans){
            $trans_id=$AccountsCore->transCreate($trans);
            /* COMPABILITY PATCH */
            $this->create('document_trans', ['trans_id' => $trans_id, 'doc_id' => $trans['doc_id'], 'trans_role' => $trans['trans_role'], 'type' => "{$trans['acc_debit_code']}_{$trans['acc_credit_code']}"]);
        }
    }

    protected function transSchemeUpdate() {
        $document_transaction_scheme=$this->document_transaction_scheme;
        $doc_id=$this->doc('doc_id');
        $trans_list=$this->transSchemeCalculate($document_transaction_scheme);
        $AccountsCore = $this->Hub->load_model("AccountsCore");
        foreach($trans_list as $trans){
            //print_r($trans);
            $sql_find_trans_id="
                SELECT COALESCE( 
                (SELECT trans_id FROM acc_trans WHERE doc_id='$doc_id' AND trans_role='{$trans['trans_role']}'),
                (SELECT trans_id FROM document_trans WHERE doc_id='$doc_id' AND trans_role='{$trans['trans_role']}'),
                0 ) trans_id";
            $trans_id=$this->get_value($sql_find_trans_id);
            $AccountsCore->transUpdate($trans_id,$trans);
        }
    }

    protected function transSchemeDelete() {
        $doc_id=$this->doc('doc_id');
        $sql="SELECT
                trans_id
            FROM
                acc_trans
            WHERE
                doc_id='$doc_id'
            ";
        $delete_list=$this->get_list($sql);
        if( !count($delete_list) ){
            /* COMPABILITY PATCH */
            $sql="SELECT
                    trans_id
                FROM
                    document_trans
                WHERE
                    doc_id='$doc_id'
                ";
            $delete_list=$this->get_list($sql);
        }
        $AccountsCore = $this->Hub->load_model("AccountsCore");
        foreach($delete_list as $trans){
            $AccountsCore->transDelete($trans->trans_id);
        }
    }
    
    private function transSchemeCalculate($document_transaction_scheme){
        $calculated_trans_list=[];
        if ($this->Hub->pcomp('curr_code') == $this->Hub->acomp('curr_code')) {
            $doc_ratio = 0;
        } else {
            $doc_ratio = $this->doc('doc_ratio');
        }
        $foot = $this->footGet($this->doc_id);
        foreach ($document_transaction_scheme as $transTemplate) {
            $amount = $foot->{$transTemplate['trans_role']} ?? null;
            if ($amount == null) {
                continue;
            }
            $amount_alt = $doc_ratio > 0 ? $amount / $doc_ratio : 0;
            $description = $this->transSchemeRenderTpl($transTemplate['description_tpl']);
            $trans = [
                'doc_id' => $this->doc('doc_id'),
                'active_company_id' => $this->doc('active_company_id'),
                'passive_company_id' => $this->doc('passive_company_id'),
                'acc_debit_code'=>$transTemplate['acc_debit_code'],
                'acc_credit_code'=>$transTemplate['acc_credit_code'],
                'cstamp' => $this->doc('cstamp'),
                'amount' => $amount,
                'amount_alt' => $amount_alt,
                'trans_role'=>$transTemplate['trans_role'],
                'description' => $description
            ];
            $calculated_trans_list[]=$trans;
        }
        return $calculated_trans_list;
    }

    private function transSchemeRenderTpl($template) {
        $pcomp = $this->Hub->svar('pcomp')??null;
        $acomp = $this->Hub->svar('acomp')??null;
        $doc = $this->document_properties;
        $doc_type_name=$this->doc_type_name;
        $renderer= function () use ($template,$acomp,$pcomp,$doc,$doc_type_name) {
            error_reporting(E_ALL ^ E_NOTICE);
                $rendered=eval('return "' . addslashes($template) . '";');
            error_reporting(E_ALL );
            return $rendered;
        };
        return $renderer();
    }    
}