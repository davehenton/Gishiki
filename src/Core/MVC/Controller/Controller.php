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

namespace Gishiki\Core\MVC\Controller;

use Gishiki\Database\DatabaseManager;
use Gishiki\Logging\LoggerManager;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Gishiki\Algorithms\Collections\GenericCollection;

/**
 * The Gishiki base controller:
 * every controller inherits from this class
 *
 * @author Benato Denis <benato.denis96@gmail.com>
 */
abstract class Controller
{
    /**
     * This is a clone of the request the client have send to this server.
     *
     * @var RequestInterface the request the controller must fulfill
     */
    protected $request;

    /**
     * This is the response that will be sent back to the client from this server.
     *
     * @var ResponseInterface the response the controller must generate
     */
    protected $response;

    /**
     * This is the collection of arguments passed to the URI.
     *
     * @var GenericCollection the collection of arguments passed to the URI
     */
    protected $arguments;

    /**
     * @var array an array containing specified plugin collection as instantiated objects
     */
    protected $plugins;

    /**
     * @var DatabaseManager a container for alive database connections
     */
    protected $connections;

    /**
     * @var LoggerManager a container for alive loggers instances
     */
    protected $loggers;

    /**
     * Create a new controller that will fulfill the given request filling the given response.
     *
     * __Warning:__ you should *never* attempt to use another construction in your controllers,
     * unless it calls parent::__construct(), and it doesn't accept arguments
     *
     * @param  RequestInterface  $controllerRequest   the request arrived from the client
     * @param  ResponseInterface $controllerResponse  the response to be given to the client
     * @param  GenericCollection $controllerArguments the collection of matched URI params
     * @param  array             $plugins             the array containing passed plugins
     * @throws ControllerException the error preventing the controller creation
     */
    public function __construct(RequestInterface &$controllerRequest, ResponseInterface &$controllerResponse, GenericCollection &$controllerArguments, array &$plugins)
    {
        $this->connections = new DatabaseManager();

        //save the request
        $this->request = $controllerRequest;

        //save the response
        $this->response = $controllerResponse;

        //save the arguments collection
        $this->arguments = $controllerArguments;

        //load middleware collection
        $this->plugins = [];
        foreach ($plugins as $pluginKey => &$pluginValue) {
            try {
                $reflectedMiddleware = new \ReflectionClass($pluginValue);
                $this->plugins[$pluginKey] = $reflectedMiddleware->newInstanceArgs([&$this->request, &$this->response]);
            } catch (\ReflectionException $ex) {
                throw new ControllerException("Invalid plugin class", 1);
            }
        }
    }

    /**
     * Load additional information inside the current controller.
     *
     * *Note:* this function is designed to be called *only* by
     * the Route class!
     *
     * @param array $args additional parameters
     */
    public function loadDependencies(array $args = [])
    {
        if ((array_key_exists('connections', $args)) && ($args['connections'] instanceof DatabaseManager)) {
            $this->connections = $args['connections'];
        }

        if ((array_key_exists('loggers', $args)) && ($args['loggers'] instanceof LoggerManager)) {
            $this->loggers = $args['loggers'];
        }
    }

    /**
     * Get the HTTP response.
     *
     * @return ResponseInterface the HTTP response
     */
    public function &getResponse() : ResponseInterface
    {
        return $this->response;
    }

    /**
     * Get the HTTP request.
     *
     * @return RequestInterface the HTTP request
     */
    public function &getRequest() : RequestInterface
    {
        return $this->request;
    }

    /**
     * Execute a function of any plugin that has been bind to this controller:
     *
     * <code>
     * class MyPlugin extends Plugin {
     *    public function doSomethingSpecial($arg1, $arg2) {
     *
     *    }
     * }
     *
     * //inside the controller:
     * $this->doSomethingSpecial($name, $surname);
     * </code>
     *
     * @param string $name      the name of the called function
     * @param array  $arguments the list of passed arguments as an array
     * @return mixed the value returned from the function
     * @throws ControllerException the function doesn't exists in any plugin
     */
    public function __call($name, array $arguments)
    {
        $returnValue = null;
        $executed = false;

        foreach ($this->plugins as &$plugin) {
            try {
                $reflectedFunction = new \ReflectionMethod($plugin, $name);
                $reflectedFunction->setAccessible(true);
                $returnValue = $reflectedFunction->invokeArgs($plugin, $arguments);

                //update response
                $this->response = $plugin->getResponse();

                $executed = true;
            } catch (\ReflectionException $ex) {
                //there is nothing to be catched. Invoked method is not in this plugin
            }
        }

        if (!$executed) {
            throw new ControllerException('None of loaded plugins implements '.$name.' function', 0);
        }

        return $returnValue;
    }
}
