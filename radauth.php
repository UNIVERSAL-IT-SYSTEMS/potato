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

include "config.php";
include "User.class.php";
include "Guest.class.php";
include "Token.class.php";
include "mschap.php";

try {
    $dbh = new PDO("mysql:host=${dbServer};dbname=${dbName}", $dbUser, $dbPassword);
} catch (Exception $ignore) {
    echo "Database error.";
    exit();
}

$options = getopt("u:p:h:r:s:c:");

$userName = $options["u"];
$passPhrase = $options["p"];

$mschapChallengeHash = pack( 'H*', $options["h"] );
$mschapResponse = pack( 'H*', $options["r"] );

$idNAS = $options["s"];
$idClient = $options["c"];

$mschap = false;
if (empty($passPhrase)) {
    if (empty($mschapChallengeHash) && empty($mschapResponse)) {
        exit();
    } else {
        $mschap=true;
    }
}

if ( strtolower(substr( $userName, -6 )) == ".guest" ) {
    // Guest login
    $guestName = substr( $userName, 0, -6 );
    $guest = new Guest();

    try {
        $guest->fetch($guestName);

        if ($mschap) {
            $pwHash = NtPasswordHash($guest->getPassword());
            $calcResponse = ChallengeResponse($mschapChallengeHash, $pwHash);
            if ($calcResponse == $mschapResponse) {
                echo $guest->getPassword();
            }
        } else {
            # Cleartext password available; see if it's the correct one
            if ($guest->getPassword() == $passPhrase) {
                echo $guest->getPassword();
            }
        }
    } catch (NoGuestException $ignore) {
    }
    exit();
}

try {
    $user = new User();
    $user->fetch($userName);
    $user->verifySanity();

    // See if token caching is enabled for this NAS
    if(isset($tokenCache) && isset($tokenCache[$idNAS])) {
        $token = new Token();
        $token->setTokenLife($tokenCache[$idNAS]);
        if($token->fetch($userName, $idClient, $idNAS)) {
            // A token exists for this user, client, and NAS
            if( $mschap ? $token->checkTokenMschap($mschapChallengeHash, $mschapResponse) : $token->checkToken($passPhrase) ) {
                // The token is valid
                echo $token->getToken();
                $user->log( array("message"=>"Accepted cached token", "idNAS"=>$idNAS, "idClient"=>$idClient));
                exit();
            } else {
                // Invalid token attempted. Delete it.
                $token->delete();
                $user->log( array("message"=>"Cached token deleted", "idNAS"=>$idNAS, "idClient"=>$idClient));
            }
        }
    }

    if ( $mschap ? $user->checkOTPmschap($mschapChallengeHash, $mschapResponse) : $user->checkOTP($passPhrase) ) {
        if ( ! $user->isMemberOf($groupUser) ) {
            // User not member of access group
            $user->invalidLogin( array( "message"=>"Valid login, but user is not a member of ${groupUser}", "idNAS"=>$idNAS, "idClient"=>$idClient));
        } elseif ( $user->isLockedOut() ) {
            // Account locked out
            $user->invalidLogin( array( "message"=>"Valid login, but account locked out", "idNAS"=>$idNAS, "idClient"=>$idClient));
        } elseif ( $user->isThrottled() ) {
            $user->invalidLogin( array( "message"=>"Valid login, but login denied due to throttling", "idNAS"=>$idNAS, "idClient"=>$idClient));
        } elseif ( $user->replayAttack()) {
            // Replay attack
            $user->invalidLogin( array( "message"=>"OTP replay", "idNAS"=>$idNAS, "idClient"=>$idClient));
        } else {
            $user->validLogin( array("idNAS"=>$idNAS, "idClient"=>$idClient));
            echo $user->getPassPhrase();

            // Save the token if token caching is enabled for this NAS
            if(isset($token)) {
                $token->setToken($user->getPassPhrase());
                $token->save();
                $user->log( array("message"=>"Caching token", "idNAS"=>$idNAS, "idClient"=>$idClient));
            }
        }
    } else {
        $user->invalidLogin( array("idNAS"=>$idNAS, "idClient"=>$idClient));
    }
} catch (NoSuchUserException $ignore) {
}

?>
