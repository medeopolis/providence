<?php

/**
 * CALogger class which inherits the interface of KLogger.
 */
class CALogger extends KLogger
{
    /**
     * Overridden instance method to return CALogger instead of KLogger
     *
     * @param string  $logDirectory File path to the logging directory
     * @param integer $severity     One of the pre-defined severity constants
     * @return CALogger
     */
    public static function instance($logDirectory = false, $severity = false)
    {
        if ($severity === false) {
            $severity = self::$_defaultSeverity;
        }
        
        if ($logDirectory === false) {
            if (count(self::$instances) > 0) {
                return current(self::$instances);
            } else {
                $logDirectory = dirname(__FILE__);
            }
        }

        if (in_array($logDirectory, self::$instances)) {
            return self::$instances[$logDirectory];
        }

        self::$instances[$logDirectory] = new self($logDirectory, $severity);

        return self::$instances[$logDirectory];
    }
}
