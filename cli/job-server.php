<?php
require_once(__DIR__.'/../vendor/autoload.php');

use PHPianola\JobServer\Job;

//setup queue
$num_requests = 10000;

$batch = array();
$batch_size = 10;

$queue = new SplQueue();
for ($i = 0; $i < $num_requests; $i++) {

    //batch requests to cut down on server/worker chatter
    if (count($batch) == $batch_size || $i == $num_requests) {
        $queue->push(new Job\HttpRequestBatch($batch));
        $batch = array();
    } else {
        $batch[] = empty($argv[1]) ? 'http://localhost' : $argv[1];
    }
}

//start server
$server = new \PHPianola\JobServer(__DIR__.'/server.sock', $queue, PHPianola\Util\Timer::start());
$server->setMaxWorkers(5);
//$server->setLogger(new \PHPianola\Log\StdOut());
$server->start();
