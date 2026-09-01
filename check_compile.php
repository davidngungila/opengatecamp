<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$compiler = $app['blade.compiler'];
$compiled = $compiler->compileString(file_get_contents(__DIR__.'/resources/views/members/index.blade.php'));
$p = strpos($compiled, 'CHP-1001');
echo ($p !== false ? 'data present in compiled' : 'DATA MISSING FROM COMPILED')."\n";
echo substr($compiled, 0, 900)."\n";
