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

namespace Gishiki\Security\Hashing;

/**
 * This class provides hashing functions while abstracting the algorithm.
 *
 * Note: This class uses OpenSSL for strong encryption
 *
 * @author Benato Denis <benato.denis96@gmail.com>
 */
final class Hasher
{
    /**
     * @var integer|null the hashing algorithm, or null on error
     */
    private $algorithm = null;

    /**
     * @var bool true if the algorithm name has to be passed
     */
    private $algorithmRequired = false;

    /**
     * @var string the name of the function to be called to produce a message digest
     */
    private $hashCallback;

    /**
     * @var string the name of the function to be called to verify a message digest
     */
    private $verifyCallback;

    /**
     * Create an object that provides an easy and unique interface to use any of the supported algorithms
     *
     * @param string $algorithm the algorithm to be used
     * @throws HashingException the given algorithm is unsupported
     */
    public function __construct($algorithm = Algorithm::BCRYPT)
    {
        //check if the hashing algorithm is supported
        if (strcmp($algorithm, Algorithm::BCRYPT) == 0) {
            $this->algorithm = $algorithm;
            $this->hashCallback = Algorithm::class."::".Algorithm::BCRYPT."Hash";
            $this->verifyCallback = Algorithm::class."::".Algorithm::BCRYPT."Verify";
        } elseif (strcmp($algorithm, Algorithm::PBKDF2) == 0) {
            $this->algorithm = $algorithm;
            $this->hashCallback = Algorithm::class."::".Algorithm::BCRYPT."Hash";
            $this->verifyCallback = Algorithm::class."::".Algorithm::BCRYPT."Verify";
        } elseif (strcmp($algorithm, Algorithm::ROT13) == 0) {
            $this->algorithm = $algorithm;
            $this->hashCallback = Algorithm::class."::".Algorithm::ROT13."Hash";
            $this->verifyCallback = Algorithm::class."::".Algorithm::ROT13."Verify";
        } elseif ((in_array($algorithm, openssl_get_md_methods())) && (in_array($algorithm, hash_algos()))) {
            $this->algorithm = $algorithm;
            $this->algorithmRequired = true;
            $this->hashCallback = Algorithm::class."::opensslHash";
            $this->verifyCallback = Algorithm::class."::opensslVerify";
        }

        if (is_null($this->algorithm)) {
            throw new HashingException('Unsupported hashing algorithm', 0);
        }
    }

    /**
     * Generate the hash of the given message using the chosen algorithm
     *
     * @param string $message the message to be hashed
     *
     * @return string the result of the hashing algorithm
     *
     * @throws \InvalidArgumentException the message or the message digest is given as a non-string or an empty string
     * @throws HashingException          the error occurred while generating the hash for the given message
     */
    public function hash($message)
    {
        $callbackParams = ($this->algorithmRequired) ? [$message, $this->algorithm] : [$message];

        return call_user_func_array($this->hashCallback, $callbackParams);
    }

    /**
     * Check the hash of the given message using the chosen algorithm
     *
     * @param string $message the message to be checked against the digest
     * @param string $digest  the message digest to be checked
     *
     * @return bool the result of the check: true on success, false otherwise
     *
     * @throws \InvalidArgumentException the message or the message digest is given as a non-string or an empty string
     * @throws HashingException          the error occurred while generating the hash for the given message
     */
    public function verify($message, $digest)
    {
        $callbackParams = ($this->algorithmRequired) ? [$message, $digest, $this->algorithm] : [$message, $digest];

        return call_user_func_array($this->verifyCallback, $callbackParams);
    }
}
