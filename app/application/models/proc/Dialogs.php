<?php
require_once 'Data.php';

class Dialogs extends Data {

    private function insertProduct($product_code) {
	$this->Base->query("INSERT INTO " . BAY_DB_MAIN . ".prod_list SET product_code='$product_code'", false);
	$this->Base->query("INSERT INTO " . BAY_DB_MAIN . ".stock_entries SET product_code='$product_code'", false);
	$this->Base->query("INSERT INTO " . BAY_DB_MAIN . ".price_list SET product_code='$product_code'", false);
	$this->Base->query("INSERT INTO price_list SET product_code='$product_code'", false);
    }

    public function getProduct($product_code, $parent_id) {
	if (!$product_code){
	    return array(
		'product_code' => $this->Base->get_row("SELECT CONCAT('N',COUNT(*)) FROM " . BAY_DB_MAIN . ".prod_list", 0)
	    );
	}
	return $this->Base->get_row("SELECT pp.*,pl.*,sl.* FROM " . BAY_DB_MAIN . ".prod_list pl LEFT JOIN " . BAY_DB_MAIN . ".price_list pp ON (pl.product_code=pp.product_code) LEFT JOIN " . BAY_DB_MAIN . ".stock_entries sl ON(pl.product_code=sl.product_code) WHERE pl.product_code='$product_code'");
    }

    public function deleteProduct($product_code) {
	$this->Base->set_level(2);
	$this->Base->query("DELETE FROM " . BAY_DB_MAIN . ".stock_entries WHERE product_code='$product_code'");
	$this->Base->query("DELETE FROM " . BAY_DB_MAIN . ".price_list WHERE product_code='$product_code'");
	$this->Base->query("DELETE FROM " . BAY_DB_MAIN . ".prod_list WHERE product_code='$product_code'",false);
	return mysql_errno()==0;
    }

    public function updateProduct($product_code, $product_data) {
	$this->Base->set_level(2);
	if( !$product_code && $product_data['new_product_code'] ){
	    $product_code=$product_data['new_product_code'];
	    $exists_in_all_tables=$this->Base->get_row("SELECT COUNT(*) FROM " . BAY_DB_MAIN . ".prod_list pl JOIN " . BAY_DB_MAIN . ".price_list pp USING(product_code) JOIN " . BAY_DB_MAIN . ".stock_entries sl USING(product_code) WHERE pl.product_code='$product_code'", 0);
	    if ( !$exists_in_all_tables ){
		$this->insertProduct($product_code);
	    }
	}
	//Update ".BAY_DB_MAIN.".prod_list
	$set = "
	ru='$product_data[ru]',
	ua='$product_data[ua]',
	en='$product_data[en]',
	product_code='{$product_data['new_product_code']}',
	product_uktzet='$product_data[product_uktzet]',
	product_spack='$product_data[product_spack]',
	product_bpack='$product_data[product_bpack]',
	product_weight='$product_data[product_weight]',
	product_volume='$product_data[product_volume]',
	product_unit='$product_data[product_unit]',
	is_service='$product_data[is_service]'";
	$this->Base->query("UPDATE " . BAY_DB_MAIN . ".prod_list SET $set WHERE product_code='$product_code'");

	//Update ".BAY_DB_MAIN.".price_list
	$set = "
	    sell='$product_data[sell]',
	    buy='$product_data[buy]',
	    curr_code='$product_data[curr_code]'";
	$this->Base->query("UPDATE price_list SET $set WHERE product_code='$product_code'");

	//Update stock_list
	$set = "
	    parent_id='$product_data[parent_id]'";
	$this->Base->query("UPDATE " . BAY_DB_MAIN . ".stock_entries SET $set WHERE product_code='$product_code'");
	
	$set = "
	    product_wrn_quantity='{$product_data['product_wrn_quantity']}',
	    party_label='{$product_data['party_label']}'";
	$this->Base->query("UPDATE stock_entries SET $set WHERE product_code='$product_code'");

	$this->Base->msg("Товар сохранен!");
	return true;
    }
}
?>

