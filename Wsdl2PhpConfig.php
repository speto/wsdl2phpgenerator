<?php

namespace Wsdl2Php;

/**
 * Class that contains all the settings possible for the Wsdl2PhpGenerator
 *
 * @package Wsdl2PhpGenerator
 * @author Fredrik Wallgren <fredrik@wallgren.me>
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
class Config
{
  /**
   *
   * @var string The name to use as namespace in the new classes, no namespaces is used if empty
   * @access private
   */
  private $namespaceName;

  /**
   *
   * @var bool Descides if the output is collected to one file or spread over one file per class
   * @access private
   */
  private $oneFile;

  /**
   *
   * @var bool Decides if the output should protect all classes with if(!class_exists statements
   * @access private
   */
  private $classExists;

  /**
   *
   * @var string The directory where to put the file(s)
   * @access private
   */
  private $outputDir;

  /**
   *
   * @var string The wsdl file to use to generate the classes
   * @access private
   */
  private $inputFile;

  /**
   * The array should contain the strings for the options to enable
   *
   * @var array containing all features in the options for the client
   */
  private $optionFeatures;

  /**
   *
   * @var string The wsdl cache to use if any. Possible values WSDL_CACHE_NONE, WSDL_CACHE_DISK, WSDL_CACHE_MEMORY or WSDL_CACHE_BOTH
   */
  private $wsdlCache;

  /**
   *
   * @var string The compression string to use
   */
  private $compression;

  /**
   * Sets all variables
   *
   * @param string $inputFile
   * @param string $outputDir
   * @param bool $oneFile
   * @param bool $classExists
   * @param string $namespaceName
   * @param array $optionsFeatures
   * @param string $wsdlCache
   * @param string $compression
   */
  public function __construct($inputFile, $outputDir, $oneFile, $classExists, $namespaceName = '', $optionsFeatures = array(), $wsdlCache = '', $compression = '')
  {
    $this->namespaceName = $namespaceName;
    $this->oneFile = $oneFile;
    $this->classExists = $classExists;
    $this->outputDir = $outputDir;
    if (substr($this->outputDir, 0, -1) != '/')
    {
      $this->outputDir .= '/';
    }
    $this->inputFile = $inputFile;
    $this->optionFeatures = $optionsFeatures;
    $this->wsdlCache = '';
    if (in_array($wsdlCache, array('WSDL_CACHE_NONE', 'WSDL_CACHE_DISK', 'WSDL_CACHE_MEMORY', 'WSDL_CACHE_BOTH')))
    {
      $this->wsdlCache = $wsdlCache;
    }
    $this->compression = $compression;
  }

  /**
   * @return string Returns the namespace name the output should use
   * @access public
   */
  public function getNamespaceName()
  {
    return $this->namespaceName;
  }

  /**
   * @return bool Returns if the output should be gathered to one file
   * @access public
   */
  public function getOneFile()
  {
    return $this->oneFile;
  }

  /**
   * @return bool Returns if the output should be protected with class_exists statements
   * @access public
   */
  public function getClassExists()
  {
    return $this->classExists;
  }

  /**
   * @return string Returns the path of the output directory
   * @access public
   */
  public function getOutputDir()
  {
    return $this->outputDir;
  }

  /**
   * @return string Returns the path of the input file
   * @access public
   */
  public function getInputFile()
  {
    return $this->inputFile;
  }

  /**
   *
   * @return array An array of strings of all the features to enable
   */
  public function getOptionFeatures()
  {
    return $this->optionFeatures;
  }

  /**
   *
   * @return string Returns the string with the constant to use for wsdl cache
   */
  public function getWsdlCache()
  {
    return $this->wsdlCache;
  }

  /**
   *
   * @return string The compression value to use for the client
   */
  public function getCompression()
  {
    return $this->compression;
  }
}