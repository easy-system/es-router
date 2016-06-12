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
 * The route interface.
 */
interface RouteInterface extends \Serializable
{
    /**
     * Gets the path.
     *
     * @return string The path
     */
    public function getPath();

    /**
     * Gets the default parameters.
     *
     * @return array The default parameters
     */
    public function getDefaults();

    /**
     * Gets the constraints of parameters.
     *
     * @return array The constraints of parameters
     */
    public function getConstraints();

    /**
     * Gets the expected Uri schemes.
     *
     * @return array The schemes
     */
    public function getSchemes();

    /**
     * Gets the expected request methods.
     *
     * @return array The request methods
     */
    public function getMethods();

    /**
     * Assemble the route.
     *
     * @param array $params The route parameters
     *
     * @throws \InvalidArgumentException
     *
     * - If the value of required placeholder is not specified
     * - If a specified parameter not match with route constraints
     *
     * @return string The route path
     */
    public function assemble($params = []);

    /**
     * Find matches for the given request.
     *
     * @param  $request \Psr\Http\Message\ServerRequestInterface The request
     *
     * @return RouteMatch|false The matching result if request matches, false otherwise
     */
    public function match(ServerRequestInterface $request);
}
