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


class Error
{

	public static $levels = array(
		0                  => 'Error',
		E_ERROR            => 'Error',
		E_WARNING          => 'Warning',
		E_PARSE            => 'Parsing Error',
		E_NOTICE           => 'Notice',
		E_CORE_ERROR       => 'Core Error',
		E_CORE_WARNING     => 'Core Warning',
		E_COMPILE_ERROR    => 'Compile Error',
		E_COMPILE_WARNING  => 'Compile Warning',
		E_USER_ERROR       => 'User Error',
		E_USER_WARNING     => 'User Warning',
		E_USER_NOTICE      => 'User Notice',
		E_STRICT           => 'Runtime Notice'
	);

	public static $fatal_levels = array(E_PARSE, E_ERROR, E_USER_ERROR, E_COMPILE_ERROR);

	public static $count = 0;

	public static $non_fatal_cache = array();
	
	protected static $fb = null;
	
	public static function _init()
	{
		require_once PKGPATH.'firephp'.DS.'vendor'.DS.'FirePHP.class.php';
		static::$fb = \FirePHP::getInstance(true);
	}

	/**
	 * Native PHP shutdown handler
	 *
	 * @return  string
	 */
	public static function shutdown_handler()
	{
		$last_error = error_get_last();

		// Only show valid fatal errors
		if ($last_error AND in_array($last_error['type'], static::$fatal_levels))
		{
			$severity = static::$levels[$last_error['type']];
			logger(\Fuel::L_ERROR, $severity.' - '.$last_error['message'].' in '.$last_error['file'].' on line '.$last_error['line']);

			$error = new \ErrorException($last_error['message'], $last_error['type'], 0, $last_error['file'], $last_error['line']);
			if (\Fuel::$env != \Fuel::PRODUCTION)
			{
				static::show_php_error($error);
			}
			else
			{
				static::show_production_error($error);
			}

			exit(1);
		}
	}

	/**
	 * PHP Exception handler
	 *
	 * @param   Exception  $e  the exception
	 * @return  bool
	 */
	public static function exception_handler(\Exception $e)
	{
		if (method_exists($e, 'handle'))
		{
			return $e->handle();
		}

		$severity = ( ! isset(static::$levels[$e->getCode()])) ? $e->getCode() : static::$levels[$e->getCode()];
		logger(\Fuel::L_ERROR, $severity.' - '.$e->getMessage().' in '.$e->getFile().' on line '.$e->getLine());

		if (\Fuel::$env != \Fuel::PRODUCTION)
		{
			static::show_php_error($e);
		}
		else
		{
			static::show_production_error($e);
		}
	}

	/**
	 * PHP Error handler
	 *
	 * @param   int     $severity  the severity code
	 * @param   string  $message   the error message
	 * @param   string  $filepath  the path to the file throwing the error
	 * @param   int     $line      the line number of the error
	 * @return  bool    whether to continue with execution
	 */
	public static function error_handler($severity, $message, $filepath, $line)
	{
		if (static::$count <= Config::get('errors.throttling', 10))
		{
			logger(\Fuel::L_ERROR, $severity.' - '.$message.' in '.$filepath.' on line '.$line);

			if (\Fuel::$env != \Fuel::PRODUCTION and ($severity & error_reporting()) == $severity)
			{
				static::$count++;
				static::show_php_error(new \ErrorException($message, $severity, 0, $filepath, $line));
			}
		}
		elseif (\Fuel::$env != \Fuel::PRODUCTION
				and static::$count == (\Config::get('error_throttling', 10) + 1)
				and ($severity & error_reporting()) == $severity)
		{
			static::$count++;
			static::notice('Error throttling threshold was reached, no more full error reports are shown.', true);
		}

		return true;
	}

	/**
	 * Shows an error.  It will stop script execution if the error code is not
	 * in the errors.continue_on whitelist.
	 *
	 * @param   Exception  $e  the exception to show
	 * @return  void
	 */
	public static function show_php_error(\Exception $e)
	{
		$fatal = (bool)( ! in_array($e->getCode(), \Config::get('errors.continue_on', array())));

		if (\Fuel::$is_cli)
		{
			$data = static::prepare_exception($e);
			\Cli::write(\Cli::color($data['severity'].' - '.$data['message'].' in '.\Fuel::clean_path($data['filepath']).' on line '.$data['error_line'], 'red'));
			return;
		}

		try
		{
			static::$fb->error($e);
		}
		catch (\Exception $e)
		{
			echo $e->getMessage().'<br />';
		}
		
		if ($fatal)
		{
			if ( ! headers_sent())
			{
				$protocol = \Input::server('SERVER_PROTOCOL') ? \Input::server('SERVER_PROTOCOL') : 'HTTP/1.1';
				header($protocol.' 500 Internal Server Error');
			}
			
			exit();
		}
	}

	/**
	 * Shows a small notice error, only when not in production or when forced.
	 * This is used by several libraries to notify the developer of certain things.
	 *
	 * @param   string  $msg          the message to display
	 * @param   bool    $always_show  whether to force display the notice or not
	 * @return  void
	 */
	public static function notice($msg, $always_show = false)
	{
		$trace = array_merge(array('file' => '(unknown)', 'line' => '(unknown)'), \Arr::element(debug_backtrace(), 1));
		logger(\Fuel::L_DEBUG, 'Notice - '.$msg.' in '.$trace['file'].' on line '.$trace['line']);

		if (\Fuel::$is_test or ( ! $always_show and (\Fuel::$env == \Fuel::PRODUCTION or \Config::get('errors.notices', true) === false)))
		{
			return;
		}
		
		static::$fb->warn($msg, 'Notice - in '.\Fuel::clean_path($trace['file']).' on line '.$trace['line']);
	}

	/**
	 * Shows the errors/production view and exits.  This only gets
	 * called when an error occurs in production mode.
	 *
	 * @return  void
	 */
	public static function show_production_error(\Exception $e)
	{
		// when we're on CLI, always show the php error
		if (\Fuel::$is_cli)
		{
			return static::show_php_error($e);
		}

		if ( ! headers_sent())
		{
			$protocol = \Input::server('SERVER_PROTOCOL') ? \Input::server('SERVER_PROTOCOL') : 'HTTP/1.1';
			header($protocol.' 500 Internal Server Error');
		}
		exit(\View::forge('errors'.DS.'production'));
	}

	protected static function prepare_exception(\Exception $e)
	{
		$data = array();
		$data['severity']	= $e->getCode();
		$data['message']	= $e->getMessage();
		$data['filepath']	= str_replace("\\", "/", \Fuel::clean_path($e->getFile()));
		$data['error_line']	= $e->getLine();

		$data['severity'] = ( ! isset(static::$levels[$data['severity']])) ? $data['severity'] : static::$levels[$data['severity']];
		
		return $data;
	}

}


