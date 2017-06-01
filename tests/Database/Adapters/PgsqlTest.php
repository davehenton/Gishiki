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

namespace Gishiki\tests\Database\Adapters;

use Gishiki\Database\Adapters\Pgsql;
use Gishiki\Database\DatabaseException;

/**
 * The tester for the Pgsql class.
 *
 * @author Benato Denis <benato.denis96@gmail.com>
 */
class PgsqlTest extends DatabaseTest
{

    protected function getDatabase()
    {
        $postgreConnectionStr = (getenv("PG_CONN") === false) ? "host=localhost;port=5432;dbname=travis;user=vagrant;password=vagrant" : getenv("PG_CONN");

        return new Pgsql($postgreConnectionStr);
    }

    public function testBadConnectionParam()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Pgsql(null);
    }

    public function testBadConnection()
    {
        $this->expectException(DatabaseException::class);

        new Pgsql("database=doesntExists");
    }
}