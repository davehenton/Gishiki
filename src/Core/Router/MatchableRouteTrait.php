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

use Gishiki\Algorithms\Strings\SimpleLexer;

/**
 * Working implementation of RouteInterface ready to be implemented by a route.
 *
 * Written to ease the process of extending the Router component
 * with routes written by third-parties and reducing my own codebase.
 *
 * @see RouteInterface Documentation
 *
 * @author Benato Denis <benato.denis96@gmail.com>
 */
trait MatchableRouteTrait
{
    /**
     * uri => string (begins with / example: "/do_something")
     * status => integer (the HTTP return status code, used for error handling)
     * verbs => string[] (the array of string of allowed/matchable HTTP methods)
     *
     * @var array the route definition
     */
    protected $route = [];

    public function getURI() : string
    {
        return $this->route["uri"];
    }

    public function getStatus() : int
    {
        return $this->route["status"];
    }

    public function getMethods() : array
    {
        return $this->route["verbs"];
    }

    public function matches($method, $url, &$matchedExpr) : bool
    {
        return ((in_array($method, $this->getMethods())) && (static::matchURI($this->getURI(), $url, $matchedExpr)));
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
     * @throws RouterException  the given type is invalid
     * @return bool true on success, false otherwise
     */
    protected static function paramCheck($urlSplit, $type) : bool
    {
        if ((!is_int($type)) || ($type < 0) || ($type > 4)) {
            throw new RouterException("Invalid parameter type", 100);
        }

        $result = false;

        switch ($type) {
            case 0:
                $result = SimpleLexer::isUnsignedInteger($urlSplit);
                break;

            case 1:
                $result = SimpleLexer::isSignedInteger($urlSplit);
                break;

            case 2:
                $result = SimpleLexer::isFloat($urlSplit);
                break;

            case 3:
                $result = SimpleLexer::isString($urlSplit);
                break;

            case 4:
                $result = SimpleLexer::isEmail($urlSplit);
                break;
        }

        return $result;
    }

    /**
     * Check weather a piece of an URL matches the corresponding piece of URI
     *
     * @param  string $uriSplit the slice of URI to be checked
     * @param  string $urlSplit the slice of URL to be checked
     * @param  mixed  $params   used to register the correspondence (if any)
     * @return bool  true if the URL slice matches the URI slice, false otherwise
     */
    protected static function matchCheck($uriSplit, $urlSplit, &$params) : bool
    {
        $result = false;

        if ((strlen($uriSplit) >= 7) && ($uriSplit[0] == '{') && ($uriSplit[strlen($uriSplit) - 1] == '}')) {
            $uriSplitRev = substr($uriSplit, 1, strlen($uriSplit) - 2);
            $uriSplitExploded = explode(':', $uriSplitRev);
            $uriParamType = strtolower($uriSplitExploded[1]);

            $type = null;

            if (strcmp($uriParamType, 'uint') == 0) {
                $type = 0;
            } elseif (strcmp($uriParamType, 'int') == 0) {
                $type = 1;
            } elseif ((strcmp($uriParamType, 'str') == 0) || (strcmp($uriParamType, 'string') == 0)) {
                $type = 3;
            } elseif (strcmp($uriParamType, 'float') == 0) {
                $type = 2;
            } elseif ((strcmp($uriParamType, 'email') == 0) || (strcmp($uriParamType, 'mail') == 0)) {
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
        } elseif (strcmp($uriSplit, $urlSplit) == 0) {
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
    public static function matchURI($uri, $url, &$matchedExpr) : bool
    {
        if (!is_string($url)) {
            throw new \InvalidArgumentException("The URL must be given as a valid string");
        }

        if (!is_string($uri)) {
            throw new \InvalidArgumentException("The URI must be given as a valid string");
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
