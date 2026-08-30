<?php
/** ---------------------------------------------------------------------
 * tests/lib/Logging/KLoggerDeprecatedTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage tests
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
use PHPUnit\Framework\TestCase;

require_once(__CA_LIB_DIR__.'/Logging/KLogger/KLogger.php');

class KLoggerDeprecatedTest extends TestCase {

	private $logDir;
	private $originalErrorHandler;
	private $deprecations;

	protected function setUp(): void {
		$this->logDir = sys_get_temp_dir().'/ca_kshim_test_'.uniqid();
		@mkdir($this->logDir, 0777, true);
		$this->deprecations = [];
		$this->originalErrorHandler = set_error_handler([$this, 'captureDeprecation']);
		$this->_resetDeprecationOnceFlag();
	}

	protected function tearDown(): void {
		if ($this->originalErrorHandler !== null) {
			restore_error_handler();
			$this->originalErrorHandler = null;
		}
		$files = @glob($this->logDir.'/log_*.txt');
		if (is_array($files)) {
			foreach($files as $f) { @unlink($f); }
		}
		@rmdir($this->logDir);
	}

	/**
	 * Error handler that captures E_USER_DEPRECATED calls for assertions
	 */
	public function captureDeprecation($errno, $errstr) {
		if ($errno === E_USER_DEPRECATED) {
			$this->deprecations[] = $errstr;
			return true;
		}
		return false;
	}

	public function testClassExists() {
		$this->assertTrue(class_exists('KLogger'));
		$this->assertTrue(is_subclass_of('KLogger', 'CALogger'));
	}

	public function testInheritsConstants() {
		$this->assertEquals(CALogger::DEBUG, KLogger::DEBUG);
		$this->assertEquals(CALogger::INFO, KLogger::INFO);
		$this->assertEquals(CALogger::NO_ARGUMENTS, KLogger::NO_ARGUMENTS);
	}

	public function testConstructionEmitsDeprecationWarning() {
		$log = new KLogger($this->logDir, KLogger::DEBUG, 'shim');
		$this->assertCount(1, $this->deprecations);
		$this->assertStringContainsString('KLogger class is deprecated', $this->deprecations[0]);
	}

	public function testDeprecationWarningEmittedOnlyOnce() {
		$log = new KLogger($this->logDir, KLogger::DEBUG, 'shim');
		$this->assertCount(1, $this->deprecations);
		$log2 = new KLogger($this->logDir, KLogger::DEBUG, 'shim');
		$this->assertCount(1, $this->deprecations);
	}

	public function testInstanceEmitsDeprecationWarning() {
		$log = KLogger::instance($this->logDir, KLogger::DEBUG);
		$this->assertCount(1, $this->deprecations);
		$this->assertStringContainsString('KLogger class is deprecated', $this->deprecations[0]);
	}

	public function testDelegateLoggingToCALogger() {
		$log = new KLogger($this->logDir, KLogger::DEBUG, 'writes');
		$log->logInfo('through the shim');
		$contents = file_get_contents($this->logDir.'/log_writes_'.date('Y-m-d').'.txt');
		$this->assertStringContainsString('through the shim', $contents);
	}

	public function testMutingViaConfiguration() {
		// Temporarily disable the warning in the loaded configuration and verify
		// the shim does not emit an E_USER_DEPRECATED, then restore.
		$restore = $this->_setDeprecatedShimWarning(0);
		try {
			$log = new KLogger($this->logDir, KLogger::DEBUG, 'muted');
			$this->assertCount(0, $this->deprecations);
		} finally {
			$restore();
		}

		// After restoring, a fresh shim instance should warn again
		$this->deprecations = [];
		$this->_resetDeprecationOnceFlag();
		$log = new KLogger($this->logDir, KLogger::DEBUG, 'unmuted');
		$this->assertCount(1, $this->deprecations);
	}

	/**
	 * Set the loaded configuration's logging.deprecated_shim_warning value via
	 * reflection and return a closure that restores the previous value.
	 */
	private function _setDeprecatedShimWarning($value) {
		$config = Configuration::load();
		$prop = new ReflectionProperty($config, 'ops_config_settings');
		$prop->setAccessible(true);
		$settings = $prop->getValue($config);
		$previous = $settings['assoc']['logging'] ?? null;
		$settings['assoc']['logging'] = ['backend' => 'klogger', 'deprecated_shim_warning' => $value];
		$prop->setValue($config, $settings);
		$this->_clearGetCache();

		return function() use ($config, $prop, $previous) {
			$settings = $prop->getValue($config);
			if ($previous === null) {
				unset($settings['assoc']['logging']);
			} else {
				$settings['assoc']['logging'] = $previous;
			}
			$prop->setValue($config, $settings);
			$this->_clearGetCache();
		};
	}

	/**
	 * Clear Configuration::$s_get_cache so get() re-reads the mutated settings
	 */
	private function _clearGetCache() {
		$prop = new ReflectionProperty('Configuration', 's_get_cache');
		$prop->setAccessible(true);
		$cached = $prop->getValue();
		if (is_array($cached)) {
			foreach($cached as $k => $v) {
				foreach($v as $key => $val) {
					if ($key === 'logging') { unset($cached[$k][$key]); }
				}
			}
		}
		$prop->setValue($cached);
	}

	/**
	 * Reset the once-only deprecation notice flag so a subsequent construction
	 * emits the warning again
	 */
	private function _resetDeprecationOnceFlag() {
		$prop = new ReflectionProperty('KLogger', '_deprecated_notice_shown');
		$prop->setAccessible(true);
		$prop->setValue(null, false);
	}
}