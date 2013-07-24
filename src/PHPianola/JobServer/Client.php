<?php
namespace PHPianola\JobServer;

use \PHPianola\Ipc;

/**
 * Client
 *
 * @author warmans
 */
class Client implements \Psr\Log\LoggerAwareInterface
{
    private $socket;
    private $logger;

    public function __construct($server_socket_path)
    {
        $this->logger = new \PHPianola\Log\Blackhole();
        
        $this->socket = new Ipc\Socket(AF_UNIX, SOCK_STREAM, 0);
        $this->socket->connect($server_socket_path);
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->socket->setLogger($logger);
    }

    public function sendJob(Job $job)
    {
        $this->socket->write(new Package(Package::TYPE_JOB, $job));
    }
}
