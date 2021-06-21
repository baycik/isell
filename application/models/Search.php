<?php

class Search extends Catalog{
    public function comboSearch( string $q ){
        session_write_close();
        return [
            'results'=>[
                'prods'=>[
                    'name'=>"Товары",
                    'results'=>$this->findProducts($q)
                ],
                'pcomps'=>[
                    'name'=>"Контрагенты",
                    "results"=>$this->findPassiveCompanies($q)
                ],
                'docs'=>[
                    'name'=>"Документы",
                    'results'=>$this->findDocuments($q)
                ]
            ]
        ];
    }
    
    
    private function findPassiveCompanies($q){
        $pcomps=$this->Hub->load_model('Company')->listFetch($q,'',false,0,3);
        foreach($pcomps as $pcomp){
            $pcomp->title=$pcomp->label;
            $pcomp->description=$pcomp->company_name;
            $pcomp->url="javascript:App.search.findPcomp({$pcomp->company_id});";
        }
        return $pcomps;
    }
    
    private function findDocuments($q){
        $andwhere='';
	$assigned_path=  $this->Hub->svar('user_assigned_path');
	if( $assigned_path ){
	    $andwhere.=" AND path LIKE '$assigned_path%'";
	}
        $nums_filter="LIKE '%". str_replace('','%',$q)."%'";
        $sql="
	    SELECT 
		doc_id,
		doc_num,
                DATE_FORMAT(dl.cstamp,'%d.%m.%Y') doc_date,
                passive_company_id,
                active_company_id,
                (SELECT doc_type_name FROM document_types WHERE doc_type=dl.doc_type) doc_type_name,
                (SELECT SUM(ROUND(invoice_price*product_quantity*(1+dl.vat_rate/100),2)) FROM document_entries de WHERE de.doc_id=dl.doc_id)  doc_total,
                (SELECT label FROM companies_tree JOIN companies_list USING(branch_id) WHERE company_id=passive_company_id) label
	    FROM 
		document_list dl
		    LEFT JOIN
		document_view_list dv USING(doc_id)
	    WHERE 
                (view_num $nums_filter OR doc_num $nums_filter) 
                    AND doc_type IN (1,2,3,4,-1,-2)
                    $andwhere
            GROUP BY doc_id
	    ORDER BY dl.cstamp DESC
	    LIMIT 3";
        $docs= $this->get_list($sql);
        foreach($docs as $doc){
            $doc->title="$doc->doc_type_name №$doc->doc_num от $doc->doc_date";
            $doc->description="<div style='width:200px;display:inline-block'>{$doc->label}</div> <div style='display:inline-block;color:blue'>$doc->doc_total</div>";
            $doc->url="javascript:App.search.findDoc({$doc->doc_id},{$doc->active_company_id},{$doc->passive_company_id});";
        }
        return $docs;
    }
    
    private function findProducts($q){
        $doc_id=$this->Hub->svar('doc_id');
        $products=$this->Hub->load_model('DocumentItems')->suggestFetch($q,0,3,$doc_id);
        foreach($products as $prod){
            $prod->title="$prod->product_name";
            $prod->description="
                <div style='width:100px;display:inline-block'>{$prod->product_code}</div> <div style='width:100px;display:inline-block;color:green'>$prod->leftover$prod->product_unit</div> <div style='display:inline-block;color:blue'>$prod->product_price_total</div>
                    ";
            $prod->image="Storage/image_flush/?size=30x30&path=/dynImg/$prod->product_img";
            $prod->url="javascript:App.search.findProd($prod->product_id,'$prod->product_code');";
        }
        return $products;
    }
    
    
}