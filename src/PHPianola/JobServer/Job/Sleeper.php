<?php
namespace PHPianola\JobServer\Job;

/**
 * Sleeper - used for testing
 *
 * @author warmans
 */
class Sleeper extends \PHPianola\JobServer\Job
{
    public function execute()
    {
        sleep(1);
    }
}
