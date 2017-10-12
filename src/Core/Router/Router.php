<?php
/**************************************************************************
Copyright 2017 Benato Denis

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
 *****************************************************************************/

namespace Gishiki\Core\Router;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Gishiki\Algorithms\Strings\SimpleLexer;
use Gishiki\Algorithms\Collections\GenericCollection;
use Gishiki\Services\ErrorHandling;

/**
 * This component represents the application as a set of HTTP rules.
 *
 * @author Benato Denis <benato.denis96@gmail.com>
 */
final class Router
{
    /**
     * @var array a list of registered Gishiki\Core\Route ordered my method to allow faster search
     */
    private $routes = [
        Route::GET => [],
        Route::POST => [],
        Route::PUT => [],
        Route::DELETE => [],
        Route::HEAD => [],
        Route::OPTIONS => [],
        Route::PATCH => []
    ];

    /**
     * Equals to call register, accept a value, not only a reference.
     *
     * @see Router::register
     */
    public function add(Route $route)
    {
        return $this->register($route);
    }

    /**
     * Register a route within this router.
     *
     * @param Route $route the route to be registered
     */
    public function register(Route &$route)
    {
        //put a reference to the object inside allowed methods for a faster search
        foreach ($route->getMethods() as $method) {
            if ((strcmp($method, Route::GET) == 0) ||
                (strcmp($method, Route::POST) == 0) ||
                (strcmp($method, Route::PUT) == 0) ||
                (strcmp($method, Route::DELETE) == 0) ||
                (strcmp($method, Route::HEAD) == 0) ||
                (strcmp($method, Route::OPTIONS) == 0) ||
                (strcmp($method, Route::PATCH) == 0))
            {
                $this->routes[$method][] = &$route;
            }
        }
    }

    /**
     * Check if the given url and method match a route (even a non-200 OK route is allowed).
     *
     * @param string $method the HTTP used verb
     * @param string $url    the url decoded string of the called url
     * @param array  $params will contains matched url slices
     * @return null|Route the matched route or null
     */
    protected function search($method, $url, array &$params)
    {
        foreach ($this->routes[$method] as $currentRoute) {

            //if the current URL matches the current URI
            if (self::matches($currentRoute->getURI(), $url, $params)) {

                //this will hold the parameters passed on the URL
                return $currentRoute;
            }
        }

        return null;
    }

    /**
     * Check if the given URL matches a rule in a HTTP method different
     * from the one used to perform the request.
     *
     * @param  string $requestURL    the HTTP used address
     * @param  string $requestMethod the HTTP method used to query the resource
     * @return bool true if the given url is matched in some other methods
     */
    protected function checkNotAllowed($requestURL, $requestMethod) : bool
    {
        foreach (array_keys($this->routes) as $method) {
            $matchedRoute = (strcmp($method, $requestMethod) != 0) ?
                $this->search($method, $requestURL, $params) : false;

            if (!is_null($matchedRoute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Load error-handling routes to be used when
     * a bad request is sent to the server.
     *
     * @param string $method the HTTP verb of the request
     * @return array the list of error-handling routes
     */
    protected function loadErrorHandlers($method) : array
    {
        $errorHandlers = [];

        foreach ($this->routes[$method] as &$currentRoute) {
            if (($currentRoute->getStatus() == Route::NOT_ALLOWED) && (strcmp($currentRoute->getURI(), "") == 0)) {
                $errorHandlers[Route::NOT_ALLOWED] = $currentRoute;
            }

            if (($currentRoute->getStatus() == Route::NOT_FOUND) && (strcmp($currentRoute->getURI(), "") == 0)) {
                $errorHandlers[Route::NOT_FOUND] = $currentRoute;
            }
        }

        //if any error handler was not loaded use the default one
        if (!in_array(Route::NOT_FOUND, array_keys($errorHandlers))) {
            //load the default 404 NOT FOUND handler
            $errorHandlers[Route::NOT_FOUND] = new Route([
                "verbs" => [
                    Route::GET,
                    Route::DELETE,
                    Route::PATCH,
                    Route::OPTIONS,
                    Route::HEAD,
                    Route::GET,
                    Route::PUT,
                    Route::POST
                ],
                "uri" => "",
                "status" => Route::OK,
                "controller" => ErrorHandling::class,
                "action" => "notFound",
            ]);
        }

        //if any error handler was not loaded use the default one
        if (!in_array(Route::NOT_ALLOWED, array_keys($errorHandlers))) {
            //load the default 405 NOT ALLOWED handler
            $errorHandlers[Route::NOT_ALLOWED] = new Route([
                "verbs" => [
                    Route::GET,
                    Route::DELETE,
                    Route::PATCH,
                    Route::OPTIONS,
                    Route::HEAD,
                    Route::GET,
                    Route::PUT,
                    Route::POST
                ],
                "uri" => "",
                "status" => Route::OK,
                "controller" => ErrorHandling::class,
                "action" => "notAllowed",
            ]);
        }
    }

    /**
     * Run the router and serve the current request.
     *
     * This function is __CALLED INTERNALLY__ and, therefore
     * it __MUST NOT__ be called by the user!
     *
     * @param  RequestInterface  $requestToFulfill the request to be served/fulfilled
     * @param  ResponseInterface $response         the response to be filled
     * @param  array             $controllerArgs   an associative array with more parameters to be passed to the called controller
     */
    public function run(RequestInterface &$requestToFulfill, ResponseInterface &$response, array $controllerArgs = [])
    {
        //clone the request
        $request = clone $requestToFulfill;

        $params = [];

        $matchedRoute = $this->search($request->getMethod(), urldecode($request->getUri()->getPath()), $params);

        if (!is_null($matchedRoute)) {
            //this will hold the parameters passed on the URL
            $deductedParams = new GenericCollection($params);

            $matchedRoute($request, $response, $deductedParams, $controllerArgs);

            return;
        }

        throw new \Exception("test2");

        $routeNotFound = null;
        $routeNotAllowed = null;

        $errorHandlers = $this->loadErrorHandlers($request->getMethod());
        $routeNotAllowed = $errorHandlers[Route::NOT_ALLOWED];
        $routeNotFound = $errorHandlers[Route::NOT_FOUND];

        $emptyDeductedParam = new GenericCollection();

        //check if this is a 404 or a 405
        if ($this->checkNotAllowed(urldecode($request->getUri()->getPath()), $request->getMethod())) {
            //this is a 405 error and the notAllowed route must be followed
            $routeNotAllowed($request, $response, $emptyDeductedParam, $controllerArgs);

            return;
        }

        //this is a 404 error and the notFound route must be followed
        $routeNotFound($request, $response, $emptyDeductedParam, $controllerArgs);
    }

    /**
     * Check if a piece of URL matches a parameter of the given type.
     * List of types:
     *  - 0 unsigned integer
     *  - 1 signed integer
     *  - 2 float
     *  - 3 string
     *  - 4 email
     *
     * @param $urlSplit  string the piece of URL to be checked
     * @param $type      int    the type of accepted parameter
     *
     * @return bool true on success, false otherwise
     */
    private static function paramCheck($urlSplit, $type) : bool
    {
        switch ($type)
        {
            case 0:
                return SimpleLexer::isUnsignedInteger($urlSplit);

            case 1:
                return SimpleLexer::isSignedInteger($urlSplit);

            case 2:
                return SimpleLexer::isFloat($urlSplit);

            case 3:
                return SimpleLexer::isString($urlSplit);

            case 4:
                return SimpleLexer::isEmail($urlSplit);

            default:
                return false;
        }
    }

    /**
     * Check weather a piece of an URL matches the corresponding piece of URI
     *
     * @param  string $uriSplit the slice of URI to be checked
     * @param  string $urlSplit the slice of URL to be checked
     * @param  array $params   used to register the correspondence (if any)
     * @return bool  true if the URL slice matches the URI slice, false otherwise
     */
    private static function matchCheck($uriSplit, $urlSplit, array &$params) : bool
    {
        $result = false;

        if ((strlen($uriSplit) >= 7) && ($uriSplit[0] == '{') && ($uriSplit[strlen($uriSplit) - 1] == '}')) {
            $uriSplitRev = substr($uriSplit, 1, strlen($uriSplit) - 2);
            $uriSplitExploded = explode(':', $uriSplitRev);
            $uriParamType = strtolower($uriSplitExploded[1]);

            $type = null;

            if (strcmp($uriParamType, 'uint') == 0) {
                $type = 0;
            } else if (strcmp($uriParamType, 'int') == 0) {
                $type = 1;
            } else if ((strcmp($uriParamType, 'str') == 0) || (strcmp($uriParamType, 'string') == 0)) {
                $type = 3;
            } else if (strcmp($uriParamType, 'float') == 0) {
                $type = 2;
            } else if ((strcmp($uriParamType, 'email') == 0) || (strcmp($uriParamType, 'mail') == 0)) {
                $type = 4;
            }

            //check the url piece against one of the given model
            if (self::paramCheck($urlSplit, $type)) {
                //matched url piece with the correct type: "1" checked against a string has to become 1
                $urlSplitCType = $urlSplit;
                $urlSplitCType = (($type == 0) || ($type == 1)) ? intval($urlSplit) : $urlSplitCType;
                $urlSplitCType = ($type == 2) ? floatval($urlSplit) : $urlSplitCType;

                $result = true;
                $params[$uriSplitExploded[0]] = $urlSplitCType;
            }
        } else if (strcmp($uriSplit, $urlSplit) == 0) {
            $result = true;
        }

        return  $result;
    }

    /**
     * Check if the given URL matches the route URI.
     * $matchedExpr is given as an associative array: name => value
     *
     * @param string $uri         the URI to be matched against the given URL
     * @param string $url         the URL to be matched
     * @param mixed  $matchedExpr an *empty* array
     * @return bool true if the URL matches the URI, false otherwise
     */
    public static function matches($uri, $url, &$matchedExpr) : bool
    {
        if ((!is_string($url)) || (strlen($url) <= 0)) {
            throw new \InvalidArgumentException("The URL must be given as a non-empty string");
        }

        if ((!is_string($uri)) || (strlen($uri) <= 0)) {
            throw new \InvalidArgumentException("The URI must be given as a non-empty string");
        }

        $matchedExpr = [];
        $result = true;

        $urlSlices = explode('/', $url);
        $uriSlices = explode('/', $uri);

        $slicesCount = count($uriSlices);
        if ($slicesCount != count($urlSlices)) {
            return false;
        }

        for ($i = 0; ($i < $slicesCount) && ($result); $i++) {
            //try matching the current URL slice with the current URI slice
            $result = self::matchCheck($uriSlices[$i], $urlSlices[$i], $matchedExpr);
        }

        return $result;
    }
}