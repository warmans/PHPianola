<?php
namespace PHPianola\JobServer;

use \PHPianola\Ipc;

/**
 * Worker
 *
 * @author warmans
 */
class Worker implements \Psr\Log\LoggerAwareInterface
{
    private $socket;
    private $logger;

    public function __construct($server_socket_path)
    {
        $this->socket = new Ipc\Socket(AF_UNIX, SOCK_STREAM, 0);
        $this->socket->connect($server_socket_path);
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->socket->setLogger($logger);
    }

    public function work()
    {
        $collection = new Ipc\Socket\Collection();
        $collection->attach($this->socket);



        while (1) {
            //initial request
            $this->socket->write(new Ipc\Package(Package::TYPE_JOB_REQUEST, null));

            if (!count($collection)) {
                $this->logger->debug("No sockets to select");
                return;
            }

            $this->logger->debug("SELECTING");
            $data_to_handle = $collection->select(null);


            foreach ($data_to_handle['read'] as $read_socket) {

                $msgs = $read_socket->read();

                if (!count($msgs)) {
                    $collection->detach($read_socket);
                    $this->logger->debug("Server Disconnected");
                    return;
                }

                foreach ($msgs as $msg) {

                    $this->logger->debug('New Job');

                    //only respond to non ack/fire-and-forget messages
                    if (!in_array($msg->getType(), array(Ipc\Package::TYPE_ACK, Ipc\Package::TYPE_FIRE_AND_FORGET))) {
                        $read_socket->write(new Ipc\Package(Ipc\Package::TYPE_ACK, 'OK', $msg->getFrom()));
                    }

                    if ($result = $this->handleMsg($msg)) {
                        $this->logger->info('Job Completed by '.getmypid());
                    } else {
                        $this->logger->error('Job Failed by '.getmypid());
                    }
                }
            }
        }
    }

    public function handleMsg($msg)
    {
        switch ($msg->getType()) {

            case (Package::TYPE_JOB):
                return $msg->getPayload()->execute();
                break;

            default:
                //other
                break;
        }
    }
}
