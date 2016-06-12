<?php
/**
 * This file is part of the "Easy System" package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Damon Smith <damon.easy.system@gmail.com>
 */
namespace Es\Router\Exception;

use Es\Exception\NotFoundExceptionInterface;
use Exception;

/**
 * The exception, which throws the router if noting found for the
 * request matching.
 */
class RouteNotFoundException extends Exception implements NotFoundExceptionInterface
{
}
