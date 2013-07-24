<?php
namespace PHPianola\Ipc;

/**
 * Socket Decorator
 *
 * Wrap a socket resource in an object so we can use it in an OO fashion.
 *
 * @author warmans
 */
class Socket implements \Psr\Log\LoggerAwareInterface
{
    /**
     * The end of transmission char(s). Note these are stripped from data so be careful!
     */
    const EOF = "\0\0";

    /*
     * Number of bytes to send per data block
     */
    const CHUNK_SIZE = 4096;

    private $socket;
    private $socket_config = array();
    private $log;

    /**
     * First param can either be a socket resource or a domain. If a domain is passed a type and protocol
     * may also be required.
     *
     * @param mixed $domain_or_socket
     * @param string $type
     * @param string $protocol
     * @throws \RuntimeException
     */
    public function __construct($domain_or_socket=null, $type=null, $protocol=0)
    {
        if (!is_resource($domain_or_socket)) {
            $this->socket = socket_create($domain_or_socket, $type, $protocol);

            if (!$this->socket) {
                throw $this->getSocketException('Unable to create socket');
            }

            $this->setConf('domain', $domain_or_socket);
            $this->setConf('type', $type);
            $this->setConf('protocol', $protocol);

        } else {
            $this->socket = $domain_or_socket;
        }

        //by default dn't log anywhere
        $this->log = new \Psr\Log\NullLogger();
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->log = $logger;
    }

    public function getPeerinfo()
    {
        if (!$this->getConf('peer_address')) {

            $addr = $port = null;
            if(!socket_getpeername($this->socket, $addr, $port)){
                return false;
            }

            $this->setConf('peer_address', $addr);
            $this->setConf('peer_port', $port);
        }

        return $this->getConf('peer_address').($this->getConf('peer_port') ? ":{$this->getConf('peer_port')}" : '');
    }

    /**
     * Wrap a socket resource in a new instance of this class
     *
     * @param resource $socket
     * @return \static
     */
    public static function wrapSocket($socket)
    {
        return new static($socket);
    }

    /**
     * Set an option on the underlying socket
     *
     * @param int $level
     * @param int $optname
     * @param int $optval
     */
    public function setOption($level, $optname, $optval=1)
    {
        socket_set_option($this->socket, $level, $optname, $optval);
    }

    /**
     * Bind to a particular port or socket file.
     *
     * @param type $address
     * @param type $port
     * @throws type
     */
    public function bind($address, $port=null)
    {
        if (!socket_bind($this->socket, $address, $port)){
            throw $this->getSocketException('Bind failed');
        }

        $this->setConf('bind_address', $address);
        $this->setConf('bind_port', $port);
    }

    /**
     * Connect to the given address
     *
     * @param string $address
     * @param mixed $port
     * @throws \RuntimeException
     */
    public function connect($address, $port=null)
    {
        if (!socket_connect($this->socket, $address, $port)) {
            throw $this->getSocketException("Unable to connect to $address".($port ? ":$port" : ''));
        }

        $this->setConf('connected_address', $address);
        $this->setConf('connected_port', $port);
    }

    /**
     * Listen for incoming connections. Sort of like the oposite of connect.
     *
     * @param int $backlog
     * @throws \RuntimeException
     */
    public function listen($backlog=null)
    {
        if (!socket_listen($this->socket, $backlog)) {
            throw $this->getSocketException("Listen failed");
        }

        $this->setConf('listen_backlog', $backlog);
    }

    /**
     * Accept a socket connection
     *
     * @return Socket
     * @throws \RuntimeException
     */
    public function accept()
    {
        if (!($connected = socket_accept($this->socket))) {
            throw $this->getSocketException("Listen failed");
        }

        //wrap raw socket
        $new_socket = self::wrapSocket($connected);
        $new_socket->setLogger($this->log);
        return $new_socket;
    }

    public function setBlocking($blocking=true)
    {
        if($blocking){
            if (socket_set_block($this->socket)) {
                $this->setConf('blocking', $blocking);
            }
        } else {
            if (socket_set_nonblock($this->socket)) {
                $this->setConf('blocking', $blocking);
            }
        }
    }

    /**
     * Send some data over the socket
     *
     * @param string $buffer data to send
     * @return boolean
     * @throws \Exception
     */
    public function write(Package $data)
    {
        //serialise package to string and add terminator
        $data = $this->sanitizeData($data->serialise()) . self::EOF;

        $buffer_len = strlen($data);
        $buffer_sent = 0;

        $this->log->debug(">-- Sending...   $buffer_len Bytes | ".$data);

        while ($buffer_sent < $buffer_len) {

            //do send
            $sent = socket_write(
                $this->socket,
                substr($data, $buffer_sent),
                $buffer_len-$buffer_sent
            );

            //check errors
            if ($sent === false) {
                return false;
            }

            //acumulate size
            $buffer_sent += $sent;

            //debug
            $this->log->debug("->- Sending...   ".$buffer_sent." ($buffer_len) Bytes");
        }

        // debug
        $this->log->debug("--> Sending...   Complete $buffer_sent Bytes");

        //sent OK
        return true;
    }

    /**
     * Recieve some data from a socket
     *
     * @param resource $socket
     * @param int $timeout
     * @return Package
     * @throws \RuntimeException
     */
    public function read()
    {
        $bytes_recieved = 0;
        $recieved = $buffer = '';

        $this->log->debug("--< Recieving...");

        while($buffer = socket_read($this->socket, self::CHUNK_SIZE)) {

            $this->log->debug("--< Buffing $buffer");

            //accumulate total bytes recieved
            $bytes_recieved += strlen($buffer);

            //debugging
            $this->log->debug("-<- Recieving... ".strlen($buffer)." ($bytes_recieved) Bytes");

            //aggregate message
            $recieved .= $buffer;

            //we have reached the end of a package. There may be more so just set non-blocking and continue reading.
            //if the next iteration is empty we can assume we're done and break.
            //we can't just break as soon as we see EOF because we might have more data to read but by coincidence
            //the EOF char happened to be at the end of this CHUNK
            if (substr($buffer, (0-(strlen(self::EOF))), strlen(self::EOF)) == self::EOF) {
                socket_set_nonblock($this->getSocket()); //hard call to nonblock to avoid messing with the conf
            }

            //last iteration we set non-blocking to make sure there was no data left in the buffer.
            if($buffer === ''){
                //set blocking to whatever it was before we started messing about with it
                $this->setBlocking($this->getConf('blocking'));
                break;
            }
        }

        //we may have recived many packages aggregated into a single read so try and split them up based on the EOF char
        $recieved_packages = array();
        if ($recieved) {
            foreach(explode(self::EOF, rtrim($recieved. self::EOF)) as $serialised_package){
                $package = Package::unserialise($serialised_package);
                if ($package->getType()) {
                    $recieved_packages[] = Package::unserialise($serialised_package);
                }
            }
        }

        $this->log->debug("<-- Recieving... Complete $bytes_recieved Bytes | ".$recieved);

        if (!$buffer) {
            $this->log->debug("-/- Connection Closed");
        }

        return $recieved_packages;
    }

    /**
     * Return undelying socket resource.
     *
     * @return resource
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * Check if this is a duplicate of another socket
     *
     * @param \PHPianola\JobServer\Transport\Socket\Socket $socket
     */
    public function sameAs(Socket $socket)
    {
        return ($socket->getSocket() == $this->getSocket());
    }

    /**
     * Close the socket
     */
    public function close()
    {
        socket_shutdown($this->socket);
        socket_close($this->socket);
    }

    /**
     * We store all the config that is applied to the socket incase we need to use it laster
     *
     * @param string $key
     * @param string $val
     */
    protected function setConf($key, $val)
    {
        $this->socket_config[$key] = $val;
    }

    /**
     * Get socket config
     *
     * @param string $key
     * @return string|null
     */
    public function getConf($key)
    {
        return (isset($this->socket_config[$key])) ? $this->socket_config[$key] : null;
    }

    /**
     * Create exception including socket error
     *
     * @param string $msg
     * @return \RuntimeException
     */
    protected function getSocketException($msg)
    {
        $errorcode = socket_last_error();
        $msg = $msg.': '.socket_last_error().' ('.socket_strerror($errorcode).')';
        socket_clear_error();
        return new \RuntimeException($msg);
    }

    /**
     * Clear EOL chars from data so we can't prematurely end a transmission.
     *
     * @param string $buffer
     */
    protected function sanitizeData($data)
    {
        return str_replace(self::EOF, '', $data);
    }
}
