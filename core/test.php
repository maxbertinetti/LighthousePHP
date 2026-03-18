<?php

/**
 * Lightweight testing framework for Lighthouse.
 */

$lh_test_results = [];

/**
 * Exception used for assertion failures.
 */
class LighthouseAssertionFailed extends RuntimeException
{
}

/**
 * Load all test case files from the project test directory.
 *
 * @param string|null $root
 * @return array<int, array{name:string, callback:callable}>
 */
function lh_collect_tests(?string $root = null): array
{
    $base = $root ?? dirname(__DIR__);
    $tests = [];

    foreach (glob($base . '/tests/*Test.php') ?: [] as $testFile) {
        $loaded = require $testFile;

        if (is_array($loaded)) {
            $tests = array_merge($tests, $loaded);
        }
    }

    return $tests;
}

/**
 * Register a test case.
 *
 * @param string $name
 * @param callable $callback
 * @return array{name:string, callback:callable}
 */
function lh_test(string $name, callable $callback): array
{
    return [
        'name' => $name,
        'callback' => $callback,
    ];
}

/**
 * Assert that two values are identical.
 *
 * @param mixed $expected
 * @param mixed $actual
 * @param string $message
 * @return void
 */
function lh_assert_same($expected, $actual, string $message = ''): void
{
    if ($expected === $actual) {
        return;
    }

    $detail = $message !== '' ? $message : 'Expected values to be identical.';
    $detail .= ' Expected: ' . var_export($expected, true) . ' Actual: ' . var_export($actual, true);

    throw new LighthouseAssertionFailed($detail);
}

/**
 * Assert that a value is truthy.
 *
 * @param mixed $value
 * @param string $message
 * @return void
 */
function lh_assert_true($value, string $message = ''): void
{
    if ($value) {
        return;
    }

    throw new LighthouseAssertionFailed($message !== '' ? $message : 'Expected condition to be true.');
}

/**
 * Assert that a string contains a substring.
 *
 * @param string $needle
 * @param string $haystack
 * @param string $message
 * @return void
 */
function lh_assert_contains(string $needle, string $haystack, string $message = ''): void
{
    if (strpos($haystack, $needle) !== false) {
        return;
    }

    $detail = $message !== '' ? $message : "Expected to find '{$needle}' in output.";

    throw new LighthouseAssertionFailed($detail);
}

/**
 * Assert that an array has a specific key.
 *
 * @param string $key
 * @param array $array
 * @param string $message
 * @return void
 */
function lh_assert_array_has_key(string $key, array $array, string $message = ''): void
{
    if (array_key_exists($key, $array)) {
        return;
    }

    $detail = $message !== '' ? $message : "Expected array key '{$key}' to exist.";

    throw new LighthouseAssertionFailed($detail);
}

/**
 * Run a list of tests and print a summary.
 *
 * @param array<int, array{name:string, callback:callable}> $tests
 * @return int
 */
function lh_run_tests(array $tests): int
{
    $passed = 0;
    $failed = 0;

    foreach ($tests as $test) {
        $name = $test['name'];
        $callback = $test['callback'];

        try {
            $callback();
            $passed++;
            fwrite(STDOUT, "PASS {$name}\n");
        } catch (Throwable $throwable) {
            $failed++;
            fwrite(STDOUT, "FAIL {$name}\n");
            fwrite(STDOUT, '  ' . $throwable->getMessage() . "\n");
        }
    }

    fwrite(STDOUT, "\nTests: " . count($tests) . ", Passed: {$passed}, Failed: {$failed}\n");

    return $failed === 0 ? 0 : 1;
}
