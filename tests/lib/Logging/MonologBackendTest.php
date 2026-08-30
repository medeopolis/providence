<?php
/** ---------------------------------------------------------------------
 * tests/lib/Logging/MonologBackendTest.php
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

require_once(__CA_LIB_DIR__.'/Logging/Backends/Monolog/MonologBackend.php');

class MonologBackendTest extends TestCase {

	private $logDir;

	protected function setUp(): void {
		$this->logDir = sys_get_temp_dir().'/ca_monolog_test_'.uniqid();
		@mkdir($this->logDir, 0777, true);
	}

	protected function tearDown(): void {
		$files = @glob($this->logDir.'/log_*.txt');
		if (is_array($files)) {
			foreach($files as $f) { @unlink($f); }
		}
		@rmdir($this->logDir);
	}

	private function _requireMonolog() {
		if (!class_exists('\Monolog\Logger')) {
			$this->markTestSkipped('Monolog 3 is not installed in this environment.');
		}
	}

	private function _logFilePath($logName = null) {
		return $this->logDir.'/log_'.($logName ? "{$logName}_" : '').date('Y-m-d').'.txt';
	}

	public function testLogFileCreatedWithExpectedName() {
		$this->_requireMonolog();
		$b = new MonologBackend($this->logDir, MonologBackend::DEBUG, 'myapp');
		$b->logInfo('hello');
		$this->assertFileExists($this->_logFilePath('myapp'));
	}

	public function testLogWritesMessage() {
		$this->_requireMonolog();
		$b = new MonologBackend($this->logDir, MonologBackend::DEBUG, 'msg');
		$b->logInfo('a message');
		$contents = file_get_contents($this->_logFilePath('msg'));
		$this->assertStringContainsString('a message', $contents);
	}

	public function testLevelNamesAppearInOutput() {
		$this->_requireMonolog();
		$b = new MonologBackend($this->logDir, MonologBackend::DEBUG, 'lvl');
		$b->logDebug('d');
		$b->logInfo('i');
		$b->logNotice('n');
		$b->logWarn('w');
		$b->logError('e');
		$b->logCrit('c');
		$b->logAlert('a');
		$b->logEmerg('em');
		$contents = file_get_contents($this->_logFilePath('lvl'));
		$this->assertStringContainsString('.DEBUG:', $contents);
		$this->assertStringContainsString('.INFO:', $contents);
		$this->assertStringContainsString('.NOTICE:', $contents);
		$this->assertStringContainsString('.WARNING:', $contents);
		$this->assertStringContainsString('.ERROR:', $contents);
		$this->assertStringContainsString('.CRITICAL:', $contents);
		$this->assertStringContainsString('.ALERT:', $contents);
		$this->assertStringContainsString('.EMERGENCY:', $contents);
	}

	public function testContextIncluded() {
		$this->_requireMonolog();
		$b = new MonologBackend($this->logDir, MonologBackend::DEBUG, 'ctx');
		$b->logInfo('with context', ['k' => 'v']);
		$contents = file_get_contents($this->_logFilePath('ctx'));
		$this->assertStringContainsString('"k":"v"', $contents);
	}

	public function testThresholdFiltersLowerSeverity() {
		$this->_requireMonolog();
		$b = new MonologBackend($this->logDir, MonologBackend::ERR, 'thresh');
		$b->logDebug('hidden debug');
		$b->logInfo('hidden info');
		$b->logWarn('hidden warn');
		$b->logError('shown error');
		$contents = file_get_contents($this->_logFilePath('thresh'));
		$this->assertStringNotContainsString('hidden debug', $contents);
		$this->assertStringNotContainsString('hidden info', $contents);
		$this->assertStringNotContainsString('hidden warn', $contents);
		$this->assertStringContainsString('shown error', $contents);
	}

	public function testOFFDoesNotWrite() {
		$this->_requireMonolog();
		$b = new MonologBackend($this->logDir, MonologBackend::OFF, 'off');
		$b->logInfo('should not appear');
		$this->assertFileDoesNotExist($this->_logFilePath('off'));
	}

	public function testWriteFreeFormLineWritesRegardlessOfThreshold() {
		$this->_requireMonolog();
		$b = new MonologBackend($this->logDir, MonologBackend::ERR, 'free');
		$b->writeFreeFormLine('raw line');
		$contents = file_get_contents($this->_logFilePath('free'));
		$this->assertStringContainsString('raw line', $contents);
	}

	public function testMessageQueueNonEmptyAfterConstruction() {
		$this->_requireMonolog();
		$b = new MonologBackend($this->logDir, MonologBackend::DEBUG, 'queue');
		$messages = $b->getMessages();
		$this->assertNotEmpty($messages);
		$msg = $b->getMessage();
		$this->assertTrue(is_string($msg) && strlen($msg) > 0);
		$this->assertSame([], $b->getMessages());
	}

	public function testGetLogFilePath() {
		$this->_requireMonolog();
		$b = new MonologBackend($this->logDir, MonologBackend::DEBUG, 'path');
		$this->assertEquals($this->_logFilePath('path'), $b->getLogFilePath());
	}
}