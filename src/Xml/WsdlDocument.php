<?php


namespace Wsdl2PhpGenerator\Xml;


use Exception;
use SoapClient;
use SoapFault;
use Wsdl2PhpGenerator\ConfigInterface;

/**
 * The WSDL document represents a file which is used to access a SOAP service.
 *
 * This is the key point for extracting information about the service, which functions it exposes and which data types
 * are used when calling them.
 */
class WsdlDocument extends SchemaDocument
{

    /**
     * An instance of a PHP SOAP client based on the WSDL file
     *
     * @var SoapClient
     */
    protected $soapClient;

    /**
     * The configuration.
     *
     * @var ConfigInterface
     */
    protected $config;

    private $namespace;

    public function __construct(ConfigInterface $config, $wsdlUrl)
    {
        $this->config = $config;

        // Never use PHP WSDL cache to when creating the SoapClient instance used to extract information.
        // Otherwise we risk generating code for a WSDL that is no longer valid.
        $options = array_merge($this->config->get('soapClientOptions'), array('cache_wsdl' => WSDL_CACHE_NONE));

        try {
            $soapClientClass = new \ReflectionClass($this->config->get('soapClientClass'));
            $this->soapClient = $soapClientClass->newInstance($wsdlUrl, $options);
            parent::__construct($config, $wsdlUrl); // we need to pass $config for proxy settings
        } catch (SoapFault $e) {
            throw new Exception('Unable to load WSDL: ' . $e->getMessage(), $e->getCode(), $e);
        }
        $this->initNamespace();
    }

    /**
     * Returns representations of all the data types used when working with the SOAP service.
     *
     * @return TypeNode[] Data types related to the service.
     */
    public function getTypes()
    {
        $types = array();

        /** @var \DOMElement[] $documentTypes */
        $documentTypes = $this->xpath('//s:simpleType[@name]|//s:complexType[@name]');
        foreach($documentTypes as $documentType) {
            $name = $documentType->getAttribute("name");
            $type = new TypeNode($this->document, $documentType, $name, $this->namespace);
            $types[] = $type;
        }

        /** @var \DOMElement[] $documentTypes */
        $documentTypes = $this->xpath('//s:complexType[not(@name)]');
        foreach($documentTypes as $documentType) {
            $typeName = $documentType->parentNode->parentNode->parentNode->getAttribute("name");
            $elementName = $documentType->parentNode->getAttribute("name");
            $name = $typeName . ucfirst($elementName);
            $type = new TypeNode($this->document, $documentType, $name, $this->namespace, true);
            $types[] = $type;
        }


        return $types;
    }

    /**
     * Returns a representation of the service described by the WSDL file.
     *
     * @return ServiceNode The service described by the WSDL.
     */
    public function getService()
    {
        $serviceNodes = $this->element->getElementsByTagName('service');
        if ($serviceNodes->length > 0) {
            return new ServiceNode($this->document, $serviceNodes->item(0));
        }
        return null;
    }

    /**
     * Returns representations of all the operations exposed by the service.
     *
     * @return OperationNode[] The operations exposed by the service.
     */
    public function getOperations()
    {
        $functions = array();
        foreach ($this->soapClient->__getFunctions() as $functionString) {
            $function = new OperationNode($functionString);
            $functionNodes = $this->xpath('//wsdl:operation[@name=%s]', $function->getName());
            if ($functionNodes->length > 0) {
                $function->setElement($this->document, $functionNodes->item(0));
                $functions[] = $function;
            }
        }
        return $functions;
    }

    private function initNamespace()
    {
        $childs = $this->xpath('//wsdl:definitions');
        /** @var \DOMElement $child */
        $child = $childs->item(0);
        $attribute = $child->getAttribute("targetNamespace");
        $this->namespace = $attribute;
    }
}
