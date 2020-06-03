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
        $sql="SELECT * FROM plugin_campaign_list ORDER BY campaign_name";
        return $this->get_list($sql);
    }
    
    public function campaignGet( int $campaign_id, int $visibility_filter=1 ){
        $this->Hub->set_level(3);
        $settings=$this->get_row("SELECT *,$visibility_filter visibility_filter FROM plugin_campaign_list WHERE campaign_id='$campaign_id'");
        //$settings->subject_manager_include=explode(',',$settings->subject_manager_include);
        return [
            'settings'=>$settings,
            'staff_list'=>$this->Hub->load_model("Pref")->getStaffList(),
            'bonuses'=>$this->bonusesGet( $campaign_id, $visibility_filter ),
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
        //$and_case[]=" level<= $user_level";
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
        return $where?$where." AND level<= $user_level":0;
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
                campaign_bonus_ratio1=0,
                bonus_visibility=1";
        $ok=$this->query($sql);
        $this->bonusPeriodsFill( $this->db->insert_id() );
        return $ok;
    }
    
    public function bonusUpdate( int $campaign_bonus_id, string $field, string $value=''){
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
    
    private function bonusUpdateQueue($campaign_id){
        $sql="
            UPDATE 
                plugin_campaign_bonus 
            SET 
                campaign_queue= (@queue:=@queue+1) 
            WHERE 
                campaign_id=$campaign_id
                AND (@queue:=0)+1
                AND bonus_visibility>0
            ORDER BY campaign_queue";
        $this->query($sql);
    }
    
    public function bonusRemove( int $campaign_bonus_id ){
        $this->Hub->set_level(3);
        $this->bonusPeriodsClear( $campaign_bonus_id );
        return $this->delete('plugin_campaign_bonus',['campaign_bonus_id'=>$campaign_bonus_id]);
    }
    
    private function bonusGet($campaign_bonus_id){
        return $this->get('plugin_campaign_bonus',['campaign_bonus_id'=>$campaign_bonus_id]);
    }
    private function bonusesGet( int $campaign_id, int $visibility_filter ){
        $this->bonusUpdateQueue($campaign_id);
        $sql="SELECT
            * 
            FROM 
                plugin_campaign_bonus 
            WHERE 
                campaign_id='$campaign_id' 
                AND IF($visibility_filter>0,bonus_visibility>=$visibility_filter,COALESCE(bonus_visibility,0)=-$visibility_filter)
            ORDER BY campaign_queue";
        $bonuses=$this->get_list($sql);
        return $bonuses;
    }    
    
    ////////////////////////////////////////////////////
    //PERIODS HANDLING
    ////////////////////////////////////////////////////
    private function bonusPeriodsClear( $campaign_bonus_id ){
        $this->delete('plugin_campaign_bonus_periods',['campaign_bonus_id'=>$campaign_bonus_id]);
    }
    
    public function bonusPeriodUpdate( int $campaign_bonus_period_id, string $field, string $value='' ){
        $this->Hub->set_level(3);
        return $this->update('plugin_campaign_bonus_periods',[$field=>$value],['campaign_bonus_period_id'=>$campaign_bonus_period_id]);
    }
    
    public function bonusPeriodDuplicate ( int $this_period_id, int $prev_period_id ){
        $this->Hub->set_level(3);
        $sql="
            UPDATE
                plugin_campaign_bonus_periods pcbp_this
                    JOIN
                plugin_campaign_bonus_periods pcbp_prev
            SET
                pcbp_this.period_plan1=pcbp_prev.period_plan1,
                pcbp_this.period_plan2=pcbp_prev.period_plan2,
                pcbp_this.period_plan3=pcbp_prev.period_plan3,
                
                pcbp_this.period_reward1=pcbp_prev.period_reward1,
                pcbp_this.period_reward2=pcbp_prev.period_reward2,
                pcbp_this.period_reward3=pcbp_prev.period_reward3
            WHERE
                pcbp_this.campaign_bonus_period_id = $this_period_id
                AND pcbp_prev.campaign_bonus_period_id = $prev_period_id
                ";
        $this->query($sql);
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
        $this->Hub->set_level(3);
        session_write_close();
        return $this->bonusCalculateResult($campaign_bonus_id);
    }
    
    private function bonusCalculateResult( int $campaign_bonus_id, bool $only_current_period=false, int $bonus_limit=0 ){
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
        $limit="";
        if( $bonus_limit ){
            $limit="LIMIT $bonus_limit";
        }
        
        $sql="SELECT
            *,
            ROUND(
                IF(bonus_base>period_plan3 AND period_plan3,IF(period_reward3,period_reward3,bonus_base*campaign_bonus_ratio3/100),
                IF(bonus_base>period_plan2 AND period_plan2,IF(period_reward2,period_reward2,bonus_base*campaign_bonus_ratio2/100),
                IF(bonus_base>period_plan1,IF(period_reward1,period_reward1,bonus_base*campaign_bonus_ratio1/100),
            0)))) bonus_result,
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
                period_reward1,
                period_reward2,
                period_reward3,
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
            ORDER BY period_year DESC,period_quarter DESC,period_month DESC
            $limit) tt";
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
            'table'=>"(SELECT doc_id,doc_entry_id,de.product_code,invoice_price,de.product_quantity,de.breakeven_price,de.self_price FROM $table)",
            'where'=>$where
        ];
    }
    private function bonusCalculateVolume($campaign_bonus,$period_on,$client_filter){
        $product_range= $this->bonusCalculateProductRange($campaign_bonus);
        
        //print_r($product_range);
        $detailed_select="
            analyse_brand,
            analyse_type,
            pl.product_code,
            ru product_name,
            SUM(product_quantity) product_quantity,
            ROUND(AVG(self_price),2) self_price,
            ROUND(AVG(breakeven_price),2) breakeven_price,
            ROUND(AVG(invoice_price * (dl.vat_rate/100+1)),2) sell_price,
            ROUND(SUM(invoice_price*product_quantity* (dl.vat_rate/100+1))) total_sum,
            ROUND(COALESCE(AVG((invoice_price * (dl.vat_rate/100+1)-GREATEST(breakeven_price,self_price))),0),2) diff_price,
            ";
        $select="
                COALESCE(invoice_price * product_quantity * (dl.vat_rate/100+1),0)";
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
                    LEFT JOIN
                prod_list pl USING(product_code)
                ";
        $where="";
        return [
            'select'=>$select,
            'detailed_select'=>$detailed_select,
            'table'=>$table,
            'where'=>$where
        ];
    }
    private function bonusCalculateProfit($campaign_bonus,$period_on,$client_filter){
        $product_range= $this->bonusCalculateProductRange($campaign_bonus);
        $detailed_select="
            analyse_brand,
            analyse_type,
            pl.product_code,
            ru product_name,
            SUM(product_quantity) product_quantity,
            ROUND(AVG(self_price),2) self_price,
            ROUND(AVG(breakeven_price),2) breakeven_price,
            ROUND(AVG(invoice_price * (dl.vat_rate/100+1)),2) sell_price,
            ROUND(SUM(invoice_price*product_quantity* (dl.vat_rate/100+1))) total_sum,
            ROUND(COALESCE(AVG((invoice_price * (dl.vat_rate/100+1)-GREATEST(breakeven_price,self_price))),0),2) diff_price,
            ";
        $select="
                COALESCE(GREATEST(invoice_price * (dl.vat_rate/100+1)-GREATEST(breakeven_price,self_price),0) * product_quantity,0)";
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
                    LEFT JOIN
                prod_list pl USING(product_code)
                ";
        $where="";
        return [
            'select'=>$select,
            'detailed_select'=>$detailed_select,
            'table'=>$table,
            'where'=>$where
        ];
    }
    private function bonusCalculatePayment($campaign_bonus,$period_on,$client_filter){
        $payment_account="361";
        $detailed_select="
            description,
            ROUND(SUM(IF(acc_debit_code=$payment_account,amount,-amount))) total_sum,
            (SELECT ROUND(SUM(IF(acc_debit_code=$payment_account,amount,-amount))) FROM acc_trans at2 WHERE 
                at2.passive_company_id=at.passive_company_id
                AND YEAR(cstamp)<=period_year AND MONTH(cstamp)<=period_month
                AND (acc_debit_code=$payment_account OR acc_credit_code=$payment_account)) debt_finish,";
        $select="COALESCE( IF( 
                                acc_credit_code='$payment_account' 
                                AND (acc_debit_code LIKE '30%' OR acc_debit_code LIKE '31%')
                                AND at.cstamp>'{$campaign_bonus->campaign_start_at}' AND at.cstamp<'{$campaign_bonus->campaign_finish_at}', 
                            amount,0),0)";
        $table="
                    LEFT JOIN
                        acc_trans at ON $period_on
                            AND passive_company_id IN (SELECT company_id FROM companies_list JOIN companies_tree USING(branch_id) WHERE $client_filter)
                            AND (acc_debit_code=$payment_account OR acc_credit_code=$payment_account)
                ";
        $where="";
        return [
            'select'=>$select,
            'detailed_select'=>$detailed_select,
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
        $sql="SELECT * FROM plugin_campaign_list JOIN plugin_campaign_bonus USING(campaign_id) WHERE liable_user_id=$liable_user_id ORDER BY campaign_queue";
        $personal_bonuses=[];
        $campaign_list=$this->get_list($sql);
        $result_total=$campaign_list[0]->campaign_fixed_payment;
        foreach( $campaign_list as $campaign ){
            if( $campaign->bonus_visibility==2 ){//visible in widget
                $current_result=$this->bonusCalculateResult($campaign->campaign_bonus_id,true);
                $personal_bonuses[]=[
                    'campaign_name'=>$campaign->campaign_name,
                    'current_result'=>$current_result
                ];
                $result_total+=$current_result[0]->bonus_result;
            }
        }
        return [
                'total'=>$result_total,
                'bonuses'=>$personal_bonuses
                ];
    }
    
    public function bonusCalculateCampaignTotal( int $campaign_id =0 ){
        $this->Hub->set_level(2);
        $sql="SELECT * FROM plugin_campaign_list JOIN plugin_campaign_bonus USING(campaign_id) WHERE campaign_id=$campaign_id";
        $campaign_list=$this->get_list($sql);
        if( !$campaign_list ){
            return 0;
        }
        $result_total=$campaign_list[0]->campaign_fixed_payment;
        foreach( $campaign_list as $campaign ){
            if( $campaign->bonus_visibility>0 ){
                $current_result=$this->bonusCalculateResult($campaign->campaign_bonus_id,false,3);
                $result_total+=$current_result[0]->bonus_result;
            }
        }
        return $result_total;
    }
    
    public function dashboardMobiSell(){
        $this->Hub->set_level(2);
        $this->load->view("dashboard_mobisell.html");
    }
    public function dashboardiSell(){
        $this->Hub->set_level(2);
        $this->load->view("dashboard_isell.html");
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
        if( !$sqls ){
            return ['invoice_count'=>0,'client_count'=>0];
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
            CONCAT(
            '%',COALESCE(ROUND(campaign_bonus_ratio1,1),''),
            '/',COALESCE(ROUND(campaign_bonus_ratio2,1),''),
            '/',COALESCE(ROUND(campaign_bonus_ratio3,1),'')
            ) bonus_ratios,
            ROUND(bonus_base*campaign_bonus_ratio1/100) result1,
            ROUND(bonus_base*campaign_bonus_ratio2/100) result2,
            ROUND(bonus_base*campaign_bonus_ratio3/100) result3
        FROM (
            SELECT
                COALESCE(campaign_bonus_ratio1,0) campaign_bonus_ratio1,
                COALESCE(campaign_bonus_ratio2,0) campaign_bonus_ratio2,
                COALESCE(campaign_bonus_ratio3,0) campaign_bonus_ratio3,
                company_name,
                {$bonus_base['detailed_select']}
                ROUND(SUM( {$bonus_base['select']} )) bonus_base
            FROM
                plugin_campaign_bonus pcb 
                    JOIN
                plugin_campaign_bonus_periods pcbp USING (campaign_bonus_id)
                {$bonus_base['table']}
                    LEFT JOIN
                companies_list ON company_id=passive_company_id
            WHERE
                campaign_bonus_id = $campaign_bonus_id
                AND campaign_bonus_period_id=$campaign_bonus_period_id
                {$bonus_base['where']}
            GROUP BY $group_by
            ORDER BY bonus_base DESC) tt";
            //die($sql);
        return [
                    'entries'=>$this->get_list($sql),
                    'campaign_bonus'=>$campaign_bonus
                ];
    }
    
    public function campaignDetailedView( int $campaign_bonus_id, int $campaign_bonus_period_id, string $group_by='product_code' ){
        $table=$this->bonusCalculateDetailedResult( $campaign_bonus_id, $campaign_bonus_period_id, $group_by );
        switch($group_by){
            case 'trans_id':
                $struct=[
                    ['Field'=>'company_name','Comment'=>'Клиент','Width'=>40],
                    ['Field'=>'description','Comment'=>'Назначение','Width'=>40]
                ];
                break;
            case 'company_id':
                $struct=[
                    ['Field'=>'company_name','Comment'=>'Клиент','Width'=>40]
                ];
                break;
            case 'analyse_brand':
                $struct=[
                    ['Field'=>'analyse_brand','Comment'=>'Бренд','Width'=>20]
                ];
                break;
            case 'analyse_type':
                $struct=[
                    ['Field'=>'analyse_type','Comment'=>'Тип','Width'=>20]
                ];
                break;
            case 'product_code':
                $struct=[
                    ['Field'=>'product_code','Comment'=>'Код','Width'=>20],
                    ['Field'=>'product_name','Comment'=>'Название','Width'=>30]
                ];
                break;
            default :
                $struct=[
                    ['Field'=>'company_name','Comment'=>'Клиент','Width'=>20],
                    ['Field'=>'analyse_brand','Comment'=>'Бренд','Width'=>20],
                    ['Field'=>'analyse_type','Comment'=>'Тип','Width'=>20],
                    ['Field'=>'product_code','Comment'=>'Код','Width'=>20],
                    ['Field'=>'product_name','Comment'=>'Название','Width'=>30]
                ];
        }
        
        if( $table['campaign_bonus']->bonus_type=='PAYMENT' ){
            $struct= array_merge($struct,[
            ['Field'=>'debt_start','Comment'=>'Начальный долг','Align'=>'right','Width'=>20],
            ['Field'=>'total_sum','Comment'=>'Изменение долга','Align'=>'right','Width'=>20],
            ['Field'=>'debt_finish','Comment'=>'Конечный долг','Align'=>'right','Width'=>20],
            ['Field'=>'bonus_base','Comment'=>'Оплаты','Width'=>20,'Align'=>'right'],
            ]);
        } else {
            $struct= array_merge($struct,[
            ['Field'=>'bonus_base','Comment'=>'База Бонуса','Width'=>20,'Align'=>'right'],
            ['Field'=>'product_quantity','Comment'=>'Кол-во','Width'=>10,'Align'=>'right'],
            ['Field'=>'self_price','Comment'=>'Себ','Width'=>10,'Align'=>'right'],
            ['Field'=>'breakeven_price','Comment'=>'Порог','Width'=>10,'Align'=>'right'],
            ['Field'=>'sell_price','Comment'=>'Продажа','Width'=>10,'Align'=>'right'],
            ['Field'=>'diff_price','Comment'=>'Разница','Width'=>10,'Align'=>'right'],
            ['Field'=>'total_sum','Comment'=>'Сумма','Width'=>10,'Align'=>'right']
            ]);
        }
        $additional_cols=[
            
            ['Field'=>'bonus_ratios','Comment'=>'%','Width'=>15,'Align'=>'center'],
            ['Field'=>'result1','Comment'=>'Рез1','Width'=>10,'Align'=>'right']
            ];
        
        if( $table['entries'][0]->campaign_bonus_ratio1 != $table['entries'][0]->campaign_bonus_ratio2 ){
            $additional_cols[]=['Field'=>'result2','Comment'=>'Рез2','Width'=>10,'Align'=>'right'];
            
        }
        if( $table['entries'][0]->campaign_bonus_ratio1 != $table['entries'][0]->campaign_bonus_ratio3 ){
            $additional_cols[]=['Field'=>'result3','Comment'=>'Рез3','Width'=>10,'Align'=>'right'];
        }
        $struct= array_merge($struct,$additional_cols);
        
        //return $table;
        
        $total_row=[
            'bonus_ratios'=>'',
            'result1'=>0,
            'result2'=>0,
            'result3'=>0,
            'total_sum'=>0,
            'bonus_base'=>0,
            'debt_finish'=>0,
            'debt_start'=>0
        ];
        
        
        
        
        
        foreach($table['entries'] as $row){
            $total_row['result1']+=$row->result1;
            $total_row['result2']+=$row->result2;
            $total_row['result3']+=$row->result3;
            $total_row['total_sum']+=$row->total_sum;
            $total_row['bonus_base']+=$row->bonus_base;
            if( isset($row->debt_finish) ){
                $row->debt_start=$row->debt_finish-$row->total_sum;
                $total_row['debt_start']+=$row->debt_start;
                $total_row['debt_finish']+=$row->debt_finish;
            }
        }
        array_unshift($table['entries'],$total_row);
        
        //print_r($table);die;
        
        $out_type='.print';
	$dump=[
	    'tpl_files'=>'/GridTpl.xlsx',
	    'title'=>"Экспорт таблицы",
	    'user_data'=>[
		'email'=>$this->Hub->svar('pcomp')?$this->Hub->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'struct'=>$struct,
	    'view'=>[
		'rows'=>$table['entries']
	    ]
	];
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
    
    
    
    
    public function bonusPeriodBreakEvenRecalculate( int $campaign_bonus_period_id ){
        $bonus_period=$this->get_row("SELECT * FROM plugin_campaign_bonus_periods JOIN plugin_campaign_bonus USING(campaign_bonus_id) WHERE campaign_bonus_period_id=$campaign_bonus_period_id");
        $client_filter=$this->clientListFilterGet($bonus_period->campaign_id);
        $sql="UPDATE 
                    document_entries 
                    JOIN
                    document_list USING(doc_id)
                SET 
                    breakeven_price = ROUND(GET_BREAKEVEN_PRICE(product_code,passive_company_id,doc_ratio,self_price),2)
                WHERE 
                    YEAR(cstamp)=$bonus_period->period_year
                    AND MONTH(cstamp)=$bonus_period->period_month
                    AND doc_type=1
                    AND passive_company_id IN (SELECT company_id FROM companies_list JOIN companies_tree USING(branch_id) WHERE $client_filter)";
        return $this->query($sql);
    }
}