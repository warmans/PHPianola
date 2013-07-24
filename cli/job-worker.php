<?php
require_once(__DIR__.'/../vendor/autoload.php');

$worker = new PHPianola\JobServer\Worker(__DIR__.'/server.sock');
$worker->setLogger(new \PHPianola\Log\StdOut());
$worker->work();
