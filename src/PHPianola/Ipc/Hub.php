<?php
namespace PHPianola\Ipc;

/**
 * Hub
 *
 * Acts sort of like a network hub. Any message recieved is just relayed to all connected sockets.
 *
 * @author warmans
 */
class Hub extends AbstractServer
{
    protected function handleMsgs($active_socket, $msgs=array())
    {
        $this->logger->debug('recieved '.count($msgs).' messages: '.print_r($msgs));

        foreach ($this->socket_collection as $client) {
            //send to all clients excluding original sender and myself
            if (!$client->sameAs($this->listen_socket)) {
                if (!$client->sameAs($active_socket)) {
                    foreach($msgs as $msg){
                        $this->logger->debug('relay sent to '.$client->getPeerinfo());
                        $client->write($msg);
                    }
                } else {
                    $this->logger->debug('relay skipped '.$client->getPeerinfo());
                }
            }
        }
    }
}
