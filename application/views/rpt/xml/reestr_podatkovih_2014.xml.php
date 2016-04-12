<?php
$this->view['c_reg'] = '08';
$this->view['c_raj'] = '32';
$this->view['tin'] = str_pad($this->view['a']['company_code'], 10, '0', STR_PAD_LEFT);
$this->view['c_doc'] = 'J12';
$this->view['c_doc_sub'] = '015';
$this->view['c_doc_ver'] = '7';
$this->view['c_doc_stan'] = '1';
$this->view['c_doc_type'] = '00';
$this->view['c_doc_cnt'] = str_pad(1, 7, '0', STR_PAD_LEFT);
$this->view['period_type'] = '1';
$this->view['period_month'] = substr($this->view['period'], 5, 2);
$this->view['period_year'] = substr($this->view['period'], 0, 4);
$this->view['c_sti_orig'] = $this->view['c_reg'] . $this->view['c_raj'];


$this->view['document_type']="ПНЕ";


$this->file_name_override =
	  $this->view['c_reg'] 
        . $this->view['c_raj'] 
        . $this->view['tin']
        . $this->view['c_doc'] 
        . $this->view['c_doc_sub']
        . str_pad($this->view['c_doc_ver'], 2, '0', STR_PAD_LEFT) 
        . $this->view['c_doc_stan'] 
        . $this->view['c_doc_type']
        . $this->view['c_doc_cnt'] 
        . $this->view['period_type'] 
        . $this->view['period_month'] 
        . $this->view['period_year'] 
        . $this->view['c_sti_orig'] . '.xml';
header('Content-Disposition: attachment;filename="' .$this->file_name_override . '"');
