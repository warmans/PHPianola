<?php
namespace PHPianola\Log;

/**
 * AbstractLogger
 *
 * @author warmans
 */
abstract class AbstractLogger extends \Psr\Log\AbstractLogger
{
    protected function interpolate($message, array $context=array()){
        $replace = array();
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}
