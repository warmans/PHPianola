<?php
namespace PHPianola\JobServer;

/**
 * Abstract Job
 *
 * @author warmans
 */
abstract class Job
{
    protected function returnResult($sucess, $result)
    {
        return array('success' => $sucess, 'result' => $result);
    }
    
    /**
     * how many descrete jobs does this encompass?
     */
    public function getJobCount(){
        return 1;
    }

    abstract public function execute();
}
