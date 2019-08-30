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
    private $xsd_source = null;
    private $xsd_choices = [];
    private $xsd_relations = [];
    private $xsd_settings = [];
    private $xsd_templates = [];
    private $main_info = [];
    private $current_xml = [];
    private $current_product_index = '';
    private $curr_comp_tag = '';
    private $previousTag = '';
    private $motherNode = '';
    private $grandmotherNode = '';
    
    
    public function index() {
        require_once 'settings.php';
        $this->xsd_choices = $xsd_choices;
        $this->xsd_relations = $xsd_relations;
        $this->xsd_settings = $xsd_settings;    
        $file = './application/plugins/XsdToXml/models/SCHFDOPPR.xsd';
        $this->xsd_source = file_get_contents($file);
        $this->domDocument = new \DOMDocument('1.0');
        $this->domDocument->formatOutput = true;
        $this->domDocument->preserveWhiteSpace = false;
        $this->parseStructure();
    }
   

    private function parseStructure() {
        $this->xsd_source = str_replace('xs:', '', $this->xsd_source);
        $xml_object = simplexml_load_string($this->xsd_source);
        $php_object = $this->xml2array($xml_object);
        $main_structure = $php_object['element'];
        $templates = $php_object['complexType'];
        $this->main_info = json_decode(file_get_contents('./application/plugins/XsdToXml/models/object.php'));
        $this->xsd_templates = $templates;
        $xmlPlain = $this->arrayToXml($main_structure);
        $this->domDocument->loadXml($xmlPlain);
        $this->domDocument->encoding='windows-1251';
        $this->domDocument->save('test.xml');
    }
    
    
    
    private function xml2array($xmlObject, $out = array()) {
        foreach ((array) $xmlObject as $index => $node) {
            $out[$index] = is_object($node) || is_array($node) ? $this->xml2array($node) : $node;
        }
        return $out;
    }

    private function arrayToXml($array, $rootElement = null, $resultXml = null, $tagName = null) {
        if ($resultXml === null) {
            $resultXml = new SimpleXMLElement($rootElement !== null ? $rootElement : '<Файл/>');
        }
        $templates = $this->xsd_templates;
        
        $tagName = $array['@attributes']['name'];
        
         if(isset($array['complexType']['sequence']['element'])){
            $children = $array['complexType']['sequence']['element'];
        } else {
            $children = false;
        }
        
        if(isset($array['@attributes']['type'])){
            $tagTemplateName = $array['@attributes']['type'];
        } else {
            $tagTemplateName = false;
        }
        
        if ($tagTemplateName) {
            $type_element = $this->findType($tagTemplateName, $tagName);
            $this->motherNode = $tagTemplateName;
            if ($type_element) {
                return $this->arrayToXml($type_element, $tagName, $resultXml, $tagName);
            }
        }
        
        if(isset($array['complexType']['choice']['element'])){
            $choice = $this->makeChoice($tagName, $array['complexType']['choice']['element']);
            if ($choice) {
                $tagName = $choice['@attributes']['name'];
                return $this->arrayToXml($choice, $tagName, $resultXml->addChild($tagName), $tagName);
            }
        } 
        
        if(isset( $array['complexType']['attribute'])){
            $attributes = $array['complexType']['attribute'];
        } else {
            $attributes = false;
        }
        /*
         if($tagName == 'Контакт'){
             $attributes = $children['attribute'];
             $children = [];
            }  
        */
        $resultXml = $this->addAttributesToElement($resultXml, $attributes);
        
        /*
        if(empty($resultXml)){
            print_r($tagName);
            return;
        } */
           
        
        
        
               
        $this->previousTag = $tagName;
        $this->currentXML = $array;
        
        if($children){  
            if (array_key_exists('0',$children)) {
                foreach($children as $child){
                     $tagName = $child['@attributes']['name'];
                   /*$childAttributesFound = $this->checkElementAttributes($child);
                    if(empty($childAttributesFound)){
                        continue;
                    }*/
                    $this->arrayToXml($child, $tagName, $resultXml->addChild($tagName), $tagName);
                }
            } else {
                if(empty($children['@attributes']['name'])){
                    print_r($this->previousTag);
                    die;
                }
                  
                if(!empty($children['@attributes']['name'])){
                    $tagName = $children['@attributes']['name'];
                } else {
                    $tagName = $this->previousTag;
                }
                
                $this->arrayToXml($children, $tagName, $resultXml->addChild($tagName), $tagName);
            }
        } else {
            //print_r($tagName);
           
        } 
        
        return $resultXml->saveXML();
        
        
        
        foreach ($array as $k => $v) {
            if(!is_array($v)){
                print_r($v);
                die;
            }       
            
            if (isset($v['@attributes']['name'])) {
                if (strpos($v['@attributes']['name'], 'Тип') > -1) {
                    $currentElementName = $currentElementName;
                } else {
                    $currentElementName = $v['@attributes']['name'];
                }
            }  else if ($k === 'choice') {
                $v = $this->makeChoice($currentElementName, $array);
                $currentElementName = $v['@attributes']['name'];
            } else if ($rootElement === null) {
                $currentElementName = 'Файл';
            }
            if (isset($this->xsd_settings[$currentElementName]) && $this->curr_comp_tag != $currentElementName) {
                $this->curr_comp_tag = $currentElementName;

                if ($this->xsd_settings[$currentElementName] == 'rows') {
                    $products = $this->main_info->view->rows;
                    foreach ($products as $key => $product) {
                        $this->current_product_index = $key;
                        $this->arrayToXml($array[0], $currentElementName, $_xml->addChild($currentElementName), $templates, $currentElementName);
                    }
                    $this->current_product_index = '';
                    continue;
                }
            }
            if ($_xml === null) {
                $_xml = new SimpleXMLElement($rootElement !== null ? $rootElement : '<' . $currentElementName . '/>');
            }
            /*
              if(isset($v['@attributes']['minOccurs']) && $v['@attributes']['minOccurs'] == 0){
              if(isset($v['complexType']['attribute'])){
              print_r($v);
              }
              } */
            if (isset($v['simpleType'])) {
                if (isset($v['simpleType']['restriction']['enumeration']['@attributes']['value'])) {
                    //print_r($v);
                    $this->arrayToXml($v['simpleType']['restriction']['enumeration'], $currentElementName, $_xml->addChild($currentElementName, $v['simpleType']['restriction']['enumeration']['@attributes']['value']), $templates, $currentElementName);
                    continue;
                }
                if (isset($v['simpleType']['restriction']['@attributes']['base'])) {
                    $v['attribute'][0]['@attributes']['name'] = $currentElementName; 
                }
            }

            $value = '';
            if (isset($v['attribute'])) {
                foreach ($v['attribute'] as $key => $item) {
                    if (isset($item['@attributes'])) {
                        $value = $this->getAttributeByName($item['@attributes']['name']);
                        if ($value) {
                            $_xml->addAttribute($item['@attributes']['name'], $value);
                        }
                    }
                }
                if(empty($_xml) && !isset($v['sequence']['element']) && !isset($v['sequence']['element'])){
                    $this->current_xml[] = $currentElementName;
                }
            }
          
           /*     
            if (isset($v['@attributes']['type'])) {
                $type_element = $this->findType($v['@attributes']['type'], $templates);
                $this->motherNode = $currentElementName;
                if ($type_element) {
                    $this->arrayToXml($type_element, $currentElementName, $_xml->addChild($currentElementName), $templates, $currentElementName);
                    continue;
                }
            }

            if ($k === '@attributes' || $k === 'annotation' || $k === 'attribute') {
                continue;
            }*/
            
            
              /*
            if ($k === 'complexType' || $k === 'sequence' || ($k === 'element' && isset($v['0'])) || (isset($v['@attributes']['name']) && strpos($v['@attributes']['name'], 'Тип') > -1)) {
                $this->arrayToXml($v['complexType']['sequence'], $currentElementName, $_xml, $templates, $currentElementName);
                continue;
            }*/

            if (is_array($v)) {
                $this->arrayToXml($v['complexType'], $currentElementName, $_xml->addChild($currentElementName), $templates, $currentElementName);
            } else {
                $_xml->addChild($currentElementName, $v);
            }
        }
        return $_xml->saveXML();
    }

    private function prepare(){
        
    }
    
    
    private function checkElementAttributes($element){
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
        $value = '';
        if ($attributes) {
            foreach ($attributes as $key => $item) {
                if (isset($item['@attributes'])) {
                    $value = $this->getAttributeByName($item['@attributes']['name']);
                    if ($value) {
                        $resultXml->addAttribute($item['@attributes']['name'], $value);
                    }
                }
            }
        }
        return $resultXml;
    }
    
    
    private function findType($type_name, $tagName) {
        $resultElement = [
            '@attributes' => ['name'=> $tagName],
            'complexType' => []
        ];
        foreach ($this->xsd_templates as $k => $v) {
            if (isset($v['@attributes']['name']) && $v['@attributes']['name'] == $type_name) {
                $resultElement['complexType'] = $v;
            return $resultElement;
            }
        }
        return false;
    }

    private function makeChoice($tagName, $array) {
        $choices = $this->xsd_choices;
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
    
    private function getAttributeByName($attribute_name) {
        $current_data_block = $this->getCurrentDataBlock();
        return $this->getDataByAttributeName($current_data_block, $attribute_name);
        
    }
    
    private function getCurrentDataBlock(){
        $prepared_data = $this->prepareDataForAttribute();
        $current_data_block = $prepared_data->view->head;
        if (!empty($this->curr_comp_tag)) {
            $current_data_block = $prepared_data->view->{$this->xsd_settings[$this->curr_comp_tag]};
        }
        if ($this->current_product_index !== '') {
            $current_data_block = $prepared_data->view->rows[$this->current_product_index];
        }
        return $current_data_block;
    }
    
    private function prepareDataForAttribute(){
        $this->xsd_relations['ДатаИнфПр'] = date('d.m.Y');
        $this->xsd_relations['ВремИнфПр'] = date('H.i.s');
        $this->xsd_relations['НомСтр'] = $this->current_product_index * 1 + 1;
        return $this->main_info;
        
    }
    
    private function getDataByAttributeName($current_data_block, $attribute_name){
        foreach ($this->xsd_relations as $key => $item) {
            if ($attribute_name === $key) {
                if (isset($current_data_block->{$item})) {
                    return $current_data_block->{$item};
                }
            }
        }
        if (isset($this->xsd_relations[$attribute_name])) {
            return $this->xsd_relations[$attribute_name];
        }
        return false;
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
