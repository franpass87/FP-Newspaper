<?php
/**
 * Test per REST Controller
 *
 * @package FPNewspaper\Tests\REST
 */

namespace FPNewspaper\Tests\REST;

use FPNewspaper\Tests\TestCase;
use FPNewspaper\REST\Controller;
use Brain\Monkey\Functions;

/**
 * Test REST API Controller
 */
class ControllerTest extends TestCase {
    
    /**
     * @var Controller
     */
    private $controller;
    
    protected function setUp(): void {
        parent::setUp();
        
        // Mock WordPress functions
        Functions\when('register_rest_route')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('get_transient')->justReturn(false);
        Functions\when('set_transient')->justReturn(true);
        Functions\when('delete_transient')->justReturn(true);
        
        $this->controller = new Controller();
    }
    
    /**
     * Test che il controller venga costruito correttamente
     */
    public function test_controller_constructed() {
        $this->assertInstanceOf(Controller::class, $this->controller);
    }
    
    /**
     * Test namespace REST API
     */
    public function test_rest_namespace() {
        $this->assertEquals('fp-newspaper/v1', Controller::NAMESPACE);
    }
    
    /**
     * Test permission check restituisce true per admin
     */
    public function test_check_permission_returns_true_for_admin() {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(true);
        
        $result = $this->controller->check_permission();
        $this->assertTrue($result);
    }
    
    /**
     * Test permission check restituisce false per non-admin
     */
    public function test_check_permission_returns_false_for_non_admin() {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(false);
        
        $result = $this->controller->check_permission();
        $this->assertFalse($result);
    }
}


