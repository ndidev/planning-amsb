<?php

// Path: api/src/Core/Router/Router.php

declare(strict_types=1);

namespace App\Core\Router;

/**
 * Router.
 * 
 * It is a modified version of AltoRouter
 * to better suit the needs of the application.
 * 
 * @link http://altorouter.com/
 * 
 * @phpstan-type RouteArray array{0: string, 1: mixed, 2?: string}
 */
class Router
{
    /**
     * Array of all routes (incl. named routes).
     * @var Route[]
     */
    protected array $routes = [];

    /**
     * Array of all named routes.
     * @var array<string, Route>
     */
    protected array $namedRoutes = [];

    /**
     * Can be used to ignore leading part of the Request URL (if main file lives in subdirectory of host)
     */
    protected string $basePath = '';

    /**
     * Array of default match types (regex helpers)
     * @var array<string, string>
     */
    protected array $matchTypes = [
        'i'  => '[0-9]++',
        'a'  => '[0-9A-Za-z]++',
        'h'  => '[0-9A-Fa-f]++',
        '*'  => '.+?',
        '**' => '.++',
        ''   => '[^/\.]++'
    ];

    /**
     * Create router in one call from config.
     *
     * @param array $routes
     * @param string $basePath
     * @param array<string, string> $matchTypes
     * 
     * @phpstan-param RouteArray[] $routes
     */
    public function __construct(
        array $routes = [],
        string $basePath = '',
        array $matchTypes = []
    ) {
        $this->addRoutes($routes);
        $this->setBasePath($basePath);
        $this->addMatchTypes($matchTypes);
    }

    /**
     * Retrieves all routes.
     * Useful if you want to process or display routes.
     * 
     * @return Route[] All routes.
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Add multiple routes at once.
     * 
     * Add routes from array in the following format:
     *
     * ```php
     * $routes = [
     *   [string $route, mixed $target, ?string $name]
     * ];
     * ```
     *
     * @param array|\Traversable $routes
     * 
     * @phpstan-param array<RouteArray>|\Traversable<RouteArray> $routes
     * 
     * @author Koen Punt
     */
    public function addRoutes(array|\Traversable $routes): void
    {
        foreach ($routes as $route) {
            call_user_func_array([$this, 'map'], $route);
        }
    }

    /**
     * Set the base path.
     * Useful if you are running your application from a subdirectory.
     * @param string $basePath
     */
    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    /**
     * Add named match types. It uses array_merge so keys can be overwritten.
     *
     * @param array<string, string> $matchTypes The key is the name and the value is the regex.
     */
    public function addMatchTypes(array $matchTypes): void
    {
        $this->matchTypes = array_merge($this->matchTypes, $matchTypes);
    }

    /**
     * Map a route to a target
     *
     * @param string $path The route regex, custom regex must start with an @. You can use multiple pre-set regex filters, like [i:id]
     * @param mixed $target The target where this route should point to. Can be anything.
     * @param string $name Optional name of this route. Supply if you want to reverse route this url in your application.
     * 
     * @throws \RuntimeException
     */
    public function map(string $path, mixed $target, ?string $name = null): void
    {
        $route = new Route($path, $target, $name);

        $this->routes[] = $route;

        if ($name) {
            if (isset($this->namedRoutes[$name])) {
                throw new \RuntimeException("Can not redeclare route '{$name}'");
            }
            $this->namedRoutes[$name] = $route;
        }

        return;
    }

    /**
     * Reversed routing
     *
     * Generate the URL for a named route. Replace regexes with supplied parameters
     *
     * @param string                $routeName The name of the route.
     * @param array<string, string> $params    Associative array of parameters to replace placeholders with.
     * 
     * @return string The URL of the route with named parameters in place.
     * 
     * @throws \RuntimeException
     */
    public function generate(string $routeName, array $params = []): string
    {

        // Check if named route exists
        if (!isset($this->namedRoutes[$routeName])) {
            throw new \RuntimeException("Route '{$routeName}' does not exist.");
        }

        // Replace named parameters
        $route = $this->namedRoutes[$routeName];

        // prepend base path to route url again
        $path = $route->getPath();
        $url = $this->basePath . $path;

        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $path, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $index => $match) {
                list($block, $pre, $type, $param, $optional) = $match;

                if ($pre) {
                    $block = substr($block, 1);
                }

                if (isset($params[$param])) {
                    // Part is found, replace for param value
                    $url = str_replace($block, $params[$param], $url);
                } elseif ($optional && $index !== 0) {
                    // Only strip preceding slash if it's not at the base
                    $url = str_replace($pre . $block, '', $url);
                } else {
                    // Strip match block
                    $url = str_replace($block, '', $url);
                }
            }
        }

        return $url;
    }

    /**
     * Match a given Request Url against stored routes.
     * 
     * @param string $requestUrl The request url to match.
     * 
     * @return array{target: mixed, params: array<string, mixed>, name: ?string}|false Array with route information on success, false on failure (no match).
     */
    public function match(?string $requestUrl = null): array|false
    {

        $params = [];

        // set Request Url if it isn't passed as parameter
        if ($requestUrl === null) {
            $requestUrl = $_SERVER['REQUEST_URI'] ?? '/';
        }

        // strip base path from request url
        $requestUrl = substr($requestUrl, strlen($this->basePath));

        // Strip query string (?a=b) from Request Url
        if (($strpos = strpos($requestUrl, '?')) !== false) {
            $requestUrl = substr($requestUrl, 0, $strpos);
        }

        $lastRequestUrlChar = $requestUrl ? $requestUrl[strlen($requestUrl) - 1] : '';

        foreach ($this->routes as $route) {
            list($path, $target, $name) = $route->toArray();

            if ($path === '*') {
                // * wildcard (matches all)
                $match = true;
            } elseif (isset($path[0]) && $path[0] === '@') {
                // @ regex delimiter
                $pattern = '`' . substr($path, 1) . '`u';
                $match = preg_match($pattern, $requestUrl, $params) === 1;
            } elseif (($position = strpos($path, '[')) === false) {
                // No params in url, do string comparison
                $match = strcmp($requestUrl, $path) === 0;
            } else {
                // Compare longest non-param string with url before moving on to regex
                // Check if last character before param is a slash, because it could be optional if param is optional too (see https://github.com/dannyvankooten/AltoRouter/issues/241)
                if (strncmp($requestUrl, $path, $position) !== 0 && ($lastRequestUrlChar === '/' || $path[$position - 1] !== '/')) {
                    continue;
                }

                $regex = $this->compileRoute($path);
                $match = preg_match($regex, $requestUrl, $params) === 1;
            }

            if ($match) {
                if ($params) {
                    foreach ($params as $key => &$value) {
                        if (is_numeric($key)) {
                            unset($params[$key]);
                            continue;
                        }

                        if (is_numeric($value)) {
                            $value = (int) $value;
                        }
                    }
                }

                return [
                    'target' => $target,
                    'params' => $params,
                    'name' => $name
                ];
            }
        }

        return false;
    }

    /**
     * Compile the regex for a given route (EXPENSIVE)
     * 
     * @param string $route
     * 
     * @return string
     */
    protected function compileRoute(string $route): string
    {
        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
            $matchTypes = $this->matchTypes;
            foreach ($matches as $match) {
                list($block, $pre, $type, $param, $optional) = $match;

                if (isset($matchTypes[$type])) {
                    $type = $matchTypes[$type];
                }
                if ($pre === '.') {
                    $pre = '\.';
                }

                $optional = $optional !== '' ? '?' : null;

                //Older versions of PCRE require the 'P' in (?P<named>)
                $pattern = '(?:'
                    . ($pre !== '' ? $pre : null)
                    . '('
                    . ($param !== '' ? "?P<$param>" : null)
                    . $type
                    . ')'
                    . $optional
                    . ')'
                    . $optional;

                $route = str_replace($block, $pattern, $route);
            }
        }
        return "`^$route$`u";
    }
}
