<?php
require_once(__DIR__.'/../vendor/autoload.php');

use \PHPianola\Ipc;

unlink('/tmp/client.sock');

$socket = new Ipc\Socket(AF_UNIX, SOCK_STREAM, 0);
$socket->setLogger(new \PHPianola\Log\StdOut());
$socket->bind('/tmp/client.sock');
$socket->connect('/tmp/server.sock');

$socket->write(new Ipc\Package(Ipc\Package::TYPE_FIRE_AND_FORGET, 'DO SOMETHING'));

$socket->write(new Ipc\Package(Ipc\Package::TYPE_REGISTERED, 'DO SOMETHING_ELSE'));

$collection = new Ipc\Socket\Collection();
$collection->attach($socket);

$got_response = 0;
while ($got_response < 2) {

    echo "SELECTING \n";
    $data_to_handle = $collection->select(null);

    if ($data_to_handle['changed'] > 0) {

        foreach ($data_to_handle['read'] as $read_socket) {

            $responses = $read_socket->read();
            if (count($responses)) {
                print_r($responses);
                $got_response += count($responses);
            }
        }
    }
    echo "not enough responses \n";
}

$socket->close();
