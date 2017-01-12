<?php


namespace Wsdl2PhpGenerator\Xml;

/**
 * An XML node which represents a specific type of element used when interacting with a SOAP service.
 */
class TypeNode extends XmlNode
{

    /**
     * The original version of the type as returned by the SOAP client.
     *
     * @var string
     */
    protected $wsdlType;

    /**
     * The name of the type.
     *
     * @var string
     */
    protected $name;

    /**
     * The datatype of the value represented by the element.
     *
     * @var string
     */
    protected $restriction;

    private $namespace;

    /**
     * @var bool
     */
    private $anonymous;

    /**
     * @param string $wsdlType The type as represented by the SOAP client.
     */
    public function __construct($document, $element, $name, $namespace, $anonymous = false)
    {
        parent::__construct($document, $element);
        $this->name = $name;
        $this->restriction = $this->parseRestriction();
        $this->namespace = $namespace;
        $this->anonymous = $anonymous;
    }

    private function parseRestriction()
    {
        if($this->element) {
            $childs = $this->xpath("s:restriction");
            if($childs->length > 0) {
                /** @var \DOMElement $child */
                $child = $childs->item(0);
                $attribute = $child->getAttribute("base");
                $restriction = $this->cleanNamespace($attribute);
                return $restriction;
            }
        }

        return "";
    }

    /**
     * Returns whether a sub element of the type may be undefined for the type.
     *
     * @param string $name The name of the sub element.
     * @return bool Whether the sub element may be undefined for the type.
     */
    public function isElementNillable($name)
    {
        foreach ($this->element->getElementsByTagName('element') as $element) {
            if ($element->getAttribute('name') == $name && $element->getAttribute('nillable') == true) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns whether a sub element of the type is an array of elements.
     * @param $name string The name of the sub element
     * @return bool Whether the sub element is an array of elements.
     */
    public function isElementArray($name)
    {
        foreach ($this->element->getElementsByTagName('element') as $element) {
            if ($element->getAttribute('name') == $name &&
                  ($element->getAttribute('maxOccurs') == 'unbounded'
                    || $element->getAttribute('maxOccurs') >= 2)
              ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the minOccurs value of the element.
     * @param $name string The name of the sub element
     * @return int the minOccurs value of the element
     */
    public function getElementMinOccurs($name)
    {
        foreach ($this->element->getElementsByTagName('element') as $element) {
            if ($element->getAttribute('name') == $name) {
                $minOccurs = $element->getAttribute('minOccurs');
                if ($minOccurs === '') {
                    return null;
                }
                return (int) $minOccurs;
            }
        }
        return null;
    }

    /**
     * Returns the base type for the type.
     *
     * This is used to model inheritance between types.
     *
     * @return string The name of the base type for the type.
     */
    public function getBase()
    {
        $base = null;

        if($this->cleanNamespace($this->element->firstChild->nodeName) === "complexContent") {
            if($this->cleanNamespace($this->element->firstChild->firstChild->nodeName) === "extension") {
                $base = $this->cleanNamespace($this->element->firstChild->firstChild->getAttribute('base'));
            }
        }

        return $base;
    }

    /**
     * Returns the sub elements of the type.
     *
     * The elements are returned as an array where keys are names of sub elements and values are their type.
     *
     * @return array An array of sub element names and types.
     */
    public function getElements()
    {
        $parts = array();
        $elements = $this->xpath("s:sequence/s:element|s:complexContent/s:extension/s:sequence/s:element");
        /** @var \DOMNodeList|\DOMElement[] $elements */
        foreach($elements as $element) {
            $name = $element->getAttribute("name");
            if($element->getAttribute("type")) {
                $typeName = $this->cleanNamespace($element->getAttribute("type"));
            } else {
                $main = $element->parentNode->parentNode->getAttribute("name");
                $doc = $name;
                $typeName = $main . ucfirst($doc);
            }
            if ($this->isElementArray($name)) {
                $typeName .= '[]';
            }

            $parts[$name] = $typeName;
        }

        return $parts;
    }

    /**
     * Returns the pattern which the type represents if any.
     *
     * @return string The pattern.
     */
    public function getPattern()
    {
        $pattern = null;

        if ($patternNodes = $this->element->getElementsByTagName('pattern')) {
            if ($patternNodes->length > 0) {
                $pattern = $patternNodes->item(0)->getAttribute('value');
            }
        }

        return $pattern;
    }

    /**
     * Returns an array of values that the type may have if the type is an enumeration.
     *
     * @return string[] The valid enumeration values.
     */
    public function getEnumerations()
    {
        $enums = array();
        foreach ($this->element->getElementsByTagName('enumeration') as $enum) {
            $enums[] = $enum->getAttribute('value');
        };
        return $enums;
    }

    /**
     * Returns the value the type may have.
     *
     * @return string the value of the type.
     */
    public function getRestriction()
    {
        return $this->restriction;
    }

    /**
     * Returns the name of the type.
     *
     * @return string The type name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns whether the type is complex ie. that is may contain sub elements or not.
     *
     * @return bool Whether the type is complex.
     */
    public function isComplex()
    {
        // If array is defied as inherited from array type it has restricton to array elements type, but still is complexType
        return
          $this->restriction == 'struct' ||
          $this->element->localName == 'complexType';
    }

    /**
     * Returns whether the type is an array.
     *
     * @return bool If the type is an array.
     */
    public function isArray()
    {
        $parts = $this->getElements();

        // Array types are complex types with one element, their names begins with 'ArrayOf'.
        // So if not - that's not array. Only field must be array also.
        return $this->isComplex()
            && count($parts) == 1
            && (substr($this->name, 0, 7) == 'ArrayOf')
            && substr(reset($parts), -2, 2) == '[]';
    }

    /**
     * Returns whether the type is abstract.
     *
     * @return bool Whether the type is abstract.
     */
    public function isAbstract()
    {
        return $this->element->hasAttribute('abstract')
            && $this->element->getAttribute('abstract') == 'true';
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return bool
     */
    public function isAnonymous()
    {
        return $this->anonymous;
    }
}
