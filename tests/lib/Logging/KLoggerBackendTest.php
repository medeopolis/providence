<?php
/** ---------------------------------------------------------------------
 * tests/lib/Logging/KLoggerBackendTest.php
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

require_once(__CA_LIB_DIR__.'/Logging/Backends/KLogger/KLogger.php');

class KLoggerBackendTest extends TestCase {

	private $logDir;

	protected function setUp(): void {
		$this->logDir = sys_get_temp_dir().'/ca_klogger_test_'.uniqid();
		@mkdir($this->logDir, 0777, true);
	}

	protected function tearDown(): void {
		$files = @glob($this->logDir.'/log_*.txt');
		if (is_array($files)) {
			foreach($files as $f) { @unlink($f); }
		}
		@rmdir($this->logDir);
	}

	/**
	 * Path of the log file written for the given log name on today's date
	 */
	private function _logFilePath($logName = null) {
		return $this->logDir.'/log_'.($logName ? "{$logName}_" : '').date('Y-m-d').'.txt';
	}

	public function testLogFileCreatedWithExpectedName() {
		$b = new KLoggerBackend($this->logDir, KLoggerBackend::DEBUG, 'myapp');
		$b->logInfo('hello');
		$this->assertFileExists($this->_logFilePath('myapp'));
	}

	public function testLogLineFormat() {
		$b = new KLoggerBackend($this->logDir, KLoggerBackend::DEBUG, 'fmt');
		$b->logInfo('a line');
		$contents = file_get_contents($this->_logFilePath('fmt'));
		$this->assertMatchesRegularExpression('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} - INFO --> a line$/', trim($contents));
	}

	public function testLogErrorLevelLabel() {
		$b = new KLoggerBackend($this->logDir, KLoggerBackend::DEBUG, 'err');
		$b->logError('oops');
		$contents = file_get_contents($this->_logFilePath('err'));
		$this->assertStringContainsString('- ERROR --> oops', $contents);
	}

	public function testLogWarnLevelLabel() {
		$b = new KLoggerBackend($this->logDir, KLoggerBackend::DEBUG, 'w');
		$b->logWarn('careful');
		$contents = file_get_contents($this->_logFilePath('w'));
		$this->assertStringContainsString('- WARN --> careful', $contents);
	}

	public function testArgsAreIncludedInLogLine() {
		$b = new KLoggerBackend($this->logDir, KLoggerBackend::DEBUG, 'args');
		$b->logInfo('with args', ['a' => 1, 'b' => 'two']);
		$contents = file_get_contents($this->_logFilePath('args'));
		$this->assertStringContainsString("'a' => 1", $contents);
		$this->assertStringContainsString("'b' => 'two'", $contents);
	}

	public function testThresholdFiltersLowerSeverity() {
		$b = new KLoggerBackend($this->logDir, KLoggerBackend::ERR, 'thresh');
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
		$b = new KLoggerBackend($this->logDir, KLoggerBackend::OFF, 'off');
		$b->logInfo('should not appear');
		$this->assertFileDoesNotExist($this->_logFilePath('off'));
	}

	public function testWriteFreeFormLineWritesRegardlessOfThreshold() {
		$b = new KLoggerBackend($this->logDir, KLoggerBackend::ERR, 'free');
		$b->writeFreeFormLine('raw line');
		$contents = file_get_contents($this->_logFilePath('free'));
		$this->assertStringContainsString('raw line', $contents);
	}

	public function testWriteFreeFormLineUnchanged() {
		$b = new KLoggerBackend($this->logDir, KLoggerBackend::DEBUG, 'free2');
		$b->writeFreeFormLine("no timestamp here");
		$contents = file_get_contents($this->_logFilePath('free2'));
		$this->assertStringNotContainsString('INFO -->', $contents);
		$this->assertStringNotContainsString('DEBUG -->', $contents);
	}

	public function testMessageQueueContainsOpenSuccess() {
		$b = new KLoggerBackend($this->logDir, KLoggerBackend::DEBUG, 'queue');
		$messages = $b->getMessages();
		$this->assertNotEmpty($messages);
		$this->assertSame('The log file was opened successfully.', $b->getMessage());
	}

	public function testMessageQueuePopAndClear() {
		$b = new KLoggerBackend($this->logDir, KLoggerBackend::DEBUG, 'queue2');
		$this->assertEquals(['The log file was opened successfully.'], $b->getMessages());
		$b->clearMessages();
		$this->assertSame([], $b->getMessages());
		$this->assertSame(null, $b->getMessage());
	}

	public function testCreatesLogDirectoryIfMissing() {
		$dir = $this->logDir.'/nested/sub';
		$b = new KLoggerBackend($dir, KLoggerBackend::DEBUG, 'nested');
		$b->logInfo('nested write');
		$this->assertDirectoryExists($dir);
		$this->assertFileExists($dir.'/log_nested_'.date('Y-m-d').'.txt');
	}
}