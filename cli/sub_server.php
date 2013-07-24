<?php
require_once(__DIR__.'/../vendor/autoload.php');

use \PHPianola\Ipc;

unlink('/tmp/sub_srv.sock');

$socket = new Ipc\Socket(AF_UNIX, SOCK_STREAM, 0);
$socket->setLogger(new \PHPianola\Log\StdOut());
$socket->bind('/tmp/sub_srv.sock');
$socket->connect('/tmp/server.sock');

$collection = new Ipc\Socket\Collection();
$collection->attach($socket);

while (1) {

    if (!count($collection)) {
        return;
    }

    echo "SELECTING \n";
    $data_to_handle = $collection->select(null);

    if ($data_to_handle['changed'] > 0) {

        foreach ($data_to_handle['read'] as $read_socket) {

            $msgs = $read_socket->read();
            if(!count($msgs)){
                $collection->detach($read_socket);
                echo "Server Disconnected\n";
                return;
            }

            foreach ($msgs as $msg) {
                echo 'New Message '.$msg->getPayload()."\n";

                //only respond to non ack/fire-and-forget messages
                if (!in_array($msg->getType(), array(Ipc\Package::TYPE_ACK, Ipc\Package::TYPE_FIRE_AND_FORGET))) {
                    $read_socket->write(new Ipc\Package(Ipc\Package::TYPE_ACK, 'OK', $msg->getFrom()));
                }
            }

        }
    }
}
