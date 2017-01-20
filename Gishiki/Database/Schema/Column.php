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

namespace Gishiki\Database\Schema;

/**
 * Represent a column inside a table of a relational database.
 *
 * @author Benato Denis <benato.denis96@gmail.com>
 */
final class Column
{
    /**
     * @var string the name of the column
     */
    protected $name;

    /**
     * @var int the type of the column, expressed as one of the ColumnType constants
     */
    protected $type;

    /**
     * @var bool TRUE if the column uses auto increment
     */
    protected $ai;

    /**
     * @var bool TRUE if the column cannot hold null
     */
    protected $notNull;

    /**
     * @var Table a reference to the table containing the column
     */
    protected $table;

    /**
     * Initialize a column with the given name.
     * This function internally calls setName(), and you should catch
     * exceptions thrown by that function.
     *
     * @param  Table a reference to the table containing the this column
     * @param string $name the name of the column
     */
    public function __construct(Table &$table, $name)
    {
        $this->name = '';
        $this->dataType = 0;
        $this->ai = false;

        $this->table = &$table;

        $this->setName($name);
    }

    /**
     * Get a reference to the table containing this column.
     *
     * @return Table a reference to the table
     */
    public function &getTable()
    {
        return $this->table;
    }

    /**
     * Change the primary key flag on the column.
     *
     * @param bool $enable TRUE is used to flag a primary key column as such
     *
     * @throws \InvalidArgumentException the new status is invalid
     */
    public function &setPrimaryKey($enable)
    {
        if (!is_bool($enable)) {
            throw new \InvalidArgumentException('The auto-increment flag of a column must be given as a boolean value');
        }

        $this->ai = $enable;

        return $this;
    }

    /**
     * Change the auto increment flag on the column.
     *
     * @param bool $enable TRUE if the column is a primary key
     */
    public function getPrimaryKey()
    {
        return $this->ai;
    }

    /**
     * Change the auto increment flag on the column.
     *
     * @param bool $enable TRUE is used to enables auto increment
     *
     * @throws \InvalidArgumentException the new status is invalid
     */
    public function &setAutoIncrement($enable)
    {
        if (!is_bool($enable)) {
            throw new \InvalidArgumentException('The auto-increment flag of a column must be given as a boolean value');
        }

        $this->ai = $enable;

        return $this;
    }

    /**
     * Change the auto increment flag on the column.
     *
     * @param bool $enable TRUE is used to enables auto increment
     */
    public function getAutoIncrement()
    {
        return $this->ai;
    }

    /**
     * Change the name of the current column.
     *
     * @param string $name the name of the column
     *
     * @throws \InvalidArgumentException the column name is invalid
     */
    public function &setName($name)
    {
        //avoid bad names
        if ((!is_string($name)) || (strlen($name) < 0)) {
            throw new \InvalidArgumentException('The name of a column must be expressed as a non-empty string');
        }

        $this->name = $name;

        return $this;
    }

    /**
     * Retrieve the name of the column.
     *
     * @return string the column name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Change the type of the current table passing as argument one
     * of the ColumnType contants.
     *
     * @param string $type the type of the column
     *
     * @throws \InvalidArgumentException the column name is invalid
     */
    public function &settype($type)
    {
        //avoid bad names
        if ((!is_integer($type)) || (
                ($type != ColumnType::INTEGER) && ($type != ColumnType::TEXT)
                && ($type != ColumnType::REAL) && ($type != ColumnType::DATETIME))) {
            throw new \InvalidArgumentException('The type of the column is invalid.');
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Retrieve the type of the column.
     *
     * @return int the column name
     */
    public function getType()
    {
        return $this->type;
    }
}
