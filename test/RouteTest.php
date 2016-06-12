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
use Es\Router\Route;
use Es\Router\RouteMatch;

class RouteTest extends \PHPUnit_Framework_TestCase
{
    public function testGetPath()
    {
        $route = new Route('/foo');
        $this->assertSame('/foo', $route->getPath());
    }

    public function testGetDefaults()
    {
        $defaults = [
            'foo' => 'bar',
            'bat' => 'baz',
        ];
        $route = new Route('/', $defaults);
        $this->assertSame($defaults, $route->getDefaults());
    }

    public function testGetConstraints()
    {
        $constraints = [
            'foo' => '\d+',
            'bar' => '[a-zA-Z]+',
        ];
        $route = new Route('/:foo/:bar', null, $constraints);
        $this->assertSame($constraints, $route->getConstraints());
    }

    public function testGetSchemes()
    {
        $schemes = ['https'];
        $route   = new Route('/', null, null, $schemes);
        $this->assertSame($schemes, $route->getSchemes());
    }

    public function testGetMethods()
    {
        $methods = ['GET', 'POST'];
        $route   = new Route('/', null, null, null, $methods);
        $this->assertSame($methods, $route->getMethods());
    }

    public function literalDataProvider()
    {
        $literals = [
            '/foo',
            '/foo/',
            '/foo/bar',
            '/foo/bar/',
            '/foo/bar/baz',
            '/foo/bar/baz/',
            '/foo/bar-bat:baz[baq]',
            '/foo <bar> [:baz№3] {bat}',
        ];
        $return = [];
        foreach ($literals as $literal) {
            $return[] = [$literal];
        }

        return $return;
    }

    /**
     * @dataProvider literalDataProvider
     */
    public function testMatchLiteralsOnSuccess($path)
    {
        $route   = new Route($path);
        $uri     = new Uri($path);
        $request = (new ServerRequest())->withUri($uri);
        $result  = $route->match($request);
        $this->assertInstanceOf(RouteMatch::CLASS, $result);
    }

    /**
     * @dataProvider literalDataProvider
     */
    public function testMatchLiteralsOnFailure($path)
    {
        $route   = new Route($path);
        $uri     = new Uri('/');
        $request = (new ServerRequest())->withUri($uri);
        $result  = $route->match($request);
        $this->assertFalse($result);
    }

    public function placeholderDataProvider()
    {
        return [
            // route path    |  url to match  | key | value
            ['/:foo',         '/123',          'foo', '123'],
            ['/:foo',         '/321/',         'foo', '321'],
            ['/foo/:bar',     '/foo/123',      'bar', '123'],
            ['/foo/:bar',     '/foo/321/',     'bar', '321'],
            ['/foo/bar/:baz', '/foo/bar/123',  'baz', '123'],
            ['/foo/bar/:baz', '/foo/bar/321/', 'baz', '321'],
        ];
    }

    /**
     * @dataProvider placeholderDataProvider
     */
    public function testMatchPlaceholdersOnSuccess($routePath, $url, $expectedKey, $expectedValue)
    {
        $route   = new Route($routePath);
        $uri     = new Uri($url);
        $request = (new ServerRequest())->withUri($uri);
        $result  = $route->match($request);
        $this->assertInstanceOf(RouteMatch::CLASS, $result);
        $this->assertSame($expectedValue, $result->getParam($expectedKey));
    }

    /**
     * @dataProvider placeholderDataProvider
     */
    public function testMatchPlaceholdersOnFailure($path)
    {
        $route   = new Route($path);
        $uri     = new Uri('/');
        $request = (new ServerRequest())->withUri($uri);
        $result  = $route->match($request);
        $this->assertFalse($result);
    }

    public function optionalSegmentDataProvider()
    {
        return [
            ['/~foo',          '/'],
            ['/~foo',          '/foo'],
            ['/~foo',          '/foo/'],
            ['/~foo/bar',      '/bar'],
            ['/~foo/bar',      '/foo/bar'],
            ['/foo/~bar',      '/foo'],
            ['/foo/~bar',      '/foo/bar'],
            ['/foo/~bar/~baz', '/foo/bar'],
            ['/foo/~bar/~baz', '/foo/baz'],
            ['/foo/~bar/~baz', '/foo/bar/baz'],
        ];
    }

    /**
     * @dataProvider optionalSegmentDataProvider
     */
    public function testMatchOptionalSegmentOnSuccess($routePath, $url)
    {
        $route   = new Route($routePath);
        $uri     = new Uri($url);
        $request = (new ServerRequest())->withUri($uri);
        $result  = $route->match($request);
        $this->assertInstanceOf(RouteMatch::CLASS, $result);
    }

    /**
     * @dataProvider optionalSegmentDataProvider
     */
    public function testMatchOptionalSegmentOnFailure($routePath)
    {
        $route   = new Route($routePath);
        $uri     = new Uri('/url/contains/no/match/');
        $request = (new ServerRequest())->withUri($uri);
        $result  = $route->match($request);
        $this->assertFalse($result);
    }

    public function optionalSegmentWitPlaceholderDataProvider()
    {
        return [
            // route path | url to match | expected
            ['/~:foo/~:bar', '/cob/con', ['foo' => 'cob', 'bar' => 'con']],
            ['/~foo/~:bar',  '/con',     ['bar' => 'con']],
            ['/~:foo/~:bar', '/con',     ['foo' => 'con']],
        ];
    }

    /**
     * @dataProvider optionalSegmentWitPlaceholderDataProvider
     */
    public function testMatchOptionalSegmentWithPlaceholdersOnSuccess($routePath, $url, array $expected)
    {
        $route   = new Route($routePath);
        $uri     = new Uri($url);
        $request = (new ServerRequest())->withUri($uri);
        $result  = $route->match($request);
        $this->assertInstanceOf(RouteMatch::CLASS, $result);
        foreach ($expected as $key => $value) {
            $this->assertSame($value, $result->getParam($key));
        }
    }

    public function testMatchWithDefaults()
    {
        $path     = '/foo/~:bar';
        $defaults = ['bar' => 'bat'];
        $route    = new Route($path, $defaults);
        //
        $uri     = new Uri('/foo');
        $request = (new ServerRequest())->withUri($uri);
        $result  = $route->match($request);
        $this->assertInstanceOf(RouteMatch::CLASS, $result);
        $this->assertSame('bat', $result->getParam('bar'));
        //
        $uri     = new Uri('/foo/baz');
        $request = (new ServerRequest())->withUri($uri);
        $result  = $route->match($request);
        $this->assertInstanceOf(RouteMatch::CLASS, $result);
        $this->assertSame('baz', $result->getParam('bar'));
    }

    public function testMatchWithConstraintsOnSuccess()
    {
        $path        = '/~:foo/~:bar';
        $constraints = ['foo' => '\d*'];
        $route       = new Route($path, null, $constraints);
        $uri         = new Uri('/soc');
        $request     = (new ServerRequest())->withUri($uri);
        $result      = $route->match($request);
        $this->assertInstanceOf(RouteMatch::CLASS, $result);
        $this->assertSame('soc', $result->getParam('bar'));
    }

    public function testMatchWithConstraintsOnFailure()
    {
        $path        = '/:foo';
        $constraints = ['foo' => '\d+'];
        $route       = new Route($path, null, $constraints);
        $uri         = new Uri('/soc');
        $request     = (new ServerRequest())->withUri($uri);
        $result      = $route->match($request);
        $this->assertFalse($result);
    }

    public function testMatchWithSchemesOnSuccess()
    {
        $path    = '/foo';
        $schemes = ['HtTp'];
        $route   = new Route($path, null, null, $schemes);
        $uri     = new Uri('http:/foo');
        $request = (new ServerRequest())->withUri($uri);
        $result  = $route->match($request);
        $this->assertInstanceOf(RouteMatch::CLASS, $result);
        $this->assertSame('http', $result->getParam('request_scheme'));
    }

    public function testMatchWithSchemesOnFailure()
    {
        $path    = '/foo';
        $schemes = ['http'];
        $route   = new Route($path, null, null, $schemes);
        $uri     = new Uri('https:/foo');
        $request = (new ServerRequest())->withUri($uri);
        $result  = $route->match($request);
        $this->assertFalse($result);
    }

    public function testMatchWithMethodsOnSuccess()
    {
        $path    = '/foo';
        $methods = ['get', 'pOsT'];
        $route   = new Route($path, null, null, null, $methods);
        $uri     = new Uri('/foo');
        $request = (new ServerRequest())->withUri($uri)->withMethod('post');
        $result  = $route->match($request);
        $this->assertInstanceOf(RouteMatch::CLASS, $result);
        $this->assertSame('POST', $result->getParam('request_method'));
    }

    public function testMatchWithMethodsOnFailure()
    {
        $path    = '/foo';
        $methods = ['get', 'put'];
        $route   = new Route($path, null, null, null, $methods);
        $uri     = new Uri('/foo');
        $request = (new ServerRequest())->withUri($uri)->withMethod('post');
        $result  = $route->match($request);
        $this->assertFalse($result);
    }

    public function testInvalidPlaceholderNameRaiseException()
    {
        $path = '/:foo&bar';
        $this->setExpectedException('InvalidArgumentException');
        $route = new Route($path);
    }

    public function testInvalidSegmentNameRaiseException()
    {
        $path = '/foo?bar';
        $this->setExpectedException('InvalidArgumentException');
        $route = new Route($path);
    }

    public function testAssembleRaiseExceptionIfRequiredPlaceholderIsNotSpecified()
    {
        $path  = '/:foo';
        $route = new Route($path);
        $this->setExpectedException('InvalidArgumentException');
        $route->assemble([]);
    }

    public function testAssembleRaiseExceptionIfSpecifiedParameterNotMatchOfRouteConstraintsForOptionalParameter()
    {
        $path        = '/~:foo';
        $constraints = ['foo' => '\d*'];
        $route       = new Route($path, null, $constraints);
        $this->setExpectedException('InvalidArgumentException');
        $route->assemble(['foo' => 'some_string']);
    }

    public function testAssembleRaiseExceptionIfSpecifiedParameterNotMatchOfRouteConstraintsForRequiredParameter()
    {
        $path        = '/:foo';
        $constraints = ['foo' => '\d+'];
        $route       = new Route($path, null, $constraints);
        $this->setExpectedException('InvalidArgumentException');
        $route->assemble(['foo' => 'some_string']);
    }

    public function testAssembleOnSuccess()
    {
        $path    = '/foo/~bar/:bat/~:baz';
        $route   = new Route($path);
        $params  = ['bat' => 333];
        $expects = '/foo/333';
        $this->assertSame($expects, $route->assemble($params));
    }

    public function testAssembleOnSuccessWithDefaults()
    {
        $path     = '/foo/~bar/:bat/~:baz';
        $defaults = ['baz' => 'gaz'];
        $route    = new Route($path, $defaults);
        $params   = ['bat' => 333];
        $expects  = '/foo/333/gaz';
        $this->assertSame($expects, $route->assemble($params));
    }

    public function testSerializable()
    {
        $route = new Route(
            '/:foo/~:bar',
            ['bar' => 'baz'],
            ['foo' => '\d+'],
            ['https'],
            ['get', 'post']
        );
        $serialized = serialize($route);
        $this->assertEquals($route, unserialize($serialized));
    }
}
