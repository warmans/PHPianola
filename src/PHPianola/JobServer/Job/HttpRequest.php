<?php
namespace PHPianola\JobServer\Job;

/**
 * HTTP Request
 *
 * @author warmans
 */
class HttpRequest extends \PHPianola\JobServer\Job
{
    private $uri;

    public function __construct($uri)
    {
        $this->uri = $uri;
    }

    public function execute()
    {
        $page = file_get_contents($this->uri);
        return $this->returnResult(true, md5($page).':200');
    }
}
