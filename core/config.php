<?php

/**
 * Configuration Loader for Lighthouse
 *
 * Loads a single environment INI file, validates it against the committed
 * contract in config/config.ini.example, and exposes read access through
 * lh_config('section.key').
 */

$lh_config = null;
$lh_config_base_dir_override = null;

/**
 * Return the active config directory.
 *
 * @return string
 */
function lh_config_base_dir(): string
{
    global $lh_config_base_dir_override;

    if (is_string($lh_config_base_dir_override) && $lh_config_base_dir_override !== '') {
        return $lh_config_base_dir_override;
    }

    return dirname(__DIR__) . '/config';
}

/**
 * Resolve the active application environment.
 *
 * Preferred source is a constant defined by the CLI. Until the CLI is in
 * place, APP_ENV and development are accepted as runtime fallbacks.
 *
 * @return string
 */
function lh_env(): string
{
    if (defined('LIGHTHOUSE_ENV')) {
        return (string) constant('LIGHTHOUSE_ENV');
    }

    $env = getenv('APP_ENV');

    if (is_string($env) && $env !== '') {
        return $env;
    }

    return 'development';
}

/**
 * Return whether the current environment matches the provided name.
 *
 * @param string $name
 * @return bool
 */
function lh_is_env(string $name): bool
{
    return lh_env() === $name;
}

/**
 * Parse an INI file with sections and strict scalar scanning.
 *
 * @param string $path
 * @return array
 */
function lh_parse_ini(string $path): array
{
    $parsed = parse_ini_file($path, true, INI_SCANNER_TYPED);

    if (!is_array($parsed)) {
        lh_config_fail("Unable to parse configuration file: {$path}");
    }

    return $parsed;
}

/**
 * Build the config path for an environment.
 *
 * @param string $env
 * @return string
 */
function lh_config_path(string $env): string
{
    return lh_config_base_dir() . "/config.{$env}.ini";
}

/**
 * Fail fast for invalid configuration state.
 *
 * @param string $message
 * @return never
 */
function lh_config_fail(string $message)
{
    http_response_code(500);
    die($message);
}

/**
 * Validate runtime config against the committed contract.
 *
 * Contract is strict: all sections and keys must match exactly.
 *
 * @param array $contract
 * @param array $runtime
 * @return void
 */
function lh_validate_config(array $contract, array $runtime): void
{
    $contractSections = array_keys($contract);
    $runtimeSections = array_keys($runtime);

    sort($contractSections);
    sort($runtimeSections);

    if ($contractSections !== $runtimeSections) {
        lh_config_fail('Configuration sections do not match config/config.ini.example.');
    }

    foreach ($contract as $section => $values) {
        if (!is_array($values) || !isset($runtime[$section]) || !is_array($runtime[$section])) {
            lh_config_fail("Configuration section [{$section}] is invalid.");
        }

        $contractKeys = array_keys($values);
        $runtimeKeys = array_keys($runtime[$section]);

        sort($contractKeys);
        sort($runtimeKeys);

        if ($contractKeys !== $runtimeKeys) {
            lh_config_fail("Configuration keys for [{$section}] do not match config/config.ini.example.");
        }
    }
}

/**
 * Load and cache configuration once.
 *
 * @return array
 */
function lh_load_config(): array
{
    global $lh_config;

    if (is_array($lh_config)) {
        return $lh_config;
    }

    $baseDir = lh_config_base_dir();
    $contractPath = $baseDir . '/config.ini.example';
    $runtimePath = lh_config_path(lh_env());

    if (!file_exists($contractPath)) {
        lh_config_fail('Missing configuration contract: config/config.ini.example');
    }

    if (!file_exists($runtimePath)) {
        lh_config_fail("Missing environment configuration file: {$runtimePath}");
    }

    $contract = lh_parse_ini($contractPath);
    $runtime = lh_parse_ini($runtimePath);

    lh_validate_config($contract, $runtime);

    $lh_config = $runtime;
    return $lh_config;
}

/**
 * Replace the cached configuration.
 *
 * Primarily used by tests and CLI flows that need to swap environments.
 *
 * @param array|null $config
 * @return void
 */
function lh_config_set(?array $config): void
{
    global $lh_config;

    $lh_config = $config;
}

/**
 * Clear the cached configuration so the next read reloads from disk.
 *
 * @return void
 */
function lh_config_reset(): void
{
    lh_config_set(null);
}

/**
 * Override the config base directory.
 *
 * @param string|null $directory
 * @return void
 */
function lh_config_set_base_dir(?string $directory): void
{
    global $lh_config_base_dir_override;

    $lh_config_base_dir_override = $directory;
}

/**
 * Read a configuration value via dot notation.
 *
 * Examples:
 * - lh_config('database.host')
 * - lh_config('app.name')
 *
 * @param string|null $key
 * @param mixed $default
 * @return mixed
 */
function lh_config(?string $key = null, $default = null)
{
    $config = lh_load_config();

    if ($key === null || $key === '') {
        return $config;
    }

    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }

        $value = $value[$segment];
    }

    return $value;
}

/**
 * Apply environment-specific runtime defaults.
 *
 * @return void
 */
function lh_apply_environment_defaults(): void
{
    if (!lh_is_env('development')) {
        return;
    }

    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}
