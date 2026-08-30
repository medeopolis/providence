<?php

require_once(__DIR__.'/../CALogger.php');

/**
 * Deprecated KLogger class.
 *
 * This class exists as a drop-in replacement for the original KLogger class so
 * that any code which still references KLogger (for example third-party plugins
 * or legacy code) continues to work. It is a thin subclass of CALogger that
 * passes all logging through to the active CALogger backend.
 *
 * New code should use CALogger directly. Using this class emits an
 * E_USER_DEPRECATED warning (once per process) which can be silenced by setting
 * "deprecated_shim_warning" to 0 in the "logging" configuration block.
 */
class KLogger extends CALogger
{
    /**
     * Whether the deprecation warning has already been shown in this process
     * @var bool
     */
    private static $_deprecated_notice_shown = false;

    /**
     * Determine whether the deprecated shim warning should be emitted, based on
     * the "logging" configuration block.
     *
     * @return bool
     */
    protected static function showDeprecatedWarning()
    {
        try {
            if (class_exists('Configuration')) {
                $logging = Configuration::load()->get('logging');
                if (is_array($logging) && array_key_exists('deprecated_shim_warning', $logging)) {
                    return (bool)$logging['deprecated_shim_warning'];
                }
            }
        } catch (Exception $e) {
            // fall through to the default
        }
        return true;
    }

    /**
     * Emit the deprecation warning once per process, unless muted.
     *
     * @return void
     */
    protected static function emitDeprecatedNotice()
    {
        if (self::$_deprecated_notice_shown) {
            return;
        }
        self::$_deprecated_notice_shown = true;

        if (!self::showDeprecatedWarning()) {
            return;
        }

        trigger_error('The KLogger class is deprecated. Please update your code to use CALogger.', E_USER_DEPRECATED);    }

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
        self::emitDeprecatedNotice();
        parent::__construct($logDirectory, $severity, $logName);
    }

    /**
     * Partially implements the Singleton pattern. Each $logDirectory gets one
     * instance.
     *
     * @param string  $logDirectory File path to the logging directory
     * @param integer $severity     One of the pre-defined severity constants
     * @return KLogger
     */
    public static function instance($logDirectory = false, $severity = false)
    {
        self::emitDeprecatedNotice();
        return parent::instance($logDirectory, $severity);
    }
}
