<?php

namespace WebMoves\PluginBase\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WebMoves\PluginBase\Plugin;
use WebMoves\PluginBase\Contracts\Plugin\PluginCore;

/**
 * Test case for the Plugin class
 */
class AbstractPluginTest extends TestCase
{
    /**
     * Test plugin initialization
     */
    public function testInitPlugin()
    {
        // Create a mock plugin implementation
        $plugin = MockPlugin::init_plugin(__FILE__, '1.0.0', 'mock-plugin');
        
        // Assert that the plugin is initialized
        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->assertInstanceOf(MockPlugin::class, $plugin);
        
        // Test get_instance returns the same instance
        $instance = MockPlugin::get_instance();
        $this->assertSame($plugin, $instance);
        
        // Test get_core returns a valid core
        $core = $plugin->get_core();
        $this->assertInstanceOf(PluginCore::class, $core);
        $this->assertEquals('1.0.0', $core->get_version());
        $this->assertEquals('mock-plugin', $core->get_text_domain());
    }
    
    /**
     * Test exception when initializing plugin twice
     */
    public function testInitPluginTwiceThrowsException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Plugin already initialized');
        
        // Reset the MockPlugin instance
        MockPlugin::reset_instance();
        
        // Initialize once
        MockPlugin::init_plugin(__FILE__, '1.0.0');
        
        // Initialize again - should throw exception
        MockPlugin::init_plugin(__FILE__, '1.0.0');
    }
    
    /**
     * Test exception when getting instance before initialization
     */
    public function testGetInstanceBeforeInitThrowsException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Plugin not initialized');
        
        // Reset the MockPlugin instance
        MockPlugin::reset_instance();
        
        // Try to get instance before initialization
        MockPlugin::get_instance();
    }
    
    /**
     * Test service registration
     */
    public function testServiceRegistration()
    {
        // Reset the MockPlugin instance
        MockPlugin::reset_instance();
        
        // Initialize with a test service
        $plugin = MockPlugin::init_plugin(__FILE__, '1.0.0');
        
        // Check if the service was registered
        $core = $plugin->get_core();
        $service = $core->get('test-service');
        
        $this->assertEquals('test-value', $service);
    }
    
    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        // Reset the MockPlugin instance
        MockPlugin::reset_instance();
        
        parent::tearDown();
    }
}

/**
 * Mock implementation of Plugin for testing
 */
class MockPlugin extends Plugin
{
    private static $initialized = false;
    
    /**
     * Reset the instance for testing using Reflection API
     */
    public static function reset_instance(): void
    {
        // Use Reflection to access the private static property
        $reflection = new \ReflectionClass(Plugin::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, null);
        
        self::$initialized = false;
    }
    
    /**
     * Initialize the plugin
     */
    public function initialize(): void
    {
        self::$initialized = true;
    }
    
    /**
     * Get services for the plugin
     */
    public function get_services(): array
    {
        return [
            'test-service' => 'test-value',
        ];
    }
    
    /**
     * Check if the plugin was initialized
     */
    public static function was_initialized(): bool
    {
        return self::$initialized;
    }
}