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

class NoSuchUserException extends Exception { }

class User {
    private $userName;
    private $secret;
    private $pin;
    private $invalidLogins;
    public $errors = array();
    public $passPhrase;
    private $maxDrift = 300;
    private $hotpCounter;
    private $hotpLookahead = 10;

    function fetch($userName) {
        global $dbh;
        $ps = $dbh->prepare("SELECT * FROM User where userName=:userName");
        $ps->execute(array(":userName"=>$userName));

        $this->userName=$userName;
        if ( $row = $ps->fetch() ) {
            $this->secret=$row['secret'];
            $this->pin=$row['pin'];
            $this->hotpCounter=$row['hotpCounter'];
            $this->invalidLogins=$row['invalidLogins'];
        } else {
            throw new NoSuchUserException();
        }
    }

    // Verify that the user has both token and pin, else throw an exception
    function verifySanity() {
        if ( empty($this->secret) || empty($this->pin) ) {
            throw new NoSuchUserException();
        }
    }

    function checkOTP($passPhrase) {
        if ( strlen($passPhrase) > 6 ) {
            $providedPP = substr($passPhrase, -6);
            $providedPin = substr($passPhrase, 0, -6);
            return ( $providedPin != $this->pin ? false : $this->checkHOTP($providedPP) );
        } else {
            return $this->checkMOTP($passPhrase);
        }
    }

    function checkMOTP ($passPhrase) {
        $now = intval( gmdate("U") / 10 );
        for ( $time = $now + ($this->maxDrift/10) ; $time >= $now - ($this->maxDrift/10) ; $time-- ) {
            $otp = substr( md5($time . $this->secret . $this->pin ), 0, 6);
            if ( $otp == $passPhrase ) {
                $this->passPhrase = $otp;
                return true;
            }
        }
        return false;
    }

    function checkHOTP ($passPhrase) {
        for ( $c = $this->hotpCounter; $c < $this->hotpCounter + $this->hotpLookahead ; $c++ ) {
            $otp = $this->oathTruncate($this->oathHotp($c));
            if ( $otp == $passPhrase ) {
                $this->passPhrase = $this->pin . $otp;
                $this->hotpCounter = $c+1;
                $this->save();
                return true;
            }
        }
        return false;
    }

    // perform mschapv2 authentication
    function checkOTPmschap ($challengeHash, $response) {
        $now = intval( gmdate("U") / 10 );
        $validPasswords = array();
        $validOtps = array();
        for ( $time = $now + ($this->maxDrift/10); $time >= $now - ($this->maxDrift/10) ; $time-- ) {
            $otp = substr( md5($time . $this->secret . $this->pin ), 0, 6);

            $pwHash = NtPasswordHash($otp);
            $calcResponse = ChallengeResponse($challengeHash, $pwHash);

            if ( $calcResponse == $response ) {
                $this->passPhrase = $otp;
                return true;
            }
        }

        // Repeat process for HOTP token
        for ( $c = $this->hotpCounter; $c < $this->hotpCounter + $this->hotpLookahead ; $c++ ) {
            $otp = $this->pin . $this->oathTruncate($this->oathHotp($c));
            $pwHash = NtPasswordHash($otp);
            $calcResponse = ChallengeResponse($challengeHash, $pwHash);
            if ( $calcResponse == $response ) {
                $this->passPhrase = $otp;
                $this->hotpCounter = $c+1;
                $this->save();
                return true;
            }
        }

        return false;
    }

    // Authenticate user
    function authenticate($password) {
        global $demo;
        if (isset($demo)) {
            return (in_array($this->userName, array_keys($demo)) && $demo[$this->userName]['pw']==$password);
        }
        return (pam_auth( $this->userName, $password ) );
    }

    // Get the fullname of the user
    function getFullName() {
        global $demo;
        if (isset($demo)) {
            return $demo[$this->userName]['fullName'];
        }
        $userInfo = posix_getpwnam($this->userName);
        return iconv('UTF-8', 'UTF-16LE', $userInfo['gecos']);
    }

    // Get the username of the user
    function getUserName() {
        return $this->userName;
    }

    // Save/create the user
    function save() {
        global $dbh;
        $ps = $dbh->prepare("INSERT INTO User (userName, secret, pin, hotpCounter) VALUES (:userName, :secret, :pin, :hotpCounter) ON DUPLICATE KEY UPDATE secret=:secret, pin=:pin, hotpCounter=:hotpCounter");
        $ps->execute(array( ":userName" => $this->userName,
                            ":secret" => $this->secret,
                            ":pin" => $this->pin,
                            ":hotpCounter" => $this->hotpCounter));
    }

    // Set the amount of invalid login attempts performed against this account
    function setInvalidLogins($invalidLogins) {
        $this->invalidLogins = $invalidLogins;
    }

    // Set the username of this user
    function setUserName($userName) {
        $this->userName = strtolower($userName);
    }

    // Delete the user from the database
    function delete() {
        global $dbh;
        $ps = $dbh->prepare("DELETE FROM User where `userName`=:userName");
        $ps->execute(array( ":userName" => $this->userName ) );
    }

    function getErrors() {
        if (count($this->errors) == 1) {
            return $this->errors[0];
        }
        return "<ul><li>" . array_join("</li>\n<li>", $this->errors) . "</li></ul>\n";
    }

    function hasToken() {
        return ( !empty($this->secret) );
    }

    function hasPin() {
        return ( !empty($this->pin) );
    }

    function invalidLogin() {
        global $dbh;
        $ps = $dbh->prepare("UPDATE User set invalidLogins = invalidLogins+1 where userName=:userName");
        $ps->execute(array(":userName"=>$this->userName));
    }

    // Is this user an administrator
    function isAdmin() {
        global $groupAdmin, $demo;
        if (isset($demo)) {
            return ( $demo[$this->userName]['admin'] );
        }
        $groupInfo = posix_getgrnam($groupAdmin);
        return (in_array($this->userName, $groupInfo['members']));
    }

    // Is the user a member of $group?
    function isMemberOf($group) {
        global $demo;
        if (isset($demo)) {
            return ( in_array( $this->userName, array_keys($demo) ) );
        }
        $groupInfo = posix_getgrnam($group);
        $userInfo = posix_getpwnam($this->userName);
        return ( in_array( $this->userName, $groupInfo['members'] ) || $userInfo['gid'] == $groupInfo['gid'] );
    }

    function isLockedOut() {
        global $invalidLoginLimit;
        return ( !isset($invalidLoginLimit) ? false : ($this->invalidLogins > $invalidLoginLimit ? true : false ));
    }

    // Have there been too many failed login attempts in the past $throttleLoginTime seconds?
    function isThrottled() {
        global $dbh, $throttleLoginTime, $throttleLoginAttempts;
        if (!isset($throttleLoginTime)) {
            return false;
        }

        $ps = $dbh->prepare('SELECT count(*) FROM Log where time > (now() - ' . $throttleLoginTime . ') AND userName=:userName AND message like "FAIL%"');
        $ps->execute(array( ":userName" => $this->userName ));
        $result = $ps->fetch();
        return ($result[0] > $throttleLoginAttempts);
    }

    function log($message) {
        global $dbh;

        // Don't log the pin...
        $pp = strlen($this->passPhrase) > 6 ? substr($this->passPhrase, -6) : $this->passPhrase;

        $ps = $dbh->prepare("INSERT INTO Log (userName, passPhrase, message) VALUES (:userName, :passPhrase, :message)");
        $ps->execute(array( ":userName" => $this->userName,
                            ":passPhrase" => $pp,
                            ":message" => $message));
    }

    function replayAttack() {
        global $dbh;
        $ps = $dbh->prepare('SELECT count(*) from Log where time > (now() - ' . $this->maxDrift*2 . ') AND userName=:userName AND passPhrase=:passPhrase AND message like "Success%"');
        $ps->execute(array(":userName"=>$this->userName,
                           ":passPhrase"=>$this->passPhrase));
        $result = $ps->fetch();
        return ($result[0] > 0);
    }

    function setPin($newPin) {
        if ( strlen($newPin) >= 4 
             && is_numeric($newPin) ) {
            $this->pin = $newPin;
        } else {
            array_push($this->errors, "Invalid PIN. Make sure your selected PIN contains at least four digits.");
        }
    }

    function setSecret($newSecret) {
        $this->hotpCounter = 0;
        $this->secret = str_replace(" ", "", $newSecret);
    }

    function unlock($unlocker) {
        global $dbh;
        $ps = $dbh->prepare("UPDATE User set invalidLogins = 0 where userName=:userName");
        $ps->execute(array(":userName"=>$this->userName));
        $this->log("Account unlocked by " . htmlentities($unlocker));
    }

    function validLogin($source = "") {
        global $dbh;
        $ps = $dbh->prepare("UPDATE User set invalidLogins = 0 where userName=:userName");
        $ps->execute(array(":userName"=>$this->userName));
        $this->log("Success" . ($source=="" ? "" : " [ " . $source . " ]"));
    }

    function hotpResync($passPhrase1, $passPhrase2, $passPhrase3) {
        if ($this->hotpCounter == 0) {
            return 0;
        }
        for ($offset=0; $offset<1000; $offset++) {
            $c = $this->hotpCounter + $offset;
            if ( $this->oathTruncate($this->oathHotp($c)) == $passPhrase1 ) {
                // We found a match. Verify the other passPhrases as well.
                if ( ( $this->oathTruncate($this->oathHotp($c+1)) == $passPhrase2 ) &&
                     ( $this->oathTruncate($this->oathHotp($c+2)) == $passPhrase3 ) ) {
                    $this->hotpCounter = $c+3;
                    $this->save();
                    return $offset+3;
                }
            }
        }
        return 0;
    }

    // This code from http://php.net/manual/en/function.hash-hmac.php
    function oathHotp ($counter) {
        // Counter
        // the counter value can be more than one byte long, so we need to go multiple times
        $cur_counter = array(0, 0, 0, 0, 0, 0, 0, 0);
        for($i=7;$i>=0;$i--) {
            $cur_counter[$i] = pack ('C*', $counter);
            $counter = $counter >> 8;
        }
        $bin_counter = implode($cur_counter);
        $bin_counter = str_pad ($bin_counter, 8, chr(0), STR_PAD_LEFT);
        $hash = hash_hmac ('sha1', $bin_counter, pack( 'H*', $this->secret));
        return $hash;
    }

    // This code from http://php.net/manual/en/function.hash-hmac.php
    function oathTruncate($hash, $length = 6) {
        // Convert to dec
        foreach(str_split($hash,2) as $hex) {
            $hmac_result[]=hexdec($hex);
        }

        // Find offset
        $offset = $hmac_result[19] & 0xf;

        // Algorithm from RFC
        return
        (
            (($hmac_result[$offset+0] & 0x7f) << 24 ) |
            (($hmac_result[$offset+1] & 0xff) << 16 ) |
            (($hmac_result[$offset+2] & 0xff) << 8 ) |
            ($hmac_result[$offset+3] & 0xff)
        ) % pow(10,$length);
    }
}
?>
