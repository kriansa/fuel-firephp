<?php
/**
 * FirePHP package implementation.
 *
 * @author     Kriansa
 * @version    1.0
 * @package    Fuel
 * @subpackage FirePHP
 */

Autoloader::add_classes(array(
	'Fuel\\Core\\Debug'       => __DIR__.'/classes/debug.php',
	'Fuel\\Core\\Error'       => __DIR__.'/classes/error.php',
));