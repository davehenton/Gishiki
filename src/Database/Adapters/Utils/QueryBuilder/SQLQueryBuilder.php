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

namespace Gishiki\Database\Adapters\Utils\QueryBuilder;

use Gishiki\Database\Adapters\Utils\SQLGenerator\SQLWrapper;
use Gishiki\Database\Runtime\SelectionCriteria;
use Gishiki\Database\Runtime\ResultModifier;
use Gishiki\Database\Schema\Table;

/**
 * Uses SQL generators to generate valid SQL queries for a specific type of RDBMS.
 *
 * @author Benato Denis <benato.denis96@gmail.com>
 */
trait SQLQueryBuilder
{
    public function createTableQuery(Table $table)
    {
        //build the sql query
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->createTable($table->getName())->definedAs($table->getColumns());

        return $queryBuilder;
    }

    public function insertQuery($collection, array $adaptedData)
    {
        //build the sql query
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->insertInto($collection)->values($adaptedData);

        return $queryBuilder;
    }

    public function updateQuery($collection, array $adaptedData, SelectionCriteria $where)
    {
        //build the sql query
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->update($collection)->set($adaptedData)->where($where);

        return $queryBuilder;
    }

    public function deleteQuery($collection, SelectionCriteria $where)
    {
        //build the sql query
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->deleteFrom($collection)->where($where);

        return $queryBuilder;
    }

    public function deleteAllQuery($collection)
    {
        //build the sql query
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->deleteFrom($collection);

        return $queryBuilder;
    }

    public function readQuery($collection, SelectionCriteria $where, ResultModifier $mod)
    {
        //build the sql query
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->selectAllFrom($collection)->where($where)->limitOffsetOrderBy($mod);

        return $queryBuilder;
    }

    public function selectiveReadQuery($collection, $fields, SelectionCriteria $where, ResultModifier $mod)
    {
        //build the sql query
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->selectFrom($collection, $fields)->where($where)->limitOffsetOrderBy($mod);

        return $queryBuilder;
    }
}
