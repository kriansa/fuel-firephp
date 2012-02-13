<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * Debug class
 *
 * The Debug class is a simple utility for debugging variables, objects, arrays, etc by outputting information to the display.
 *
 * @package		Fuel
 * @category	Core
 * @author		Phil Sturgeon
 * @link		http://docs.fuelphp.com/classes/debug.html
 */
class Debug
{

	protected static $files = array();
	
	protected static $fb = null;
	
	public static function _init()
	{
		require_once PKGPATH.'firephp'.DS.'vendor'.DS.'FirePHP.class.php';
		static::$fb = \FirePHP::getInstance(true);
	}

	/**
	 * Quick and nice way to output a mixed variable to the browser
	 *
	 * @static
	 * @access	public
	 * @return	string
	 */
	public static function dump()
	{
		$backtrace = debug_backtrace();

		// If being called from within, show the file above in the backtrack
		if (strpos($backtrace[0]['file'], 'core/classes/debug.php') !== FALSE)
		{
			$callee = $backtrace[1];
		}
		else
		{
			$callee = $backtrace[0];
		}

		$callee['file'] = \Fuel::clean_path($callee['file']);

		$arguments = func_get_args();
		$i = 0;
		
		static::$fb->group($callee['file'].' @ line: '.$callee['line']);
		foreach($arguments as $argument)
		{
			static::$fb->log($argument, 'Variable #'.++$i);
		}
		static::$fb->groupEnd();
	}
	
	/**
	 * Return the FirePHP handle to display messages
	 *
	 * @static
	 * @access	public
	 * @return	FirePHP object
	 */
	public static function firephp()
	{
		return static::$fb;
	}

	/**
	 * Quick and nice way to output a mixed variable to the browser
	 *
	 * @static
	 * @access	public
	 * @return	string
	 */
	public static function inspect()
	{
		$backtrace = debug_backtrace();

		$callee = $backtrace[0];

		$arguments = func_get_args();
		$total_arguments = count($arguments);

		$callee['file'] = \Fuel::clean_path($callee['file']);

		$i=0;
		$table = array();
		$table[] = array('Var #', 'Type', 'Value');

		foreach($arguments as $argument)
		{
			$table[] = array('Debug #'.++$i.' of '.$total_arguments, static::get_type($argument), $argument);
		}

		static::$fb->table($callee['file'].' @ line: '.$callee['line'], $table);
	}

	/**
	 * Gets the type of given var
	 *
	 * @param	mixed	$var	the variable
	 * @return	string	The type of the var.
	 */
	public static function get_type($var)
	{
		if (is_array($var))
		{
			return "Array, ".count($var)." elements";
		}
		elseif (is_string($var))
		{
			return "String, ".strlen($var)." characters";
		}
		elseif (is_float($var))
		{
			return "Float";
		}
		elseif (is_long($var))
		{
			return "Integer";
		}
		elseif (is_null($var))
		{
			return "Null";
		}
		elseif (is_bool($var))
		{
			return "Boolean";
		}
		elseif (is_double($var))
		{
			return "Double";
		}
		elseif (is_object($var))
		{
			return "Object: ".get_class($var);
		}
		
		return $var;
	}

	/**
	 * Returns the debug lines from the specified file
	 *
	 * @access	protected
	 * @param	string		the file path
	 * @param	int			the line number
	 * @param	bool		whether to use syntax highlighting or not
	 * @param	int			the amount of line padding
	 * @return	array
	 */
	public static function file_lines($filepath, $line_num, $highlight = true, $padding = 5)
	{
		// We cache the entire file to reduce disk IO for multiple errors
		if ( ! isset(static::$files[$filepath]))
		{
			static::$files[$filepath] = file($filepath, FILE_IGNORE_NEW_LINES);
			array_unshift(static::$files[$filepath], '');
		}

		$start = $line_num - $padding;
		if ($start < 0)
		{
			$start = 0;
		}

		$length = ($line_num - $start) + $padding + 1;
		if (($start + $length) > count(static::$files[$filepath]) - 1)
		{
			$length = NULL;
		}

		$debug_lines = array_slice(static::$files[$filepath], $start, $length, TRUE);

		if ($highlight)
		{
			$to_replace = array('<code>', '</code>', '<span style="color: #0000BB">&lt;?php&nbsp;', "\n");
			$replace_with = array('', '', '<span style="color: #0000BB">', '');

			foreach ($debug_lines as & $line)
			{
				$line = str_replace($to_replace, $replace_with, highlight_string('<?php ' . $line, TRUE));
			}
		}

		return $debug_lines;
	}

	public static function backtrace()
	{
		$backtrace = debug_backtrace();
		array_shift($backtrace);

		$callee = $backtrace[0];
		$callee['file'] = \Fuel::clean_path($callee['file']);

		return static::$fb->fb($backtrace, $callee['file'].' @ line: '.$callee['line'], \FirePHP::TRACE);
	}

	/**
	* Prints a list of all currently declared classes.
	*
	* @access public
	* @static
	*/
	public static function classes()
	{
		return static::dump(get_declared_classes());
	}

	/**
	* Prints a list of all currently declared interfaces (PHP5 only).
	*
	* @access public
	* @static
	*/
	public static function interfaces()
	{
		return static::dump(get_declared_interfaces());
	}

	/**
	* Prints a list of all currently included (or required) files.
	*
	* @access public
	* @static
	*/
	public static function includes()
	{
	return static::dump(get_included_files());
	}

	/**
	 * Prints a list of all currently declared functions.
	 *
	 * @access public
	 * @static
	 */
	public static function functions()
	{
		return static::dump(get_defined_functions());
	}

	/**
	 * Prints a list of all currently declared constants.
	 *
	 * @access public
	 * @static
	 */
	public static function constants()
	{
		return static::dump(get_defined_constants());
	}

	/**
	 * Prints a list of all currently loaded PHP extensions.
	 *
	 * @access public
	 * @static
	 */
	public static function extensions()
	{
		return static::dump(get_loaded_extensions());
	}

	/**
	 * Prints a list of all HTTP request headers.
	 *
	 * @access public
	 * @static
	 */
	public static function headers()
	{
		return static::dump(getAllHeaders());
	}

	/**
	 * Prints a list of the configuration settings read from <i>php.ini</i>
	 *
	 * @access public
	 * @static
	 */
	public static function phpini()
	{
		if ( ! is_readable(get_cfg_var('cfg_file_path')))
		{
			return false;
		}

		// render it
		return static::dump(parse_ini_file(get_cfg_var('cfg_file_path'), true));
	}

	/**
	 * Benchmark anything that is callable
	 *
	 * @access public
	 * @static
	 */
	public static function benchmark($callable, array $params = array())
	{
		// get the before-benchmark time
		if (function_exists('getrusage'))
		{
			$dat = getrusage();
			$utime_before = $dat['ru_utime.tv_sec'] + round($dat['ru_utime.tv_usec']/1000000, 4);
			$stime_before = $dat['ru_stime.tv_sec'] + round($dat['ru_stime.tv_usec']/1000000, 4);
		}
		else
		{
			list($usec, $sec) = explode(" ", microtime());
			$utime_before = ((float)$usec + (float)$sec);
			$stime_before = 0;
		}

		// call the function to be benchmarked
		$result = is_callable($callable) ? call_user_func_array($callable, $params) : null;

		// get the after-benchmark time
		if (function_exists('getrusage'))
		{
			$dat = getrusage();
			$utime_after = $dat['ru_utime.tv_sec'] + round($dat['ru_utime.tv_usec']/1000000, 4);
			$stime_after = $dat['ru_stime.tv_sec'] + round($dat['ru_stime.tv_usec']/1000000, 4);
		}
		else
		{
			list($usec, $sec) = explode(" ", microtime());
			$utime_after = ((float)$usec + (float)$sec);
			$stime_after = 0;
		}

		return array(
			'user' => sprintf('%1.6f', $utime_after - $utime_before),
			'system' => sprintf('%1.6f', $stime_after - $stime_before),
			'result' => $result
		);
	}

}

