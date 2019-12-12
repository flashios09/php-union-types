<?php
declare(strict_types=1);

// (!) don't remove this line, `<script>` tag used for browser-sync(livereload)
require 'browser-sync.html';
require 'vendor/autoload.php';

// $PATH_TO_APP = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR : '';
// define('UnionTypes.PATH_TO_APP', $PATH_TO_APP);

$whoops = new \Whoops\Run();
$whoops->prependHandler(new \Whoops\Handler\PrettyPageHandler());
$whoops->register();
