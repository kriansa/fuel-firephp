<?php
// Authors:
// - cadorn, Christoph Dorn <christoph@christophdorn.com>, Copyright 2007, New BSD License
// - qbbr, Sokolov Innokenty <sokolov.innokenty@gmail.com>, Copyright 2011, New BSD License
// - cadorn, Christoph Dorn <christoph@christophdorn.com>, Copyright 2011, MIT License

/**
 * *** BEGIN LICENSE BLOCK *****
 * 
 * [MIT License](http://www.opensource.org/licenses/mit-license.php)
 * 
 * Copyright (c) 2007+ [Christoph Dorn](http://www.christophdorn.com/)
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * 
 * ***** END LICENSE BLOCK *****
 * 
 * @copyright       Copyright (C) 2007+ Christoph Dorn
 * @author          Christoph Dorn <christoph@christophdorn.com>
 * @license         [MIT License](http://www.opensource.org/licenses/mit-license.php)
 * @package         FirePHPCore
 */

/**
 * Sends the given data to the FirePHP Firefox Extension.
 * The data can be displayed in the Firebug Console or in the
 * "Server" request tab.
 * 
 * For more information see: http://www.firephp.org/
 * 
 * @copyright       Copyright (C) 2007+ Christoph Dorn
 * @author          Christoph Dorn <christoph@christophdorn.com>
 * @license         [MIT License](http://www.opensource.org/licenses/mit-license.php)
 * @package         FirePHPCore
 */
class FirePHP {

    /**
     * FirePHP version
     *
     * @var string
     */
    const VERSION = '1.0b1rc1-fuel';

    /**
     * Firebug LOG level
     *
     * Logs a message to firebug console.
     * 
     * @var string
     */
    const LOG = 'LOG';
  
    /**
     * Firebug INFO level
     *
     * Logs a message to firebug console and displays an info icon before the message.
     * 
     * @var string
     */
    const INFO = 'INFO';
    
    /**
     * Firebug WARN level
     *
     * Logs a message to firebug console, displays an warning icon before the message and colors the line turquoise.
     * 
     * @var string
     */
    const WARN = 'WARN';
    
    /**
     * Firebug ERROR level
     *
     * Logs a message to firebug console, displays an error icon before the message and colors the line yellow. Also increments the firebug error count.
     * 
     * @var string
     */
    const ERROR = 'ERROR';
    
    /**
     * Dumps a variable to firebug's server panel
     *
     * @var string
     */
    const DUMP = 'DUMP';
    
    /**
     * Displays a stack trace in firebug console
     *
     * @var string
     */
    const TRACE = 'TRACE';
    
    /**
     * Displays an exception in firebug console
     * 
     * Increments the firebug error count.
     *
     * @var string
     */
    const EXCEPTION = 'EXCEPTION';
    
    /**
     * Displays an table in firebug console
     *
     * @var string
     */
    const TABLE = 'TABLE';
    
    /**
     * Starts a group in firebug console
     * 
     * @var string
     */
    const GROUP_START = 'GROUP_START';
    
    /**
     * Ends a group in firebug console
     * 
     * @var string
     */
    const GROUP_END = 'GROUP_END';
    
    /**
     * Singleton instance of FirePHP
     *
     * @var FirePHP
     */
    protected static $instance = null;
    
    /**
     * Flag whether we are logging from within the exception handler
     * 
     * @var boolean
     */
    protected $inExceptionHandler = false;

    /**
     * Wildfire protocol message index
     *
     * @var integer
     */
    protected $messageIndex = 1;
    
    /**
     * Options for the library
     * 
     * @var array
     */
    protected $options = array('maxDepth' => 10,
                               'maxObjectDepth' => 5,
                               'maxArrayDepth' => 5,
                               'useNativeJsonEncode' => false);

    /**
     * Filters used to exclude object members when encoding
     * 
     * @var array
     */
    protected $objectFilters = array(
        'firephp' => array('objectStack', 'instance', 'json_objectStack'),
        'firephp_test_class' => array('objectStack', 'instance', 'json_objectStack')
    );

    /**
     * A stack of objects used to detect recursion during object encoding
     * 
     * @var object
     */
    protected $objectStack = array();

    /**
     * Flag to enable/disable logging
     * 
     * @var boolean
     */
    protected $enabled = true;

    /**
     * When the object gets serialized only include specific object members.
     * 
     * @return array
     */  
    public function __sleep()
    {
        return array('options', 'objectFilters', 'enabled');
    }
    
    /**
     * Gets singleton instance of FirePHP
     *
     * @param boolean $autoCreate
     * @return FirePHP
     */
    public static function getInstance($autoCreate = false)
    {
        if ($autoCreate === true && !self::$instance) {
            self::init();
        }
        return self::$instance;
    }
    
    /**
     * Creates FirePHP object and stores it for singleton access
     *
     * @return FirePHP
     */
    public static function init()
    {
        return self::setInstance(new self());
    }

    /**
     * Set the instance of the FirePHP singleton
     * 
     * @param FirePHP $instance The FirePHP object instance
     * @return FirePHP
     */
    public static function setInstance($instance)
    {
        return self::$instance = $instance;
    }

    /**
     * Enable and disable logging to Firebug
     * 
     * @param boolean $enabled TRUE to enable, FALSE to disable
     * @return void
     */
    public function setEnabled($enabled)
    {
       $this->enabled = $enabled;
    }
    
    /**
     * Check if logging is enabled
     * 
     * @return boolean TRUE if enabled
     */
    public function getEnabled()
    {
        return $this->enabled;
    }
    
    /**
     * Specify a filter to be used when encoding an object
     * 
     * Filters are used to exclude object members.
     * 
     * @param string $class The class name of the object
     * @param array $filter An array of members to exclude
     * @return void
     */
    public function setObjectFilter($class, $filter)
    {
        $this->objectFilters[strtolower($class)] = $filter;
    }
  
    /**
     * Set some options for the library
     * 
     * Options:
     *  - maxDepth: The maximum depth to traverse (default: 10)
     *  - maxObjectDepth: The maximum depth to traverse objects (default: 5)
     *  - maxArrayDepth: The maximum depth to traverse arrays (default: 5)
     *  - useNativeJsonEncode: If true will use json_encode() (default: true)
     * 
     * @param array $options The options to be set
     * @return void
     */
    public function setOptions($options)
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Get options from the library
     *
     * @return array The currently set options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set an option for the library
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     * @throws Exception
     */  
    public function setOption($name, $value)
    {
        if (!isset($this->options[$name])) {
            throw $this->newException('Unknown option: ' . $name);
        }
        $this->options[$name] = $value;
    }

    /**
     * Get an option from the library
     *
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function getOption($name)
    {
        if (!isset($this->options[$name])) {
            throw $this->newException('Unknown option: ' . $name);
        }
        return $this->options[$name];
    }

    /**
     * Start a group for following messages.
     * 
     * Options:
     *   Collapsed: [true|false]
     *   Color:     [#RRGGBB|ColorName]
     *
     * @param string $name
     * @param array $options OPTIONAL Instructions on how to log the group
     * @return true
     * @throws Exception
     */
    public function group($name, $options = null)
    {
    
        if (!$name) {
            throw $this->newException('You must specify a label for the group!');
        }

        if ($options) {
            if (!is_array($options)) {
                throw $this->newException('Options must be defined as an array!');
            }
            if (array_key_exists('Collapsed', $options)) {
                $options['Collapsed'] = ($options['Collapsed']) ? 'true' : 'false';
            }
        }

        return $this->fb(null, $name, FirePHP::GROUP_START, $options);
    }
  
    /**
     * Ends a group you have started before
     *
     * @return true
     * @throws Exception
     */
    public function groupEnd()
    {
        return $this->fb(null, null, FirePHP::GROUP_END);
    }

    /**
     * Log object with label to firebug console
     *
     * @see FirePHP::LOG
     * @param mixes $object
     * @param string $label
     * @return true
     * @throws Exception
     */
    public function log($object, $label = null, $options = array())
    {
        return $this->fb($object, $label, FirePHP::LOG, $options);
    } 

    /**
     * Log object with label to firebug console
     *
     * @see FirePHP::INFO
     * @param mixes $object
     * @param string $label
     * @return true
     * @throws Exception
     */
    public function info($object, $label = null, $options = array())
    {
        return $this->fb($object, $label, FirePHP::INFO, $options);
    } 

    /**
     * Log object with label to firebug console
     *
     * @see FirePHP::WARN
     * @param mixes $object
     * @param string $label
     * @return true
     * @throws Exception
     */
    public function warn($object, $label = null, $options = array())
    {
        return $this->fb($object, $label, FirePHP::WARN, $options);
    } 

    /**
     * Log object with label to firebug console
     *
     * @see FirePHP::ERROR
     * @param mixes $object
     * @param string $label
     * @return true
     * @throws Exception
     */
    public function error($object, $label = null, $options = array())
    {
        return $this->fb($object, $label, FirePHP::ERROR, $options);
    } 

    /**
     * Dumps key and variable to firebug server panel
     *
     * @see FirePHP::DUMP
     * @param string $key
     * @param mixed $variable
     * @return true
     * @throws Exception
     */
    public function dump($key, $variable, $options = array())
    {
        if (!is_string($key)) {
            throw $this->newException('Key passed to dump() is not a string');
        }
        if (strlen($key) > 100) {
            throw $this->newException('Key passed to dump() is longer than 100 characters');
        }
        if (!preg_match_all('/^[a-zA-Z0-9-_\.:]*$/', $key, $m)) {
            throw $this->newException('Key passed to dump() contains invalid characters [a-zA-Z0-9-_\.:]');
        }
        return $this->fb($variable, $key, FirePHP::DUMP, $options);
    }
  
    /**
     * Log a trace in the firebug console
     *
     * @see FirePHP::TRACE
     * @param string $label
     * @return true
     * @throws Exception
     */
    public function trace($label)
    {
        return $this->fb($label, FirePHP::TRACE);
    } 

    /**
     * Log a table in the firebug console
     *
     * @see FirePHP::TABLE
     * @param string $label
     * @param string $table
     * @return true
     * @throws Exception
     */
    public function table($label, $table, $options = array())
    {
        return $this->fb($table, $label, FirePHP::TABLE, $options);
    }

    /**
     * Check if FirePHP is installed on client
     *
     * @return boolean
     */
    public function detectClientExtension()
    {
        // Check if FirePHP is installed on client via User-Agent header
        if (@preg_match_all('/\sFirePHP\/([\.\d]*)\s?/si', $this->getUserAgent(), $m) &&
           version_compare($m[1][0], '0.0.6', '>=')) {
            return true;
        } else
        // Check if FirePHP is installed on client via X-FirePHP-Version header
        if (@preg_match_all('/^([\.\d]*)$/si', $this->getRequestHeader('X-FirePHP-Version'), $m) &&
           version_compare($m[1][0], '0.0.6', '>=')) {
            return true;
        }
        return false;
    }
 
    /**
     * Log varible to Firebug
     * 
     * @see http://www.firephp.org/Wiki/Reference/Fb
     * @param mixed $object The variable to be logged
     * @return boolean Return TRUE if message was added to headers, FALSE otherwise
     * @throws Exception
     */
    public function fb($object)
    {
        if (!$this->getEnabled()) {
            return false;
        }

        if ($this->headersSent($filename, $linenum)) {
            // If we are logging from within the exception handler we cannot throw another exception
            if ($this->inExceptionHandler) {
                // Simply echo the error out to the page
                echo '<div style="border: 2px solid red; font-family: Arial; font-size: 12px; background-color: lightgray; padding: 5px;"><span style="color: red; font-weight: bold;">FirePHP ERROR:</span> Headers already sent in <b>' . $filename . '</b> on line <b>' . $linenum . '</b>. Cannot send log data to FirePHP. You must have Output Buffering enabled via ob_start() or output_buffering ini directive.</div>';
            } else {
                throw $this->newException('Headers already sent in ' . $filename . ' on line ' . $linenum . '. Cannot send log data to FirePHP. You must have Output Buffering enabled via ob_start() or output_buffering ini directive.');
            }
        }
      
        $type = null;
        $label = null;
        $options = array();
      
        if (func_num_args() == 1) {
        } else if (func_num_args() == 2) {
            switch (func_get_arg(1)) {
                case self::LOG:
                case self::INFO:
                case self::WARN:
                case self::ERROR:
                case self::DUMP:
                case self::TRACE:
                case self::EXCEPTION:
                case self::TABLE:
                case self::GROUP_START:
                case self::GROUP_END:
                    $type = func_get_arg(1);
                    break;
                default:
                    $label = func_get_arg(1);
                    break;
            }
        } else if (func_num_args() == 3) {
            $type = func_get_arg(2);
            $label = func_get_arg(1);
        } else if (func_num_args() == 4) {
            $type = func_get_arg(2);
            $label = func_get_arg(1);
            $options = func_get_arg(3);
        } else {
            throw $this->newException('Wrong number of arguments to fb() function!');
        }

        if (!$this->detectClientExtension()) {
            return false;
        }

        $skipFinalObjectEncode = false;
      
        if ($object instanceof Exception) {
          
            $trace = $object->getTrace();
			$file = \Fuel::clean_path($object->getFile());
			$line = $object->getLine();
			
			$object = array(
				'Class' => get_class($object),
				'Message' => $file.' @ line: '.$line.' - '.$object->getMessage(),
				'File' => $file,
				'Line' => $line,
				'Type' => 'throw',
				'Trace' => $this->_escapeTrace($trace)
			);
			$skipFinalObjectEncode = true;
            $type = self::EXCEPTION;
          
        } else if ($type == self::TRACE) {
            $trace = $object ? $object : debug_backtrace();
            if (!$trace) return false;
			$i = 0;
			$object = array(
				'Class' => isset($trace[$i]['class']) ? $trace[$i]['class'] : '',
				'Type' => isset($trace[$i]['type']) ? $trace[$i]['type'] : '',
				'Function' => isset($trace[$i]['function']) ? $trace[$i]['function'] : '',
				'Message' => $label,
				'File' => isset($trace[$i]['file']) ? Fuel::clean_path($trace[$i]['file']) : '',
				'Line' => isset($trace[$i]['line']) ? $trace[$i]['line'] : '',
				'Args' => isset($trace[$i]['args']) ? $this->encodeObject($trace[$i]['args']) : '',
				'Trace' => $this->_escapeTrace(array_splice($trace, $i + 1)),
			);

			$skipFinalObjectEncode = true;
    
        } else
        if ($type == self::TABLE) {
          
            if (isset($object[0]) && is_string($object[0])) {
                $object[1] = $this->encodeTable($object[1]);
            } else {
                $object = $this->encodeTable($object);
            }
    
            $skipFinalObjectEncode = true;
          
        } else if ($type == self::GROUP_START) {
          
            if (!$label) {
                throw $this->newException('You must specify a label for the group!');
            }
          
        } else {
            if ($type === null) {
                $type = self::LOG;
            }
        }
        
        $this->setHeader('X-Wf-Protocol-1', 'http://meta.wildfirehq.org/Protocol/JsonStream/0.2');
        $this->setHeader('X-Wf-1-Plugin-1', 'http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/' . self::VERSION);
     
        $structureIndex = 1;
        if ($type == self::DUMP) {
            $structureIndex = 2;
            $this->setHeader('X-Wf-1-Structure-2', 'http://meta.firephp.org/Wildfire/Structure/FirePHP/Dump/0.1');
        } else {
            $this->setHeader('X-Wf-1-Structure-1', 'http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1');
        }
      
        if ($type == self::DUMP) {
            $msg = '{"' . $label . '":' . $this->jsonEncode($object, $skipFinalObjectEncode) . '}';
        } else {
            $msgMeta = $options;
            $msgMeta['Type'] = $type;
            if ($label !== null) {
                $msgMeta['Label'] = $label;
            }
            $msg = '[' . $this->jsonEncode($msgMeta) . ',' . $this->jsonEncode($object, $skipFinalObjectEncode) . ']';
        }
        
        $parts = explode("\n", chunk_split($msg, 5000, "\n"));

        for ($i = 0; $i < count($parts); $i++) {
            
            $part = $parts[$i];
            if ($part) {
                
                if (count($parts) > 2) {
                    // Message needs to be split into multiple parts
                    $this->setHeader('X-Wf-1-' . $structureIndex . '-' . '1-' . $this->messageIndex,
                                     (($i == 0) ? strlen($msg) : '')
                                     . '|' . $part . '|'
                                     . (($i < count($parts) - 2) ? '\\' : ''));
                } else {
                    $this->setHeader('X-Wf-1-' . $structureIndex . '-' . '1-' . $this->messageIndex,
                                     strlen($part) . '|' . $part . '|');
                }
                
                $this->messageIndex++;
                
                if ($this->messageIndex > 99999) {
                    throw $this->newException('Maximum number (99,999) of messages reached!');             
                }
            }
        }
    
        $this->setHeader('X-Wf-1-Index', $this->messageIndex - 1);
    
        return true;
    }
  
    /**
     * Standardizes path for windows systems.
     *
     * @param string $path
     * @return string
     */
    protected function _standardizePath($path)
    {
        return preg_replace('/\\\\+/', '/', $path);
    }
  
    /**
     * Escape trace path for windows systems
     *
     * @param array $trace
     * @return array
     */
    protected function _escapeTrace($trace)
    {
        if (!$trace) return $trace;
        for ($i = 0; $i < sizeof($trace); $i++) {
            if (isset($trace[$i]['file'])) {
                $trace[$i]['file'] = \Fuel::clean_path($this->_escapeTraceFile($trace[$i]['file']));
            }
            if (isset($trace[$i]['args'])) {
                $trace[$i]['args'] = $this->encodeObject($trace[$i]['args']);
            }
        }
        return $trace;    
    }
  
    /**
     * Escape file information of trace for windows systems
     *
     * @param string $file
     * @return string
     */
    protected function _escapeTraceFile($file)
    {
        /* Check if we have a windows filepath */
        if (strpos($file, '\\')) {
            /* First strip down to single \ */

            $file = preg_replace('/\\\\+/', '\\', $file);

            return $file;
        }
        return $file;
    }

    /**
     * Check if headers have already been sent
     *
     * @param string $filename
     * @param integer $linenum
     */
    protected function headersSent(&$filename, &$linenum)
    {
        return headers_sent($filename, $linenum);
    }

    /**
     * Send header
     *
     * @param string $name
     * @param string $value
     */
    protected function setHeader($name, $value)
    {
        return header($name . ': ' . $value);
    }

    /**
     * Get user agent
     *
     * @return string|false
     */
    protected function getUserAgent()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) return false;
        return $_SERVER['HTTP_USER_AGENT'];
    }

    /**
     * Get all request headers
     * 
     * @return array
     */
    public static function getAllRequestHeaders()
    {
        static $_cachedHeaders = false;
        if ($_cachedHeaders !== false) {
            return $_cachedHeaders;
        }
        $headers = array();
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                $headers[strtolower($name)] = $value;
            }
        } else {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[strtolower(str_replace(' ', '-', str_replace('_', ' ', substr($name, 5))))] = $value;
                }
            }
        }
        return $_cachedHeaders = $headers;
    }

    /**
     * Get a request header
     *
     * @return string|false
     */
    protected function getRequestHeader($name)
    {
        $headers = self::getAllRequestHeaders();
        if (isset($headers[strtolower($name)])) {
            return $headers[strtolower($name)];
        }
        return false;
    }

    /**
     * Returns a new exception
     *
     * @param string $message
     * @return Exception
     */
    protected function newException($message)
    {
        return new Exception($message);
    }
  
    /**
     * Encode an object into a JSON string
     * 
     * Uses PHP's jeson_encode() if available
     * 
     * @param object $object The object to be encoded
     * @param boolean $skipObjectEncode
     * @return string The JSON string
     */
    public function jsonEncode($object, $skipObjectEncode = false)
    {
        if (!$skipObjectEncode) {
            $object = $this->encodeObject($object);
        }
        
        if (function_exists('json_encode')
           && $this->options['useNativeJsonEncode'] != false) {
            return json_encode($object);
        } else {
            return $this->json_encode($object);
        }
    }

    /**
     * Encodes a table by encoding each row and column with encodeObject()
     * 
     * @param array $table The table to be encoded
     * @return array
     */  
    protected function encodeTable($table)
    {
        if (!$table) return $table;
        
        $newTable = array();
        foreach ($table as $row) {

            if (is_array($row)) {
                $newRow = array();

                foreach ($row as $item) {
                    $newRow[] = $this->encodeObject($item);
                }

                $newTable[] = $newRow;
            }
        }

        return $newTable;
    }

    /**
     * Encodes an object including members with
     * protected and private visibility
     * 
     * @param object $object The object to be encoded
     * @param integer $Depth The current traversal depth
     * @return array All members of the object
     */
    protected function encodeObject($object, $objectDepth = 1, $arrayDepth = 1, $maxDepth = 1)
    {
        if ($maxDepth > $this->options['maxDepth']) {
            return '** Max Depth (' . $this->options['maxDepth'] . ') **';
        }

        $return = array();
    
        if (is_resource($object)) {
    
            return '** ' . (string) $object . ' **';
    
        } else if (is_object($object)) {
    
            if ($objectDepth > $this->options['maxObjectDepth']) {
                return '** Max Object Depth (' . $this->options['maxObjectDepth'] . ') **';
            }
            
            foreach ($this->objectStack as $refVal) {
                if ($refVal === $object) {
                    return '** Recursion (' . get_class($object) . ') **';
                }
            }
            array_push($this->objectStack, $object);
                    
            $return['__className'] = $class = get_class($object);
            $classLower = strtolower($class);

            $reflectionClass = new ReflectionClass($class);
            $properties = array();
            foreach ($reflectionClass->getProperties() as $property) {
                $properties[$property->getName()] = $property;
            }
                
            $members = (array)$object;
    
            foreach ($properties as $plainName => $property) {
    
                $name = $rawName = $plainName;
                if ($property->isStatic()) {
                    $name = 'static:' . $name;
                }
                if ($property->isPublic()) {
                    $name = 'public:' . $name;
                } else if ($property->isPrivate()) {
                    $name = 'private:' . $name;
                    $rawName = "\0" . $class . "\0" . $rawName;
                } else if ($property->isProtected()) {
                    $name = 'protected:' . $name;
                    $rawName = "\0" . '*' . "\0" . $rawName;
                }
    
                if (!(isset($this->objectFilters[$classLower])
                     && is_array($this->objectFilters[$classLower])
                     && in_array($plainName, $this->objectFilters[$classLower]))) {
    
                    if (array_key_exists($rawName, $members) && !$property->isStatic()) {
                        $return[$name] = $this->encodeObject($members[$rawName], $objectDepth + 1, 1, $maxDepth + 1);
                    } else {
                        if (method_exists($property, 'setAccessible')) {
                            $property->setAccessible(true);
                            $return[$name] = $this->encodeObject($property->getValue($object), $objectDepth + 1, 1, $maxDepth + 1);
                        } else
                        if ($property->isPublic()) {
                            $return[$name] = $this->encodeObject($property->getValue($object), $objectDepth + 1, 1, $maxDepth + 1);
                        } else {
                            $return[$name] = '** Need PHP 5.3 to get value **';
                        }
                    }
                } else {
                    $return[$name] = '** Excluded by Filter **';
                }
            }
            
            // Include all members that are not defined in the class
            // but exist in the object
            foreach ($members as $rawName => $value) {
    
                $name = $rawName;

                if ($name{0} == "\0") {
                    $parts = explode("\0", $name);
                    $name = $parts[2];
                }

                $plainName = $name;
    
                if (!isset($properties[$name])) {
                    $name = 'undeclared:' . $name;
    
                    if (!(isset($this->objectFilters[$classLower])
                         && is_array($this->objectFilters[$classLower])
                         && in_array($plainName, $this->objectFilters[$classLower]))) {
    
                        $return[$name] = $this->encodeObject($value, $objectDepth + 1, 1, $maxDepth + 1);
                    } else {
                        $return[$name] = '** Excluded by Filter **';
                    }
                }
            }
            
            array_pop($this->objectStack);
            
        } elseif (is_array($object)) {
    
            if ($arrayDepth > $this->options['maxArrayDepth']) {
                return '** Max Array Depth (' . $this->options['maxArrayDepth'] . ') **';
            }
          
            foreach ($object as $key => $val) {                

                // Encoding the $GLOBALS PHP array causes an infinite loop
                // if the recursion is not reset here as it contains
                // a reference to itself. This is the only way I have come up
                // with to stop infinite recursion in this case.
                if ($key == 'GLOBALS'
                   && is_array($val)
                   && array_key_exists('GLOBALS', $val)) {
                    $val['GLOBALS'] = '** Recursion (GLOBALS) **';
                }

                if (!$this->is_utf8($key)) {
                    $key = utf8_encode($key);
                }

                $return[$key] = $this->encodeObject($val, 1, $arrayDepth + 1, $maxDepth + 1);
            }
        } else {
            if ($this->is_utf8($object)) {
                return $object;
            } else {
                return utf8_encode($object);
            }
        }
        return $return;
    }

    /**
     * Returns true if $string is valid UTF-8 and false otherwise.
     *
     * @param mixed $str String to be tested
     * @return boolean
     */
    protected function is_utf8($str)
    {
        if (function_exists('mb_detect_encoding')) {
            return (
                mb_detect_encoding($str, 'UTF-8', true) == 'UTF-8' &&
                ($str === null || $this->jsonEncode($str, true) !== 'null')
            );
        }
        $c = 0;
        $b = 0;
        $bits = 0;
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);
            if ($c > 128) {
                if (($c >= 254)) return false;
                elseif ($c >= 252) $bits = 6;
                elseif ($c >= 248) $bits = 5;
                elseif ($c >= 240) $bits = 4;
                elseif ($c >= 224) $bits = 3;
                elseif ($c >= 192) $bits = 2;
                else return false;
                if (($i + $bits) > $len) return false;
                while($bits > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191) return false;
                    $bits--;
                }
            }
        }
        return ($str === null || $this->jsonEncode($str, true) !== 'null');
    } 

    /**
     * Converts to and from JSON format.
     *
     * JSON (JavaScript Object Notation) is a lightweight data-interchange
     * format. It is easy for humans to read and write. It is easy for machines
     * to parse and generate. It is based on a subset of the JavaScript
     * Programming Language, Standard ECMA-262 3rd Edition - December 1999.
     * This feature can also be found in  Python. JSON is a text format that is
     * completely language independent but uses conventions that are familiar
     * to programmers of the C-family of languages, including C, C++, C#, Java,
     * JavaScript, Perl, TCL, and many others. These properties make JSON an
     * ideal data-interchange language.
     *
     * This package provides a simple encoder and decoder for JSON notation. It
     * is intended for use with client-side Javascript applications that make
     * use of HTTPRequest to perform server communication functions - data can
     * be encoded into JSON notation for use in a client-side javascript, or
     * decoded from incoming Javascript requests. JSON format is native to
     * Javascript, and can be directly eval()'ed with no further parsing
     * overhead
     *
     * All strings should be in ASCII or UTF-8 format!
     *
     * LICENSE: Redistribution and use in source and binary forms, with or
     * without modification, are permitted provided that the following
     * conditions are met: Redistributions of source code must retain the
     * above copyright notice, this list of conditions and the following
     * disclaimer. Redistributions in binary form must reproduce the above
     * copyright notice, this list of conditions and the following disclaimer
     * in the documentation and/or other materials provided with the
     * distribution.
     *
     * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
     * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
     * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
     * NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
     * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
     * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
     * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
     * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
     * TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
     * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
     * DAMAGE.
     *
     * @category
     * @package     Services_JSON
     * @author      Michal Migurski <mike-json@teczno.com>
     * @author      Matt Knapp <mdknapp[at]gmail[dot]com>
     * @author      Brett Stimmerman <brettstimmerman[at]gmail[dot]com>
     * @author      Christoph Dorn <christoph@christophdorn.com>
     * @copyright   2005 Michal Migurski
     * @version     CVS: $Id: JSON.php,v 1.31 2006/06/28 05:54:17 migurski Exp $
     * @license     http://www.opensource.org/licenses/bsd-license.php
     * @link        http://pear.php.net/pepr/pepr-proposal-show.php?id=198
     */
   
     
    /**
     * Keep a list of objects as we descend into the array so we can detect recursion.
     */
    private $json_objectStack = array();


   /**
    * convert a string from one UTF-8 char to one UTF-16 char
    *
    * Normally should be handled by mb_convert_encoding, but
    * provides a slower PHP-only method for installations
    * that lack the multibye string extension.
    *
    * @param    string  $utf8   UTF-8 character
    * @return   string  UTF-16 character
    * @access   private
    */
    private function json_utf82utf16($utf8)
    {
        // oh please oh please oh please oh please oh please
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
        }

        switch (strlen($utf8)) {
            case 1:
                // this case should never be reached, because we are in ASCII range
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return $utf8;

            case 2:
                // return a UTF-16 character from a 2-byte UTF-8 char
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0x07 & (ord($utf8{0}) >> 2))
                       . chr((0xC0 & (ord($utf8{0}) << 6))
                       | (0x3F & ord($utf8{1})));

            case 3:
                // return a UTF-16 character from a 3-byte UTF-8 char
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr((0xF0 & (ord($utf8{0}) << 4))
                       | (0x0F & (ord($utf8{1}) >> 2)))
                       . chr((0xC0 & (ord($utf8{1}) << 6))
                       | (0x7F & ord($utf8{2})));
        }

        // ignoring UTF-32 for now, sorry
        return '';
    }

   /**
    * encodes an arbitrary variable into JSON format
    *
    * @param    mixed   $var    any number, boolean, string, array, or object to be encoded.
    *                           see argument 1 to Services_JSON() above for array-parsing behavior.
    *                           if var is a strng, note that encode() always expects it
    *                           to be in ASCII or UTF-8 format!
    *
    * @return   mixed   JSON string representation of input var or an error if a problem occurs
    * @access   public
    */
    private function json_encode($var)
    {
        if (is_object($var)) {
            if (in_array($var, $this->json_objectStack)) {
                return '"** Recursion **"';
            }
        }
          
        switch (gettype($var)) {
            case 'boolean':
                return $var ? 'true' : 'false';

            case 'NULL':
                return 'null';

            case 'integer':
                return (int) $var;

            case 'double':
            case 'float':
                return (float) $var;

            case 'string':
                // STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT
                $ascii = '';
                $strlen_var = strlen($var);

               /*
                * Iterate over every character in the string,
                * escaping with a slash or encoding to UTF-8 where necessary
                */
                for ($c = 0; $c < $strlen_var; ++$c) {

                    $ord_var_c = ord($var{$c});

                    switch (true) {
                        case $ord_var_c == 0x08:
                            $ascii .= '\b';
                            break;
                        case $ord_var_c == 0x09:
                            $ascii .= '\t';
                            break;
                        case $ord_var_c == 0x0A:
                            $ascii .= '\n';
                            break;
                        case $ord_var_c == 0x0C:
                            $ascii .= '\f';
                            break;
                        case $ord_var_c == 0x0D:
                            $ascii .= '\r';
                            break;

                        case $ord_var_c == 0x22:
                        case $ord_var_c == 0x2F:
                        case $ord_var_c == 0x5C:
                            // double quote, slash, slosh
                            $ascii .= '\\' . $var{$c};
                            break;

                        case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
                            // characters U-00000000 - U-0000007F (same as ASCII)
                            $ascii .= $var{$c};
                            break;

                        case (($ord_var_c & 0xE0) == 0xC0):
                            // characters U-00000080 - U-000007FF, mask 110XXXXX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c, ord($var{$c + 1}));
                            $c += 1;
                            $utf16 = $this->json_utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xF0) == 0xE0):
                            // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}));
                            $c += 2;
                            $utf16 = $this->json_utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xF8) == 0xF0):
                            // characters U-00010000 - U-001FFFFF, mask 11110XXX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}),
                                         ord($var{$c + 3}));
                            $c += 3;
                            $utf16 = $this->json_utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xFC) == 0xF8):
                            // characters U-00200000 - U-03FFFFFF, mask 111110XX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}),
                                         ord($var{$c + 3}),
                                         ord($var{$c + 4}));
                            $c += 4;
                            $utf16 = $this->json_utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xFE) == 0xFC):
                            // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}),
                                         ord($var{$c + 3}),
                                         ord($var{$c + 4}),
                                         ord($var{$c + 5}));
                            $c += 5;
                            $utf16 = $this->json_utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;
                    }
                }

                return '"' . $ascii . '"';

            case 'array':
                /*
                 * As per JSON spec if any array key is not an integer
                 * we must treat the the whole array as an object. We
                 * also try to catch a sparsely populated associative
                 * array with numeric keys here because some JS engines
                 * will create an array with empty indexes up to
                 * max_index which can cause memory issues and because
                 * the keys, which may be relevant, will be remapped
                 * otherwise.
                 *
                 * As per the ECMA and JSON specification an object may
                 * have any string as a property. Unfortunately due to
                 * a hole in the ECMA specification if the key is a
                 * ECMA reserved word or starts with a digit the
                 * parameter is only accessible using ECMAScript's
                 * bracket notation.
                 */

                // treat as a JSON object
                if (is_array($var) && count($var) && (array_keys($var) !== range(0, sizeof($var) - 1))) {
                  
                    $this->json_objectStack[] = $var;

                    $properties = array_map(array($this, 'json_name_value'),
                                            array_keys($var),
                                            array_values($var));

                    array_pop($this->json_objectStack);

                    foreach ($properties as $property) {
                        if ($property instanceof Exception) {
                            return $property;
                        }
                    }

                    return '{' . join(',', $properties) . '}';
                }

                $this->json_objectStack[] = $var;

                // treat it like a regular array
                $elements = array_map(array($this, 'json_encode'), $var);

                array_pop($this->json_objectStack);

                foreach ($elements as $element) {
                    if ($element instanceof Exception) {
                        return $element;
                    }
                }

                return '[' . join(',', $elements) . ']';

            case 'object':
                $vars = self::encodeObject($var);

                $this->json_objectStack[] = $var;

                $properties = array_map(array($this, 'json_name_value'),
                                        array_keys($vars),
                                        array_values($vars));

                array_pop($this->json_objectStack);
              
                foreach ($properties as $property) {
                    if ($property instanceof Exception) {
                        return $property;
                    }
                }
                     
                return '{' . join(',', $properties) . '}';

            default:
                return null;
        }
    }

   /**
    * array-walking function for use in generating JSON-formatted name-value pairs
    *
    * @param    string  $name   name of key to use
    * @param    mixed   $value  reference to an array element to be encoded
    *
    * @return   string  JSON-formatted name-value pair, like '"name":value'
    * @access   private
    */
    private function json_name_value($name, $value)
    {
        // Encoding the $GLOBALS PHP array causes an infinite loop
        // if the recursion is not reset here as it contains
        // a reference to itself. This is the only way I have come up
        // with to stop infinite recursion in this case.
        if ($name == 'GLOBALS'
           && is_array($value)
           && array_key_exists('GLOBALS', $value)) {
            $value['GLOBALS'] = '** Recursion **';
        }
    
        $encodedValue = $this->json_encode($value);

        if ($encodedValue instanceof Exception) {
            return $encodedValue;
        }

        return $this->json_encode(strval($name)) . ':' . $encodedValue;
    }
}