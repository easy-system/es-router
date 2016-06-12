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

use Es\Http\Uri;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use UnexpectedValueException;

/**
 * The route.
 */
class Route implements RouteInterface
{
    /**
     * The path of route.
     *
     * @var string
     */
    protected $path = '/';

    /**
     * The defaults of route parameters.
     *
     * @var array
     */
    protected $defaults = [];

    /**
     * The constraints of route parameters.
     *
     * @var array
     */
    protected $constraints = [];

    /**
     * The allowed schemes.
     *
     * @var array
     */
    protected $schemes = [];

    /**
     * The allowed methods.
     *
     * @var array
     */
    protected $methods = [];

    /**
     * Parts of the route.
     *
     * @var array
     */
    protected $parts = [];

    /**
     * The compiled regular expression.
     *
     * @var string
     */
    protected $compiled = '';

    /**
     * Constructor.
     *
     * @param string $path        The route path
     * @param array  $defaults    The defaults
     * @param array  $constraints The constraints
     * @param array  $schemes     The allowed schemes
     * @param array  $methods     The allowed methods
     */
    public function __construct(
        $path,
        array $defaults = null,
        array $constraints = null,
        array $schemes = null,
        array $methods = null
    ) {
        $this->path = (string) $path;
        if (null != $defaults) {
            $this->defaults = $defaults;
        }
        if (null != $constraints) {
            $this->constraints = $constraints;
        }
        if (null != $schemes) {
            $this->schemes = array_map('strtolower', $schemes);
        }
        if (null != $methods) {
            $this->methods = array_map('strtoupper', $methods);
        }
        $this->compile();
    }

    /**
     * Gets the path.
     *
     * @return string The path
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Gets the default parameters.
     *
     * @return array The default parameters
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * Gets the constraints of parameters.
     *
     * @return array The constraints of parameters
     */
    public function getConstraints()
    {
        return $this->constraints;
    }

    /**
     * Gets the expected Uri schemes.
     *
     * @return array The schemes
     */
    public function getSchemes()
    {
        return $this->schemes;
    }

    /**
     * Gets the expected request methods.
     *
     * @return array The request methods
     */
    public function getMethods()
    {
        return $this->methods;
    }

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
    public function assemble($params = [])
    {
        $params = array_merge($this->defaults, $params);
        $parts  = $this->parts;

        $return = [];
        foreach ($parts as $part) {
            if (0 === strpos($part, '~') && 1 !== strpos($part, ':')) {
                continue;
            }
            if (0 === strpos($part, ':')) {
                if (! isset($params[ltrim($part, ':')])) {
                    throw new InvalidArgumentException(sprintf(
                        'Missing value for the placeholder "%s".',
                        $part
                    ));
                }
                $name = ltrim($part, ':');
                if (isset($this->constraints[$name])
                    && ! preg_match(
                            '#\A' . $this->constraints[$name] . '\Z#',
                            $params[$name]
                        )
                ) {
                    throw new InvalidArgumentException(sprintf(
                        'The value "%s" of parameter "%s" must be constrain '
                        . 'with regex "%s".',
                        $params[$name],
                        $name,
                        $this->constraints[$name]
                    ));
                }
                $return[] = Uri::encode($params[$name]);

                continue;
            }
            if (1 === strpos($part, ':')) {
                $name = ltrim($part, '~:');
                if (! isset($params[$name])) {
                    continue;
                }
                if (isset($this->constraints[$name])
                    && ! preg_match(
                            '#\A' . $this->constraints[$name] . '\Z#',
                            $params[$name]
                        )
                ) {
                    throw new InvalidArgumentException(sprintf(
                        'The value "%s" of parameter "%s" must be constrain '
                        . 'with regex "%s".',
                        $params[$name],
                        $name,
                        $this->constraints[$name]
                    ));
                }
                $return[] = Uri::encode($params[$name]);

                continue;
            }
            $return[] = Uri::encode(ltrim($part, '~'));
        }

        return '/' . implode('/', $return);
    }

    /**
     * Find matches for the given request.
     *
     * @param  $request \Es\Http\Psr\ServerRequestInterface The request
     *
     * @return RouteMatch|false The matching result if request matches, false otherwise
     */
    public function match(ServerRequestInterface $request)
    {
        $method = strtoupper($request->getMethod());
        if (! empty($this->methods) && ! in_array($method, $this->methods)) {
            return false;
        }
        $uri    = $request->getUri();
        $scheme = $uri->getScheme();
        if (! empty($this->schemes) && ! in_array($scheme, $this->schemes)) {
            return false;
        }
        $path = rtrim($uri->getPath(), '/');

        $regex  = $this->compiled;
        $result = preg_match($regex, $path, $matches);

        if (! $result) {
            return false;
        }

        $params = $this->defaults;
        foreach ($matches as $key => &$value) {
            if (is_int($key)) {
                continue;
            }
            if ($value === '') {
                continue;
            }
            $params[$key] = $value;
        }
        $params['request_method'] = $method;
        $params['request_scheme'] = $scheme;

        return new RouteMatch($params);
    }

    /**
     * Compiles route.
     */
    protected function compile()
    {
        $this->parts = $parts = preg_split(
            '#\/#', $this->path, -1, PREG_SPLIT_NO_EMPTY
        );
        $regex = '';

        foreach ($parts as &$part) {
            $control = substr($part, 0, 2);
            if (false !== strpos($control, ':')) {
                if (! preg_match('#\A(~)?(:){1}[a-zA-Z0-9]+\Z#', $part)) {
                    throw new InvalidArgumentException(sprintf(
                        'Invalid placeholder name "%s" provided; must contain '
                        . 'only english alphanumeric characters.',
                        $part
                    ));
                }
            } elseif (preg_match('/[\#\?]+/', $part)) {
                throw new InvalidArgumentException(sprintf(
                    'The segment "%s" of path "%s" contains illegal characters.',
                    $part,
                    $this->path
                ));
            }
            if ('~:' === substr($part, 0, 2)) {
                $part = ltrim($part, '~:');
                $regex .= '\/?(?P<' . $part . '>';

                if (isset($this->constraints[$part])) {
                    $regex .= $this->constraints[$part] . ')';
                } else {
                    $regex .= '[^\/]*)';
                }
            } elseif (':' === substr($part, 0, 1)) {
                $part = ltrim($part, ':');
                $regex .= '\/(?P<' . $part . '>';

                if (isset($this->constraints[$part])) {
                    $regex .= $this->constraints[$part] . ')';
                } else {
                    $regex .= '[^\/]+)';
                }
            } elseif ('~' === substr($part, 0, 1)) {
                $part = ltrim($part, '~');
                $regex .= '\/?(' . preg_quote(Uri::encode($part)) . ')?';
            } else {
                $part = $part;
                $regex .= '\/' . preg_quote(Uri::encode($part));
            }
        }
        $this->compiled = '#\A' . $regex . '\Z#';
    }

    /**
     * Serializes the route.
     *
     * @return string The string representation of object
     */
    public function serialize()
    {
        return serialize([
            $this->path,
            $this->schemes,
            $this->methods,
            $this->defaults,
            $this->constraints,
            $this->parts,
            $this->compiled,
        ]);
    }

    /**
     * Constructs route.
     *
     * @param  string The string representation of object
     */
    public function unserialize($serialized)
    {
        list(
            $this->path,
            $this->schemes,
            $this->methods,
            $this->defaults,
            $this->constraints,
            $this->parts,
            $this->compiled
        ) = unserialize($serialized);
    }
}
