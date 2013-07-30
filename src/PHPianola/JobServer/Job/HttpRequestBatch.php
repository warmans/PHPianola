<?php
namespace PHPianola\JobServer\Job;

/**
 * HTTP Request Batch
 *
 * @author warmans
 */
class HttpRequestBatch extends \PHPianola\JobServer\Job
{
    private $uris = array();

    public function __construct(array $uris)
    {
        $this->uris = $uris;
    }

    public function getJobCount()
    {
        return count($this->uris);
    }

    public function execute()
    {
        $done = 0;
        foreach ($this->uris as $uri) {
            //only store results that are unique
            file_get_contents($uri);
            $done++;
        }
        return $this->returnResult(true, $done.':200');
    }
}
