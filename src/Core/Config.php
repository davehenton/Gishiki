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

namespace Gishiki\Core;

use Gishiki\Algorithms\Strings\Manipulation;
use Gishiki\Algorithms\Collections\SerializableCollection;

/**
 * Used to parse an application file and to generate a valid configuration.
 *
 * @author Benato Denis <benato.denis96@gmail.com>
 */
final class Config
{

    /**
     * @var array the application configuration
     */
    protected $configuration;

    /**
     * Generate configuration from a json file and environment values.
     *
     * If available the configuration will be load from cache.
     *
     * @param  string $filename the path and name of the file
     * @throws Exception the error preventing the file to be read
     */
    public function __construct($filename)
    {
        if (!file_exists($filename)) {
            throw new Exception("The given configuration file cannot be read", 100);
        }

        //get the json encoded application settings
        $configContent = file_get_contents($filename);

        //parse the settings file
        $incompleteConfig = SerializableCollection::deserialize($configContent)->all();

        //complete the request
        $this->configuration = $this->getValueFromEnvironment($incompleteConfig);
    }

    /**
     * Get the application configuration.
     *
     * @return SerializableCollection the configuration
     */
    public function getConfiguration() : SerializableCollection
    {
        return new SerializableCollection($this->configuration);
    }

    /**
     * Complete the configuration resolving every value placeholder.
     *
     * Read more on documentation.
     *
     * @param  array $collection the configuration to be finished
     * @return array the completed configuration
     */
    protected function getValueFromEnvironment(array &$collection) : array
    {
        foreach ($collection as &$value) {
            //check for substitution
            if ((is_string($value)) && ((strpos($value, '{{@') === 0) && (strpos($value, '}}') !== false))) {
                if (($toReplace = Manipulation::getBetween($value, '{{@', '}}')) != '') {
                    $value = getenv($toReplace);
                    if ($value !== false) {
                        continue;
                    } elseif (defined($toReplace)) {
                        $value = constant($toReplace);
                    }
                }
            } elseif (is_array($value)) {
                $value = $this->getValueFromEnvironment($value);
            }
        }

        return $collection;
    }
}
