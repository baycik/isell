<?php
/* User Level: 2
 * Group Name: Продажи
 * Plugin Name: CampaignManager
 * Version: 2019-01-05
 * Description: Расчет планов и бонусов менеджеров
 * Author: baycik 2019
 * Author URI: 
 * Trigger After: CampaignManager
 */
class CampaignManager extends Catalog{
    public $min_level=2;
    
    public function index(){
        $this->Hub->set_level(3);
        $this->load->view('campaign_manager.html');
    }
    
    public function install(){
        $this->Hub->set_level(4);
	$install_file=__DIR__."/../install/install.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($install_file);
    }
    
    public function uninstall(){
        $this->Hub->set_level(4);
	$uninstall_file=__DIR__."/../install/uninstall.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($uninstall_file);
    }
    
    public function campaignListFetch(){
        $this->Hub->set_level(3);
        $sql="SELECT * FROM plugin_campaign_list";
        return $this->get_list($sql);
    }
    
    public function campaignGet( int $campaign_id ){
        $this->Hub->set_level(3);
        $settings=$this->get_row("SELECT * FROM plugin_campaign_list WHERE campaign_id='$campaign_id'");
        //$settings->subject_manager_include=explode(',',$settings->subject_manager_include);
        return [
            'settings'=>$settings,
            'staff_list'=>$this->Hub->load_model("Pref")->getStaffList(),
            'bonuses'=>$this->bonusesGet( $campaign_id ),
            'stock_category_list'=>$this->treeFetch('stock_tree',0,'top')
        ];
    }
    
    public function campaignAdd( string $campaign_name ){
        $this->Hub->set_level(3);
        return $this->create('plugin_campaign_list',['campaign_name'=>$campaign_name,'liable_user_id'=>0,'subject_manager_include'=>0,'subject_manager_exclude'=>0]);
    }
    
    public function campaignRemove(int $campaign_id){
        $this->Hub->set_level(3);
        $this->query("DELETE p,b FROM plugin_campaign_bonus_periods p JOIN plugin_campaign_bonus b USING(campaign_bonus_id) WHERE campaign_id = $campaign_id");
        $this->delete('plugin_campaign_bonus',['campaign_id'=>$campaign_id]);
        return $this->delete('plugin_campaign_list',['campaign_id'=>$campaign_id]);
    }
    
    public function campaignUpdate(int $campaign_id,string $field,string $value){
        $this->Hub->set_level(3);
        return $this->update('plugin_campaign_list',[$field=>$value],['campaign_id'=>$campaign_id]);
    }
    
    private function clientListFilterGet($campaign_id){
        $this->Hub->set_level(2);
        $settings=$this->get_row("SELECT * FROM plugin_campaign_list WHERE campaign_id='$campaign_id'");
        $assigned_path=  $this->Hub->svar('user_assigned_path');
        $user_level=     $this->Hub->svar('user_level');
        $or_case=[];
        $and_case=[];
        $and_case[]=" level<= $user_level";
        if( $assigned_path ){
            $and_case[]=" path LIKE '%".str_replace(",", "%' OR path LIKE '%", $assigned_path)."%'";
        }
        if( $settings->subject_path_include ){
            $or_case[]=" path LIKE '%".str_replace(",", "%' OR path LIKE '%", $settings->subject_path_include)."%'";
        }
        if( $settings->subject_path_exclude ){
            $and_case[]=" path NOT LIKE '%".str_replace(",", "%' AND path NOT LIKE '%", $settings->subject_path_exclude)."%'";
        }
        if( $settings->subject_manager_include ){
            $or_case[]=" manager_id IN ($settings->subject_manager_include)";
        }
        if( $settings->subject_manager_exclude ){
            $and_case[]=" manager_id NOT IN ($settings->subject_manager_exclude)";
        }
        $where="";
        if( count($or_case) ){
            $where="(".implode(' OR ',$or_case).")";
        }
        if( count($and_case) ){
            if( count($or_case) ){
                $where.=" AND ";
            }
            $where.=implode(' AND ', $and_case);
        }
        return $where?$where:0;
    }
    
    public function clientListFetch(int $campaign_id, int $offset=0,int $limit=30,string $sortby='label',string $sortdir='ASC',array $filter){
        $this->Hub->set_level(3);
        $having=$this->makeFilter($filter);
        $where=$this->clientListFilterGet($campaign_id);
        $sql="
            SELECT 
                label,
                path
            FROM
                companies_list
                    JOIN
                companies_tree USING(branch_id)
            WHERE $where
            HAVING $having
            ORDER BY $sortby $sortdir
	    LIMIT $limit OFFSET $offset";
        return $this->get_list($sql);
    }
    
    public function bonusAdd( int $campaign_id ){
        $this->Hub->set_level(3);
        $sql="
            INSERT INTO 
                plugin_campaign_bonus
            SET
                campaign_id='$campaign_id',
                bonus_type='VOLUME',
                campaign_start_at=DATE_FORMAT(NOW(),'%Y-%m-%d 00:00:00'),
                campaign_finish_at=DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 YEAR),'%Y-%m-%d 23:59:59'),
                campaign_grouping_interval='NOGROUP',
                campaign_bonus_ratio1=0";
        $ok=$this->query($sql);
        $this->bonusPeriodsFill( $this->db->insert_id() );
        return $ok;
    }
    
    public function bonusUpdate( int $campaign_bonus_id, string $field, string $value){
        $this->Hub->set_level(3);
        $period_fill_is_needed=false;
        function validate_period($date){
            $now_year=date("Y");
            $needed_year=substr($date,0,4);
            if( abs($now_year-$needed_year)>3 ){
                return false;
            }
            return true;
        }
        if( $field === 'campaign_grouping_interval' ){
            $this->bonusPeriodsClear( $campaign_bonus_id );
            $period_fill_is_needed=true;
        } else if( $field === 'campaign_start_at' && validate_period($value) ){
            $value=substr($value,0,10).' 00:00:00';
            $period_fill_is_needed=true;
        } else if( $field === 'campaign_finish_at' && validate_period($value) ){
            $value=substr($value,0,10).' 23:59:59';
            $period_fill_is_needed=true;
        }
        $ok=$this->update('plugin_campaign_bonus',[$field=>$value],['campaign_bonus_id'=>$campaign_bonus_id]);
        if( $period_fill_is_needed ){
            $this->bonusPeriodsFill( $campaign_bonus_id );
        }
        return $ok;
    }
    
    public function bonusRemove( int $campaign_bonus_id ){
        $this->Hub->set_level(3);
        $this->bonusPeriodsClear( $campaign_bonus_id );
        return $this->delete('plugin_campaign_bonus',['campaign_bonus_id'=>$campaign_bonus_id]);
    }
    
    private function bonusGet($campaign_bonus_id){
        return $this->get('plugin_campaign_bonus',['campaign_bonus_id'=>$campaign_bonus_id]);
    }
    private function bonusesGet( int $campaign_id ){
        $bonuses=$this->get_list("SELECT * FROM plugin_campaign_bonus WHERE campaign_id='$campaign_id'");
//        foreach($bonuses as $bonus){
//            $bonus->periods=$this->bonusCalculate($bonus->campaign_bonus_id);
//        }
        return $bonuses;
    }    
    
    ////////////////////////////////////////////////////
    //PERIODS HANDLING
    ////////////////////////////////////////////////////
    private function bonusPeriodsClear( $campaign_bonus_id ){
        $this->delete('plugin_campaign_bonus_periods',['campaign_bonus_id'=>$campaign_bonus_id]);
    }
    
    public function bonusPeriodUpdate( int $campaign_bonus_period_id, string $field, string $value){
        $this->Hub->set_level(3);
        return $this->update('plugin_campaign_bonus_periods',[$field=>$value],['campaign_bonus_period_id'=>$campaign_bonus_period_id]);
    }
    
    private function bonusPeriodsFill( $campaign_bonus_id ){
        $bonus=$this->bonusGet($campaign_bonus_id);
        if( $bonus->campaign_grouping_interval && $bonus->campaign_start_at && $bonus->campaign_finish_at ){
            $start_year= substr($bonus->campaign_start_at, 0, 4);
            $start_month = substr($bonus->campaign_start_at, 5, 2)*1;
            $start_quarter=ceil($start_month/3);

            $finish_year= substr($bonus->campaign_finish_at, 0, 4);
            $finish_month = substr($bonus->campaign_finish_at, 5, 2);
            $finish_quarter=ceil($finish_month/3);

            $delta_year=$finish_year-$start_year;
            $delta_month=$finish_month + $delta_year*12 - $start_month;
            $delta_quarter=$finish_quarter - $start_quarter + $delta_year*4;
            
            $delete_sql="DELETE FROM plugin_campaign_bonus_periods WHERE campaign_bonus_id='$campaign_bonus_id' AND (";
            if( $bonus->campaign_grouping_interval =='YEAR' ){
                for( $y=$start_year;$y<=$finish_year; $y++ ){
                    $data=[
                        'campaign_bonus_id'=>$campaign_bonus_id,
                        'period_year'=>$y,
                        'period_quarter'=>0,
                        'period_month'=>0,
                        'period_plan1'=>0,
                        'period_plan2'=>0,
                        'period_plan3'=>0
                    ];
                    $this->bonusPeriodCreate($data);
                }
                $delete_sql.="  period_year<$start_year 
                                OR period_year>$finish_year";
            }
            if( $bonus->campaign_grouping_interval =='QUARTER' ){
                for( $q=$start_quarter;$q<=$start_quarter+$delta_quarter; $q++ ){
                    $year=$start_year+ceil($q/4)-1;
                    $quarter=($q-1)%4+1;
                    $data=[
                        'campaign_bonus_id'=>$campaign_bonus_id,
                        'period_year'=>$year,
                        'period_quarter'=>$quarter,
                        'period_month'=>0,
                        'period_plan1'=>0,
                        'period_plan2'=>0,
                        'period_plan3'=>0
                    ];
                    $this->bonusPeriodCreate($data);
                }
                $delete_sql.="  period_year<$start_year 
                                OR period_year>$finish_year
                                OR period_year=$start_year AND period_quarter<$start_quarter
                                OR period_year=$finish_year AND period_quarter>$finish_quarter";
            }
            if( $bonus->campaign_grouping_interval =='MONTH' ){
                for( $m=$start_month;$m<=$start_month+$delta_month; $m++ ){
                    $year=$start_year+ceil($m/12)-1;
                    $month=($m-1)%12+1;
                    $quarter=ceil($month/3);
                    $data=[
                        'campaign_bonus_id'=>$campaign_bonus_id,
                        'period_year'=>$year,
                        'period_quarter'=>$quarter,
                        'period_month'=>$month,
                        'period_plan1'=>0,
                        'period_plan2'=>0,
                        'period_plan3'=>0
                    ];
                    //print_r($data);
                    $this->bonusPeriodCreate($data);
                }
                $delete_sql.="  period_year<$start_year 
                                OR period_year>$finish_year
                                OR period_year=$start_year AND period_month<$start_month
                                OR period_year=$finish_year AND period_month>$finish_month";
            }
            if( $bonus->campaign_grouping_interval =='NOGROUP' ){
                $data=[
                    'campaign_bonus_id'=>$campaign_bonus_id,
                    'period_year'=>0,
                    'period_quarter'=>0,
                    'period_month'=>0,
                    'period_plan1'=>0,
                    'period_plan2'=>0,
                    'period_plan3'=>0
                ];
                $this->bonusPeriodCreate($data);
                $delete_sql.="  period_year<>0 ";//delete all periods
            }
            $delete_sql.=")";
            $this->query($delete_sql);
            //echo $delete_sql;
            return true;
        }
        return false;
    }
    
    private function bonusPeriodCreate($data){
        $set=[];
        foreach($data as $field=>$value ){
            $set[]=" $field='$value'";
        }
        $sql="INSERT IGNORE INTO plugin_campaign_bonus_periods SET ". implode(',', $set);
        return $this->query($sql);
    }
    
    public function bonusCalculate( int $campaign_bonus_id ){
        $this->Hub->set_level(3);$campaign_bonus=$this->bonusGet($campaign_bonus_id);
        return $this->bonusCalculateResult($campaign_bonus_id);
    }
    
    private function bonusCalculateResult( int $campaign_bonus_id, bool $only_current_period=false ){
        $campaign_bonus=$this->bonusGet($campaign_bonus_id);
        if( !$campaign_bonus ){
            return false;//notfound
        }
        $client_filter=$this->clientListFilterGet($campaign_bonus->campaign_id);
        $is_current_detection="1";
        switch( $campaign_bonus->campaign_grouping_interval ){
            case 'MONTH':
                $period_on=" YEAR(cstamp)=period_year AND MONTH(cstamp)=period_month ";
                $is_current_detection="CONCAT(period_month,'.',period_year)=CONCAT(MONTH(NOW()),'.',YEAR(NOW()))";
                break;
            case 'QUARTER':
                $period_on=" YEAR(cstamp)=period_year AND QUARTER(cstamp)=period_quarter";
                $is_current_detection="CONCAT(period_quarter,'.',period_year)=CONCAT(QUARTER(NOW()),'.',YEAR(NOW()))";
                break;
            case 'YEAR':
                $period_on=" YEAR(cstamp)=period_year ";
                $is_current_detection="period_year=YEAR(NOW())";
                break;
            default :
                $period_on=" 1 ";//whole period
        }
        switch( $campaign_bonus->bonus_type ){
            case 'VOLUME':
                $bonus_base= $this->bonusCalculateVolume($campaign_bonus,$period_on,$client_filter);
                break;
            case 'PROFIT':
                $bonus_base= $this->bonusCalculateProfit($campaign_bonus,$period_on,$client_filter);
                break;
            case 'PAYMENT':
                $bonus_base= $this->bonusCalculatePayment($campaign_bonus,$period_on,$client_filter);
                break;
            default:
                return false;
        }
        
        $current_filter="";
        if( $only_current_period ){
            $current_filter="AND $is_current_detection";
        }
        
        $sql="SELECT
            *,
            ROUND(bonus_base*
                IF(bonus_base>period_plan3 AND period_plan3 AND campaign_bonus_ratio3,campaign_bonus_ratio3,
                IF(bonus_base>period_plan2 AND period_plan2 AND campaign_bonus_ratio2,campaign_bonus_ratio2,
                IF(bonus_base>period_plan1,campaign_bonus_ratio1,0
            )))/100) bonus_result,
            ROUND(bonus_base/period_plan1*100) period_percent1,
            ROUND(bonus_base/period_plan2*100) period_percent2,
            ROUND(bonus_base/period_plan3*100) period_percent3,
            (SELECT label FROM stock_tree WHERE branch_id=product_category_id) product_category_label,
            $is_current_detection is_current
        FROM (
            SELECT
                pcb.*,
                campaign_bonus_period_id,
                period_year,
                period_quarter,
                period_month,
                period_plan1,
                period_plan2,
                period_plan3,
                ROUND(SUM( {$bonus_base['select']} )) bonus_base
            FROM
                plugin_campaign_bonus pcb 
                    JOIN
                plugin_campaign_bonus_periods pcbp USING (campaign_bonus_id)
                {$bonus_base['table']}
            WHERE
                campaign_bonus_id = $campaign_bonus_id
                {$bonus_base['where']}
                $current_filter
            GROUP BY period_year,period_quarter,period_month
            ORDER BY period_year DESC,period_quarter DESC,period_month DESC) tt";
            //die($sql);
        return $this->get_list($sql);
    }
    private function bonusCalculateProductRange($campaign_bonus){
        $table="document_entries de";
        $where="";
        if( $campaign_bonus->product_category_id ){
            $stock_category_ids=$this->treeGetSub('stock_tree', $campaign_bonus->product_category_id);
            $table.=" JOIN stock_entries se ON de.product_code=se.product_code AND se.parent_id IN (". implode(',', $stock_category_ids).")";
        }
        if( $campaign_bonus->product_brand_filter || $campaign_bonus->product_type_filter ){
            $brand_filter =" analyse_brand LIKE '%". str_replace(',', "%' OR  analyse_brand LIKE '%", $campaign_bonus->product_brand_filter)."%'";
            $type_filter =" analyse_type  LIKE '%". str_replace(',', "%' OR  analyse_type  LIKE '%", $campaign_bonus->product_type_filter)."%'";
            $table.=" JOIN prod_list pl ON de.product_code=pl.product_code AND ($brand_filter) AND ($type_filter)";
        }
        return [
            'table'=>"(SELECT doc_id,doc_entry_id,product_code,invoice_price,de.product_quantity,de.breakeven_price,de.self_price FROM $table)",
            'where'=>$where
        ];
    }
    
    private function bonusCalculateVolume($campaign_bonus,$period_on,$client_filter){
        $product_range= $this->bonusCalculateProductRange($campaign_bonus);
        
        //print_r($product_range);
        $detailed_select="";
        $select="COALESCE(invoice_price * product_quantity * (dl.vat_rate/100+1),0)";
        $table="
                    LEFT JOIN
                document_list dl ON $period_on 
                                    AND doc_type = 1 
                                    AND is_commited 
                                    AND NOT notcount 
                                    AND passive_company_id IN (SELECT company_id FROM companies_list JOIN companies_tree USING(branch_id) WHERE $client_filter)
                                    AND dl.cstamp>'{$campaign_bonus->campaign_start_at}' AND dl.cstamp<'{$campaign_bonus->campaign_finish_at}'
                    LEFT JOIN
                {$product_range['table']} product_range USING (doc_id)
                ";
        $where="";
        return [
            'select'=>$select,
            'table'=>$table,
            'where'=>$where
        ];
    }
    private function bonusCalculateProfit($campaign_bonus,$period_on,$client_filter){
        $product_range= $this->bonusCalculateProductRange($campaign_bonus);
        $select="COALESCE((invoice_price-GREATEST(breakeven_price,self_price)) * product_quantity * (dl.vat_rate/100+1),0)";
        $table="
                    LEFT JOIN
                document_list dl ON $period_on 
                                    AND doc_type = 1 
                                    AND is_commited 
                                    AND NOT notcount 
                                    AND passive_company_id IN (SELECT company_id FROM companies_list JOIN companies_tree USING(branch_id) WHERE $client_filter)
                                    AND dl.cstamp>'{$campaign_bonus->campaign_start_at}' AND dl.cstamp<'{$campaign_bonus->campaign_finish_at}'
                    LEFT JOIN
                {$product_range['table']} product_range USING (doc_id)
                ";
        $where="";
        return [
            'select'=>$select,
            'table'=>$table,
            'where'=>$where
        ];
    }
    private function bonusCalculatePayment($campaign_bonus,$period_on,$client_filter){
        $payment_account="361";
        $select="COALESCE(amount,0)";
        $table="
                    LEFT JOIN
                        acc_trans at ON $period_on
                            AND passive_company_id IN (SELECT company_id FROM companies_list JOIN companies_tree USING(branch_id) WHERE $client_filter)
                            AND acc_credit_code='$payment_account'
                            AND at.cstamp>'{$campaign_bonus->campaign_start_at}' AND at.cstamp<'{$campaign_bonus->campaign_finish_at}'
                ";
        $where="";
        return [
            'select'=>$select,
            'table'=>$table,
            'where'=>$where
        ];
    }
    
    public function bonusChartView(){
        $this->Hub->set_level(2);
        $this->load->view('bonus_chart.html');
    }
    
    public function bonusCalculatePersonal(){
        $this->Hub->set_level(2);
        $liable_user_id=$this->Hub->svar('user_id');
        $sql="SELECT * FROM plugin_campaign_list JOIN plugin_campaign_bonus USING(campaign_id) WHERE liable_user_id=$liable_user_id";
        $personal_bonuses=[];
        $campaign_list=$this->get_list($sql);
        $result_total=0;
        foreach( $campaign_list as $campaign ){
            $current_result=$this->bonusCalculateResult($campaign->campaign_bonus_id,true);
            $personal_bonuses[]=[
                'campaign_name'=>$campaign->campaign_name,
                'current_result'=>$current_result
            ];
            $result_total+=$current_result[0]->bonus_result;
        }
        return [
                'total'=>$result_total,
                'bonuses'=>$personal_bonuses
                ];
    }
    
    
    
    public function dashboardMobiSell(){
        $this->Hub->set_level(2);
        $this->load->view("dashboard_mobisell.html");
    }
    
    public function dashboardManagerStatistics(){
        $this->Hub->set_level(2);
        $liable_user_id=$this->Hub->svar('user_id');
        $campaigns=$this->get_list("SELECT * FROM plugin_campaign_bonus JOIN plugin_campaign_list USING(campaign_id) WHERE liable_user_id='$liable_user_id'");
        $sqls=[];
        foreach($campaigns as $campaign){
            $client_filter=$this->clientListFilterGet($campaign->campaign_id);
            $sqls[]="
                SELECT
                    doc_id,passive_company_id
                FROM
                    document_list dl 
                WHERE 
                    doc_type = 1 
                    AND is_commited 
                    AND NOT notcount 
                    AND passive_company_id IN (SELECT company_id FROM companies_list JOIN companies_tree USING(branch_id) WHERE $client_filter)
                    AND MONTH(cstamp) = MONTH(CURRENT_DATE()) AND YEAR(cstamp) = YEAR(CURRENT_DATE())";
        }
        $super_table=implode(') UNION (',$sqls);
        $sql="
            SELECT 
                COUNT(DISTINCT doc_id) invoice_count,
                COUNT(DISTINCT passive_company_id) client_count
            FROM (($super_table))t";
        return $this->get_row($sql);
    }
    
    
    
    
    ///////////////////////////////////////////////////
    //EXTENSION
    ///////////////////////////////////////////////////
    
    
    private function bonusCalculateDetailedResult( int $campaign_bonus_id, int $campaign_bonus_period_id, string $group_by ){
        $campaign_bonus=$this->bonusGet($campaign_bonus_id);
        if( !$campaign_bonus ){
            return false;//notfound
        }
        $client_filter=$this->clientListFilterGet($campaign_bonus->campaign_id);
        $is_current_detection="1";
        switch( $campaign_bonus->campaign_grouping_interval ){
            case 'MONTH':
                $period_on=" YEAR(cstamp)=period_year AND MONTH(cstamp)=period_month ";
                $is_current_detection="CONCAT(period_month,'.',period_year)=CONCAT(MONTH(NOW()),'.',YEAR(NOW()))";
                break;
            case 'QUARTER':
                $period_on=" YEAR(cstamp)=period_year AND QUARTER(cstamp)=period_quarter";
                $is_current_detection="CONCAT(period_quarter,'.',period_year)=CONCAT(QUARTER(NOW()),'.',YEAR(NOW()))";
                break;
            case 'YEAR':
                $period_on=" YEAR(cstamp)=period_year ";
                $is_current_detection="period_year=YEAR(NOW())";
                break;
            default :
                $period_on=" 1 ";//whole period
        }
        switch( $campaign_bonus->bonus_type ){
            case 'VOLUME':
                $bonus_base= $this->bonusCalculateVolume($campaign_bonus,$period_on,$client_filter);
                break;
            case 'PROFIT':
                $bonus_base= $this->bonusCalculateProfit($campaign_bonus,$period_on,$client_filter);
                break;
            case 'PAYMENT':
                $bonus_base= $this->bonusCalculatePayment($campaign_bonus,$period_on,$client_filter);
                break;
            default:
                return false;
        }
        
        $current_filter="";
//        if( $only_current_period ){
//            $current_filter="AND $is_current_detection";
//        }
        
        $sql="SELECT
            *,
            CONCAT('%',COALESCE(campaign_bonus_ratio1,''),'/',COALESCE(campaign_bonus_ratio2,''),'/',COALESCE(campaign_bonus_ratio3,'')) bonus_ratios,
            ROUND(bonus_base*campaign_bonus_ratio1/100) result1,
            ROUND(bonus_base*campaign_bonus_ratio2/100) result2,
            ROUND(bonus_base*campaign_bonus_ratio3/100) result3
        FROM (
            SELECT
                campaign_bonus_ratio1,
                campaign_bonus_ratio2,
                campaign_bonus_ratio3,
                company_name,
                analyse_brand,
                analyse_type,
                pl.product_code,
                ru product_name,
                SUM(product_quantity) product_quantity,
                ROUND(AVG(self_price),2) self_price,
                ROUND(AVG(breakeven_price),2) breakeven_price,
                ROUND(AVG(invoice_price),2) sell_price,
                ROUND(SUM(invoice_price*product_quantity* (dl.vat_rate/100+1))) sell_sum,
                ROUND(COALESCE(AVG((invoice_price-GREATEST(breakeven_price,self_price)) * (dl.vat_rate/100+1)),0),2) diff_price,
                ROUND(SUM({$bonus_base['select']})) bonus_base
            FROM
                plugin_campaign_bonus pcb 
                    JOIN
                plugin_campaign_bonus_periods pcbp USING (campaign_bonus_id)
                {$bonus_base['table']}
                    LEFT JOIN
                prod_list pl USING(product_code)
                    LEFT JOIN
                companies_list ON company_id=passive_company_id
            WHERE
                campaign_bonus_id = $campaign_bonus_id
                AND campaign_bonus_period_id=$campaign_bonus_period_id
                {$bonus_base['where']}
            GROUP BY $group_by
            ORDER BY $group_by) tt";
            //die($sql);
        return $this->get_list($sql);
    }
    
    public function campaignDetailedView( int $campaign_bonus_id, int $campaign_bonus_period_id, string $group_by='product_code' ){
        $table=$this->bonusCalculateDetailedResult( $campaign_bonus_id, $campaign_bonus_period_id, $group_by );
        
        
        
        $out_type='.print';
        
        $struct=[
            
            ['Field'=>'company_name','Comment'=>'Клиент'],
            ['Field'=>'analyse_brand','Comment'=>'Бренд'],
            ['Field'=>'analyse_type','Comment'=>'Тип'],
            ['Field'=>'product_code','Comment'=>'Код'],
            ['Field'=>'product_name','Comment'=>'Название'],
            ['Field'=>'product_quantity','Comment'=>'Кол-во'],
            ['Field'=>'self_price','Comment'=>'Себ'],
            ['Field'=>'breakeven_price','Comment'=>'Порог'],
            ['Field'=>'sell_price','Comment'=>'Продажа'],
            ['Field'=>'diff_price','Comment'=>'Разница'],
            ['Field'=>'sell_sum','Comment'=>'Сумма'],
            ['Field'=>'bonus_base','Comment'=>'База Б.'],
            ['Field'=>'result1','Comment'=>'Рез1'],
            ['Field'=>'result2','Comment'=>'Рез2'],
            ['Field'=>'result3','Comment'=>'Рез3'],
            ['Field'=>'bonus_ratios','Comment'=>'Бонусы'],
            
        ];
        //return $table;
        
        
	$dump=[
	    'tpl_files'=>'/GridTpl.xlsx',
	    'title'=>"Экспорт таблицы",
	    'user_data'=>[
		'email'=>$this->Hub->svar('pcomp')?$this->Hub->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'struct'=>$struct,
	    'view'=>[
		'rows'=>$table
	    ]
	];
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
}