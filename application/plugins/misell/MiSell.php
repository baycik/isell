<?php
/* User Level: 1
 * Group Name: Мобильное
 * Plugin Name: MiSell
 * Plugin URI: isellsoft.com
 * Version: 1
 * Description: Мобильное приложение для приема заказов
 * Author: baycik 2016
 * Author URI: isellsoft.com
 */
include 'models/Catalog.php';
class MiSell extends Catalog{
    function __construct($Base){
	ob_start('ob_gzhandler');
	$user_id=$Base->svar('user_id');
	if( !$user_id ){
	    $user_login=$this->request('user_login');
	    $user_pass=$this->request('user_pass');
	    $User=$Base->load_model('User');
	    if( $user_login && $user_pass && $User->SignIn($user_login,$user_pass) ){
		return;
	    }
	    include 'login.html';
	    exit;
	}
    }
    public function index(){
	$this->Base->set_level(1);
	include_once 'libraries/report/RainTpl.php';
	raintpl::configure( 'tpl_dir', 'application/plugins/MiSell/' );
	raintpl::configure( 'cache_dir', 'application/plugins/MiSell/cache/' );
	$tplData=$this->getTplData();
	$this->rain=new RainTPL();
	$this->rain->assign('d',$tplData);
	$this->rain->assign('db', json_encode($tplData) );
	$this->rain->assign('scripts', file_get_contents('application/plugins/MiSell/scripts.html') );
	$this->rain->draw('MiSell');
    }
    public function logout(){
	$User=$this->Base->load_model('User');
	$User->SignOut();
	header("Location: ./");
	exit();
    }
    public function getTplData(){
        $d=array();
        $d['stock_tree']=$this->treeFetch('stock_tree',0);
        $d['companies_tree']=$this->getCompaniesTree();
        $d['user_sign']=$this->Base->svar('user_sign');
        return $d;
    }
    public function getCompaniesTree(){
	$level=$this->Base->svar('user_level');
	$assigned_path=  $this->Base->svar('user_assigned_path');
	$companies_folder_list=$this->get_list("SELECT branch_id,label,is_leaf FROM companies_tree WHERE level<=$level AND path LIKE '$assigned_path%'");
	$tree=[];
	foreach($companies_folder_list as $folder){
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
			AND IF( {$folder->is_leaf},branch_id='{$folder->branch_id}',parent_id='{$folder->branch_id}')";
	    $companies_list=$this->get_list($sql);
	    if($companies_list){
		//$folder->children=$companies_list;
		$tree[$folder->label]=$companies_list;
	    }
	}
	return $tree;
    }
//    public function getCompaniesTree1(){
//        $list=$this->getManagerClients($this->Base->svar('user_id'));
//        $this->Base->svar('allowed_comps',$list);
//        $tree=array();
//        $folder="";
//        foreach($list as $item){
//            if($item->folder!=$folder){
//                $folder=$item->folder;
//                $tree["$folder"]=array();
//            }
//            $tree["$folder"][]=$item;
//        }
//        return $tree;
//    }
//    private function getManagerClients( $user_id ){
//        $sql="SELECT 
//                COALESCE(ct2.label,'...') folder,
//                ct.label label,
//                company_id,
//                company_person,
//                company_mobile,
//                company_address,
//                company_description
//            FROM
//                companies_list cl
//                    JOIN
//                companies_tree ct ON cl.branch_id = ct.branch_id
//                    LEFT JOIN
//                companies_tree ct2 ON ct.parent_id = ct2.branch_id
//            WHERE
//                manager_id = $user_id
//            ORDER BY ct2.label";
//        return $this->get_list($sql);
//    }
    public function suggest(){
	$q=$this->request('q');
	$parent_id=$this->request('parent_id');
	$company_id=$this->request('company_id','int',0);
        $clues=explode(' ',$q);
	$usd_ratio=$this->Base->pref('usd_ratio');
	$cases=[];
	if($parent_id){
	    $parent_ids=$this->treeGetSub('stock_tree',$parent_id);
	    $cases[]="(parent_id='".implode("' OR parent_id='",$parent_ids)."')";
	}
        foreach($clues as $clue){
            $cases[]="(product_code LIKE '%$clue%' OR ru LIKE '%$clue%')";
        }
        $where=implode(' AND ',$cases);
        $sql="SELECT 
                    product_code code,
                    ru name,
                    product_spack spack,
                    product_quantity,
                    product_unit unit,
		    product_img,
		    GET_PRICE(product_code,$company_id,$usd_ratio) price
                FROM
                    prod_list
                JOIN
                    stock_entries se USING (product_code)
                WHERE $where
                ORDER BY fetch_count - DATEDIFF(NOW(), fetch_stamp) DESC, product_code
                LIMIT 20";
        return $this->get_list($sql);
    }
//    public function orderCalculate($order,$company_id){
//        if( !$this->checkCompanyId($company_id) ){
//            return "Can't use this client!!!";
//        }
//        $this->Base->LoadClass('Pref');
//        $prefs=$this->Base->Pref->getPrefs('dollar_ratio');
//        foreach($order as $product_code=>$entry){
//            $order[$product_code]['price']=$this->orderGetPrice($product_code, $company_id,$prefs['dollar_ratio']);
//        }
//        return $order;
//    }
    public function orderSend(){
	$order=$this->request('order','json');
	$company_id=$this->request('company_id');
	$comment=$this->request('comment');
//         if( !$this->checkCompanyId($company_id) ){
//            return "Can't use this client!!!";
//        }
	$Company=$this->Base->load_model('Company');
	$Company->selectActiveCompany(1);
	$Company->selectPassiveCompany($company_id);
        $this->orderAnnounceRecieved($comment);
	$Document=$this->Base->load_model('DocumentItems');
	$Document->createDocument(1);
	$Document->headUpdate( "doc_data",$comment.' (заказ с приложения)' );
        foreach($order as $product_code=>$entry){
            $Document->entryAdd( $product_code, $entry['qty'] );
        }
        return true;
    }
    public function orderAnnounceRecieved($comment){
        $pcomp_name=$this->Base->pcomp('label');
        $user_sign=$this->Base->svar('user_sign');
	$Utils=$this->Base->load_model('Utils');
        $text="Пользователем $user_sign, был прислан заказ для $pcomp_name в ".date("d.m.Y H:i");
        $Utils->sendEmail( "krim@nilson.ua", "Мобильный заказ от $user_sign для $pcomp_name", $text, NULL, 'nocopy' );
        $Utils->sendSms("+79787288233",$text.$comment);
        $Utils->sendSms("+79788440954",$text.$comment);
        $Utils->sendSms("+79788308996",$text.$comment);
    }
//    private function orderGetPrice($product_code,$company_id,$dollar_ratio){
//        $sql="SELECT 
//                discount
//            FROM
//                companies_discounts cd
//                    JOIN
//                stock_tree st ON (cd.branch_id = st.top_id)
//                    JOIN
//                stock_entries se ON (st.branch_id = se.parent_id)
//            WHERE
//                se.product_code = '$product_code'
//                    AND cd.company_id = '$company_id'";
//        $discount=$this->Base->get_row($sql,0);
//        $discount!==NULL?$discount:1;
//        $row=$this->Base->get_row("SELECT * FROM price_list WHERE '$product_code'=product_code OR '$product_code' RLIKE CONCAT('^',product_code,'$')");
//        $sell=$discount*($row['price_uah']?$row['price_uah']:$row['price_usd']*$dollar_ratio);
//        return round($sell,2);
//    }
//    private function checkCompanyId($company_id){
//        $list=$this->Base->svar('allowed_comps');
//        foreach($list as $comp){
//            if($comp->company_id===$company_id){
//                return true;
//	    }
//        }
//        return false;
//    }
}
?>
