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

/**
 * The result of matching a route.
 */
class RouteMatch
{
    /**
     * The parameters of matched route.
     *
     * @var array
     */
    protected $params = [];

    /**
     * The name of matched route.
     *
     * @var null|string
     */
    protected $routeName;

    /**
     * Constructor.
     *
     * @param array $params The parameters of matched route
     */
    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    /**
     * Sets the name of matched route.
     *
     * @param string $name The name of matched route
     *
     * @return self
     */
    public function setMatchedRouteName($name)
    {
        $this->routeName = (string) $name;

        return $this;
    }

    /**
     * Gets the name of matched route.
     *
     * @return null|string The name of matched route, if any
     */
    public function getMatchedRouteName()
    {
        return $this->routeName;
    }

    /**
     * Sets a parameter.
     *
     * @param string $name  The parameter name
     * @param mixed  $value The value of parameter
     *
     * @return self
     */
    public function setParam($name, $value)
    {
        $this->params[(string) $name] = $value;

        return $this;
    }

    /**
     * Gets a specific parameter.
     *
     * @param string $name    The parameter name
     * @param mixed  $default The value by default
     *
     * @return mixed The parameter value on success, $default otherwise
     */
    public function getParam($name, $default = null)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }

        return $default;
    }

    /**
     * Gets all parameters.
     *
     * @return array The parameters
     */
    public function getParams()
    {
        $params          = $this->params;
        $params['route'] = $this->routeName;

        return $params;
    }
}
