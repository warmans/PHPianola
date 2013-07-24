<?php
namespace PHPianola\Ipc;

/**
 * Abstract Server
 *
 * Implements almost everything required to act as a server, it just doesn't implement message handling.
 *
 * @author warmans
 */
abstract class AbstractServer implements \Psr\Log\LoggerAwareInterface
{
    /**
     * @var \PHPianola\Log\AbstractLogger
     */
    protected $logger;

    /**
     * @var \PHPianola\Ipc\Socket
     */
    protected $listen_socket;

    /**
     * @var \PHPianola\Ipc\Socket\Collection
     */
    protected $socket_collection;

    /**
     *
     * @param string $listen_socket path to a unix socket
     */
    public function __construct($socket_path)
    {
        $this->listen_socket = new Socket(AF_UNIX, SOCK_STREAM, 0);
        $this->listen_socket->bind($socket_path);
        $this->listen_socket->listen();

        $this->logger = new \PHPianola\Log\Blackhole();
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->listen_socket->setLogger($logger);
    }

    public function start()
    {
        $this->socket_collection = new Socket\Collection();
        $this->socket_collection->attach($this->listen_socket);

        while (true) {

            //go into blocking selection
            $this->logger->debug('selecting...');
            $data_to_handle = $this->socket_collection->select(null);
            $this->logger->debug("{$data_to_handle['changed']} clients changed");

            if ($data_to_handle['changed'] < 1) {
                continue;
            }

            foreach ($data_to_handle['read'] as $active_socket) {
                $this->handleEvent($active_socket);
            }
        }
    }

    protected function handleEvent($active_socket)
    {
        //add logger to active client
        $active_socket->setLogger($this->logger);

        //check for connections
        if ($active_socket->sameAs($this->listen_socket)) {

            $new_client = $active_socket->accept();

            if (!$this->socket_collection->contains($new_client)) {
                //new client connected
                $this->socket_collection->attach($new_client);
                $this->logger->debug('new client connected: ' . print_r($new_client->getPeerinfo(), true));
            }

            return;
        }

        $this->logger->debug($active_socket->getPeerinfo(). ' changed');

        //new message or someone disconnected
        $msgs = $active_socket->read();

        if (!count($msgs)) {
            $this->logger->debug('client disconnected: '.$active_socket->getPeerinfo());
            $this->socket_collection->detach($active_socket);
            return;
        }

        $this->handleMsgs($active_socket, $msgs);
    }


    abstract protected function handleMsgs($active_socket, $msgs=array());
}
