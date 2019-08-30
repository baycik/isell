<?php
/**
 * This class catapulted volcanic xml to the surface. ;)
 * This class generates xml markup by xsd file.
 * Feel free to append your custom-data by node-namespace.
 * Your assigned data should represent the attributes.
 * FYI: You have to create a xsd file from xml.
 * Use can use http://www.dotkam.com/2008/05/28/generate-xsd-from-xml/ as converter
 * until we implement our own converter! Or you
 * Design-type: Local elements / global complex types
 * Detect simple content type: Smart
 *
 * @author        redhotmagma        info[at]redhotmagma[dot]de        (http://www.redhotmagma.de)
 * @version        0.1
 * @example
$vulcanoXsdToXmlGenerator = new VulcanoXsdToXmlGenerator($pathToXsdFile);
 * $nodeData = array(
 * 'attributeName1' => 'attributeValue1',
 * 'attributeName2' => 'attributeValue2',
 * 'attributeName3' => 'attributeValue3',
 * );
 * $vulcanoXsdToXmlGenerator->appendNodeData('nodeName', $nodeData);
 * //$vulcanoXsdToXmlGenerator->setOption(VulcanoXsdToXmlGenerator::OPTION_HOLD_ON_MISSING_DATA_NODE_ATTRIBUTE);
 * $xml = $vulcanoXsdToXmlGenerator->generate();
 * var_dump($xml);
 */
class VulcanoXsdToXmlGenerator {
	/**
	 * Behavior for missing data attributes by node name.
	 */
	const OPTION_HOLD_ON_MISSING_DATA_NODE_ATTRIBUTE = 1;
	/**
	 * Behavior for empty attribute value.
	 */
	const OPTION_SHOW_MISSING_NODE_ATTRIBUTES_AS_EMPTY = 2;
	/**
	 * Behavior for missing nodes.
	 */
	const OPTION_SKIP_MISSING_NODE_DATA = 4;
	/**
	 * Default value for empty attribute values.
	 */
	const EMPTY_CELL_VALUE = '?';
	/**
	 * @var int
	 */
	private $options = self::OPTION_SHOW_MISSING_NODE_ATTRIBUTES_AS_EMPTY;
	/**
	 * Path to xsd file.
	 *
	 * @var string
	 */
	private $xsdFile;
	/**
	 * Contains the content of xsd file.
	 *
	 * @var string
	 */
	private $xsdSource;
	/**
	 * Contains the structure of xml.
	 *
	 * @var array
	 */
	private $xmlStructure = array();
	/**
	 * Contains all attributes of nodes.
	 *
	 * @var array
	 */
	private $nodeDefinitionAttributes = array();
	/**
	 * Contains the custom assigned data.
	 * Each node can be contain multiple data and
	 * your data will shown as attributes in a node.
	 *
	 * @var array
	 */
	private $assignedData = array();
	/**
	 * @var \DOMDocument
	 */
	private $domDocument;
	/**
	 * XML Iteration reference.
	 *
	 * @var array
	 */
	private $xmlReference = array();
	/**
	 * Contains optional marked nodes. They will not displayed on missing assigned data.
	 *
	 * @var array
	 */
	private $optionalNodes = array();
	/**
	 * @param    string $xsdFile path o file
	 *
	 * @throws \Exception
	 */
	public function __construct($xsdFile) {
		$this->xsdFile = file_get_contents($xsdFile);
                /*print_r($this->xsdFile);
                die;
		if (!is_readable($this->xsdFile)) {
			throw new \Exception('This file does not exists or is not readable.');
		}*/
		$this->xsdSource = file_get_contents($xsdFile);
		$this->domDocument = new \DOMDocument('1.0', "UTF-8");
		$this->domDocument->formatOutput = true;
		$this->domDocument->preserveWhiteSpace = false;
	}
	public function generate() {
		$this->parseStructure();
	}
        
     
       
        
        

}