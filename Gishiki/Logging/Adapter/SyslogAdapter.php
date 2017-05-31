<?php
/****************************************************************************
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
 *******************************************************************************/

namespace Gishiki\Logging\Adapter;

use Gishiki\Algorithms\Manipulation;
use Psr\Log\LogLevel;
use Psr\Log\AbstractLogger;

/**
 * An helper class for storing logs of what happens on the server.
 *
 * Benato Denis <benato.denis96@gmail.com>
 */
class SyslogAdapter extends AbstractLogger
{
    /**
     * @var string The name of the program that is generating log entries
     */
    private $identity = null;

    /**
     * Setup a logger for an application or a component.
     *
     * This is the syslog version of the logger
     *
     * @param string $identity the name of the application
     */
    public function __construct($identity = '')
    {
        (strlen($identity) > 0) ? $this->identity = $identity : 'Gishiki';
    }

    public function log($level, $message, array $context = [])
    {
        $interpolatedMsg = Manipulation::interpolate($message, $context);

        //get the urgency level:
        $syslogLevel = LOG_EMERG;
        switch ($level) {
            case LogLevel::EMERGENCY:
                $syslogLevel = LOG_EMERG;
                break;

            case LogLevel::ALERT:
                $syslogLevel = LOG_ALERT;
                break;

            case LogLevel::CRITICAL:
                $syslogLevel = LOG_CRIT;
                break;

            case LogLevel::ERROR:
                $syslogLevel = LOG_ERR;
                break;

            case LogLevel::WARNING:
                $syslogLevel = LOG_WARNING;
                break;

            case LogLevel::NOTICE:
                $syslogLevel = LOG_NOTICE;
                break;

            case LogLevel::INFO:
                $syslogLevel = LOG_INFO;
                break;

            default:
                $syslogLevel = LOG_DEBUG;
        }

        if (openlog($this->identity, LOG_NDELAY | LOG_PID, LOG_USER)) {
            //save the log using the UNIX standard logging ultility
            syslog($this->GetLevel(), $interpolatedMsg);

            //virtually close the connection to syslogd
            closelog();
        }
    }
}