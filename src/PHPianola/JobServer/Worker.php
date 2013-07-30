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

                    if ($result = $this->handleMsg($msg)) {
                        //send report
                        $read_socket->write(
                            new Ipc\Package(
                                Package::TYPE_JOB_REPORT,
                                json_encode(array_merge($result, array('worker' => getmypid()))),
                                $msg->getFrom()
                            )
                        );
                    } else {
                        //discard failed jobs. The sender knows how many they sent. They can infer the failure rate.
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
            default:
                return array('success' => false, 'result' => 'unknown type:'.$msg->getType());
        }
    }
}
