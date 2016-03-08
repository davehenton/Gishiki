<?php
/****************************************************************************
Copyright 2015 Benato Denis

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

namespace Gishiki\Logging {

    /**
     * An helper class for storing logs of what happens on the server
     *
     * Benato Denis <benato.denis96@gmail.com>
     */
    abstract class LogManagement
    {
        private static $connected = FALSE;

        /**
         * This is used to interract with the log collection, but the way it's done depends on the log used method
         */
        protected static $logCollection;

        /**
         * Parse the log source connection string
         *
         * @param $connectionString string the cache server connection string
         * @return array the parsing results
         */
        static function ParseLogConnection($connectionString)/* : array*/
        {
            //create some empty log collection source details
            $conectionDetails = [
                "source_type" => "",
            ];

            //if the connection string is not empty.....
            if (strlen($connectionString) > 0) {
                //try fetching the log collection source type, address and port
                $strings = explode("://", $connectionString, 2);
                if ((strtolower($strings[0]) == "graylog") || (strtolower($strings[0]) == "graylog2")) {
                    //divide host from port
                    $hostport = explode(":", $strings[1], 2);

                    //return the parsed connection string
                    return [
                        "source_type" => "graylog2",
                        "host" => $hostport[0],
                        "port" => intval($hostport[1])
                    ];
                }
            }
            //return the connection details in form of an array
            return $conectionDetails;
        }

        /**
         * Initialize the logging engine for the current request.
         * This function is automatically called by the framework.
         * Another call to this function won't produce any effects.
         */
        static function Initialize() {
            if (!self::$connected) {
                //initialize the logging engine only if it is needed
                if (\Gishiki\Core\Environment::GetCurrentEnvironment()->GetConfigurationProperty('LOGGING_ENABLED')) {
                    //parse the collection source string of log entries
                    self::$logCollection["details"] = LogManagement::ParseLogConnection(\Gishiki\Core\Environment::GetCurrentEnvironment()->GetConfigurationProperty("LOGGING_COLLECTION_SOURCE"));

                    //connect the log collection source
                    switch (self::$logCollection["details"]["source_type"]) {
                        case "graylog2":
                            //build the connection to the server
                            self::$logCollection["connection"] = new \GELFMessagePublisher(self::$logCollection["details"]["host"], self::$logCollection["details"]["port"]);

                            //the source is connected
                            self::$connected = TRUE;
                            break;

                        default:

                            break;
                    }
                }
            }
        }

        /**
         * Store a log entry to the log server/file.
         *
         * @param Log $entry the log entry to be saved/stored
         */
        static function Save(Log &$entry) {
            //use syslog to store the log entry on the current machine
            if (openlog("Gishiki" , LOG_NDELAY | LOG_PID, LOG_USER)) {
                $log_priority = 0;
                switch ($entry->GetLevel()) {
                    case \Gishiki\Logging\Priority::DEBUG:
                        $log_priority = LOG_DEBUG;
                        break;

                    case \Gishiki\Logging\Priority::ALERT:
                        $log_priority = LOG_ALERT;
                        break;

                    case \Gishiki\Logging\Priority::CRITICAL:
                        $log_priority = LOG_CRIT;
                        break;

                    case \Gishiki\Logging\Priority::EMERGENCY:
                        $log_priority = LOG_EMERG;
                        break;

                    case \Gishiki\Logging\Priority::ERROR:
                        $log_priority = LOG_ERR;
                        break;

                    case \Gishiki\Logging\Priority::INFO:
                        $log_priority = LOG_INFO;
                        break;

                    case \Gishiki\Logging\Priority::NOTICE:
                        $log_priority = LOG_NOTICE;
                        break;

                    case \Gishiki\Logging\Priority::WARNING:
                        $log_priority = LOG_WARNING;
                        break;

                    default:
                        $log_priority = LOG_CRIT;
                        break;
                }

                //save the log using the UNIX standard logging ultility
                syslog($log_priority, "[".$entry->GetTimestamp()."] (".$entry->GetShortMessage().") ".$entry->GetLongMessage()."");
                closelog();
            }

            //forward the log entry only if the connection have been established
            if (self::$connected) {
                //choose the correct way of writing to the log collection
                switch (self::$logCollection["details"]["source_type"]) {
                    case "graylog2":
                        //build the GELF message
                        $message = new \GELFMessage();

                        //fill the message
                        $message->setShortMessage($entry->GetShortMessage());
                        $message->setFullMessage($entry->GetLongMessage());
                        $message->setTimestamp($entry->GetTimestamp());
                        $message->setLevel($entry->GetLevel());

                        //publish the log entry
                        self::$logCollection["connection"]->publish($message);
                        break;

                    default:

                        break;
                }
            }
        }
    }
}