<?php

require_once(__DIR__.'/Backends/KLogger/KLogger.php');

/**
 * CALogger is the primary logging facade for CollectiveAccess. It exposes the
 * familiar KLogger interface (severity constants, log level helper methods,
 * message queue accessors) while delegating the actual logging to a pluggable
 * LoggingBackend implementation.
 *
 * The active backend is selected via the "logging" top-level configuration
 * block (app.conf): the "backend" key names the backend to use. The default
 * backend is "klogger", which preserves the original text-file logging
 * behavior.
 */
class CALogger
{
    /**
     * Error severity, from low to high. From BSD syslog RFC, secion 4.1.1
     * @link http://www.faqs.org/rfcs/rfc3164.html
     */
    const EMERG  = 0;  // Emergency: system is unusable
    const ALERT  = 1;  // Alert: action must be taken immediately
    const CRIT   = 2;  // Critical: critical conditions
    const ERR    = 3;  // Error: error conditions
    const WARN   = 4;  // Warning: warning conditions
    const NOTICE = 5;  // Notice: normal but significant condition
    const INFO   = 6;  // Informational: informational messages
    const DEBUG  = 7;  // Debug: debug messages

    /**
     * Log nothing at all
     */
    const OFF    = 8;

    /**
     * Alias for CRIT
     * @deprecated
     */
    const FATAL  = 2;

    /**
     * We need a default argument value in order to add the ability to easily
     * print out objects etc. But we can't use NULL, 0, FALSE, etc, because those
     * are often the values the developers will test for. So we'll make one up.
     */
    const NO_ARGUMENTS = 'KLogger::NO_ARGUMENTS';

    /**
     * Array of CALogger instances, part of Singleton pattern
     * @var array
     */
    private static $instances = array();

    /**
     * Current minimum logging threshold
     * @var integer
     */
    private $_severityThreshold = self::INFO;

    /**
     * The LoggingBackend that performs the actual logging
     * @var LoggingBackend
     */
    protected $backend = null;

    /**
     * Class constructor
     *
     * @param string  $logDirectory File path to the logging directory
     * @param integer $severity     One of the pre-defined severity constants
     * @param string $logName		Options custom log name
     * @return void
     */
    public function __construct($logDirectory=false, $severity=false, $logName=null)
    {
        if ($severity === false) {
            $severity = self::INFO;
        }
        if ($logDirectory === false) {
            $logDirectory = dirname(__FILE__);
        }

        $this->_severityThreshold = $severity;

        $backend_class = self::resolveBackend();
        $this->backend = new $backend_class($logDirectory, $severity, $logName);
    }

    /**
     * Partially implements the Singleton pattern. Each $logDirectory gets one
     * instance.
     *
     * @param string  $logDirectory File path to the logging directory
     * @param integer $severity     One of the pre-defined severity constants
     * @return CALogger
     */
    public static function instance($logDirectory = false, $severity = false)
    {
        if ($severity === false) {
            $severity = self::INFO;
        }
        
        if ($logDirectory === false) {
            if (count(self::$instances) > 0) {
                return current(self::$instances);
            } else {
                $logDirectory = dirname(__FILE__);
            }
        }

        if (array_key_exists($logDirectory, self::$instances)) {
            return self::$instances[$logDirectory];
        }

        self::$instances[$logDirectory] = new self($logDirectory, $severity);

        return self::$instances[$logDirectory];
    }

    /**
     * Determine the class name of the active LoggingBackend based on the
     * "logging" configuration block. Falls back to the KLogger backend if no
     * backend is configured, if configuration is unavailable, or if the
     * requested backend cannot be loaded.
     *
     * @return string
     */
    public static function resolveBackend()
    {
        $backend = 'KLoggerBackend';
        try {
            if (class_exists('Configuration')) {
                $logging = Configuration::load()->get('logging');
                if (is_array($logging) && isset($logging['backend'])) {
                    switch(strtolower($logging['backend'])) {
                        case 'monolog':
                            $backend = 'MonologBackend';
                            break;
                        case 'klogger':
                        default:
                            $backend = 'KLoggerBackend';
                            break;
                    }
                }
            }
        } catch (Exception $e) {
            $backend = 'KLoggerBackend';
        }

        if ($backend === 'MonologBackend') {
            $monolog_file = __DIR__.'/Backends/Monolog/MonologBackend.php';
            if (file_exists($monolog_file)) {
                require_once($monolog_file);
                if (class_exists('MonologBackend')) {
                    return 'MonologBackend';
                }
            }
            return 'KLoggerBackend';
        }

        return 'KLoggerBackend';
    }

    /**
     * Determine the class name of the active LoggingBackend based on the
     * "logging" configuration block. Falls back to the KLogger backend if no
     * backend is configured or if configuration is unavailable.
     *
     * @return string
     */
    public static function getBackendClass()
    {
        return self::resolveBackend();
    }

    /**
     * Writes a $line to the log with a severity level of DEBUG
     *
     * @param string $line Information to log
     * @return void
     */
    public function logDebug($line, $args = self::NO_ARGUMENTS)
    {
        $this->backend->logDebug($line, $args);
    }

    /**
     * Writes a $line to the log with a severity level of INFO. Any information
     * can be used here, or it could be used with E_STRICT errors
     *
     * @param string $line Information to log
     * @return void
     */
    public function logInfo($line, $args = self::NO_ARGUMENTS)
    {
        $this->backend->logInfo($line, $args);
    }

    /**
     * Writes a $line to the log with a severity level of NOTICE. Generally
     * corresponds to E_STRICT, E_NOTICE, or E_USER_NOTICE errors
     *
     * @param string $line Information to log
     * @return void
     */
    public function logNotice($line, $args = self::NO_ARGUMENTS)
    {
        $this->backend->logNotice($line, $args);
    }

    /**
     * Writes a $line to the log with a severity level of WARN. Generally
     * corresponds to E_WARNING, E_USER_WARNING, E_CORE_WARNING, or 
     * E_COMPILE_WARNING
     *
     * @param string $line Information to log
     * @return void
     */
    public function logWarn($line, $args = self::NO_ARGUMENTS)
    {
        $this->backend->logWarn($line, $args);
    }

    /**
     * Writes a $line to the log with a severity level of ERR. Most likely used
     * with E_RECOVERABLE_ERROR
     *
     * @param string $line Information to log
     * @return void
     */
    public function logError($line, $args = self::NO_ARGUMENTS)
    {
        $this->backend->logError($line, $args);
    }

    /**
     * Writes a $line to the log with a severity level of FATAL. Generally
     * corresponds to E_ERROR, E_USER_ERROR, E_CORE_ERROR, or E_COMPILE_ERROR
     *
     * @param string $line Information to log
     * @return void
     * @deprecated Use logCrit
     */
    public function logFatal($line, $args = self::NO_ARGUMENTS)
    {
        $this->backend->logFatal($line, $args);
    }

    /**
     * Writes a $line to the log with a severity level of ALERT.
     *
     * @param string $line Information to log
     * @return void
     */
    public function logAlert($line, $args = self::NO_ARGUMENTS)
    {
        $this->backend->logAlert($line, $args);
    }

    /**
     * Writes a $line to the log with a severity level of CRIT.
     *
     * @param string $line Information to log
     * @return void
     */
    public function logCrit($line, $args = self::NO_ARGUMENTS)
    {
        $this->backend->logCrit($line, $args);
    }

    /**
     * Writes a $line to the log with a severity level of EMERG.
     *
     * @param string $line Information to log
     * @return void
     */
    public function logEmerg($line, $args = self::NO_ARGUMENTS)
    {
        $this->backend->logEmerg($line, $args);
    }

    /**
     * Writes a $line to the log with the given severity
     *
     * @param string  $line     Text to add to the log
     * @param integer $severity Severity level of log message (use constants)
     */
    public function log($line, $severity, $args = self::NO_ARGUMENTS)
    {
        $this->backend->log($line, $severity, $args);
    }

    /**
     * Writes a line to the log without prepending a status or timestamp
     *
     * @param string $line Line to write to the log
     * @return void
     */
    public function writeFreeFormLine($line)
    {
        $this->backend->writeFreeFormLine($line);
    }

    /**
     * Returns (and removes) the last message from the queue.
     * @return string
     */
    public function getMessage()
    {
        return $this->backend->getMessage();
    }

    /**
     * Returns the entire message queue (leaving it intact)
     * @return array
     */
    public function getMessages()
    {
        return $this->backend->getMessages();
    }

    /**
     * Empties the message queue
     * @return void
     */
    public function clearMessages()
    {
        $this->backend->clearMessages();
    }

    /**
     * Sets the date format used by all instances
     * 
     * @param string $dateFormat Valid format string for date()
     */
    public static function setDateFormat($dateFormat)
    {
        $backend_class = self::getBackendClass();
        $backend_class::setDateFormat($dateFormat);
    }

    /**
     * Returns the current minimum severity threshold
     *
     * @return int
     */
    public function getSeverityThreshold()
    {
        return $this->_severityThreshold;
    }
}
