<?php
namespace PHPianola\Util;

/**
 * Timer
 *
 * @author Stefan
 */
class Timer
{
    private $start_microtime;
    private $end_microtime;

    public function __construct($start_microtime = null, $end_microtime=null)
    {
        $this->start_microtime = $start_microtime;
        $this->end_microtime = $end_microtime;
    }

    public static function start()
    {
        return new Timer(time());
    }

    /**
     * e.g. 23:01:20.2
     * @return sting
     */
    public function elapsed()
    {
        $end = $this->end_microtime ?: microtime(true);
        return $this->formatMilliseconds(($end*1000) - ($this->start_microtime*1000));
    }

    public function elapsedSeconds()
    {
        $end = $this->end_microtime ?: microtime(true);
        return ($end) - ($this->start_microtime);
    }

    /**
     * @url http://stackoverflow.com/questions/4763668/php-convert-milliseconds-to-hours-minutes-seconds-fractional
     * @param int $milliseconds
     * @return string
     */
    private function formatMilliseconds($milliseconds)
    {
        $seconds = floor($milliseconds / 1000);
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $milliseconds = $milliseconds % 1000;
        $seconds = $seconds % 60;
        $minutes = $minutes % 60;

        $format = '%u:%02u:%02u.%03u';
        $time = sprintf($format, $hours, $minutes, $seconds, $milliseconds);
        return $time;
    }
}