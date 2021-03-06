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
 * A collection of data types that a column can store.
 *
 * @author Benato Denis <benato.denis96@gmail.com>
 */
abstract class ColumnType
{
    const SMALLINT = 0;
    const INTEGER = 1;
    const BIGINT = 2;
    const TEXT = 3;
    const FLOAT = 4;
    const DOUBLE= 5;
    const DATETIME = 6;
    const NUMERIC = 7;
    const MONEY = 8;

    /**
     * This is NOT a type and MUST NOT be used!
     */
    const UNKNOWN = 9;
}
