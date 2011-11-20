<?php
/**
 * Potato
 * One-time-password self-service and administration
 * Version 1.0
 * 
 * Written by Markus Berg
 *   email: markus@kelvin.nu
 *   http://kelvin.nu/software/potato/
 * 
 * Copyright 2011 Markus Berg
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 */

class NoGuestException extends Exception { }

class Guest {
    public $userName;
    public $password;
    public $dateExpiration;

    function fetch($userName) {
        global $dbh;
        $ps = $dbh->prepare("SELECT userName, password, dateCreation + interval 7 day as dateExpiration FROM Guest where userName=:userName and dateCreation>(now() - interval 7 day)" );
        $ps->execute(array(":userName"=>$userName));

        $this->userName = $userName;
        if ( $row = $ps->fetch() ) {
            $this->password=$row['password'];
            $this->dateExpiration=$row['dateExpiration'];
        } else {
            throw new NoGuestException();
        }
    }

    function generate() {
        global $dbh;
        // Delete any old guest accounts
        $ps = $dbh->prepare("DELETE FROM Guest where userName=:userName");
        $ps->execute(array( ":userName" => $this->userName));
        // Make sure that the user entry exists
        $ps = $dbh->prepare("INSERT INTO User (userName) VALUES (:userName)");
        $ps->execute(array( ":userName" => $this->userName));
        // Create the guest account
        $ps = $dbh->prepare("INSERT INTO Guest (userName, password) VALUES (:userName, :password)");
        $ps->execute(array( ":userName" => $this->userName,
                            ":password" => $this->generatePassword()));
        $this->log("Wifi guest account activated");
    }

    function setUserName($userName) {
        $this->userName = $userName;
    }

    function getUserName() {
        return $this->userName . ".guest";
    }

    function deactivate() {
        global $dbh;
        $ps = $dbh->prepare("DELETE FROM Guest where userName=:userName");
        $ps->execute(array( ":userName" => $this->userName));
        $this->log("Wifi guest account deactivated");
    }

    /***
     * Function for generating simple semi-secure passwords for wifi 
     * guest access.
     * Based on:
     * http://www.anyexample.com/programming/php/php__password_generation.xml
     */
    function generatePassword() {
        // 57 prefixes
        $aPrefix = array('aero', 'anti', 'ante', 'ande', 'auto', 
                         'ba', 'be', 'bi', 'bio', 'bo', 'bu', 'by', 
                         'ca', 'ce', 'ci', 'cou', 'co', 'cu', 'cy', 
                         'da', 'de', 'di', 'duo', 'dy', 
                         'eco', 'ergo', 'exa', 
                         'geo', 'gyno', 
                         'he', 'hy', 'ki',
                         'intra', 
                         'ma', 'mi', 'me', 'mo', 'my', 
                         'na', 'ni', 'ne', 'no', 'ny', 
                         'omni', 
                         'pre', 'pro', 'per', 
                         'sa', 'se', 'si', 'su', 'so', 'sy', 
                         'ta', 'te', 'tri',
                         'uni');

        // 30 suffices
        $aSuffix = array('acy', 'al', 'ance', 'ate', 'able', 'an', 
                         'dom', 
                         'ence', 'er', 'en',
                         'fy', 'ful', 
                         'ment', 'ness',
                         'ist', 'ity', 'ify', 'ize', 'ise', 'ible', 'ic', 'ical', 'ous', 'ish', 'ive', 
                         'less', 
                         'sion',
                         'tion', 'ty', 
                         'or');

        // 8 vowel sounds 
        $aVowels = array('a', 'o', 'e', 'i', 'y', 'u', 'ou', 'oo'); 

        // 20 random consonants 
        $aConsonants = array('w', 'r', 't', 'p', 's', 'd', 'f', 'g', 'h', 'j', 
                             'k', 'l', 'z', 'x', 'c', 'v', 'b', 'n', 'm', 'qu');

        // Some consonants can be doubled
        $aDoubles = array('n', 'm', 't', 's');

        // "Salt"
        $aSalt = array('!', '#', '%', '?');

        $pwd = $aPrefix[array_rand($aPrefix)];

        // add random consonant(s)
        $c = $aConsonants[array_rand($aConsonants)];
        if ( in_array( $c, $aDoubles ) ) {
            // 33% chance of doubling it
            if (rand(0, 2) == 1) { 
                $c .= $c;
            }
        }
        $pwd .= $c;

        // add random vowel
        $pwd .= $aVowels[array_rand($aVowels)];

        $pwdSuffix = $aSuffix[array_rand($aSuffix)];
        // If the suffix begins with a vovel, add one or more consonants
        if ( in_array( $pwdSuffix[0], $aVowels ) ) {
            $pwd .= $aConsonants[array_rand($aConsonants)];
        }
        $pwd .= $pwdSuffix;

        $pwd .= rand(2, 999);
        # $pwd .= $aSalt[array_rand($aSalt)];

        // 50% chance of capitalizing the first letter
        if (rand(0, 1) == 1) {
            $pwd = ucfirst($pwd);
        }
        return $pwd;
    }

    /***
     * Simple test function for the password generator
     */
    function test() {
        for ( $i=0; $i< 25; $i++) {
            echo $this->generatePassword() . "<br />";
        }
    }

    function log($message) {
        global $dbh;
        $ps = $dbh->prepare("INSERT INTO Log (userName, message) VALUES (:userName, :message)");
        $ps->execute(array( ":userName" => $this->userName,
                            ":message" => $message));
    }

}

?>
