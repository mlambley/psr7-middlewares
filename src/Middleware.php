<?php

namespace Psr7Middlewares;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class Middleware
{
    const KEY = 'Psr7Middlewares\\Middleware';
    const STORAGE_KEY = 'STORAGE_KEY';

    private static $streamFactory;
    private static $namespaces = [__NAMESPACE__.'\\Middleware\\'];

    /**
     * Register a new namespace.
     *
     * @param string $namespace
     */
    public static function registerNamespace($namespace)
    {
        self::$namespaces[] = $namespace;
    }

    /**
     * Set the stream factory used by some middlewares.
     *
     * @param callable $streamFactory
     */
    public static function setStreamFactory(callable $streamFactory)
    {
        self::$streamFactory = $streamFactory;
    }

    /**
     * Set the stream factory used by some middlewares.
     *
     * @param callable|null
     */
    public static function getStreamFactory()
    {
        return self::$streamFactory;
    }

    /**
     * Create instances of the middlewares.
     *
     * @param string $name
     * @param array  $args
     */
    public static function __callStatic($name, $args)
    {
        foreach (self::$namespaces as $namespace) {
            $class = $namespace.ucfirst($name);

            if (class_exists($class)) {
                switch (count($args)) {
                    case 0:
                        return new $class();

                    case 1:
                        return new $class($args[0]);

                    default:
                        return (new \ReflectionClass($class))->newInstanceArgs($args);
                }
            }
        }

        throw new RuntimeException("The middleware {$name} does not exits");
    }

    /**
     * Create a middleware callable that acts as a "proxy" to a real middleware that must be returned by the given callback.
     *
     * @param callable $factory Takes no argument and MUST return a middleware callable or false
     * 
     * @return callable
     */
    public static function create(callable $factory)
    {
        return function (RequestInterface $request, ResponseInterface $response, callable $next) use ($factory) {
            $middleware = $factory($request, $response);

            if ($middleware === false) {
                return $next($request, $response);
            }

            if (!is_callable($middleware)) {
                throw new RuntimeException(sprintf('Factory returned "%s" instead of a callable or FALSE.', gettype($middleware)));
            }

            return $middleware($request, $response, $next);
        };
    }
}
