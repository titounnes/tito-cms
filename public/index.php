<?php 

// Path to the index.php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 */

// Load our config
if(! defined("APPPATH")){
    define('APPPATH',realpath(FCPATH . '../') . DIRECTORY_SEPARATOR);
}
require_once APPPATH .'config.php';


// Load app
require_once APPPATH . 'app.php';

$app = new app($config);

/*
 *---------------------------------------------------------------
 * LAUNCH THE APPLICATION
 *---------------------------------------------------------------
 * Now that everything is setup, it's time to actually fire
 * up the engines and make this app do its thang.
 */

$app->run();