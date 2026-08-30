<?php
/** ---------------------------------------------------------------------
 * tests/lib/Logging/CALoggerTest.php
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

require_once(__CA_LIB_DIR__.'/Logging/CALogger.php');

class CALoggerTest extends TestCase {

	private $logDir;

	protected function setUp(): void {
		$this->logDir = sys_get_temp_dir().'/ca_calogger_test_'.uniqid();
		@mkdir($this->logDir, 0777, true);
	}

	protected function tearDown(): void {
		$files = @glob($this->logDir.'/log_*.txt');
		if (is_array($files)) {
			foreach($files as $f) { @unlink($f); }
		}
		@rmdir($this->logDir);
	}

	private function _logDirForName() {
		return $this->logDir.'/sub'.md5(uniqid(mt_rand(), true));
	}

	public function testSeverityConstantValues() {
		$this->assertEquals(0, CALogger::EMERG);
		$this->assertEquals(1, CALogger::ALERT);
		$this->assertEquals(2, CALogger::CRIT);
		$this->assertEquals(3, CALogger::ERR);
		$this->assertEquals(4, CALogger::WARN);
		$this->assertEquals(5, CALogger::NOTICE);
		$this->assertEquals(6, CALogger::INFO);
		$this->assertEquals(7, CALogger::DEBUG);
		$this->assertEquals(8, CALogger::OFF);
		$this->assertEquals(2, CALogger::FATAL);
	}

	public function testPsr3ConstantAliases() {
		$this->assertEquals(CALogger::EMERG, CALogger::EMERGENCY);
		$this->assertEquals(CALogger::CRIT, CALogger::CRITICAL);
		$this->assertEquals(CALogger::ERR, CALogger::ERROR);
		$this->assertEquals(CALogger::WARN, CALogger::WARNING);
	}

	public function testLogLevelHelpersWrite() {
		$dir = $this->_logDirForName();
		$l = new CALogger($dir, CALogger::DEBUG, 'helpers');
		$l->logDebug('d');
		$l->logInfo('i');
		$l->logNotice('n');
		$l->logWarn('w');
		$l->logError('e');
		$l->logAlert('a');
		$l->logCrit('c');
		$l->logEmerg('em');
		$f = $dir.'/log_helpers_'.date('Y-m-d').'.txt';
		$this->assertFileExists($f);
		$contents = file_get_contents($f);
		foreach(['d', 'i', 'n', 'w', 'e', 'a', 'c', 'em'] as $token) {
			$this->assertStringContainsString($token, $contents, "Expecting token '{$token}' in log");
		}
	}

	public function testPsr3MethodAliasesWrite() {
		$dir = $this->_logDirForName();
		$l = new CALogger($dir, CALogger::DEBUG, 'psr3');
		$l->debug('debug alias');
		$l->info('info alias');
		$l->notice('notice alias');
		$l->warning('warning alias');
		$l->error('error alias');
		$l->alert('alert alias');
		$l->critical('critical alias');
		$l->emergency('emergency alias');
		$f = $dir.'/log_psr3_'.date('Y-m-d').'.txt';
		$this->assertFileExists($f);
		$contents = file_get_contents($f);
		foreach(['debug alias', 'info alias', 'notice alias', 'warning alias', 'error alias', 'alert alias', 'critical alias', 'emergency alias'] as $token) {
			$this->assertStringContainsString($token, $contents, "Expecting '{$token}' in log");
		}
	}

	public function testPsr3ContextPassedAsArgs() {
		$dir = $this->_logDirForName();
		$l = new CALogger($dir, CALogger::DEBUG, 'psr3ctx');
		$l->warning('warn with ctx', ['ctxkey' => 'ctxval']);
		$contents = file_get_contents($dir.'/log_psr3ctx_'.date('Y-m-d').'.txt');
		$this->assertStringContainsString('ctxkey', $contents);
		$this->assertStringContainsString('ctxval', $contents);
	}

	public function testLogMethodWithExplicitSeverity() {
		$dir = $this->_logDirForName();
		$l = new CALogger($dir, CALogger::DEBUG, 'logmeth');
		$l->log('explicit log', CALogger::WARN);
		$contents = file_get_contents($dir.'/log_logmeth_'.date('Y-m-d').'.txt');
		$this->assertStringContainsString('explicit log', $contents);
	}

	public function testThresholdFilters() {
		$dir = $this->_logDirForName();
		$l = new CALogger($dir, CALogger::ERR, 'thresh');
		$l->logDebug('hidden');
		$l->logError('shown');
		$contents = file_get_contents($dir.'/log_thresh_'.date('Y-m-d').'.txt');
		$this->assertStringNotContainsString('hidden', $contents);
		$this->assertStringContainsString('shown', $contents);
	}

	public function testWriteFreeFormLine() {
		$dir = $this->_logDirForName();
		$l = new CALogger($dir, CALogger::ERR, 'free');
		$l->writeFreeFormLine('freeform text');
		$contents = file_get_contents($dir.'/log_free_'.date('Y-m-d').'.txt');
		$this->assertStringContainsString('freeform text', $contents);
	}

	public function testMessageQueueAccessibleThroughFacade() {
		$dir = $this->_logDirForName();
		$l = new CALogger($dir, CALogger::DEBUG, 'queue');
		$this->assertNotEmpty($l->getMessages());
		$msg = $l->getMessage();
		$this->assertTrue(is_string($msg) && strlen($msg) > 0);
		$this->assertSame([], $l->getMessages());
	}

	public function testClearMessages() {
		$dir = $this->_logDirForName();
		$l = new CALogger($dir, CALogger::DEBUG, 'clear');
		$l->clearMessages();
		$this->assertSame([], $l->getMessages());
	}

	public function testInstanceSingleton() {
		$dir = $this->_logDirForName();
		$a = CALogger::instance($dir, CALogger::DEBUG);
		$b = CALogger::instance($dir, CALogger::DEBUG);
		$this->assertSame($a, $b);
	}

	public function testInstanceWithoutDirectoryReturnsExisting() {
		$dir = $this->_logDirForName();
		CALogger::instance($dir, CALogger::DEBUG);
		$existing = CALogger::instance();	// should return a cached instance
		$this->assertInstanceOf('CALogger', $existing);
	}

	public function testGetSeverityThreshold() {
		$dir = $this->_logDirForName();
		$l = new CALogger($dir, CALogger::WARN);
		$this->assertEquals(CALogger::WARN, $l->getSeverityThreshold());
	}

	public function testDefaultSeverityIsInfo() {
		$dir = $this->_logDirForName();
		$l = new CALogger($dir);
		$this->assertEquals(CALogger::INFO, $l->getSeverityThreshold());
	}

	public function testResolveBackendReturnsKnownBackend() {
		$backend = CALogger::resolveBackend();
		$this->assertContains($backend, ['KLoggerBackend', 'MonologBackend']);
	}

	public function testSetDateFormat() {
		$dir = $this->_logDirForName();
		$l = new CALogger($dir, CALogger::DEBUG, 'df');
		CALogger::setDateFormat('d/m/Y');
		$l->logInfo('epoch');
		CALogger::setDateFormat('Y-m-d G:i:s');		// restore default
		$contents = file_get_contents($dir.'/log_df_'.date('Y-m-d').'.txt');
		if (CALogger::resolveBackend() === 'KLoggerBackend') {
			// The custom date format is honored by the KLogger backend
			$this->assertMatchesRegularExpression('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4} - INFO --> epoch$/', trim($contents));
		} else {
			// Monolog does not apply a custom date format; the message should still be present
			$this->assertStringContainsString('epoch', $contents);
		}
	}
}