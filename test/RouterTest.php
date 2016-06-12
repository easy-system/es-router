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

use Es\Http\ServerRequest;
use Es\Http\Uri;
use Es\Router\Exception\RouteNotFoundException;
use Es\Router\Route;
use Es\Router\Router;
use ReflectionProperty;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function testSetDefaultParams()
    {
        $params = ['foo' => 'bar'];
        $router = new Router();
        $return = $router->setDefaultParams($params);
        $this->assertSame($router, $return);
        $reflection = new ReflectionProperty($router, 'defaultParams');
        $reflection->setAccessible(true);
        $this->assertSame($params, $reflection->getValue($router));
    }

    public function testSetDefaultParam()
    {
        $router = new Router();
        $return = $router->setDefaultParam('foo', 'bar');
        $this->assertSame($router, $return);
        $expected   = ['foo' => 'bar'];
        $reflection = new ReflectionProperty($router, 'defaultParams');
        $reflection->setAccessible(true);
        $this->assertSame($expected, $reflection->getValue($router));
    }

    public function testGetDefaultParams()
    {
        $params = ['foo' => 'bar'];
        $router = new Router();
        $router->setDefaultParams($params);
        $this->assertSame($params, $router->getDefaultParams());
    }

    public function testAddRoute()
    {
        $router = new Router();
        $route  = new Route('/foo');
        $return = $router->add('foo', $route);
        $this->assertSame($return, $router);
        $reflection = new ReflectionProperty($router, 'routes');
        $reflection->setAccessible(true);
        $expected = ['foo' => $route];
        $this->assertSame($expected, $reflection->getValue($router));
    }

    public function testHas()
    {
        $router = new Router();
        $this->assertFalse($router->has('foo'));
        $route = new Route('/foo');
        $router->add('foo', $route);
        $this->assertTrue($router->has('foo'));
    }

    public function testRemove()
    {
        $router = new Router();
        $route  = new Route('/foo');
        $router->add('foo', $route);
        $this->assertTrue($router->has('foo'));
        $return = $router->remove('foo');
        $this->assertSame($return, $router);
        $this->assertFalse($router->has('foo'));
    }

    public function testGetRaiseExceptionIfRouteNotExists()
    {
        $router = new Router();
        $this->setExpectedException('InvalidArgumentException');
        $router->get('foo');
    }

    public function testGet()
    {
        $router = new Router();
        $route  = new Route('foo');
        $router->add('foo', $route);
        $this->assertSame($route, $router->get('foo'));
    }

    public function testMatchRaiseExceptionIfRoutesEmpty()
    {
        $router = new Router();
        $this->setExpectedException('RuntimeException');
        $router->match(new ServerRequest());
    }

    public function testMatchRaiseExceptionIfNothingWasFound()
    {
        $router = new Router();
        $route  = new Route('/foo');
        $router->add('foo', $route);
        $this->setExpectedException(RouteNotFoundException::CLASS);
        $router->match(new ServerRequest());
    }

    public function testMatchOnSuccess()
    {
        $router = new Router();
        $route  = new Route('/foo');
        $router->add('foo', $route);
        $uri     = new Uri('/foo');
        $request = (new ServerRequest())->withUri($uri);
        $return  = $router->match($request);
        $this->assertSame($router, $return);
        $this->assertInstanceOf('Es\Router\RouteMatch', $router->getRouteMatch());
    }

    public function testMatchSetsDefaultsOnSuccess()
    {
        $router = new Router();
        $router->setDefaultParams([
            'foo' => 'bar',
            'baz' => 'ban',
        ]);
        $route = new Route(
            '/foo',
            ['baz' => 'bat']
        );
        $router->add('foo', $route);
        $uri     = new Uri('/foo');
        $request = (new ServerRequest())->withUri($uri);
        $router->match($request);
        $routeMatch = $router->getRouteMatch();
        $this->assertInstanceOf('Es\Router\RouteMatch', $routeMatch);
        $this->assertSame('bar', $routeMatch->getParam('foo'));
        $this->assertSame('bat', $routeMatch->getParam('baz'));
        $this->assertSame('foo', $routeMatch->getMatchedRouteName());
    }

    public function testGetRouteMatchRaiseExceptionIfNoMatchingWasFound()
    {
        $router = new Router();
        $this->setExpectedException('RuntimeException');
        $router->getRouteMatch();
    }

    public function testCount()
    {
        $router = new Router();
        $this->assertSame(0, $router->count());
        $route = new Route('/foo');
        $router->add('foo', $route);
        $this->assertSame(1, $router->count());
    }

    public function testGetIterator()
    {
        $router = new Router();
        $route  = new Route('/foo');
        $router->add('foo', $route);
        $iterator = $router->getIterator();
        $this->assertInstanceOf('ArrayIterator', $iterator);
        $expected = ['foo' => $route];
        $this->assertSame($expected, $iterator->getArrayCopy());
    }

    public function testSerializable()
    {
        $router = new Router();
        $route  = new Route('/foo');
        $router->add('foo', $route);
        $router->setDefaultParams(['foo' => 'bar']);
        $serialized = serialize($router);
        $this->assertEquals($router, unserialize($serialized));
    }

    public function testMerge()
    {
        $router = new Router();
        $source = new Router();
        $route  = new Route('/foo');
        $source->add('foo', $route);
        $params = ['foo' => 'bar'];
        $source->setDefaultParams($params);
        $router->merge($source);
        $this->assertTrue($router->has('foo'));
        $this->assertSame($route, $router->get('foo'));
        $this->assertSame($params, $router->getDefaultParams());
    }
}
