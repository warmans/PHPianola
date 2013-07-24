<?php
require_once(__DIR__.'/../vendor/autoload.php');

$client = new PHPianola\JobServer\Client(__DIR__.'/server.sock');
$client->setLogger(new \PHPianola\Log\StdOut());

for($i=0; $i<1000; $i++){
    $client->sendJob(new \PHPianola\JobServer\Job\HttpRequest('http://localhost'));
}

