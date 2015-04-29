<?php

/*
|--------------------------------------------------------------------------
| Register The Laravel Class Loader
|--------------------------------------------------------------------------
|
| In addition to using Composer, you may use the Laravel class loader to
| load your controllers and models. This is useful for keeping all of
| your classes in the "global" namespace without Composer updating.
|
*/

ClassLoader::addDirectories(array(
	                            app_path() . '/commands',
	                            app_path() . '/controllers',
	                            app_path() . '/models',
	                            app_path() . '/utils',
	                            app_path() . '/database/seeds',
                            ));

/*
|--------------------------------------------------------------------------
| Application Error Logger
|--------------------------------------------------------------------------
|
| Here we will configure the error logger setup for the application which
| is built on top of the wonderful Monolog library. By default we will
| build a basic log file setup which creates a single file for logs.
|
*/

if (Config::get('config.logs.path', storage_path() . '/logs/') != '') {
	Log::useFiles(Config::get('config.logs.path') . 'app.log');
}
else {
	Log::useFiles(storage_path() . '/logs/app.log');
}

/*
|--------------------------------------------------------------------------
| Application Error Handler
|--------------------------------------------------------------------------
|
| Here you may handle any errors that occur in your application, including
| logging them or displaying custom views for specific errors. You may
| even register several error handlers to handle different types of
| exceptions. If nothing is returned, the default error view is
| shown, which includes a detailed stack trace during debug.
|
*/

App::error(function (Exception $exception, $code) {
	Log::error($exception);
});

if (Config::get('api.curlError')) {
	App::error(function (Exception $exception) {

		// if a request is being made using cURL,
		// we want the standard errors swapped
		// by something easier to handle.

		$isCurl      = str_contains(Request::server("HTTP_USER_AGENT"), "curl");
		$shouldDebug = (bool)Config::get("app.debug");

		if ($isCurl and $shouldDebug) {
			return $exception;
		}
		if (Config::get('config.logs.path', '') != '') {
			Log::useFiles(Config::get('config.logs.path') . 'curl.log');
		}
		else {
			Log::useFiles(storage_path() . '/logs/curl.log');
		}
		Log::error($exception);

	});
}

/*
|--------------------------------------------------------------------------
| Maintenance Mode Handler
|--------------------------------------------------------------------------
|
| The "down" Artisan command gives you the ability to put an application
| into maintenance mode. Here, you will define what is displayed back
| to the user if maintenance mode is in effect for the application.
|
*/

App::down(function () {
	return Response::make("Ya volvemos!", 503);
});

/*
|--------------------------------------------------------------------------
| Require The Filters File
|--------------------------------------------------------------------------
|
| Next we will load the filters file for the application. This gives us
| a nice separate location to store our route and application filter
| definitions instead of putting them all in the main routes file.
|
*/

require app_path() . '/filters.php';
require app_path() . '/utils/Macros.php';
require app_path() . '/utils/Events.php';

Auth::extend('dummy', function ($app) {
	$provider = new \App\Util\DummyAuthProvider();

	return new \Illuminate\Auth\Guard($provider, $app['session.store']);
});

App::shutdown(function () {
	// Flush buffered logs
	if (App::bound('log.buffer')) {
		App::make('log.buffer')->close();
	}
});