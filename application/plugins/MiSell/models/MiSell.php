<?php
/* User Level: 1
 * Group Name: Мобильное
 * Plugin Name: MiSell
 * Plugin URI: isellsoft.com
 * Version: 1.1
 * Description: Мобильное приложение для приема заказов
 * Author: baycik 2016
 * Author URI: isellsoft.com
 */
//require_once 'application/models/PluginManager.php';
class MiSell extends PluginManager{
    function __construct(){
	ob_start('ob_gzhandler');
    }
//    public function init(){
//	$user_id=$this->Hub->svar('user_id');
//	if( !$user_id ){
//	    $user_login=$this->request('user_login');
//	    $user_pass=$this->request('user_pass');
//	    $User=$this->Hub->load_model('User');
//	    if( $user_login && $user_pass && $User->SignIn($user_login,$user_pass) ){
//		return;
//	    }
//	    include 'login.html';
//	    exit;
//	}	
//    }
    
    public $index=[];
    public function index(){
	$this->Hub->set_level(1);
	include_once 'application/libraries/report/RainTpl.php';
	raintpl::configure( 'tpl_dir', 'application/plugins/MiSell/' );
	raintpl::configure( 'cache_dir', 'application/plugins/MiSell/cache/' );
	$tplData=$this->getTplData();
	$this->rain=new RainTPL();
	$this->rain->assign('d',$tplData);
	$this->rain->assign('db', json_encode($tplData) );
	$this->rain->assign('scripts', file_get_contents('application/plugins/MiSell/scripts.html') );
	$this->rain->draw('MiSell');
    }
    
    public $logout=[];
    public function logout(){
	$User=$this->Hub->load_model('User');
	$User->SignOut();
	header("Location: ./");
	exit();
    }
    
    public $getTplData=[];
    public function getTplData(){
        $d=array();
        $d['stock_tree']=$this->treeFetch('stock_tree',0);
        $d['companies_tree']=$this->getCompaniesTree();
        $d['user_sign']=$this->Hub->svar('user_sign');
        return $d;
    }
    
    public $getCompaniesTree=[];
    public function getCompaniesTree(){
	$level=$this->Hub->svar('user_level');
	$assigned_path=  $this->Hub->svar('user_assigned_path');
	$companies_folder_list=$this->get_list("SELECT branch_id,label FROM companies_tree WHERE is_leaf=0 AND level<=$level AND path LIKE '$assigned_path%'");
	$tree=[];
	if( $companies_folder_list ){
	    foreach($companies_folder_list as $folder){
		$companies_list=$this->getCompaniesTreeLeafs($folder->branch_id, $level, false);
		if($companies_list){
		    $tree[$folder->label]=$companies_list;
		}
	    }
	} else {//if assignet_path pointing only to 1 client 
	    $tree['Все клиенты']=$this->getCompaniesTreeLeafs(0, $level, $assigned_path);
	}
	
	return $tree;
    }
    private function getCompaniesTreeLeafs($branch_id,$level,$assigned_path){
	$sql="SELECT
		    company_id,
		    label,
		    company_address,
		    company_person,
		    company_mobile,
		    company_description
		FROM
		    companies_tree 
			JOIN 
		    companies_list USING(branch_id) 
		WHERE 
		    is_leaf=1 
		    AND level<=$level 
		    AND IF( {$branch_id},parent_id='{$branch_id}',path ='$assigned_path')";
	return $this->get_list($sql);	
    }
    
    public $suggest=[];
    public function suggest(){
	$q=$this->request('q');
	$parent_id=$this->request('parent_id');
	$company_id=$this->request('company_id','int',0);
        $clues=explode(' ',$q);
	$usd_ratio=$this->Hub->pref('usd_ratio');
	$cases=[];
	if($parent_id){
	    $parent_ids=$this->treeGetSub('stock_tree',$parent_id);
	    $cases[]="(parent_id='".implode("' OR parent_id='",$parent_ids)."')";
	}
        foreach($clues as $clue){
            $cases[]="(product_code LIKE '%$clue%' OR ru LIKE '%$clue%')";
        }
        $where=implode(' AND ',$cases);
        $sql="
		SELECT
		    *,
		    GET_PRICE(code,$company_id,$usd_ratio) price
		FROM
		(SELECT 
                    product_code code,
                    ru name,
                    product_spack spack,
                    product_quantity,
                    product_unit unit,
		    product_img
                FROM
                    prod_list
                JOIN
                    stock_entries se USING (product_code)
                WHERE $where
                ORDER BY fetch_count - DATEDIFF(NOW(), fetch_stamp) DESC, product_code
                LIMIT 20) t";
        return $this->get_list($sql);
    }
    
    public $orderSend=[];
    public function orderSend(){
	$order=$this->request('order','json');
	$company_id=$this->request('company_id');
	$comment=$this->request('comment');
//         if( !$this->checkCompanyId($company_id) ){
//            return "Can't use this client!!!";
//        }
	$Company=$this->Hub->load_model('Company');
	$Company->selectActiveCompany(1);
	$Company->selectPassiveCompany($company_id);
        $this->orderAnnounceRecieved($comment);
	$Document=$this->Hub->load_model('DocumentItems');
	$Document->createDocument(1);
	$Document->headUpdate( "doc_data",$comment.' (заказ с приложения)' );
        foreach($order as $product_code=>$entry){
            $Document->entryAdd( $product_code, $entry['qty'] );
        }
        return true;
    }
    
    public $orderAnnounceRecieved=['string'];
    public function orderAnnounceRecieved($comment){
	$this->settings=$this->settingsDataFetch('MiSell');
        $pcomp_name=$this->Hub->pcomp('label');
        $user_sign=$this->Hub->svar('user_sign');
	$Utils=$this->Hub->load_model('Utils');
        $text="Пользователем $user_sign, был прислан заказ для $pcomp_name в ".date("d.m.Y H:i");
	if( isset($this->settings->plugin_settings->email) ){
	    $Utils->sendEmail( $this->settings->plugin_settings->email, "Мобильный заказ от $user_sign для $pcomp_name ", $text, NULL, 'nocopy' );
	}
	if( isset($this->settings->plugin_settings->phone) ){
	    $phones=  explode(',',preg_replace('|[^\d,]|', '', $this->settings->plugin_settings->phone));
	    foreach($phones as $phone){
		$Utils->sendSms($phone,"$text $comment");
	    }
	}
    }
}
