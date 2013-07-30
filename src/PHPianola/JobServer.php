<?php
namespace PHPianola;

/**
 * JobServer
 *
 * @author warmans
 */
class JobServer extends Ipc\AbstractServer
{
    private $max_workers = 5;

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

    public function setMaxWorkers($num)
    {
        $this->max_workers;
    }

    public function start()
    {
        for ($i=0; $i < $this->max_workers; $i++) {
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

                case (JobServer\Package::TYPE_JOB_REPORT):
                    $this->handleJobReport($msg->getPayload());
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
                if (!$requester->write(new JobServer\Package(JobServer\Package::TYPE_JOB, $job))) {
                    //something went wrong - re-queue job
                    $this->queue->enqueue($job);
                    $this->logger->alert('Job rejected by worker');
                } else {
                    //dispatch was a success
                    $this->jobs_dispatched += $job->getJobCount();
                }
            }
        } else {
            $time_elapsed = $this->timer->elapsedSeconds();
            echo "Queue was exhausted in $time_elapsed seconds\n";
            echo "Total jobs processed was ~$this->jobs_dispatched\n";
            echo "RPS was ~".(number_format($this->jobs_dispatched/$time_elapsed, 4))."\n";
            exit(0);
        }
    }

    protected function handleJobReport($report)
    {
        //echo print_r($report, true)."\n";
    }
}
