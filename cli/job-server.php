<?php
require_once(__DIR__.'/../vendor/autoload.php');

use PHPianola\JobServer\Job;

//setup queue
$queue = new SplQueue();
for($i=0; $i<10000; $i++){
    $queue->push(new Job\HttpRequest('http://localhost'));
}

//start server
$server = new \PHPianola\JobServer(__DIR__.'/server.sock', $queue, PHPianola\Util\Timer::start());
//$server->setLogger(new \PHPianola\Log\StdOut());
$server->start();
