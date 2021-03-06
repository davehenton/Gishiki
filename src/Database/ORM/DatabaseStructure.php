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

namespace Gishiki\Database\ORM;

use Gishiki\Algorithms\Collections\CollectionInterface;

/**
 * Build the database logic structure from a collection in a fixed format.
 *
 * @author Benato Denis <benato.denis96@gmail.com>
 */
final class DatabaseStructure extends DatabaseStructureParseAlgorithm
{
    /**
     * @var string The name of the corresponding connection
     */
    protected $connectionName;

    /**
     * @var array the collection of tables in creation order
     */
    protected $stackTables;

    /**
     * Initialize the instance with an empty database structure.
     */
    public function __construct()
    {
        $this->stackTables = [];
    }

    /**
     * Build the Database structure from a collection with a fixed structure.
     *
     * Also responsible for building the internal tables stack.
     * Tables are organized as a stack because the order matters!
     *
     * @param  CollectionInterface $structure the json description of the database
     * @throws StructureException the error in the description
     */
    public function parse(CollectionInterface &$structure)
    {
        static::parseDatabase($structure, $this->stackTables, $this->connectionName);
    }

    /**
     * Get the name of the database connection that will be used
     * to create tables and overall structure.
     *
     * @return string the name of the database connection
     */
    public function getConnectionName() : string
    {
        return $this->connectionName;
    }

    /**
     * Get the collection of tables in the exact order they need to be
     * pushed onto the database.
     *
     * @return array the collection of tables
     */
    public function &getTables() : array
    {
        //return the database structure
        return $this->stackTables;
    }
}
