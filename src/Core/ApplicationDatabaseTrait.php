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

use Gishiki\Algorithms\Collections\CollectionInterface;
use Gishiki\Database\DatabaseManager;
use Gishiki\Database\ORM\DatabaseStructure;

/**
 * This is a working implementation of database connections handler for the Application class.
 *
 * @author Benato Denis <benato.denis96@gmail.com>
 */
trait ApplicationDatabaseTrait
{
    /**
     * @var DatabaseManager the group of database connections
     */
    protected $databaseConnections;

    /**
     * @var DatabaseStructure[] the database structure list
     */
    protected $databaseStructures = [];

    /**
     * @var bool auto update the database if true
     */
    protected $autoUpdate = false;

    /**
     * Set a new value to the auto-update database operation.
     *
     * When the auto-update is on (has been __manually__ set to true)
     * the database is updated before running the application.
     *
     * Running an application is done by calling the run() function,
     * so this function __must__ be called before the run() call.
     *
     * @param bool $value the new value
     */
    public function setDatabaseAutoUpdate($value)
    {
        $this->autoUpdate = ($value === true);
    }

    /**
     * Check whether the auto-update is set on the current application.
     *
     * @return bool true on auto-update enabled
     */
    public function checkDatabaseAutoUpdate() : bool
    {
        return $this->autoUpdate;
    }

    /**
     * Get the collection of opened database handlers within the current application.
     *
     * @return DatabaseManager the collection of database handlers
     */
    public function &getDatabaseManager() : DatabaseManager
    {
        return $this->databaseConnections;
    }

    /**
     * Prepare connections to databases.
     *
     * @param array $connections the array of connections
     */
    public function connectDatabase(array $connections)
    {
        //setup the database manager if necessary
        if (!($this->databaseConnections instanceof DatabaseManager)) {
            $this->databaseConnections = new DatabaseManager();
        }

        //connect every db connection
        foreach ($connections as $connection) {
            $this->databaseConnections->connect($connection['name'], $connection['query']);
        }
    }

    /**
     * Create, fill and register a database structure within the current application.
     *
     * @param CollectionInterface $structure the database description
     */
    protected function registerDatabaseStructure(CollectionInterface &$structure)
    {
        //parse the collection
        $currentStructure = new DatabaseStructure();
        $currentStructure->parse($structure);

        //the parsed database structure is stored in the current application
        $this->databaseStructures[] = $currentStructure;
    }

    /**
     * get the full list of database structures used within the current application.
     *
     * @return DatabaseStructure[] the collection of database structures
     */
    public function getDatabaseStructure() : array
    {
        return $this->databaseStructures;
    }
}
