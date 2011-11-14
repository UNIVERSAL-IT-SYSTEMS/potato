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
include "mschap.php";

$options = getopt("u:p:c:n:q:s:");

$userName = strtolower($options["u"]);
$passPhrase = $options["p"];

$mschapAuthChallenge = pack( 'H*', substr($options["c"], 2) );
$mschapPeerChallenge = pack( 'H*', substr($options["n"], 6, 32) );
$mschapResponse = pack( 'H*', $options["q"] );

$clientShortName = $options["s"];

$mschap = (empty($passPhrase) && !empty($mschapAuthChallenge) && !empty($mschapPeerChallenge)) ? true : false;

if ( substr( $userName, -6 ) == ".guest" ) {
    // Guest login
    $guestName = substr( $userName, 0, -6 );
    $guest = new Guest();
    
    try {
        $guest->fetch($guestName);

        if ( $mschap ) {
            $mschapAuthResponse = GenerateNTResponse($mschapPeerChallenge, $mschapAuthChallenge, $userName, $guest->password);

            # Set cleartext password for mschapv2 module to either pass or fail
            echo "Cleartext-Password := \"" . $guest->password . "\"";
            if ($mschapAuthResponse == $mschapResponse ) {
                exit(0);
            }
            // Exit with noop
            exit(8);
        } else {
            # Cleartext password available; see if it's the correct one
            exit ($guest->password == $passPhrase ? 0 : 1);
        }
    } catch (NoGuestException $ignore) {
        if ($mschap) {
            // Set a random dummy password for the mschapv2 module to fail on
            echo "Cleartext-Password := \"" . md5(rand()) . "\"";
            // Exit with noop
            exit(8);
        }
        exit(1);
    }
}

try {
    $user = new User();
    $user->fetch($userName);

    $loginOk = false;

    if ( $mschap ) {
        $loginOk = $user->checkOTPmschap($mschapPeerChallenge, $mschapAuthChallenge, $mschapResponse);
    } else {
        $loginOk = $user->checkOTP($passPhrase);
    }

    if ( $loginOk ) {
        $posixGroupUser = posix_getgrnam($groupUser);
        if ( ! in_array( $userName, $posixGroupUser['members'] ) ) {
            // User not member of access group
            $user->invalidLogin();
            $user->log("Valid login, but user is not a member of ${groupUser}. [ " . $clientShortName . " ]");
        } elseif ( ! $user->hasPin() ) {
            // Account has no PIN
            $user->invalidLogin();
            $user->log("Invalid login. No pin registered to this user. [ " . $clientShortName . " ]");
        } elseif ( ! $user->hasToken() ) {
            // Account has no token
            $user->invalidLogin();
            $user->log("Invalid login. No token registered to this user. [ " . $clientShortName . " ]");
        } elseif ( $user->isLockedOut() ) {
            // Account locked out
            $user->invalidLogin();
            $user->log("Valid login, but account locked out. [ " . $clientShortName . " ]");
        } elseif ( $user->replayAttack()) {
            // Replay attack
            $user->invalidLogin();
            $user->log("Invalid login. OTP replay. [ " . $clientShortName . " ]");
        } else {
            $user->validLogin($clientShortName);
            if ($mschap) {
                echo "Cleartext-Password := \"" . $user->passPhrase . "\"";
            }
            exit(0);
        }
    } else {
        $user->invalidLogin();
        $user->log("Invalid login [ " . $clientShortName . " ]");
    }
} catch (NoSuchUserException $ignore) {
}

if ($mschap) {
    // Set a random dummy password for the mschapv2 module to fail on
    echo "Cleartext-Password := \"" . md5(rand()) . "\"";
    // Exit with noop
    exit(8);
}
exit(1);

?>
