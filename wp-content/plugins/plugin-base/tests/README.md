# WordPress Plugin Base Framework - Testing Guide

This directory contains the test suite for the WordPress Plugin Base Framework. The tests are written using PHPUnit and are designed to run without requiring a full WordPress environment.

## Directory Structure

- `tests/` - The root directory for all tests
  - `Unit/` - Unit tests for individual components
  - `bootstrap.php` - Bootstrap file for PHPUnit that sets up the testing environment

## Running Tests

To run the tests, use the following command from the plugin root directory:

```bash
vendor/bin/phpunit
```

You can also run specific test files or test suites:

```bash
# Run a specific test file
vendor/bin/phpunit tests/Unit/AbstractPluginTest.php

# Run a specific test method
vendor/bin/phpunit --filter testInitPlugin
```

## Writing Tests

### Unit Tests

Unit tests should be placed in the `tests/Unit` directory and should follow these guidelines:

1. Extend the `PHPUnit\Framework\TestCase` class
2. Use the namespace `WebMoves\PluginBase\Tests\Unit`
3. Name your test class after the class you're testing, with a `Test` suffix (e.g., `AbstractPluginTest`)
4. Name your test methods with a `test` prefix (e.g., `testInitPlugin`)

Example:

```php
<?php

namespace WebMoves\PluginBase\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WebMoves\PluginBase\YourClass;

class YourClassTest extends TestCase
{
    public function testYourMethod()
    {
        $instance = new YourClass();
        $result = $instance->yourMethod();
        $this->assertEquals('expected value', $result);
    }
}
```

### Mocking WordPress Functions

The `bootstrap.php` file provides mock implementations of common WordPress functions. If you need to mock additional WordPress functions, add them to this file following the existing pattern:

```php
if (!function_exists('your_wordpress_function')) {
    /**
     * Mock for your_wordpress_function WordPress function
     *
     * @param string $param Description of the parameter
     * @return mixed Description of the return value
     */
    function your_wordpress_function($param)
    {
        // Mock implementation
        return 'mock result';
    }
}
```

### Testing Abstract Classes

When testing abstract classes, create a concrete implementation in your test file:

```php
class MockAbstractClass extends AbstractClass
{
    // Implement abstract methods
    public function abstractMethod()
    {
        return 'implemented';
    }
}
```

### Accessing Private Properties for Testing

To test classes with private properties, use PHP's Reflection API:

```php
$reflection = new \ReflectionClass(YourClass::class);
$property = $reflection->getProperty('privateProperty');
$property->setAccessible(true);
$value = $property->getValue($instance);
```

## Code Coverage

PHPUnit is configured to generate code coverage reports. To generate a coverage report, run:

```bash
vendor/bin/phpunit --coverage-html coverage
```

This will generate an HTML coverage report in the `coverage` directory.

## Continuous Integration

It's recommended to run the tests as part of your continuous integration process. Add the following to your CI configuration:

```yaml
# Example GitHub Actions workflow
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: vendor/bin/phpunit
```

## Best Practices

1. **Test in isolation**: Each test should be independent and not rely on the state from other tests.
2. **Use setUp and tearDown**: Use these methods to set up and clean up test environments.
3. **Mock dependencies**: Use mock objects to isolate the code being tested.
4. **Test edge cases**: Include tests for edge cases and error conditions.
5. **Keep tests simple**: Each test should test one specific behavior.
6. **Use descriptive test names**: The test name should describe what is being tested.