<?php
/**
 * This file is part of the WPMKTGENGINE plugin.
 *
 * Copyright 2016 Genoo, LLC. All rights reserved worldwide.  (web: http://www.wpmktgengine.com/)
 * GPL Version 2 Licensing:
 *  PHP code is licensed under the GNU General Public License Ver. 2 (GPL)
 *  Licensed "As-Is"; all warranties are disclaimed.
 *  HTML: http://www.gnu.org/copyleft/gpl.html
 *  Text: http://www.gnu.org/copyleft/gpl.txt
 *
 * Proprietary Licensing:
 *  Remaining code elements, including without limitation:
 *  images, cascading style sheets, and JavaScript elements
 *  are licensed under restricted license.
 *  http://www.wpmktgengine.com/terms-of-service
 *  Copyright 2016 Genoo LLC. All rights reserved worldwide.
 */

/**
 * Class WPMKTENGINELoader
 * WPMKTENGINELoader implementation that implements the technical interoperability
 * standards for PHP 5.3 namespaces and class names.
 *
 * http://groups.google.com/group/php-standards/web/final-proposal
 *
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Roman S. Borschel <roman@code-factory.org>
 * @author Matthew Weier O'Phinney <matthew@zend.com>
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 * @author Fabien Potencier <fabien.potencier@symfony-project.org>
 */
class WPMKTENGINELoader
{
    /** @var string */
    private $_fileExtension = '.php';
    /** @var array|null|string */
    private $_namespace = array();
    /** @var null */
    private $_includePath;
    /** @var string */
    private $_namespaceSeparator = '\\';

    /**
     * Creates a new <tt>GenooRobotLoader</tt> that loads classes of the
     * specified namespace.
     *
     * @param string $ns The namespace to use.
     */
    public function __construct($ns = null, $includePath = null)
    {
        $this->_namespace = $ns;
        $this->_includePath = $includePath;
    }

    /**
     * @param $dir
     */
    public function setPath($dir){ $this->_includePath = $dir; }

    /**
     * @param $ns
     */
    public function addNamespace($ns){ $this->_namespace[] = $ns; }

    /**
     * Sets the namespace separator used by classes in the namespace of this class loader.
     *
     * @param string $sep The separator to use.
     */
    public function setNamespaceSeparator($sep){ $this->_namespaceSeparator = $sep; }

    /**
     * Gets the namespace seperator used by classes in the namespace of this class loader.
     *
     * @return void
     */
    public function getNamespaceSeparator(){ return $this->_namespaceSeparator; }

    /**
     * Sets the base include path for all class files in the namespace of this class loader.
     *
     * @param string $includePath
     */
    public function setIncludePath($includePath){ $this->_includePath = $includePath; }

    /**
     * Gets the base include path for all class files in the namespace of this class loader.
     *
     * @return string $includePath
     */
    public function getIncludePath(){ return $this->_includePath; }

    /**
     * Sets the file extension of class files in the namespace of this class loader.
     *
     * @param string $fileExtension
     */
    public function setFileExtension($fileExtension){ $this->_fileExtension = $fileExtension; }

    /**
     * Gets the file extension of class files in the namespace of this class loader.
     *
     * @return string $fileExtension
     */
    public function getFileExtension(){ return $this->_fileExtension; }

    /**
     * Installs this class loader on the SPL autoload stack.
     */
    public function register(){ spl_autoload_register(array($this, 'loadClass')); }

    /**
     * Uninstalls this class loader from the SPL autoloader stack.
     */
    public function unregister(){ spl_autoload_unregister(array($this, 'loadClass')); }

    /**
     * Loads the given class or interface.
     *
     * @param string $className The name of the class to load.
     * @return void
     */
    public function loadClass($className)
    {
        if(is_array($this->_namespace)){
            foreach($this->_namespace as $ns){
                if($ns.$this->_namespaceSeparator === substr($className, 0, strlen($ns.$this->_namespaceSeparator))){
                    $fileName = '';
                    $namespace = '';
                    if (false !== ($lastNsPos = strripos($className, $this->_namespaceSeparator))) {
                        $namespace = substr($className, 0, $lastNsPos);
                        $className = substr($className, $lastNsPos + 1);
                        $fileName = str_replace($this->_namespaceSeparator, DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
                    }
                    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . $this->_fileExtension;
                    require ($this->_includePath !== null ? $this->_includePath . DIRECTORY_SEPARATOR : '') . $fileName;
                }
            }
        } else {
            if (null === $this->_namespace || $this->_namespace.$this->_namespaceSeparator === substr($className, 0, strlen($this->_namespace.$this->_namespaceSeparator))){
                $fileName = '';
                $namespace = '';
                if (false !== ($lastNsPos = strripos($className, $this->_namespaceSeparator))) {
                    $namespace = substr($className, 0, $lastNsPos);
                    $className = substr($className, $lastNsPos + 1);
                    $fileName = str_replace($this->_namespaceSeparator, DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
                }
                $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . $this->_fileExtension;
                require ($this->_includePath !== null ? $this->_includePath . DIRECTORY_SEPARATOR : '') . $fileName;
            }
        }
    }
}