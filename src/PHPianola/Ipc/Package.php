<?php
namespace PHPianola\Ipc;

/**
 * Container for data transmissed between processes.
 *
 * @author warmans
 */
class Package
{
    const TYPE_ACK = 'ack';
    const TYPE_REGISTERED = 'reg';
    const TYPE_FIRE_AND_FORGET = 'faf';

    private $from = null;
    private $to = null;

    private $type;
    private $payload;

    public function __construct($type, $payload, $to=null, $from=null)
    {
        $this->from = $from ?: getmypid();
        $this->to = $to;
        $this->type = $type;
        $this->payload = $payload;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function getTo()
    {
        return $this->to;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function serialise()
    {
        return json_encode(
            array('type'=>$this->type, 'payload'=>serialize($this->payload), 'to'=>$this->getTo(), 'from'=>$this->getfrom())
        );
    }

    public static function unserialise($serialised)
    {
        $raw = json_decode($serialised, true);
        return new static($raw['type'], unserialize($raw['payload']), $raw['to'], $raw['from']);
    }
}
