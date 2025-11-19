<?php
/**
 * Base Test Case
 *
 * @package FPNewspaper\Tests
 */

namespace FPNewspaper\Tests;

use Brain\Monkey;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case con Brain Monkey setup
 */
abstract class TestCase extends PHPUnitTestCase {
    
    /**
     * Setup prima di ogni test
     */
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }
    
    /**
     * Teardown dopo ogni test
     */
    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }
}


