<?php

/* Group Name: Синхронизация
 * User Level: 2
 * Plugin Name: XsdToXml
 * Plugin URI: http://isellsoft.com
 * Version: 1.0
 * Description: Tool for XsdToXml export 
 * Author: baycik 2019
 * Author URI: http://isellsoft.com
 */

class XsdToXml extends Catalog {
    public $settings = [];
    private $xsdChoices = [];
    private $xsdRelations = [];
    private $xsdSettings = [];
    private $xsdExceptions = [];
    
    private $xsdTemplates = [];
    private $mainInfo = [];
    
    private $currentProductIndex = '';
    private $currentDataBlock = '';
    private $previousTag = '';
    private $grandmotherNode = '';
    private $fileName = '';
        
    public function index() {
        $this->setInitialInfo();
        $xsdSource = $this->loadFile();
        $xsdArray = $this->parseXSDStructure($xsdSource);
        $main_structure = $xsdArray['element'];
        $this->xsdTemplates = $xsdArray['complexType'];
        $this->modifyInputData();
        $xmlObject = $this->composeXML([$main_structure]);
        return $this->exportResult($xmlObject->saveXML());
    }
   
    private function setInitialInfo() {
        require_once 'settings.php';
        $this->xsdChoices = $xsdChoices;
        $this->xsdRelations = $xsdRelations;
        $this->xsdSettings = $xsdSettings;  
        $this->xsdExceptions = $xsdExceptions;  
        $this->mainInfo = json_decode(file_get_contents('./application/plugins/XsdToXml/models/object.php'));
    }
     
    private function loadFile() {
        $file = './application/plugins/XsdToXml/models/ON_NSCHFDOPPR.xsd';
        $xsdSource = file_get_contents($file);
        $this->domDocument = new \DOMDocument('1.0');
        $this->domDocument->formatOutput = true;
        $this->domDocument->preserveWhiteSpace = false;
        return $xsdSource;
    }
    
    private function parseXSDStructure($xsdSource) {
        $xsdSource = str_replace('xs:', '', $xsdSource);
        $xml_object = simplexml_load_string($xsdSource);
        return $this->xmlToArray($xml_object);
    }
    
    private function xmlToArray($xmlObject, $out = array()) {
        foreach ((array) $xmlObject as $index => $node) {
            $out[$index] = is_object($node) || is_array($node) ? $this->xmlToArray($node) : $node;
        }
        return $out;
    }
    
    private function exportResult($xml){
        $this->domDocument->loadXml($xml);
        $this->domDocument->encoding='windows-1251';
        return $this->domDocument->save($this->fileName.'.xml');
    } 
    
    /*==================  CREATING XML  ======================*/
    
    private function composeXML($array, $rootElement = null, $resultXml = null) {
        if ($resultXml === null){
            return $this->addMainParent($array, $rootElement);
        }
        foreach($array as $i => $element){
            $preparedElement = $this->prepareElement($element);
            
            $typeOfElement = $this->xmlCheckElementType($preparedElement);
            if ($typeOfElement) {
                $delete = $this->composeXML([$typeOfElement], $preparedElement->tagName, $resultXml);
                continue;
            }
            if ($preparedElement->choice) {
                $delete = $this->composeXML([$this->xmlCheckElementChoice($element, $preparedElement, $rootElement)], $preparedElement->tagName, $resultXml);
                continue;
            }
            if (isset($this->xsdSettings[$preparedElement->tagName]) && $this->currentDataBlock != $preparedElement->tagName) {
                $this->currentDataBlock = $preparedElement->tagName;
                if ($this->xsdSettings[$preparedElement->tagName] == 'rows') {
                    $this->xmlEntriesAdd($element, $preparedElement, $resultXml);
                    $this->currentProductIndex = '';
                    continue;
                }
            }
            $this->previousTag = $preparedElement->tagName;
            if($preparedElement->children){  
                $delete = $this->composeXML($preparedElement->children, $preparedElement->tagName, $this->addAttributesToElement($resultXml->addChild($preparedElement->tagName) , $preparedElement->attributes));
                if(!$delete){ 
                    unset($resultXml->{$preparedElement->tagName});
                }
            } else {
                if($preparedElement->value){
                    $delete = $resultXml->addChild($preparedElement->tagName, $preparedElement->value);
                    continue;
                }
                if($this->xmlCheckElementAttributes($element)){
                    $delete = $this->addAttributesToElement($resultXml->addChild($preparedElement->tagName), $preparedElement->attributes);
                } 
            }
        }
        return $resultXml;
    }

    private function prepareElement($element){
        $preparedElement = new stdClass();
        $preparedElement->value = false;
        $preparedElement->attributes = false;
        $preparedElement->children = false;
        $preparedElement->tagTemplateName = false;
        $preparedElement->choice = false;
        
        if(isset($element['@attributes']['name'])){
            $preparedElement->tagName = $element['@attributes']['name'];
        }
        if(isset($element['simpleType']['restriction'])){
            if(isset( $element['simpleType']['restriction']['enumeration']['@attributes']['value'])){
                $preparedElement->value = $element['simpleType']['restriction']['enumeration']['@attributes']['value'];
            } else {
                $preparedElement->value = $this->getAttributeByName($preparedElement->tagName);
            }
        } 
        if(isset($element['complexType']['choice']['element'])){
            $preparedElement->choice = $this->makeChoice($preparedElement->tagName, $element['complexType']['choice']['element']);
        }
        if(isset($element['complexType']['sequence']['element'])){
            $preparedElement->children = $element['complexType']['sequence']['element'];
            if (!array_key_exists('0',$preparedElement->children)){
                $preparedElement->children = [$preparedElement->children];
            }
        }
        if(isset($element['@attributes']['type'])){
            $preparedElement->tagTemplateName = $element['@attributes']['type'];
        } 
        if(isset( $element['complexType']['attribute'])){
            $preparedElement->attributes = $element['complexType']['attribute'];
        } 
        return $preparedElement;
    }
    
    private function addMainParent($array, $rootElement){
        $resultXml = new SimpleXMLElement($rootElement !== null ? $rootElement : "<Файл/>");
        $array = $array[0];
        $rootChildren = $array['complexType']['sequence']['element'];
        return $this->composeXML($rootChildren, $array['@attributes']['name'], $this->addAttributesToElement($resultXml, $array['complexType']['attribute']));
    }
        
    /*==================  ENTRIES MANAGEMENT  ======================*/
    
    private function xmlEntriesAdd($element, $preparedElement, $resultXml){
        $products = $this->mainInfo->view->rows;
        foreach ($products as $key => $product) {
            $this->currentProductIndex = $key;
            $delete = $this->composeXML([$element], $preparedElement->tagName, $resultXml);
        }
    }
    
    /*==================  TYPE MANAGEMENT  ======================*/
    
    private function xmlCheckElementType($preparedElement){
        if ($preparedElement->tagTemplateName) {
            $typeElement = $this->findType($preparedElement->tagTemplateName, $preparedElement->tagName);
            $this->previousTag = $preparedElement->tagTemplateName;
            return $typeElement;
        }
        return false;
    }
    
    private function findType($type_name, $tagName) {
        $resultElement = [
            '@attributes' => ['name'=> $tagName],
            'complexType' => []
        ];
        foreach ($this->xsdTemplates as $k => $v) {
            if (isset($v['@attributes']['name']) && $v['@attributes']['name'] == $type_name) {
                $resultElement['complexType'] = $v;
                return $resultElement;
            }
        }
        return false;
    }
    
    /*==================  CHOICE MANAGEMENT  ======================*/
    
    private function xmlCheckElementChoice($element, $preparedElement, $rootElement){
        $element['complexType']['sequence']['element'] = $preparedElement->choice;
        unset($element['complexType']['choice']);
        if(isset($this->xsdExceptions[$rootElement])){
            $preparedElement->choice['@attributes']['name'] = $preparedElement->tagName;
            $element = $preparedElement->choice;
        }
        return $element;
    }
    
    private function makeChoice($tagName, $array) {
        $choices = $this->xsdChoices;
        foreach ($array as $key => $item) {
            foreach ($choices as $k => $choice) {
                if ($this->checkIfChoiceIsCorrect($tagName, $k, $item['@attributes']['name'])) {
                    $this->grandmotherNode = $this->previousTag;
                    return $item;
                }
            }
        }
        return false;
    }

    private function checkIfChoiceIsCorrect($currentElementName, $choice_key, $attribute_name ){
        if( $choice_key === $this->previousTag . '-' . $attribute_name || 
            $choice_key === $currentElementName . '-' . $attribute_name || 
            $choice_key === $this->grandmotherNode . '-' . $attribute_name){
            return true;
        }
        return false;
    }
    
    /*==================  ATTRIBUTES MANAGEMENT  ======================*/
    
     private function xmlCheckElementAttributes($element){
        $resultXml = new SimpleXMLElement('<Файл/>');
         if(isset( $element['complexType']['attribute'])){
            $attributes = $element['complexType']['attribute'];
        } else {
            $attributes = false;
        }
        $resultXml = $this->addAttributesToElement($resultXml, $attributes);
        return $resultXml;
    } 
    
    private function addAttributesToElement($resultXml, $attributes){
        if ($attributes) { 
            if(!array_key_exists('0',$attributes)){
                $attributes = [$attributes];
            }
            foreach ($attributes as $key => $item) { 
                if (isset($item['@attributes'])) {
                    isset($item['@attributes']['use']) ? $use = $item['@attributes']['use'] : null;
                    $value = $this->getAttributeByName($item['@attributes']['name']);
                    if($value === '@'){
                        $value = $item['simpleType']['restriction']['enumeration']['@attributes']['value'];
                    }
                    if ($value) {
                        $resultXml->addAttribute($item['@attributes']['name'], $value);
                    } 
                }
            }
            if (!$value && $use === 'required' && !isset($this->xsdExceptions[$item['@attributes']['name']])) {
                echo "Warning: Uncompleted attribute \"".$item['@attributes']['name']."\"!</br>";
                die;
            } 
        }
        return $resultXml;
    }
    
    private function getAttributeByName($attribute_name) {
        $current_data_blocks = $this->getCurrentDataBlocks();
        return $this->getDataByAttributeName($current_data_blocks, $attribute_name);
    }
    
    private function getCurrentDataBlocks(){
        $prepared_data = $this->prepareSettingsData();
        $current_data_blocks = $prepared_data->view->head;
        if (!empty($this->currentDataBlock)) {
            $current_data_blocks = $prepared_data->view->{$this->xsdSettings[$this->currentDataBlock]};
        }
        if ($this->currentProductIndex !== '') {
            $current_data_blocks = $prepared_data->view->rows[$this->currentProductIndex];
        }
        return $current_data_blocks;
    }
    
    private function prepareSettingsData(){
        $this->xsdRelations['ИдОтпр'] = $this->stringToHashAdapt($this->mainInfo->view->a->company_code+$this->mainInfo->view->a->company_id);
        $this->xsdRelations['ИдПол'] = $this->stringToHashAdapt($this->mainInfo->view->p->company_code+$this->mainInfo->view->p->company_id);
        $this->fileName = $this->generateFileName();
        $this->xsdRelations['ДатаИнфПр'] = '#'.date('d.m.Y');
        $this->xsdRelations['ВремИнфПр'] = '#'.date('H.i.s');
        $this->xsdRelations['НомСтр'] = '#'.($this->currentProductIndex * 1 + 1);
        $this->xsdRelations['ИдФайл'] = '#'.$this->fileName;
        $this->xsdRelations['ИдОтпр'] = '#'.$this->xsdRelations['ИдОтпр']; 
        $this->xsdRelations['ИдПол'] = '#'.$this->xsdRelations['ИдПол']; 
        return $this->mainInfo; 
    }
    
     private function getDataByAttributeName($current_data, $attribute_name){
        $straightText = false;
        $current_data = [$current_data];
        $current_data[] = $this->mainInfo->view->head;
        $current_data[] = $this->mainInfo->view->doc_view;
        foreach ($this->xsdRelations as $key => $item) {
            if ($attribute_name === $key) {
                foreach($current_data as $current_data_block){
                    if (isset($current_data_block->{$item})) { 
                        $straightText = false;
                        $value = $current_data_block->{$item};
                        return $value;
                    } else {
                        if(strpos($item, '#') > -1){
                            $straightText = str_replace('#', '', $item);
                        }
                        if(strpos($item, '@') > -1){
                            $straightText = $item;
                        }
                    }
                }
            }
        } 
        if (!empty($this->xsdRelations[$attribute_name]) && $straightText) {
            return $straightText;
        }
        return false;
    }
    
    /*==================  DATA MODIFICATION  ======================*/
   
    private function modifyInputData(){
        $counter = ['a','p','doc_view','head','rows','footer'];
        foreach($counter as $blockName ){
            $this->mainInfo->view->{$blockName} = $this->modifyInformationBlock($blockName);
        }
    }
    
    private function modifyInformationBlock($blockName){
        $informationBlock = $this->mainInfo->view->{$blockName};
        if(isset($informationBlock->company_jaddress)){
                $informationBlock = $this->modifyAddress($informationBlock->company_jaddress, $informationBlock);
        }
        if(isset($informationBlock->company_director)){
                $informationBlock = $this->modifyDirectorName($informationBlock->company_director, $informationBlock);
        }
        if(isset($informationBlock->vat_rate)){
            $informationBlock->vat_rate .= '%';
        }
        return $informationBlock;
        
    }

    private function modifyAddress($address, $informationBlock){
        $addressArray = explode(', ',$address);
        $prefix = 'company_jaddress_';
        foreach($addressArray as $addressKey => &$addressItem){
            switch ($addressKey){
                case 0 :
                    $informationBlock->{$prefix.'index'} = $addressItem;
                    break;
                case 2 :
                    $addressItem = str_replace('ГОРОД ', "", $addressItem);
                    $informationBlock->{$prefix.'city'} = $addressItem;
                    break;
                case 3 :
                    $addressItem = str_replace('УЛИЦА ', "", $addressItem);
                    $informationBlock->{$prefix.'street'} = $addressItem;
                    break;
                case 4 :
                    $addressItem = str_replace('ДОМ ', "", $addressItem);
                    preg_match('/[0-9]+/',$addressItem, $houseNumber);
                    $housing = str_replace( $houseNumber[0], "", $addressItem);
                    $informationBlock->{$prefix.'house'} = $houseNumber[0];
                    $informationBlock->{$prefix.'housing'} = $housing;
                    break;
            }
        }
        return $informationBlock;
    }

    
     private function modifyDirectorName($directorName, $informationBlock){
        $directorNameArray = explode(' ',$directorName);
        $prefix = 'company_director_';
        $informationBlock->{$prefix.'surname'} = $directorNameArray[0];
        $informationBlock->{$prefix.'name'} = $directorNameArray[1];
        $informationBlock->{$prefix.'secondname'} = $directorNameArray[2];
        
        return $informationBlock;
    }
    
     /*==================  OTHER  ======================*/
    
    private function generateFileName(){
        $fileName = "ON_SCHFDOPPR_".
                $this->xsdRelations['ИдПол'].'_'.
                 $this->xsdRelations['ИдОтпр'].'_'.
                implode('',array_reverse(explode('.',$this->mainInfo->view->head->doc_date))).'_';
        $nameHash = $this->stringToHashAdapt($fileName);
        return $fileName .= $nameHash;
    }

    private function stringToHashAdapt($string){
        $hash = strtoupper(md5($string));
        foreach([7,12,17,22] as $index){
            $hash = substr_replace($hash, '-', $index, 0);
        }
        return $hash;
    }
    
    public $updateSettings = ['settings' => 'json'];
    public function updateSettings($settings) {
        $this->settings = $settings;
        $encoded = json_encode($settings, JSON_UNESCAPED_UNICODE);
        $sql = "
            UPDATE
                plugin_list
            SET 
                plugin_settings = '$encoded'
            WHERE plugin_system_name = 'CSVExporter'    
            ";
        $this->query($sql);
        return $this->getSettings();
    }

    public function getSettings() {
        $sql = "
            SELECT
                plugin_settings
            FROM 
                plugin_list
            WHERE plugin_system_name = 'CSVExporter'    
            ";
        $row = $this->get_row($sql);
        return json_decode($row->plugin_settings);
    }
}


INSERT INTO `isell_db`.`document_view_types` (`doc_types`, `blank_set`, `view_name`, `view_role`, `view_tpl`) VALUES ('/1/3/', 'ru', 'Счет фактура', 'tax_bill', 'ru/doc/schet-faktura.xsd');
