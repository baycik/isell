<?php

/* Group Name: Склад
 * User Level: 2
 * Plugin Name: Зонирование склада
 * Plugin URI: 
 * Version: 1.0
 * Description: Зонирование склада
 * Author: baycik 2020
 * Author URI: 
 */

class StockSectorManager extends Catalog{
    
    public function install(){
	$install_file=__DIR__."/../install/install.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($install_file);
    }
    
    public function uninstall(){
	$uninstall_file=__DIR__."/../install/uninstall.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($uninstall_file);
    }
    
    public function viewCreate( string $doc_ids, string $title='' ){
        $view=[
            'head'=>(object)[
                'issuer'=>$this->Hub->svar('user_sign'),
                'number'=>1,
                'date'=>date('H:i d.m.Y'),
                'title'=>$title
            ],
            'bills'=>[],
            'rows'=>[]
        ];
        $sql_entries_get="
            SELECT
                se.product_sector,
                dl.doc_num,
                pl.product_code,
                pl.ru product_name,
                pl.product_unit,
                de.product_quantity,
                se.product_quantity product_leftover
            FROM
                document_entries de
                    JOIN
                document_list dl USING(doc_id)
                    JOIN
                stock_entries se USING(product_code)
                    JOIN
                prod_list pl USING(product_code)
            WHERE
                doc_id IN ($doc_ids)
            ORDER BY
                product_sector,product_code,doc_num
            ";
        $view['rows']=$this->get_list($sql_entries_get);
        if(!$view['rows']){
            $view['rows']=[[]];
        }
        
        $sql_bills_get="
            SELECT
                dl.doc_num,
                DATE_FORMAT(dl.cstamp,'%d.%m.%Y') doc_date_dot,
                ct.label pcomp_label,
                dl.doc_data doc_comment,
                COUNT(doc_entry_id) doc_entry_count
            FROM
                document_entries de
                    JOIN
                document_list dl USING(doc_id)
                    JOIN
                companies_list cl ON cl.company_id=dl.passive_company_id
                    JOIN
                companies_tree ct USING(branch_id)
            WHERE
                doc_id IN ($doc_ids)
            GROUP BY doc_id
            ORDER BY
                doc_num
            ";
        $view['bills']=$this->get_list($sql_bills_get);
        if(!$view['bills']){
            $view['bills']=[[]];
        }
        
        $abbr=[];
        foreach($view['bills'] as $bill){
            $abbr[$bill->doc_num]='';
            foreach( explode( " ",$bill->pcomp_label) as $word){
                $abbr[$bill->doc_num] .= mb_substr($word, 0, 1, 'utf-8');
            }
        }
        foreach($view['rows'] as &$row){
            $row->doc_num=$row->doc_num." ".$abbr[$row->doc_num];
        }
        return (object)$view;
    }
    
    
    public function viewOut( string $doc_ids, string $out_type='.print', string $title ){
        $this->Hub->set_level(2);
        $view=$this->viewCreate( $doc_ids, $title );
	$dump=[
	    'tpl_files'=>'../../plugins/StockSectorManager/views/stockBill.xlsx',
	    'title'=>"Сводная складская накладная",
	    'user_data'=>[
		'email'=>$this->Hub->svar('pcomp')?$this->Hub->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'view'=>$view
	];
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
    
}