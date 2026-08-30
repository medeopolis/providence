<?php
/** ---------------------------------------------------------------------
 * tests/lib/Logging/KLoggerAPISurfaceTest.php
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

class KLoggerAPISurfaceTest extends TestCase {

	/**
	 * The set of public/protected methods exposed by the original KLogger class
	 * (upstream/master:app/lib/Logging/KLogger/KLogger.php), keyed by method
	 * name and mapped to the expected visibility. The deprecated KLogger shim
	 * (a subclass of CALogger) must expose at least this surface so that existing
	 * ~660 call sites that construct KLogger / call KLogger helpers keep working.
	 *
	 * __destruct is intentionally excluded: it is a magic method that the
	 * engine invokes implicitly (never called directly), and the file handle it
	 * closes lives on the wrapped KLoggerBackend, whose own __destruct() closes
	 * it. So reflection cannot see it on the shim, and that is a deliberate,
	 * functionally-equivalent deviation. See testFileHandleLifecycleWorksAcrossInstances().
	 *
	 * @var array<string,string>
	 */
	private $expectedMethods = [
		'instance'          => 'public',
		'__construct'       => 'public',
		'logDebug'          => 'public',
		'getMessage'        => 'public',
		'getMessages'       => 'public',
		'clearMessages'     => 'public',
		'setDateFormat'     => 'public',
		'logInfo'           => 'public',
		'logNotice'         => 'public',
		'logWarn'           => 'public',
		'logError'          => 'public',
		'logFatal'          => 'public',
		'logAlert'          => 'public',
		'logCrit'           => 'public',
		'logEmerg'          => 'public',
		'log'               => 'public',
		'writeFreeFormLine' => 'public',
	];

	/**
	 * Constants exposed by the original KLogger, mapped to their expected values.
	 *
	 * STATUS_LOG_OPEN / STATUS_OPEN_FAILED / STATUS_LOG_CLOSED are intentionally
	 * excluded: upstream uses them only internally (referenced as self::STATUS_*
	 * inside KLogger.php, never by external callers), so they are an
	 * implementation detail. The equivalent constants live on the wrapped
	 * KLoggerBackend.
	 *
	 * @var array<string,int|string>
	 */
	private $expectedConstants = [
		'EMERG'       => 0,
		'ALERT'       => 1,
		'CRIT'        => 2,
		'ERR'         => 3,
		'WARN'        => 4,
		'NOTICE'      => 5,
		'INFO'        => 6,
		'DEBUG'       => 7,
		'OFF'         => 8,
		'FATAL'       => 2,
		'NO_ARGUMENTS'=> 'KLogger::NO_ARGUMENTS',
	];

	public function testAllUpstreamMethodsExistWithMatchingVisibility() {
		foreach ($this->expectedMethods as $method => $visibility) {
			$this->assertTrue(
				method_exists('KLogger', $method),
				"Expected method KLogger::$method() (from upstream KLogger) is missing from the deprecated shim."
			);

			$expectedAccess = ($visibility === 'public') ? \ReflectionMethod::IS_PUBLIC : \ReflectionMethod::IS_PROTECTED;
			$ref = new \ReflectionMethod('KLogger', $method);
			$actualAccess = $ref->getModifiers();
			$this->assertTrue(
				($actualAccess & $expectedAccess) === $expectedAccess,
				"Method KLogger::$method() has incorrect visibility."
			);
		}
	}

	public function testLogSignatureMatchesUpstream() {
		$ref = new \ReflectionMethod('KLogger', 'log');
		$this->assertSame(3, $ref->getNumberOfParameters());
		$this->assertSame(2, $ref->getNumberOfRequiredParameters());

		$params = $ref->getParameters();
		$this->assertSame('line', $params[0]->getName());
		$this->assertSame('severity', $params[1]->getName());
		$this->assertSame('args', $params[2]->getName());
		$this->assertTrue($params[2]->isDefaultValueAvailable());
		$this->assertSame('KLogger::NO_ARGUMENTS', $params[2]->getDefaultValue());
	}

	public function testHelperMethodSignaturesMatchUpstream() {
		foreach (['logDebug','logInfo','logNotice','logWarn','logError','logFatal','logAlert','logCrit','logEmerg'] as $method) {
			$ref = new \ReflectionMethod('KLogger', $method);
			$this->assertSame(2, $ref->getNumberOfParameters(), "$method() should take 2 parameters.");
			$params = $ref->getParameters();
			$this->assertSame('line', $params[0]->getName());
			$this->assertSame('args', $params[1]->getName());
			$this->assertTrue($params[1]->isDefaultValueAvailable());
			$this->assertSame('KLogger::NO_ARGUMENTS', $params[1]->getDefaultValue());
		}
	}

	public function testAllUpstreamConstantsExistWithMatchingValues() {
		$ref = new \ReflectionClass('KLogger');
		foreach ($this->expectedConstants as $name => $value) {
			$this->assertTrue(
				$ref->hasConstant($name),
				"Expected constant KLogger::$name (from upstream KLogger) is missing from the deprecated shim."
			);
			$this->assertSame($value, constant('KLogger::'.$name), "Constant KLogger::$name has a different value.");
		}
	}

	public function testCallToUpstreamHelperProducesUpstreamFormat() {
		// The severity-threshold semantics of the original KLogger: a message is
		// only written when $severity <= threshold. With an INFO threshold,
		// logWarn (4) is written but logDebug (7) is not.
		$logDir = sys_get_temp_dir().'/ca_apisurf_test_'.uniqid();
		@mkdir($logDir, 0777, true);
		try {
			$log = new KLogger($logDir, KLogger::INFO, 'compatwrites');
			$log->logWarn('warning message');
			$log->log('argumented message', KLogger::WARN, ['key' => 'value']);
			$log->logDebug('debug message');
			$contents = file_get_contents($logDir.'/log_compatwrites_'.date('Y-m-d').'.txt');
			$this->assertStringContainsString('WARN --> warning message', $contents === false ? '' : $contents);
			$this->assertStringContainsString(
				'WARN --> argumented message; array (',
				$contents === false ? '' : $contents
			);
			$this->assertStringContainsString('key', $contents === false ? '' : $contents);
			$this->assertStringContainsString('value', $contents === false ? '' : $contents);
			$this->assertStringNotContainsString('debug message', $contents === false ? '' : $contents);
		} finally {
			$this->_cleanupDir($logDir);
		}
	}

	/**
	 * Upstream KLogger closes its file handle in __destruct(). The shim does not
	 * redeclare the magic method (the handle lives on the wrapped KLoggerBackend,
	 * whose own __destruct() closes it), so this verifies the observable behaviour
	 * is equivalent: writes across instances succeed and the handle is released.
	 */
	public function testFileHandleLifecycleWorksAcrossInstances() {
		$logDir = sys_get_temp_dir().'/ca_apisurf_test_'.uniqid();
		@mkdir($logDir, 0777, true);
		try {
			$log = new KLogger($logDir, KLogger::DEBUG, 'close_compat');
			$log->logWarn('first write');
			unset($log);

			$log2 = new KLogger($logDir, KLogger::DEBUG, 'close_compat');
			$log2->logWarn('second write');
			unset($log2);

			$contents = file_get_contents($logDir.'/log_close_compat_'.date('Y-m-d').'.txt');
			$this->assertStringContainsString('first write', $contents === false ? '' : $contents);
			$this->assertStringContainsString('second write', $contents === false ? '' : $contents);
		} finally {
			$this->_cleanupDir($logDir);
		}
	}

	private function _cleanupDir($dir) {
		$files = @glob($dir.'/log_*.txt');
		if (is_array($files)) {
			foreach($files as $f) { @unlink($f); }
		}
		@rmdir($dir);
	}
}
