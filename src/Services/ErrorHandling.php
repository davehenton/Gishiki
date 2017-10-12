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

namespace Gishiki\Services;

use Gishiki\Core\MVC\Controller\Controller;

/**
 * This component represents the application as a set of HTTP rules.
 *
 * @author Benato Denis <benato.denis96@gmail.com>
 */
final class ErrorHandling extends Controller
{
    public function notFound()
    {
        $this->getResponse()->getBody()->write("404 - Not Found");
    }

    public function notAllowed()
    {
        $this->getResponse()->getBody()->write("405 - Not Allowed");
    }
}