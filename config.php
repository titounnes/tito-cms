<?php if(! defined('APPPATH') ) {
    header('location: index.php');
}
$config = [
    'path' => [
        'hugo' => APPPATH . 'content/',//sesiakan dengan direktori kerja hugo Anda
        'auth' => APPPATH . 'data/',
    ],
    'html' => APPPATH . 'html/',
    'template' => APPPATH . 'template/',
    'session_name' => 'auth',
];