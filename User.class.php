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

class NoSuchUserException extends Exception { }

class User {
    public $userName;
    protected $secret;
    protected $pin;
    public $invalidLogins;
    public $errors = array();

    function fetch($userName) {
        global $dbh;
        $ps = $dbh->prepare("SELECT * FROM User where userName=:userName");
        $ps->execute(array(":userName"=>$userName));

        $this->userName=$userName;
        if ( $row = $ps->fetch() ) {
            $this->secret=$row['secret'];
            $this->pin=$row['pin'];
            $this->invalidLogins=$row['invalidLogins'];
        } else {
            throw new NoSuchUserException();
        }
    }

    function checkMOTP ($passPhrase) {
        $now = intval( gmdate("U") / 10 );
        $maxDrift = 180/10;
        $validPasswords = array();
        for ( $time = $now - $maxDrift ; $time <= $now + $maxDrift ; $time++ ) {
            $otp = substr( md5($time . $this->secret . $this->pin ), 0, 6);
            array_push($validPasswords, $otp);
        }
        return (in_array($passPhrase, $validPasswords));
    }

    function save() {
        global $dbh;
        $ps = $dbh->prepare("INSERT INTO User (userName, secret, pin) VALUES (:userName, :secret, :pin) ON DUPLICATE KEY UPDATE secret=:secret, pin=:pin");
        $ps->execute(array( ":userName" => $this->userName,
                            ":secret" => $this->secret,
                            ":pin" => $this->pin));
        $ps = $dbh->prepare("INSERT INTO Log (userName, message) VALUES (:userName, :message)");
        $ps->execute(array( ":userName" => $this->userName,
                            ":message" => "Secret or pin initialized"));
    }

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

    function invalidLogin($passPhrase) {
        global $dbh;
        $ps = $dbh->prepare("UPDATE User set invalidLogins = invalidLogins+1 where userName=:userName");
        $ps->execute(array(":userName"=>$this->userName));

        $ps = $dbh->prepare("INSERT INTO Log (userName, passPhrase, message) VALUES (:userName, :passPhrase, :message)");
        $ps->execute(array( ":userName" => $this->userName,
                            ":passPhrase" => $passPhrase,
                            ":message" => "Invalid login"));
    }

    function isAdmin() {
        global $groupAdmin;
        $groupInfo = posix_getgrnam($groupAdmin);
        return (in_array($this->userName, $groupInfo['members']));
    }

    function replayAttack($passPhrase) {
        global $dbh;
        $ps = $dbh->prepare('SELECT * from Log where time > (now() - 360) AND userName=:userName AND passPhrase=:passPhrase AND message="Success"');
        $ps->execute(array(":userName"=>$this->userName,
                            ":passPhrase"=>$passPhrase));

        return ($ps->rowCount() > 0);
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
        $this->secret = $newSecret;
    }

    function unlock() {
        global $dbh;
        $ps = $dbh->prepare("UPDATE User set invalidLogins = 0 where userName=:userName");
        $ps->execute(array(":userName"=>$this->userName));
        $ps = $dbh->prepare("INSERT INTO Log (userName, message) VALUES (:userName, :message)");
        $ps->execute(array( ":userName" => $this->userName,
                            ":message" => "Account unlocked"));
    }

    function validLogin($passPhrase) {
        global $dbh;
        $ps = $dbh->prepare("UPDATE User set invalidLogins = 0 where userName=:userName");
        $ps->execute(array(":userName"=>$this->userName));

        $ps = $dbh->prepare("INSERT INTO Log (userName, passPhrase, message) VALUES (:userName, :passPhrase, :message)");
        $ps->execute(array( ":userName" => $this->userName,
                            ":passPhrase" => $passPhrase,
                            ":message" => "Success"));
    }
}
?>
