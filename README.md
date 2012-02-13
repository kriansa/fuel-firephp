# Fuel FirePHP package

This is a simple FirePHP package to allow a better AJAX development with debugging.

Fuel's default debugger is awesome, but still breaks the layout when errors are found. So why not display them on the Firebug's console?

More about *FirePHP*: **http://www.firephp.org/**

## Installing

Clone from Github. Put it on `'packages_dir/firephp'` dir in and add to your app/config/config.php.

	git clone git://github.com/kriansa/fuel-firephp.git

Works with Fuel 1.1

## Usage

This package simply replaces the original error and exception handler and the Debug class. It also adds a FirePHP handler to log things

If you want to log something, just do the following:

```php
Debug::dump('anything');
// Or a trace
Debug::backtrace();
// Or a FirePHP event
Debug::firephp()->info('this is a test');
```

## License

Fuel FirePHP package is released under the MIT License.
