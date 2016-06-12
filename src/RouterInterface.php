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

use Psr\Http\Message\ServerRequestInterface;

/**
 * The router interface.
 */
interface RouterInterface extends \IteratorAggregate, \Countable, \Serializable
{
    /**
     * Sets all default router parameters.
     *
     * @param array $params The default parameters
     *
     * @return self
     */
    public function setDefaultParams(array $params);

    /**
     * Sets the default router parameter.
     *
     * @param string $name  The name of parameter
     * @param mixed  $value The parameter value
     *
     * @return self
     */
    public function setDefaultParam($name, $value);

    /**
     * Gets the default router parameters.
     *
     * @return array The default parameters
     */
    public function getDefaultParams();

    /**
     * Adds the route.
     *
     * @param string         $name  The route name
     * @param RouteInterface $route The instance of route
     *
     * @return self
     */
    public function add($name, RouteInterface $route);

    /**
     * Is there the given route?
     *
     * @param string $name The route name
     *
     * @return bool Returns true on success, false otherwise
     */
    public function has($name);

    /**
     * Removes the route.
     *
     * @param string $name The route name
     *
     * @return self
     */
    public function remove($name);

    /**
     * Gets the route.
     *
     * @param string $name The route name
     *
     * @throws \InvalidArgumentException If the route with given name not exists
     *
     * @return RouteInterface The route
     */
    public function get($name);

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
    public function match(ServerRequestInterface $request);

    /**
     * Gets match of route.
     *
     * @throws \RuntimeException If no matching found
     *
     * @return RouteMatch
     */
    public function getRouteMatch();

    /**
     * Gets count of routes.
     *
     * @return int The number of routes
     */
    public function count();

    /**
     * Gets iterator.
     *
     * @return \ArrayIterator The iterator
     */
    public function getIterator();

    /**
     * Merges with other router.
     *
     * @param RouterInterface $source The data source
     *
     * @return null|array If the source was passed returns
     *                    null, source data otherwise
     */
    public function merge(RouterInterface $source = null);
}
