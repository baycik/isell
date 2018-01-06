<?php

class StockSellPriceCalculate extends PluginManager {
    function __construct(){
        $this->settings=$this->settingsDataFetch('StockSellPriceCalculate');
    }
    
    public $discountsGet=[];
    public function discountsGet(){
        $sql="SELECT top_id, label FROM stock_tree WHERE parent_id=0";
        $cats=$this->get_list($sql);
        foreach( $cats as $cat ){
            if( isset($this->settings->plugin_settings->{$cat->top_id}) ){
                $cat->discount=$this->settings->plugin_settings->{$cat->top_id}*100-100;
            } else {
                $cat->discount="";
            }
        }
        return $cats;
    }
    
    public $discountsSave=['top_id'=>'int','value'=>'double'];
    public function discountsSave( $top_id, $value ){
        $this->settings->plugin_settings->{$top_id}=(1+$value/100);
        $sett= json_encode($this->settings->plugin_settings);
        return $this->settingsSave('StockSellPriceCalculate',$sett);
    }
    
    public $calc=["top_id"=>'int',"round_to"=>"double"];
    public function calc($top_id, $round_to){
        if( isset($this->settings->plugin_settings->{$top_id}) ){
            $discount=$this->settings->plugin_settings->{$top_id};
            $branch_ids = $this->treeGetSub('stock_tree', $top_id);
            $where = "WHERE parent_id IN (" . implode(',', $branch_ids) . ")";
            $sql="UPDATE price_list 
                SET
                    sell=MAX(ROUND(buy*$discount/$round_to)*$round_to,$round_to)
                WHERE 
                    product_code IN (SELECT product_code FROM stock_entries $where)";
            $this->query($sql);
            return $this->db->affected_rows();
        }
        return 0;
    }
}
