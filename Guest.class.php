<?php
/**
 * Mobile OTP self-service station and administration console
 * Version 1.0
 * 
 * PHP Version 5 with PDO, MySQL, and PAM support
 * 
 * Written by Markus Berg
 *   email: markus@kelvin.nu
 *   http://kelvin.nu/mossad/
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

class Guest {

    /***
     * Function for generating simple semi-secure passwords for wifi 
     * guest access.
     * Based on:
     * http://www.anyexample.com/programming/php/php__password_generation.xml
     */
    function generatePassword() {
        // 39 prefixes
        $aPrefix = array('a', 'aero', 'anti', 'ante', 'auto', 
                         'bi', 'bio',
                         'centi', 'cine', 'contra', 
                         'deca', 'demo', 'duo', 'dyna', 
                         'eco', 'ergo', 'extra', 
                         'geo', 'gyno', 
                         'hetero', 'hypo', 'kilo',
                         'intra', 
                         'macro', 'micro', 'maxi', 'mega', 'mini', 'mono', 
                         'nano', 'omni', 
                         'pre', 'pro', 'per', 
                         'super', 
                         'tera', 'tri', 'tetra',
                         'uni');

        // 31 suffices
        $aSuffix = array('acy', 'al', 'ance', 'ate', 'able', 
                         'dom', 
                         'ence', 'er', 'en', 'esque', 
                         'fy', 'ful', 
                         'ment', 'ness',
                         'ist', 'ity', 'ify', 'ize', 'ise', 'ible', 'ic', 'ical', 'icious', 'ous', 'ish', 'ive', 
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
        $pwd .= $aSalt[array_rand($aSalt)];

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

}

?>
