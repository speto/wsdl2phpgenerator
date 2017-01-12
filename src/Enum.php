<?php

/**
 * @package Wsdl2PhpGenerator
 */
namespace Wsdl2PhpGenerator;

use \InvalidArgumentException;
use Wsdl2PhpGenerator\PhpSource\PhpClass;
use Wsdl2PhpGenerator\PhpSource\PhpFunction;
use Wsdl2PhpGenerator\PhpSource\PhpVariable;

/**
 * Enum represents a simple type with enumerated values
 *
 * @package Wsdl2PhpGenerator
 * @author Fredrik Wallgren <fredrik.wallgren@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
class Enum extends Type
{
    /**
     * @var array The values in the enum
     */
    private $values;

    private $namespace;

    /**
     * Construct the object
     *
     * @param ConfigInterface $config The configuration
     * @param string $name The identifier for the class
     * @param string $restriction The restriction(datatype) of the values
     */
    public function __construct(ConfigInterface $config, $name, $restriction, $namespace)
    {
        parent::__construct($config, $name, $restriction);
        $this->values = array();
        $this->namespace = $namespace;

    }

    /**
     * Implements the loading of the class object
     *
     * @throws Exception if the class is already generated(not null)
     */
    protected function generateClass()
    {
        if ($this->class != null) {
            throw new Exception("The class has already been generated");
        }

        $this->class = new PhpClass($this->phpIdentifier, false);

        $first = true;

        $names = array();
        foreach ($this->values as $value) {
            $name = Validator::validateConstant($value);

            $name = Validator::validateUnique($name, function ($name) use ($names) {
                    return !in_array($name, $names);
            });

            if ($first) {
                $this->class->addConstant($name, '__default');
                $first = false;
            }

            $this->class->addConstant($value, $name);
            $names[] = $name;
        }

        $this->createConstructor();
        $this->createPrivateValueVariable();
        $this->createGetValueMethod();
        $this->createToStringMethod();
//        $this->createGetTypemapMethod();
    }

    /**
     * Adds the value, typechecks strings and integers.
     * Otherwise it only checks so the value is not null
     *
     * @param mixed $value The value to add
     * @throws InvalidArgumentException if the value doesn'nt fit in the restriction
     */
    public function addValue($value)
    {
        if ($this->datatype == 'string') {
            if (is_string($value) == false) {
                throw new InvalidArgumentException('The value(' . $value . ') is not string but the restriction demands it');
            }
        } elseif ($this->datatype == 'integer') {
            // The value comes as string from the wsdl
            if (is_string($value)) {
                $value = intval($value);
            }

            if (is_int($value) == false) {
                throw new InvalidArgumentException('The value(' . $value . ') is not int but the restriction demands it');
            }
        } else {
            if ($value == null) {
                throw new InvalidArgumentException('Value(' . $value . ') is null');
            }
        }

        $this->values[] = $value;
    }

    /**
     * Returns a comma separated list of all the possible values for the enum
     *
     * @return string
     */
    public function getValidValues()
    {
        $ret = '';
        foreach ($this->values as $value) {
            $ret .= $value . ', ';
        }

        $ret = substr($ret, 0, -2);

        return $ret;
    }

    private function createConstructor()
    {
        $source = '    $this->value = $value;';
        $function = new PhpFunction('public', '__construct', '$value = self::__default', $source, null);
        $this->class->addFunction($function);
    }

    private function createPrivateValueVariable()
    {
        $var = new PhpVariable('private', 'value', '', null);
        $this->class->addVariable($var);
    }

    private function createGetValueMethod()
    {
        $source = '    return $this->value;';
        $function = new PhpFunction('public', 'getValue', '', $source, null);
        $this->class->addFunction($function);
    }

    private function createToStringMethod()
    {
        $source = '    return (string) $this->value;';
        $function = new PhpFunction('public', '__toString', '', $source, null);
        $this->class->addFunction($function);
    }

    private function createGetTypemapMethod()
    {
        $source = '    return [
        "type_ns" => "' . $this->namespace . '",
        "type_name" => "' . $this->identifier . '",
        "from_xml" => function ($xml) {
            $value = simplexml_load_string($xml);
            $value = (' . $this->datatype . ') $value;
            return new ' . $this->identifier . '($value);
        },
    ];';
        $function = new PhpFunction('public static', 'getTypemap', '', $source, null);
        $this->class->addFunction($function);
    }
}
