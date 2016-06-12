<?php
/**
 * This file is part of the "Easy System" package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Damon Smith <damon.easy.system@gmail.com>
 */
namespace Es\Router;

use ArrayIterator;
use Es\Router\Exception\RouteNotFoundException;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * The router.
 */
class Router implements RouterInterface
{
    /**
     * Match of route.
     *
     * @var null|\Es\Router\RouteMatch
     */
    protected $match;

    /**
     * The routes.
     *
     * @var array
     */
    protected $routes = [];

    /**
     * The default router parameters.
     *
     * @var array
     */
    protected $defaultParams = [];

    /**
     * Sets all default router parameters.
     *
     * @param array $params The default parameters
     *
     * @return self
     */
    public function setDefaultParams(array $params)
    {
        $this->defaultParams = $params;

        return $this;
    }

    /**
     * Sets the default router parameter.
     *
     * @param string $name  The name of parameter
     * @param mixed  $value The parameter value
     *
     * @return self
     */
    public function setDefaultParam($name, $value)
    {
        $this->defaultParams[(string) $name] = $value;

        return $this;
    }

    /**
     * Gets the default router parameters.
     *
     * @return array The default parameters
     */
    public function getDefaultParams()
    {
        return $this->defaultParams;
    }

    /**
     * Adds the route.
     *
     * @param string         $name  The route name
     * @param RouteInterface $route The instance of route
     *
     * @return self
     */
    public function add($name, RouteInterface $route)
    {
        $this->routes[(string) $name] = $route;

        return $this;
    }

    /**
     * Is there the given route?
     *
     * @param string $name The route name
     *
     * @return bool Returns true on success, false otherwise
     */
    public function has($name)
    {
        return isset($this->routes[$name]);
    }

    /**
     * Removes the route.
     *
     * @param string $name The route name
     *
     * @return self
     */
    public function remove($name)
    {
        if (isset($this->routes[$name])) {
            unset($this->routes[$name]);
        }

        return $this;
    }

    /**
     * Gets the route.
     *
     * @param string $name The route name
     *
     * @throws \InvalidArgumentException If the route with given name not exists
     *
     * @return RouteInterface The route
     */
    public function get($name)
    {
        if (! isset($this->routes[$name])) {
            throw new InvalidArgumentException(
                sprintf('The route with given name "%s" not exists.', $name)
            );
        }

        return $this->routes[$name];
    }

    /**
     * Find matches for the given request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     *
     * @throws \RuntimeException      If the router configuration is not specified
     * @throws RouteNotFoundException If failed to find any matches
     *
     * @return self
     */
    public function match(ServerRequestInterface $request)
    {
        if (empty($this->routes)) {
            throw new RuntimeException(
                'The router configuration is not specified.'
            );
        }

        foreach ($this->routes as $name => $route) {
            if (($match = $route->match($request)) instanceof RouteMatch) {
                $match->setMatchedRouteName($name);
                foreach ($this->defaultParams as $paramName => $value) {
                    if ($match->getParam($paramName) === null) {
                        $match->setParam($paramName, $value);
                    }
                }
                $this->match = $match;

                return $this;
            }
        }
        throw new RouteNotFoundException(sprintf(
            'Failed to find the matching for requested route "%s".',
            $request->getUri()->getPath()
        ));
    }

    /**
     * Gets match of route.
     *
     * @throws \RuntimeException If no matching found
     *
     * @return RouteMatch
     */
    public function getRouteMatch()
    {
        if (! $this->match) {
            throw new RuntimeException('No matching was found.');
        }

        return $this->match;
    }

    /**
     * Gets count of routes.
     *
     * @return int The number of routes
     */
    public function count()
    {
        return count($this->routes);
    }

    /**
     * Gets iterator.
     *
     * @return \ArrayIterator The iterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->routes);
    }

    /**
     * Serializes the router.
     *
     * @return string The string representation of object
     */
    public function serialize()
    {
        return serialize([
            $this->routes,
            $this->defaultParams,
        ]);
    }

    /**
     * Constructs router.
     *
     * @param  string The string representation of object
     */
    public function unserialize($serialized)
    {
        list(
            $this->routes,
            $this->defaultParams
        ) = unserialize($serialized);
    }

    /**
     * Merges with other router.
     *
     * @param RouterInterface $source The data source
     *
     * @return null|array If the source was passed returns
     *                    null, source data otherwise
     */
    public function merge(RouterInterface $source = null)
    {
        if (null == $source) {
            return [
                $this->routes,
                $this->defaultParams,
            ];
        }
        list($routes, $params) = $source->merge();

        $this->routes        = array_merge($this->routes, $routes);
        $this->defaultParams = array_merge($this->defaultParams, $params);
    }
}
