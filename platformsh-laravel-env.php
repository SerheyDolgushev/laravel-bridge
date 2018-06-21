<?php

declare(strict_types=1);

namespace Platformsh\LaravelBridge;

mapPlatformShEnvironment();

/**
 * Map Platform.Sh environment variables to the values Laravel expects.
 *
 * This is wrapped up into a function to avoid executing code in the global
 * namespace.
 */
function mapPlatformShEnvironment() : void
{
    // If this env var is not set then we're not on a Platform.sh
    // environment or in the build hook, so don't try to do anything.
    if (!getenv('PLATFORM_APPLICATION')) {
        return;
    }

    // APP_URL (need to derive)

    // Set the application secret if it's not already set.
    $secret = getenv('APP_KEY') ?: getenv('PLATFORM_PROJECT_ENTROPY') ?: null;
    setEnvVar('APP_KEY', $secret);

    // Force secure cookies on by default, since Platform.sh is SSL-all-the-things.
    // It can be overridden explicitly.
    $secure_cookie = getenv('SESSION_SECURE_COOKIE') ?: 1;
    setEnvVar('SESSION_SECURE_COOKIE', $secure_cookie);

    if (!getenv('DB_DATABASE')) {
        mapPlatformShDatabase('database');
    }

    if (!getenv('REDIS_HOST')) {
        mapPlatformShRedisCache('rediscache');
    }

    // @TODO Should MAIL_* be set as well?

    // @TODO Should we support a redisqueue service as well?

    // @TODO Should we support a redissession service as well?

}

/**
 * Sets an environment variable in all the myriad places PHP can store it.
 *
 * @param string $name
 *   The name of the variable to set.
 * @param mixed $value
 *   The value to set.  Null to unset it.
 */
function setEnvVar(string $name, $value = null) : void
{
    if (!putenv("$name=$value")) {
        throw new \RuntimeException('Failed to create environment variable: ' . $name);
    }
    $order = ini_get('variables_order');
    if (stripos($order, 'e') !== false) {
        $_ENV[$name] = $value;
    }
    if (stripos($order, 's') !== false) {
        if (strpos($name, 'HTTP_') !== false) {
            throw new \RuntimeException('Refusing to add ambiguous environment variable ' . $name . ' to $_SERVER');
        }
        $_SERVER[$name] = $value;
    }
}

function mapPlatformShDatabase(string $dbRelationshipName) : void
{
    if (getenv('PLATFORM_RELATIONSHIPS')) {
        $relationships = json_decode(base64_decode(getenv('PLATFORM_RELATIONSHIPS'), true), true);
        if (isset($relationships[$dbRelationshipName])) {
            foreach ($relationships[$dbRelationshipName] as $endpoint) {
                if (empty($endpoint['query']['is_master'])) {
                    continue;
                }

                setEnvVar('DB_CONNECTION', $endpoint['scheme']);

                setEnvVar('DB_HOST', $endpoint['host']);
                setEnvVar('DB_PORT', $endpoint['port']);
                setEnvVar('DB_DATABASE', $endpoint['path']);
                setEnvVar('DB_USERNAME', $endpoint['username']);
                setEnvVar('DB_PASSWORD', $endpoint['password']);
            }
        }
    }
}

function mapPlatformShRedisCache(string $cacheRelationshipName) : void
{
    if (getenv('PLATFORM_RELATIONSHIPS')) {
        $relationships = json_decode(base64_decode(getenv('PLATFORM_RELATIONSHIPS'), true), true);
        if (isset($relationships[$cacheRelationshipName])) {
            setEnvVar('CACHE_DRIVER', 'redis');
            foreach ($relationships[$cacheRelationshipName] as $endpoint) {
                setEnvVar('REDIS_HOST', $endpoint['host']);
                setEnvVar('REDIS_PORT', $endpoint['port']);
                break;
            }
        }
    }
}
