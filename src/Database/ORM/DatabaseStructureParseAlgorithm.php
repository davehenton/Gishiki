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
use Gishiki\Algorithms\Collections\GenericCollection;
use Gishiki\Database\Schema\Column;
use Gishiki\Database\Schema\ColumnRelation;
use Gishiki\Database\Schema\ColumnType;
use Gishiki\Database\Schema\Table;

/**
 * An implementation ready to be used for building a database structure parser.
 *
 * @author Benato Denis <benato.denis96@gmail.com>
 */
abstract class DatabaseStructureParseAlgorithm
{
    const TYPE_MAP = [
        'text' => ColumnType::TEXT,
        'string' => ColumnType::TEXT,
        'smallint' => ColumnType::SMALLINT,
        'int' => ColumnType::INTEGER,
        'integer' => ColumnType::INTEGER,
        'bigint' => ColumnType::BIGINT,
        'money' => ColumnType::MONEY,
        'numeric' => ColumnType::NUMERIC,
        'float' => ColumnType::FLOAT,
        'double' => ColumnType::DOUBLE,
        'datetime' => ColumnType::DATETIME,
    ];

    /**
     * Build the Database structure from a collection with a fixed structure.
     *
     * Also responsible for appending each table into the stack into the exact
     * order they need to be pushed into the database.
     *
     * @param  CollectionInterface   $structure      the json description of the database
     * @param  array                 $tableStack     the collection of tables to be updated
     * @param  string                $connectionName will be filled with the connection name
     * @throws StructureException the error in the description
     */
    public function parseDatabase(CollectionInterface &$structure, array &$tableStack, &$connectionName)
    {
        if (!$structure->has('connection')) {
            throw new StructureException('A database description must contains the connection field', 0);
        }

        if ((!is_string($structure->get('connection'))) || (strlen($structure->get('connection')) <= 0)) {
            throw new StructureException('The connection name must be given as a non-empty string', 3);
        }

        if (!$structure->has('tables')) {
            throw new StructureException("A database description must contains a tables field", 1);
        }

        //update the connection name
        $connectionName = $structure->get('connection');

        foreach ($structure->get('tables') as &$table) {
            if (!is_array($table)) {
                throw new StructureException("Wrong structure: the 'tables' field must contains arrays", 2);
            }

            //update the collection tables
            $tableStack[] = self::parseTable($table, $tableStack);
        }
    }

    /**
     * Parse a table given its definition.
     *
     * @param  array     $tableDescription the definition of the table
     * @param  Column[]  $tableStack       the collection of tables to be updated
     * @return Table the table built from its description
     * @throws StructureException the error in the description
     */
    protected static function parseTable(array &$tableDescription, array &$tableStack) : Table
    {
        $table = new GenericCollection($tableDescription);

        if ((!$table->has('name')) || (!is_string($table->get('name'))) || (strlen($table->get('name')) <= 0)) {
            throw new StructureException('Each table must have a name given as a non-empty string', 4);
        }

        $currentTable = new Table($table->get('name'));

        foreach ($table->get('fields') as &$field) {
            if (!is_array($field)) {
                throw new StructureException("Wrong structure: the 'fields' field must contains arrays", 2);
            }

            //parse the current field
            $currentField = static::parseField($field, $tableStack);

            //add the field to the table
            $currentTable->addColumn($currentField);
        }

        //return the parsed table
        return $currentTable;
    }

    /**
     * Parse a field given its definition.
     *
     * @param  array    $fieldDescription the definition of the field/column
     * @param  Column[] $tableStack   the collection of tables to be updated
     * @return Column the field built from its description
     * @throws StructureException the error in the description
     */
    protected static function parseField(array &$fieldDescription, array &$tableStack) : Column
    {
        $field = new GenericCollection($fieldDescription);

        if (!$field->has('name')) {
            throw new StructureException('Each column must have a name', 5);
        }

        if (!$field->has('type')) {
            throw new StructureException('Each column must have a type', 6);
        }

        if (!array_key_exists($field->get('type'), static::TYPE_MAP)) {
            throw new StructureException('Invalid data type for column '.$field->get('name'), 7);
        }

        //build the field as it was defined
        $currentField = new Column($field->get('name'), static::TYPE_MAP[$field->get('type')]);
        $currentField->setPrimaryKey(($field->get('primary_key') === true));
        $currentField->setNotNull(($field->get('not_null') === true));
        $currentField->setAutoIncrement(($field->get('auto_increment') === true));

        if ($field->has('relation')) {
            $relation = $field->get('relation');

            if (!is_array($relation)) {
                throw new StructureException('The given relation description is not valid', 9);
            }

            //parse the current relation
            $currentRelation = static::parseRelation($relation, $tableStack);

            //register the parsed relation
            $currentField->setRelation($currentRelation);
        }

        return $currentField;
    }

    /**
     * Parse a relation given its definition.
     *
     * @param  array     $relationDescription the definition of the relation
     * @param  Column[] $tableStack          the collection of tables to be used when searching for related table and column
     * @return ColumnRelation the relation built from its description
     * @throws StructureException the error in the description
     */
    protected static function parseRelation(array &$relationDescription, array &$tableStack) : ColumnRelation
    {
        $relation = new GenericCollection($relationDescription);

        if ((!$relation->has('table')) || (!is_string($relation->get('table'))) || (strlen($relation->get('table')) < 0)) {
            throw new StructureException('To define a relation the name of the table containing the related field must be given', 10);
        }

        if ((!$relation->has('field')) || (!is_string($relation->get('field'))) || (strlen($relation->get('field')) < 0)) {
            throw new StructureException('To define a relation the name of the related field must be given', 11);
        }

        $currentRelation = null;

        //speed up the search algorithm
        $relatedTableName = $relation->get('table');
        $relatedTableField = $relation->get('field');
        $found = false;

        foreach ($tableStack as &$currentTable) {
            if (strcmp($currentTable->getName(), $relatedTableName) != 0) {
                continue;
            }


            foreach ($currentTable->getColumns() as $currentColumn) {
                if (strcmp($currentColumn->getName(), $relatedTableField) == 0) {
                    $currentRelation = new ColumnRelation($currentTable, $currentColumn);
                    $found = true;
                }
            }

            if ($found) {
                break;
            }
        }

        if (!($currentRelation instanceof ColumnRelation)) {
            throw new StructureException('The related table ('.$relation->get('table').') and column ('.$relation->get('field').') cannot be found. The related table must be defined before an external relation.', 12);
        }

        return $currentRelation;
    }
}
