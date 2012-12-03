<?php
/**
 * Potato
 * One-time-password self-service and administration
 * Version 1.2
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

class Token {
    private $userName;
    private $token;
    private $idClient;
    private $idNAS;
    private $tokenLife = "1 DAY";

    // Set the username
    function setUserName($userName) {
        $this->userName = strtolower($userName);
    }

    // Set the token
    function setToken($token) {
        $this->token = $userName;
    }

    // Set the idClient
    function setIdClient($idClient) {
        $this->idClient = $idClient;
    }

    // Set the idNAS
    function setIdNAS($idNAS) {
        $this->idNAS = $idNAS;
    }

    // Fetch token from database if one exists
    // Return true if successful
    function fetch($userName, $idClient, $idNAS) {
        $this->setUserName($userName);
        $this->setIdClient($idClient);
        $this->setIdNAS($idNAS);

        // Return if any required properties are empty
        if (empty($this->userName) || empty($this->idClient) || empty($this->idNAS)) {
            return(false);
        }

        global $dbh;
        $ps = $dbh->prepare("SELECT `token` FROM `User` where `userName`=:userName and `idNAS`=:idNAS and `idClient`=:idClient and `time`>DATE_SUB(now(), INTERVAL " . $this->tokenLife . ")");
        $ps->execute(array(":userName" => $this->userName, 
                           ":idNAS"    => $this->idNAS, 
                           ":idClient" => $this->idClient));

        $this->setToken($ps->fetchColumn());
        return(!empty($this->token));
    }

    /**
     * Verify the validity of the mschapv2 handshake
     *
     * @param string $mschapChallengeHash
     * @param string $mschapResponse
     */
    function checkTokenMschap($mschapChallengeHash, $mschapResponse) {
        $pwHash = NtPasswordHash($this->token);
        $calcResponse = ChallengeResponse($mschapChallengeHash, $pwHash);
        return($calcResponse == $mschapResponse);
    }

    /**
     * Verify that the provided token is valid
     *
     * @param string $token Token to check for validity
     */
    function checkToken($token) {
        return($this->token == $token);
    }

    /**
     * Save the token
     */
    function save() {
        global $dbh;

        $ps = $dbh->prepare("INSERT INTO `TokenCache` (`userName`, `token`, `idClient`, `idNAS`) VALUES (:userName, :token, :idClient, :idNAS)");
        $ps->execute(array( ":userName" => $this->userName,
                            ":token"    => $this->token,
                            ":idClient" => $this->idClient,
                            ":idNAS"    => $this->idNAS));
    }

    /**
     * Delete the token from the database
     */
    function delete() {
        global $dbh;
        $ps = $dbh->prepare("DELETE FROM `TokenCache` where `userName`=:userName AND `idClient`=:idClient AND `idNAS`=:idNAS");
        $ps->execute(array( ":userName" => $this->userName,
                            ":idClient" => $this->idClient,
                            ":idNAS"    => $this->idNAS));
    }

    /**
     * Vacuum expired entries from TokenCache
     */
    function vacuum() {
        global $dbh;
        $dbh->exec("DELETE FROM `TokenCache` where `time`<DATE_SUB(now(), INTERVAL " . $this->tokenLife . ")");
    }

}
?>
