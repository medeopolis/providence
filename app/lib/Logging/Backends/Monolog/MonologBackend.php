<?php

require_once(__DIR__.'/../LoggingBackendBase.php');

/**
 * MonologBackend is a LoggingBackend that delegates logging to Monolog 3
 * (https://github.com/Seldaek/monolog). It wraps a Monolog Logger configured
 * with a StreamHandler that appends to a date-stamped text file, mirroring the
 * file naming convention used by the KLogger backend.
 *
 * The KLogger severity constants are mapped onto Monolog severity levels, and
 * the message queue accessors (getMessage/getMessages/clearMessages) are
 * preserved for interface compatibility even though Monolog itself has no such
 * queue.
 */
class MonologBackend extends LoggingBackendBase
{
    /**
     * The Monolog Logger instance used to write log records
     * @var \Monolog\Logger
     */
    private $monologLogger = null;

    /**
     * Path to the log file
     * @var string
     */
    private $_logFilePath = null;

    /**
     * Class constructor
     *
     * @param string  $logDirectory File path to the logging directory
     * @param integer $severity     One of the pre-defined severity constants
     * @param string $logName		Optional custom log name
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
        $logDirectory = rtrim($logDirectory, '\\/');

        $this->_severityThreshold = $severity;

        if ($severity === self::OFF) {
            $this->addMessage('Logging is disabled.');
            return;
        }

        $this->_logFilePath = $logDirectory
            . DIRECTORY_SEPARATOR
            . 'log_' . ($logName ? "{$logName}_" : "")
            . date('Y-m-d')
            . '.txt';

        if (!file_exists($logDirectory) && !@mkdir($logDirectory, 0777, true)) {
            $this->addMessage('The log directory could not be created. Check permissions.');
            return;
        }

        try {
            $this->monologLogger = new \Monolog\Logger($logName ? (string)$logName : 'ca');

            $handler = new \Monolog\Handler\StreamHandler(
                $this->_logFilePath,
                \Monolog\Level::Debug,
                true,
                null,
                false,
                'a'
            );

            $this->monologLogger->pushHandler($handler);
            $this->addMessage('The log stream was opened successfully.');
        } catch (\Exception $e) {
            $this->addMessage('The log file could not be opened. Check permissions.');
        }
    }

    /**
     * Map a KLogger severity constant to a Monolog\Level value
     *
     * @param int $severity
     * @return \Monolog\Level
     */
    protected function mapLevel($severity)
    {
        switch ((int)$severity) {
            case self::EMERG:
                return \Monolog\Level::Emergency;
            case self::ALERT:
                return \Monolog\Level::Alert;
            case self::CRIT:
            case self::FATAL:
                return \Monolog\Level::Critical;
            case self::ERR:
                return \Monolog\Level::Error;
            case self::WARN:
                return \Monolog\Level::Warning;
            case self::NOTICE:
                return \Monolog\Level::Notice;
            case self::DEBUG:
                return \Monolog\Level::Debug;
            default:
                return \Monolog\Level::Info;
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
        if (!$this->monologLogger) {
            return;
        }
        if ($this->_severityThreshold < $severity) {
            return;
        }

        $context = [];
        if ($args !== self::NO_ARGUMENTS) {
            $context['args'] = $args;
        }

        $this->monologLogger->log($this->mapLevel($severity), (string)$line, $context);
    }

    /**
     * Writes a line to the log without prepending a status or timestamp
     *
     * @param string $line Line to write to the log
     * @return void
     */
    public function writeFreeFormLine($line)
    {
        if (!$this->monologLogger) {
            return;
        }
        $this->monologLogger->log(\Monolog\Level::Info, rtrim((string)$line, "\r\n"));
    }

    /**
     * Returns the path of the log file being written to, if any
     *
     * @return string|null
     */
    public function getLogFilePath()
    {
        return $this->_logFilePath;
    }
}
