<?php
namespace PHPianola;

/**
 * JobServer
 *
 * @author warmans
 */
class JobServer extends Ipc\AbstractServer
{
    const MAX_WORKERS = 5;

    /**
     * @var \SplQueue
     */
    private $queue;

    /**
     *
     * @var Util\Timer
     */
    private $timer;

    /**
     * @var int
     */
    private $jobs_dispatched = 0;

    public function __construct($socket_path, \SplQueue $queue, Util\Timer $timer)
    {
        parent::__construct($socket_path);
        $this->queue = $queue;
        $this->timer = $timer;
    }

    public function start()
    {
        for ($i=0; $i < self::MAX_WORKERS; $i++) {
            passthru('nohup php job-worker.php >> /tmp/workers.log 2>&1 &', $status);
        }

        parent::start();
        $this->listen_socket->setOption(SOL_SOCKET, SO_REUSEADDR);
    }

    /**
     *
     * @param \PHPianola\Ipc\Socket $active_socket
     * @param array $msgs
     */
    public function handleMsgs($active_socket, $msgs = array())
    {
        foreach ($msgs as $msg) {

            switch ($msg->getType()) {

                case (JobServer\Package::TYPE_JOB):
                    $this->queue->enqueue($msg->getPayload());
                    break;

                case (JobServer\Package::TYPE_JOB_REQUEST):
                    $this->handleJobRequest($active_socket);
                    break;

                default:
                    //other
                    break;
            }
        }
    }

    /**
     * @param \PHPianola\Ipc\Socket $requester
     */
    protected function handleJobRequest($requester)
    {
        if($this->queue->count() > 0){
            if ($job = $this->queue->shift()) {

                //send job
                if (!$requester->write(new JobServer\Package(JobServer\Package::TYPE_FIRE_AND_FORGET, $job))) {
                    //something went wrong - re-queue job
                    $this->queue->enqueue($job);
                } else {
                    $this->jobs_dispatched++;
                }
            }
        } else {
            $time_elapsed = $this->timer->elapsed();
            echo "Queue was exhausted in $time_elapsed\n";
            echo "Total jobs processed was $this->jobs_dispatched\n";
            exit(0);
        }
    }
}
