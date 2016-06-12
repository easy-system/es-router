<?php
/**
 * This file is part of the "Easy System" package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Damon Smith <damon.easy.system@gmail.com>
 */
namespace Es\Router\Test;

use Es\Router\RouteMatch;
use ReflectionProperty;

class RouteMatchTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $params = [
            'foo' => 'bar',
            'bat' => 'baz',
        ];
        $routeMatch = new RouteMatch($params);
        $reflection = new ReflectionProperty($routeMatch, 'params');
        $reflection->setAccessible(true);
        $this->assertSame($params, $reflection->getValue($routeMatch));
    }

    public function testSetMatchedRouteName()
    {
        $routeMatch = new RouteMatch();
        $return     = $routeMatch->setMatchedRouteName('foo');
        $this->assertSame($return, $routeMatch);
        $this->assertSame('foo', $routeMatch->getMatchedRouteName());
    }

    public function testGetMatchedRouteName()
    {
        $routeMatch = new RouteMatch();
        $this->assertNull($routeMatch->getMatchedRouteName());
        $routeMatch->setMatchedRouteName('foo');
        $this->assertSame('foo', $routeMatch->getMatchedRouteName());
    }

    public function testSetParam()
    {
        $routeMatch = new RouteMatch();
        $return     = $routeMatch->setParam('foo', 'bar');
        $this->assertSame($return, $routeMatch);
        $this->assertSame('bar', $routeMatch->getParam('foo'));
    }

    public function testGetParamReturnsDefaultIfParamNotExists()
    {
        $routeMatch = new RouteMatch();
        $this->assertSame('bar', $routeMatch->getParam('foo', 'bar'));
    }

    public function testGetParams()
    {
        $params = [
            'foo' => 'bar',
            'bat' => 'baz',
        ];
        $routeMatch = new RouteMatch($params);
        $routeMatch->setMatchedRouteName('foo-bar-baz');
        $return = $routeMatch->getParams();
        $this->assertTrue(empty(array_diff($params, $return)));
        $this->assertTrue(isset($return['route']));
        $this->assertSame('foo-bar-baz', $return['route']);
    }
}
