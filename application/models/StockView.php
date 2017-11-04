<?php
require_once 'Stock.php';
class StockView extends Stock{
    public $stockViewGet=['parent_id'=>'int','sortby'=>'string','sortdir'=>'(ASC|DESC)','filter'=>'json','mode'=>'string','out_type'=>'string'];
    public function stockViewGet($parent_id,$sortby,$sortdir,$filter=null,$mode="simple",$out_type){
	$this->Hub->set_level(2);
        $blank_set=$this->Hub->pref('blank_set');
	$table=$this->listFetch($parent_id,0,10000,$sortby,$sortdir,$filter=null,$mode);
	foreach ($table as $row) {
            $row->product_quantity==0?$row->product_quantity='':'';
        }
	if( $mode=="simple" ){
	    $template_file='/StockValidation.xlsx';
	} else {
	    $template_file='/StockTable.xlsx';
	}
	$dump=[
	    'tpl_files'=>$blank_set.$template_file,
	    'title'=>"Залишки на складі",
	    'user_data'=>[
		'email'=>$this->Hub->svar('pcomp')?$this->Hub->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'view'=>[
		'p'=>$this->Hub->svar('pcomp'),
		'date'=>date('d.m.Y H:i'),
		'user_sign'=>$this->Hub->svar('user_sign'),
		'cat_name'=>$this->get_value("SELECT label FROM stock_tree WHERE branch_id='{$parent_id}'"),
		'stock'=>['rows'=>$table]
	    ]
	];
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
    
    public $stockViewFileGet=['parent_id'=>'int','out_type'=>'string'];
    public function stockViewFileGet($parent_id,$out_type){
	$page=1;
	$rows=10000;
	$having=$this->decodeFilterRules();
	$blank_set=$this->Hub->pref('blank_set');
	$table=$this->listFetch($page,$rows,$parent_id,$having);
	foreach ($table['rows'] as $row) {
            $row->product_quantity==0?$row->product_quantity='':'';
            $row->product_wrn_quantity==0?$row->product_wrn_quantity='':'';
            $row->m3==0?$row->m3='':'';
            $row->m1==0?$row->m1='':'';
        }
	$dump=[
	    'tpl_files'=>$blank_set.'/StockTable.xlsx',
	    'title'=>"Справочник товаров",
	    'user_data'=>[
		'email'=>$this->Hub->svar('pcomp')?$this->Hub->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'view'=>[
		'p'=>$this->Hub->svar('pcomp'),
		'date'=>date('d.m.Y H:i'),
		'user_sign'=>$this->Hub->svar('user_sign'),
		'cat_name'=>$this->get_value("SELECT label FROM stock_tree WHERE branch_id='{$parent_id}'"),
		'stock'=>$table
	    ]
	];
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
    
    public $stockMoveViewGet=['page'=>'int','rows'=>'int','out_type'=>'string'];
    public function stockMoveViewGet($page,$rows,$out_type){
	$having=$this->decodeFilterRules();
	$blank_set=$this->Hub->pref('blank_set');
	$dump=[
	    'tpl_files'=>$blank_set.'/StockMovements.xlsx',
	    'title'=>"Рух товарів",
	    'user_data'=>[
		'email'=>$this->Hub->svar('pcomp')?$this->Hub->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'view'=>[
		'date'=>date('d.m.Y H:i'),
		'user_sign'=>$this->Hub->svar('user_sign'),
		'table'=>$this->movementsFetch($page,$rows,$having)
	    ]
	];
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
}