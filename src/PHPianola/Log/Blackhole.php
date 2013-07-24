<?php
namespace PHPianola\Log;

/**
 * Blackhole Log
 *
 * @author warmans
 */
class Blackhole extends AbstractLogger
{
    public function log($level, $message, array $context = array())
    {}
}
