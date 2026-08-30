<?php

require_once(__DIR__.'/../LoggingBackendBase.php');

/**
 * KLoggerBackend is a LoggingBackend that writes log messages to flat text
 * files, following the same file format and behavior as the original KLogger
 * class.
 *
 * This backend was adapted from the original KLogger class:
 *
 *  - Originally written for use with wpSearch
 *  - @author  Kenny Katzgrau <katzgrau@gmail.com>
 *  - @since   July 26, 2008 — Last update July 1, 2012
 *  - @link    http://codefury.net
 *  - @version 0.2.0
 */
class KLoggerBackend extends LoggingBackendBase
{
    /**
     * Internal status codes
     */
    const STATUS_LOG_OPEN    = 1;
    const STATUS_OPEN_FAILED = 2;
    const STATUS_LOG_CLOSED  = 3;

    /**
     * Current status of the log file
     * @var integer
     */
    private $_logStatus         = self::STATUS_LOG_CLOSED;
    /**
     * Path to the log file
     * @var string
     */
    private $_logFilePath       = null;
    /**
     * This holds the file handle for this instance's log file
     * @var resource
     */
    private $_fileHandle        = null;

    /**
     * Standard messages produced by the class. Can be modified for il8n
     * @var array
     */
    private $_messages = array(
        //'writefail'   => 'The file exists, but could not be opened for writing. Check that appropriate permissions have been set.',
        'writefail'   => 'The file could not be written to. Check that appropriate permissions have been set.',
        'opensuccess' => 'The log file was opened successfully.',
        'openfail'    => 'The file could not be opened. Check permissions.',
    );

    /**
     * Octal notation for default permissions of the log file
     * @var integer
     */
    private static $_defaultPermissions = 0777;

    /**
     * Class constructor
     *
     * @param string  $logDirectory File path to the logging directory
     * @param integer $severity     One of the pre-defined severity constants
     * @param string $logName		Options custom log name
     * @return void
     */
    public function __construct($logDirectory, $severity, $logName=null)
    {
        $logDirectory = rtrim($logDirectory, '\\/');

        if ($severity === self::OFF) {
            return;
        }

        $this->_logFilePath = $logDirectory
            . DIRECTORY_SEPARATOR
            . 'log_' . ($logName  ? "{$logName}_" : "")
            . date('Y-m-d')
            . '.txt';

        $this->_severityThreshold = $severity;
        if (!file_exists($logDirectory)) {
            if (!@mkdir($logDirectory, self::$_defaultPermissions, true)) {
            	$this->_logStatus = self::STATUS_OPEN_FAILED;
            	$this->addMessage($this->_messages['openfail']);
            }
        }

        if (file_exists($this->_logFilePath) && !is_writable($this->_logFilePath)) {
            $this->_logStatus = self::STATUS_OPEN_FAILED;
            $this->addMessage($this->_messages['writefail']);
            return;
        }

        if (($this->_fileHandle = @fopen($this->_logFilePath, 'a'))) {
            $this->_logStatus = self::STATUS_LOG_OPEN;
            $this->addMessage($this->_messages['opensuccess']);
        } else {
            $this->_logStatus = self::STATUS_OPEN_FAILED;
            $this->addMessage($this->_messages['openfail']);
        }
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        if ($this->_fileHandle) {
            fclose($this->_fileHandle);
        }
    }

    /**
     * Writes a $line to the log with the given severity
     *
     * @param string  $line     Text to add to the log
     * @param integer $severity Severity level of log message (use constants)
     */
    public function log($line, $severity, $args = self::NO_ARGUMENTS)
    {
        if ($this->_severityThreshold >= $severity) {
            $status = $this->_getTimeLine($severity);
            
            $line = "$status $line";
            
            if($args !== self::NO_ARGUMENTS) {
                /* Print the passed object value */
                $line = $line . '; ' . var_export($args, true);
            }
            
            $this->writeFreeFormLine($line . PHP_EOL);
        }
    }

    /**
     * Writes a line to the log without prepending a status or timestamp
     *
     * @param string $line Line to write to the log
     * @return void
     */
    public function writeFreeFormLine($line)
    {
        if ($this->_logStatus == self::STATUS_LOG_OPEN
            && $this->_severityThreshold != self::OFF) {
            if (fwrite($this->_fileHandle, $line) === false) {
                $this->addMessage($this->_messages['writefail']);
            }
        }
    }

    private function _getTimeLine($level)
    {
        $time = date($this->getDateFormat());

        switch ($level) {
            case self::EMERG:
                return "$time - EMERG -->";
            case self::ALERT:
                return "$time - ALERT -->";
            case self::CRIT:
                return "$time - CRIT -->";
            case self::FATAL: # FATAL is an alias of CRIT
                return "$time - FATAL -->";
            case self::NOTICE:
                return "$time - NOTICE -->";
            case self::INFO:
                return "$time - INFO -->";
            case self::WARN:
                return "$time - WARN -->";
            case self::DEBUG:
                return "$time - DEBUG -->";
            case self::ERR:
                return "$time - ERROR -->";
            default:
                return "$time - LOG -->";
        }
    }
}
