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

namespace Gishiki\tests\Database\Adapters\Utils;

use PHPUnit\Framework\TestCase;
use Gishiki\Database\Adapters\Utils\SQLGenerator\MySQLWrapper;
use Gishiki\Database\Schema\Table;
use Gishiki\Database\Schema\Column;
use Gishiki\Database\Schema\ColumnType;
use Gishiki\Database\Schema\ColumnRelation;

/**
 * The tester for the MySQLQueryBuilder class.
 *
 * @author Benato Denis <benato.denis96@gmail.com>
 */
class MySQLQueryBuilderTest extends TestCase
{
    public function testCreateTableWithNoForeignKeyAndMultipleTypes()
    {
        $table = new Table(__FUNCTION__);

        $idColumn = new Column('id', ColumnType::INTEGER);
        $idColumn->setNotNull(true);
        $idColumn->setPrimaryKey(true);
        $table->addColumn($idColumn);
        $nameColumn = new Column('name', ColumnType::TEXT);
        $nameColumn->setNotNull(true);
        $table->addColumn($nameColumn);
        $creditColumn = new Column('credit', ColumnType::NUMERIC);
        $creditColumn->setNotNull(true);
        $table->addColumn($creditColumn);
        $registeredColumn = new Column('paidYears', ColumnType::SMALLINT);
        $registeredColumn->setNotNull(true);
        $table->addColumn($registeredColumn);
        $registeredColumn = new Column('idCode', ColumnType::BIGINT);
        $registeredColumn->setNotNull(false);
        $table->addColumn($registeredColumn);
        $registeredColumn = new Column('registered', ColumnType::DATETIME);
        $registeredColumn->setNotNull(false);
        $table->addColumn($registeredColumn);

        $query = new MySQLWrapper();
        $query->createTable($table->getName())->definedAs($table->getColumns());

        $this->assertEquals(MySQLWrapper::beautify('CREATE TABLE IF NOT EXISTS '.__FUNCTION__.' ('
            .'id INTEGER NOT NULL, '
            .'name TEXT NOT NULL, '
            .'credit DOUBLE NOT NULL, '
            .'paidYears SMALLINT NOT NULL, '
            .'idCode BIGINT, '
            .'registered INTEGER, '
            .'PRIMARY KEY (id)'
            .')'), MySQLWrapper::beautify($query->exportQuery()));
    }

    public function testCreateTableWithNoForeignKey()
    {
        $table = new Table(__FUNCTION__);

        $idColumn = new Column('id', ColumnType::INTEGER);
        $idColumn->setNotNull(true);
        $idColumn->setPrimaryKey(true);
        $table->addColumn($idColumn);
        $nameColumn = new Column('name', ColumnType::TEXT);
        $nameColumn->setNotNull(true);
        $table->addColumn($nameColumn);
        $creditColumn = new Column('credit', ColumnType::NUMERIC);
        $creditColumn->setNotNull(true);
        $table->addColumn($creditColumn);
        $registeredColumn = new Column('registered', ColumnType::DATETIME);
        $registeredColumn->setNotNull(false);
        $table->addColumn($registeredColumn);

        $query = new MySQLWrapper();
        $query->createTable($table->getName())->definedAs($table->getColumns());

        $this->assertEquals(MySQLWrapper::beautify('CREATE TABLE IF NOT EXISTS '.__FUNCTION__.' ('
            .'id INTEGER NOT NULL, '
            .'name TEXT NOT NULL, '
            .'credit DOUBLE NOT NULL, '
            .'registered INTEGER, '
            .'PRIMARY KEY (id)'
            .')'), MySQLWrapper::beautify($query->exportQuery()));
    }

    public function testCreateTableWithForeignKey()
    {
        $tableExtern = new Table('users');
        $userIdColumn = new Column('id', ColumnType::INTEGER);
        $userIdColumn->setNotNull(true);
        $userIdColumn->setPrimaryKey(true);
        $tableExtern->addColumn($userIdColumn);

        $table = new Table('orders');

        $relation = new ColumnRelation($tableExtern, $userIdColumn);

        $idColumn = new Column('id', ColumnType::INTEGER);
        $idColumn->setNotNull(true);
        $idColumn->setPrimaryKey(true);
        $table->addColumn($idColumn);
        $nameColumn = new Column('customer_id', ColumnType::INTEGER);
        $nameColumn->setNotNull(true);
        $nameColumn->setRelation($relation);
        $table->addColumn($nameColumn);
        $creditColumn = new Column('spent', ColumnType::FLOAT);
        $creditColumn->setNotNull(true);
        $table->addColumn($creditColumn);
        $registeredColumn = new Column('ship_date', ColumnType::DATETIME);
        $registeredColumn->setNotNull(false);
        $table->addColumn($registeredColumn);

        $query = new MySQLWrapper();
        $query->createTable($table->getName())->definedAs($table->getColumns());

        $this->assertEquals(MySQLWrapper::beautify('CREATE TABLE IF NOT EXISTS orders ('
            .'id INTEGER NOT NULL, '
            .'customer_id INTEGER NOT NULL, '
            .'FOREIGN KEY (customer_id) REFERENCES users(id), '
            .'spent FLOAT NOT NULL, '
            .'ship_date INTEGER, '
            .'PRIMARY KEY (id)'
            .')'), MySQLWrapper::beautify($query->exportQuery()));
    }

    public function testCreateTableWithAutoIncrementAndNoForeignKey()
    {
        $table = new Table(__FUNCTION__);

        $idColumn = new Column('id', ColumnType::INTEGER);
        $idColumn->setAutoIncrement(true);
        $idColumn->setNotNull(true);
        $idColumn->setPrimaryKey(true);
        $table->addColumn($idColumn);
        $nameColumn = new Column('name', ColumnType::TEXT);
        $nameColumn->setNotNull(true);
        $table->addColumn($nameColumn);
        $creditColumn = new Column('credit', ColumnType::NUMERIC);
        $creditColumn->setNotNull(true);
        $table->addColumn($creditColumn);
        $registeredColumn = new Column('registered', ColumnType::DATETIME);
        $registeredColumn->setNotNull(false);
        $table->addColumn($registeredColumn);

        $query = new MySQLWrapper();
        $query->createTable($table->getName())->definedAs($table->getColumns());

        $this->assertEquals(MySQLWrapper::beautify('CREATE TABLE IF NOT EXISTS '.__FUNCTION__.' ('
            .'id INTEGER AUTO_INCREMENT NOT NULL, '
            .'name TEXT NOT NULL, '
            .'credit DOUBLE NOT NULL, '
            .'registered INTEGER, '
            .'PRIMARY KEY (id)'
            .')'), MySQLWrapper::beautify($query->exportQuery()));
    }
}