<?php 

// Path to the index.php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 */

// Load our config
define('APPPATH',realpath(FCPATH . '../') . DIRECTORY_SEPARATOR);
require_once APPPATH .'config.php';

//preparing directory
foreach($config['path'] as $dir){
    $path = APPPATH . $dir . '/';
    if($dir=='data'){
        $config['init'] = ! is_dir($path);
    }
    if(!is_dir($path)){
        mkdir($path, 0755);
        copy(APPPATH .'html/index.html', $path.'index.html');
    }
}


// Load app
require_once APPPATH . 'app.php';

$app = new app($config);

/*
 *---------------------------------------------------------------
 * INSTALALLATION FOR FIRST TIME
 *---------------------------------------------------------------
 * Now that everything is setup, it's time to actually fire
 * up the engines and make this app do its thang.
 */

if($config['init']){
    $password = 'admin123';
    $username = 'admin';
    $admin = [
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'profile' => [
            'name' => $username,
            'email' => 'example@mail.com',
            'role' => 'admin'
        ]
    ];
    file_put_contents(sprintf(APPPATH .'data/%s.json',$username), json_encode($admin));
    return $app->install($username, $password);
}

/*
 *---------------------------------------------------------------
 * LAUNCH THE APPLICATION
 *---------------------------------------------------------------
 * Now that everything is setup, it's time to actually fire
 * up the engines and make this app do its thang.
 */

$app->run();