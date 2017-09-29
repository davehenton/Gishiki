<?php
/****************************************************************************
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
 *******************************************************************************/

namespace Gishiki\Logging;

use Monolog\Logger;
use Gishiki\Algorithms\Collections\GenericCollection;

/**
 * An helper class for managing monolog logger instances.
 *
 * Benato Denis <benato.denis96@gmail.com>
 */
final class LoggerManager
{
    /**
     * @var array the list of logger instances as an associative array
     */
    protected $connections = [];

    /**
     * Create a new logger instance.
     *
     * @param string $name               the connection name
     * @param array  $details            an array containing sub-arrays of connection details
     * @throws \InvalidArgumentException invalid name or connection details
     * @return \Monolog\Logger           the new logger instance
     */
    public function connect($name, array $details)
    {
        //check for the logger name
        if ((!is_string($name)) || (strlen($name) <= 0)) {
            throw new \InvalidArgumentException('The logger name must be given as a valid non-empty string');
        }

        //create the new logger instance
        $this->connections[sha1($name)] = new Logger($name);

        foreach ($details as $handler) {
            $handlerCollection = new GenericCollection($handler);

            if ((!$handlerCollection->has('class')) || (!$handlerCollection->has('connection'))) {
                throw new \InvalidArgumentException('The logger configuration is not fully-qualified');
            }

            try {
                $adapterClassName = (strpos($handlerCollection->get('class'), "\\") === false) ?
                    'Monolog\\Handler\\'.$handlerCollection->get('class') :
                    $handlerCollection->get('class');

                //reflect the adapter
                $reflectedAdapter = new \ReflectionClass($adapterClassName);

                //bind the handler to the current logger
                $this->connections[sha1($name)]->pushHandler(
                    $reflectedAdapter->newInstanceArgs($handlerCollection->get('connection'))
                );
            } catch (\ReflectionException $ex) {
                throw new \InvalidArgumentException('The given connection requires an unknown class');
            }
        }

        //return the newly created logger
        return $this->connections[sha1($name)];
    }

    /**
     * Retrieve the PSR-3 logger instance with the given name
     *
     * @param  string|null $name         the name of the logger instance or NULL for the default one
     * @throws \InvalidArgumentException invalid name or inexistent logger instance
     * @return \Monolog\Logger           the logger instance
     */
    public function retrieve($name = null)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('The logger instance to be retrieved must be given as a valid, non-empty string or NULL');
        }

        //check for bad logger name
        if ((!is_string($name)) || (strlen($name) <= 0)) {
            throw new \InvalidArgumentException('The logger name must be given as a valid non-empty string');
        }

        if (!array_key_exists(sha1($name), $this->connections)) {
            throw new \InvalidArgumentException('The given logger name is not valid');
        }

        //return the requested connection
        return $this->connections[sha1($name)];
    }
}
