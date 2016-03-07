<?php

require_once('iSellBase.php');

class ProcDialogs extends iSellBase {

    public function ProcDialogs() {
	$this->ProcessorBase(1);
    }

    public function index() {
	$dialog_name = $this->request('dialog_name');
	header('Content-type: text/html; charset=utf-8;');
	if ($dialog_name == 'productCard')
	    include ('views/dialog/productcard.html');
	exit;
    }

    public function onGetProductCard() {
	$product_code = $this->request('product_code');
	$parent_id = $this->request('parent_id', 1, 0);
	$this->LoadClass('Dialogs');
	$pdata = $this->Dialogs->getProduct($product_code, $parent_id);
	$this->response($pdata);
    }

    public function onSaveProductCard() {
	$product_code = $this->request('product_code');
	$pdata = array();
	$pdata['ru'] = $this->request('ru');
	$pdata['ua'] = $this->request('ua');
	$pdata['en'] = $this->request('en');
	$pdata['new_product_code'] = $this->request('new_product_code');
	$pdata['product_uktzet'] = $this->request('product_uktzet');
	$pdata['product_unit'] = $this->request('product_unit');
	$pdata['product_spack'] = $this->request('product_spack', 1);
	$pdata['product_bpack'] = $this->request('product_bpack', 1);
	$pdata['product_weight'] = $this->request('product_weight', 2);
	$pdata['product_volume'] = $this->request('product_volume', 2);
	$pdata['is_service'] = $this->request('is_service', 1);

	$pdata['sell'] = $this->request('sell', 2);
	$pdata['buy'] = $this->request('buy', 2);
	$pdata['curr_code'] = $this->request('curr_code');

	$pdata['parent_id'] = $this->request('parent_id', 1);
	$pdata['product_wrn_quantity'] = $this->request('product_wrn_quantity', 1);
	$pdata['party_label'] = $this->request('party_label');

	$this->LoadClass('Dialogs');
	$this->Dialogs->updateProduct($product_code, $pdata);
    }

    public function onDeleteCode() {
	$product_code = $this->request('product_code');
	$this->LoadClass('Dialogs');
	$ok = $this->Dialogs->deleteProduct($product_code);
	$this->response($ok);
    }
}

?>