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
        file_get_contents($this->uri);
    }
}
