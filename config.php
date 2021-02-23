<?php if(! defined('APPPATH') ) {
    header('location: index.php');
}
$config = [
    'path' => [
        'draft' => 'draft',
        'hugo' => 'content',
        'auth' => 'data',
    ]
];