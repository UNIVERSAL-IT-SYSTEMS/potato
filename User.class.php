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
    private $passPhrase;
    private $maxDrift = 180;
    private $hotpCounter;
    private $hotpLookahead = 10;
    private $CSRFToken = '';

    /***
     * Return the CSRF token of this session
     */
    function getCSRFToken() {
        return $this->CSRFToken;
    }

    /***
     * Set the CSRF token of this session
     */
    function setCSRFToken($token) {
        $this->CSRFToken = $token;
    }

    /***
     * Generate url/html-friendly CSRF token. Store in object
     */
    function generateCSRFToken() {
        $vocabulary = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789.-';
        $token = '';
        for ($i=0; $i<32; $i++) {
            $token.=substr($vocabulary, rand(0, 63), 1);
        }
        $this->CSRFToken = $token;
    }

    /***
     * Verify validity of submitted CSRF token
     */
    function isValidCSRFToken($token) {
        if (empty($token) || $this->CSRFToken=='') {
            return false;
        }
        return ($token == $this->CSRFToken);
    }

    /***
     * Fetch user from database
     */
    function fetch($userName) {
        global $dbh;
        $ps = $dbh->prepare("SELECT * FROM User where userName=:userName");
        $ps->execute(array(":userName"=>$userName));

        $this->setUserName($userName);
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

    /**
     *  Return true if this is an OATH HOTP token
     */
    function isHOTP() {
        // only HOTP tokens have 20 byte long secrets
        return (strlen($this->secret)==40 ? true : false);
    }

    /**
     * Return the passphrase
     */
    function getPassPhrase() {
        return($this->passPhrase);
    }

    /**
     * Check if the passphrase is valid
     *
     * @param string $passPhrase the passphrase to be tested
     */
    function checkOTP($passPhrase) {
        if ( $this->isHOTP() ) {
            $providedPP = substr($passPhrase, -6);
            $providedPin = substr($passPhrase, 0, -6);
            return ( $providedPin != $this->pin ? false : $this->checkHOTP($providedPP) );
        } else {
            return $this->checkMOTP($passPhrase);
        }
    }

    /**
     * Verify if the user token clock is off. This is a very common source for errors
     * 
     * @param  string       $passPhrase the passphrase to be tested
     * @return false/string false if unable to sync, otherwise the amount of time that the clock is off
     */
    function checkClockDiff($passPhrase) {
        if ($this->isHOTP()) {
            return false;
        }
        $timeBase = gmdate("U");
        // search +/- twelve hours, and +/- 10 minutes on every hour
        for ($hour=-12; $hour<=12; $hour++) {
            $now = intval(($timeBase + $hour*60*60)/10);
            for ( $time = $now + 60 ; $time >= $now - 60 ; $time-- ) {
                $otp = substr( md5($time . $this->secret . $this->pin ), 0, 6);
                if ( $otp == $passPhrase ) {
                    // return the amount of diff in seconds
                    return( ($time-$now)*10 + $hour*3600 );
                }
            }
        }
        return false;
    }

    /**
     * Perform motp standard authentication
     * 
     * @param string $passPhrase the passphrase to be tested
     */
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

    /**
     * Perform standard TOTP authentication
     * 
     * @param string $passPhrase the passphrase to be tested
     */
    function checkTOTP ($passPhrase) {
        for ( $c = $this->hotpCounter; $c < $this->hotpCounter + $this->hotpLookahead ; $c++ ) {
            $otp = $this->oathTruncate($this->oathHotp($c));
            if ( $otp == $passPhrase ) {
                // php is weakly typed, so we need to make sure to zero-pad our otp
                $this->passPhrase = $this->pin . str_pad($otp, 6, "0", STR_PAD_LEFT);
                $this->hotpCounter = $c+1;
                $this->save();
                return true;
            }
        }
        return false;
    }

    /**
     * Perform standard HOTP authentication
     * 
     * @param string $passPhrase the passphrase to be tested
     */
    function checkHOTP ($passPhrase) {
        for ( $c = $this->hotpCounter; $c < $this->hotpCounter + $this->hotpLookahead ; $c++ ) {
            $otp = $this->oathTruncate($this->oathHotp($c));
            if ( $otp == $passPhrase ) {
                // php is weakly typed, so we need to make sure to zero-pad our otp
                $this->passPhrase = $this->pin . str_pad($otp, 6, "0", STR_PAD_LEFT);
                $this->hotpCounter = $c+1;
                $this->save();
                return true;
            }
        }
        return false;
    }

    /**
     * Verify validity of MSCHAPv2 handshake
     *
     * param string $challengeHash
     * param string $response
     */
    function checkOTPmschap ($challengeHash, $response) {
        if ($this->isHOTP()) {
            // OATH HOTP algorithm
            for ( $c = $this->hotpCounter; $c < $this->hotpCounter + $this->hotpLookahead ; $c++ ) {
                // php is weakly typed, so we need to make sure to zero-pad our otp
                $otp = $this->pin . str_pad($this->oathTruncate($this->oathHotp($c)), 6, "0", STR_PAD_LEFT);
                $pwHash = NtPasswordHash($otp);
                $calcResponse = ChallengeResponse($challengeHash, $pwHash);
                if ( $calcResponse == $response ) {
                    $this->passPhrase = $otp;
                    $this->hotpCounter = $c+1;
                    $this->save();
                    return true;
                }
            }
        } else {
            // mOTP algorithm
            $now = intval( gmdate("U") / 10 );
            for ( $time = $now + ($this->maxDrift/10); $time >= $now - ($this->maxDrift/10) ; $time-- ) {
                $otp = substr( md5($time . $this->secret . $this->pin ), 0, 6);

                $pwHash = NtPasswordHash($otp);
                $calcResponse = ChallengeResponse($challengeHash, $pwHash);

                if ( $calcResponse == $response ) {
                    $this->passPhrase = $otp;
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Perform posix authentication of user
     *
     * @param string $password The user-supplied password
     */
    function authenticate($password) {
        global $demo;
        if (isset($demo)) {
            return (in_array($this->userName, array_keys($demo)) && $demo[$this->userName]['pw']==$password);
        }
        return (pam_auth( $this->userName, $password ) );
    }

    /**
     * Get the posix fullname of the user
     */
    function getFullName() {
        global $demo;
        if (isset($demo)) {
            return $demo[$this->userName]['fullName'];
        }
        $userInfo = posix_getpwnam($this->userName);
        return iconv('UTF-8', 'UTF-16LE', $userInfo['gecos']);
    }

    /**
     * Get the username of the user
     */
    function getUserName() {
        return $this->userName;
    }

    /**
     * Set the username of this user
     *
     * @param string $userName
     */
    function setUserName($userName) {
        $this->userName = strtolower($userName);
    }


    /**
     * Save/create the user
     */
    function save() {
        global $dbh;

        $ps = $dbh->prepare("INSERT INTO User (userName, secret, pin, hotpCounter) VALUES (:userName, :secret, :pin, :hotpCounter) ON DUPLICATE KEY UPDATE secret=:secret, pin=:pin, hotpCounter=:hotpCounter");
        $ps->execute(array( ":userName" => $this->userName,
                            ":secret" => $this->secret,
                            ":pin" => $this->pin,
                            ":hotpCounter" => $this->hotpCounter));
    }

    /**
     * Set the current amount of invalid login attempts performed against this account
     *
     * @param integer $invalidLogins
     */
    function setInvalidLogins($invalidLogins) {
        $this->invalidLogins = $invalidLogins;
    }

    /**
     * Delete the user from the database
     */
    function delete() {
        global $dbh;
        $ps = $dbh->prepare("DELETE FROM User where `userName`=:userName");
        $ps->execute(array( ":userName" => $this->userName ) );
    }

    function getErrors() {
        if (count($this->errors) == 1) {
            return $this->errors[0];
        }
        return "<ul><li>" . implode("</li>\n<li>", $this->errors) . "</li></ul>\n";
    }

    /**
     * Does the user have a registered token?
     */
    function hasToken() {
        return ( !empty($this->secret) );
    }

    /**
     * Does the user have a registered pin?
     */
    function hasPin() {
        return ( !empty($this->pin) );
    }

    /**
     * Log an invalid login attempt
     *
     * Update the invalid logins database column, and log the invalid attempt
     */
    function invalidLogin( $aLog=array()) {
        global $dbh;
        $ps = $dbh->prepare("UPDATE User set invalidLogins = invalidLogins+1 where userName=:userName");
        $ps->execute(array(":userName"=>$this->userName));
        $aLog["status"]="Fail";
        $this->log( $aLog );
    }

    /**
     * Is this user an administrator. Checks posix group membership
     */
    function isAdmin() {
        global $groupAdmin, $demo;
        if (isset($demo)) {
            return ( $demo[$this->userName]['admin'] );
        }
        return $this->isMemberOf($groupAdmin);
    }

    /**
     * Is the user a member of $group?
     *
     * @param string $group Posix group to check membership of
     */
    function isMemberOf($group) {
        global $demo;
        if (isset($demo)) {
            return ( in_array( $this->userName, array_keys($demo) ) );
        }
        return in_array($group, $this->getUserGroups());
    }

    /**
     * Get user posix groups
     */
    function getUserGroups() {
        $groups = trim(shell_exec("id --name --groups " . $this->userName));
        $aGroups = explode(" ", $groups);
        $aGroups[] = trim(shell_exec("id --name --group " . $this->userName));

        return $aGroups;
    }


    /**
     * Is this user currently locked out?
     */
    function isLockedOut() {
        global $invalidLoginLimit;
        return ( !isset($invalidLoginLimit) ? false : ($this->invalidLogins > $invalidLoginLimit ? true : false ));
    }

    /**
     * Have there been too many failed login attempts in the past $throttleLoginTime seconds?
     */
    function isThrottled() {
        global $dbh, $throttleLoginTime, $throttleLoginAttempts;
        if (!isset($throttleLoginTime)) {
            return false;
        }

        $ps = $dbh->prepare('SELECT count(*) FROM Log where time > (now() - ' . $throttleLoginTime . ') AND userName=:userName AND status="Fail"');
        $ps->execute(array( ":userName" => $this->userName ));
        $result = $ps->fetch();
        return ($result[0] > $throttleLoginAttempts);
    }

    /**
     * Add entry to the log
     *
     * @param array $aLog key/value pairs of information to log. Keys can be one or more of [idNAS, idClient, status, message]
     */
    function log( $aLog ) {
        global $dbh;

        // Strip the PIN from the passPhrase in case of HOTP logins
        $pp = strlen($this->passPhrase) > 6 ? substr($this->passPhrase, -6) : $this->passPhrase;

        $ps = $dbh->prepare("INSERT INTO Log (userName, passPhrase, idNAS, idClient, status, message) VALUES (:userName, :passPhrase, :idNAS, :idClient, :status, :message)");
        $ps->execute(array( ":userName" => $this->userName,
                            ":passPhrase" => $pp,
                            ":idNAS" => (isset($aLog['idNAS']) ? $aLog['idNAS'] : null),
                            ":idClient" => (isset($aLog['idClient']) ? $aLog['idClient'] : null),
                            ":status" => (isset($aLog['status']) ? $aLog['status'] : null),
                            ":message" => (isset($aLog['message']) ? $aLog['message'] : null)));
    }

    /**
     * Has this passphrase already been used?
     */
    function replayAttack() {
        global $dbh;
        $ps = $dbh->prepare('SELECT count(*) from Log where time > (now() - ' . $this->maxDrift*2 . ') AND userName=:userName AND passPhrase=:passPhrase AND status="Success"');
        $ps->execute(array(":userName"=>$this->userName,
                           ":passPhrase"=>$this->passPhrase));
        $result = $ps->fetch();
        return ($result[0] > 0);
    }

    /**
     * Set the user pin
     *
     * @param string $newPin Four-digit (minimum) pin
     */
    function setPin($newPin) {
        if ( strlen($newPin) >= 4 
             && is_numeric($newPin) ) {
            $this->pin = $newPin;
        } else {
            array_push($this->errors, "Invalid PIN. Make sure your selected PIN contains at least four digits.");
        }
    }

    /**
     * Set the user secret
     *
     * @param string $newSecret Hexadecimal string (16, 24 or 32 characters in length)
     */
    function setSecret($newSecret) {
        $s = str_replace(" ", "", $newSecret);
        if (strlen($s)%8 != 0) {
            array_push($this->errors, "Secrets are typically 16, 24, or 32 characters in length (excluding spaces).");
        }
        if (!ctype_xdigit($s)) {
            array_push($this->errors, "Secrets only contain hexadecimal digits (numbers and the letters a-f)");
        }
        if (empty($this->errors)) {
            $this->hotpCounter = 0;
            $this->secret = str_replace(" ", "", $s);
        }
    }

    /**
     * Unlock user account
     */
    function unlock($unlocker) {
        global $dbh;
        $ps = $dbh->prepare("UPDATE User set invalidLogins = 0 where userName=:userName");
        $ps->execute(array(":userName"=>$this->userName));
        $this->log( array("message"=>"Account unlocked by " . htmlentities($unlocker)));
    }

    function validLogin($aLog=array()) {
        global $dbh;
        $ps = $dbh->prepare("UPDATE User set invalidLogins = 0 where userName=:userName");
        $ps->execute(array(":userName"=>$this->userName));
        $aLog["status"]="Success";
        $this->log( $aLog );
    }

    function hotpResync($passPhrase1, $passPhrase2, $passPhrase3) {
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
        // Not able to sync. Return 0
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
