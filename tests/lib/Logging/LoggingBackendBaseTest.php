<?php
/** ---------------------------------------------------------------------
 * tests/lib/Logging/LoggingBackendBaseTest.php
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

require_once(__CA_LIB_DIR__.'/Logging/Backends/LoggingBackendBase.php');

/**
 * Test the abstract LoggingBackendBase class using a minimal concrete subclass
 * that only implements the abstract constructor, log() and writeFreeFormLine()
 * methods, recording the calls made to them.
 */
class LoggingBackendBaseTest extends TestCase {

	/** @var array Calls recorded by the TestBackend (['method'=>..., 'line'=>..., 'severity'=>...]) */
	private $calls = [];

	/** Minimal concrete backend used to drive LoggingBackendBase */
	private function _makeBackend($severity = LoggingBackendBase::DEBUG) {
		return new class($this->calls, $severity) extends LoggingBackendBase {
			public $calls;
			public function __construct(&$calls, $severity = LoggingBackendBase::DEBUG) {
				$this->calls =& $calls;
				$this->_severityThreshold = $severity;
			}
			public function log($line, $severity, $args = self::NO_ARGUMENTS) {
				$this->calls[] = ['method' => 'log', 'line' => $line, 'severity' => $severity, 'args' => $args];
			}
			public function writeFreeFormLine($line) {
				$this->calls[] = ['method' => 'writeFreeFormLine', 'line' => $line];
			}
		};
	}

	public function testSeverityConstantValues() {
		$this->assertEquals(0, LoggingBackendBase::EMERG);
		$this->assertEquals(1, LoggingBackendBase::ALERT);
		$this->assertEquals(2, LoggingBackendBase::CRIT);
		$this->assertEquals(3, LoggingBackendBase::ERR);
		$this->assertEquals(4, LoggingBackendBase::WARN);
		$this->assertEquals(5, LoggingBackendBase::NOTICE);
		$this->assertEquals(6, LoggingBackendBase::INFO);
		$this->assertEquals(7, LoggingBackendBase::DEBUG);
		$this->assertEquals(8, LoggingBackendBase::OFF);
		$this->assertEquals(2, LoggingBackendBase::FATAL);	// alias of CRIT
	}

	public function testLevelHelpersRouteToLog() {
		$this->calls = [];
		$b = $this->_makeBackend();

		$b->logEmerg('e');
		$this->assertCount(1, $this->calls);
		$this->assertEquals([ 'method' => 'log', 'line' => 'e', 'severity' => LoggingBackendBase::EMERG, 'args' => LoggingBackendBase::NO_ARGUMENTS ], $this->calls[0]);

		$this->calls = [];
		$b->logAlert('a');
		$this->assertEquals(LoggingBackendBase::ALERT, $this->calls[0]['severity']);

		$this->calls = [];
		$b->logCrit('c');
		$this->assertEquals(LoggingBackendBase::CRIT, $this->calls[0]['severity']);

		$this->calls = [];
		$b->logError('er');
		$this->assertEquals(LoggingBackendBase::ERR, $this->calls[0]['severity']);

		$this->calls = [];
		$b->logWarn('w');
		$this->assertEquals(LoggingBackendBase::WARN, $this->calls[0]['severity']);

		$this->calls = [];
		$b->logNotice('n');
		$this->assertEquals(LoggingBackendBase::NOTICE, $this->calls[0]['severity']);

		$this->calls = [];
		$b->logInfo('i');
		$this->assertEquals(LoggingBackendBase::INFO, $this->calls[0]['severity']);

		$this->calls = [];
		$b->logDebug('d');
		$this->assertEquals(LoggingBackendBase::DEBUG, $this->calls[0]['severity']);

		$this->calls = [];
		$b->logFatal('f');
		$this->assertEquals(LoggingBackendBase::FATAL, $this->calls[0]['severity']);
	}

	public function testLevelHelpersPassArgsThrough() {
		$this->calls = [];
		$b = $this->_makeBackend();

		$b->logInfo('i', ['key' => 'value']);
		$this->assertCount(1, $this->calls);
		$this->assertEquals(['key' => 'value'], $this->calls[0]['args']);
	}

	public function testLogDebugIgnoresArgs() {
		// The KLogger-compatible behavior is that logDebug() does not forward args
		$this->calls = [];
		$b = $this->_makeBackend();

		$b->logDebug('d', ['should' => 'not-pass']);
		$this->assertEquals(LoggingBackendBase::NO_ARGUMENTS, $this->calls[0]['args']);
	}

	public function testMessageQueuePushPopAndClear() {
		$this->calls = [];
		$b = $this->_makeBackend();

		$this->assertSame([], $b->getMessages());
		$this->assertSame(null, $b->getMessage());

		// addMessage is protected; drive it through getMessages after construction of real backend? Here we use reflection.
		$method = new ReflectionMethod($b, 'addMessage');
		$method->setAccessible(true);
		$method->invoke($b, 'first');
		$method->invoke($b, 'second');

		$this->assertEquals(['first', 'second'], $b->getMessages());

		// getMessage pops the last
		$this->assertEquals('second', $b->getMessage());
		$this->assertEquals(['first'], $b->getMessages());

		$b->clearMessages();
		$this->assertSame([], $b->getMessages());
	}

	public function testSetDateFormat() {
		$this->calls = [];
		$b = $this->_makeBackend();

		LoggingBackendBase::setDateFormat('Y');
		// restore default so other tests are unaffected
		LoggingBackendBase::setDateFormat('Y-m-d G:i:s');

		$this->assertTrue(true);	// verifying no exception was thrown
	}

	public function testGetSeverityThresholdReflectsConstruction() {
		$this->calls = [];
		$b = $this->_makeBackend(LoggingBackendBase::ERR);
		$method = new ReflectionMethod($b, 'getSeverityThreshold');
		$method->setAccessible(true);
		$this->assertEquals(LoggingBackendBase::ERR, $method->invoke($b));
	}

	public function testImplementsInterface() {
		$this->calls = [];
		$b = $this->_makeBackend();
		$iface = class_implements($b);
		$this->assertArrayHasKey('LoggingBackend', $iface);
	}
}