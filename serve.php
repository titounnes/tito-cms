<?php
echo "\n";
echo "Tito-CMS  Line Tool - Server Time: ".date('Y-m-d H:i:s')."\n\n";
echo "Tito-CMS development server started on http://localhost:2306\n";
echo"Press Control-C to stop.\n";
define('APPPATH', __DIR__.'/');
define("FCPATH", APPPATH . "/public"); 

require_once APPPATH .'config.php';

// preparing directory
foreach($config['path'] as $dir){
    if($dir==$config['path']['auth']){
        $config['init'] = ! is_dir($config['path']['auth']);
    }
    if(!is_dir($dir)){
        mkdir($dir, 0755);
        copy($config['html'] .'index.html', $dir.'index.html');
    }
}

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
    file_put_contents(sprintf($config['path']['auth'] .'%s.json',$username), json_encode($admin));
}

shell_exec("php -S localhost:2306 -t ".FCPATH);
