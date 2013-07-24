<?php
namespace PHPianola\Log;

/**
 * StdOut Log
 *
 * @author warmans
 */
class StdOut extends AbstractLogger
{
    public function log($level, $message, array $context = array())
    {
        echo "$level | ".$this->interpolate($message, $context)."\n";
    }
}
