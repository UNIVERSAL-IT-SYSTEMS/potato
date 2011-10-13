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

include "config.php";
include "User.class.php";
include "Guest.class.php";
include "mschap.php";

$options = getopt("u:p:d:c:n:q:");

$userName = $options["u"];
$passPhrase = $options["p"];

$mschapAuthChallenge = pack( 'H*', substr($options["c"], 2) );
$mschapPeerChallenge = pack( 'H*', substr($options["n"], 6, 32) );
$mschapResponse = pack( 'H*', $options["q"] );


if ( substr( $userName, -6 ) == ".guest" ) {
    // Guest login
    $guestName = substr( $userName, 0, -6 );
    $guest = new Guest();
    
    try {
        $guest->fetch($guestName);

        if ( empty($passPhrase) ) {
            # Unknown authentication mechanism. Probably mschapv2.
            $mschapAuthResponse = GenerateNTResponse($mschapPeerChallenge, $mschapAuthChallenge, $userName, $guest->password);
            if ($mschapAuthResponse == $mschapResponse ) {
                # User auth checks out. Set cleartext password for mschap module to use
                echo "Cleartext-Password := \"" . $guest->password . "\"";
                exit(0);
            }
            exit(1);
        } else {
            # Cleartext password available; see if it's the correct one
            exit ($guest->password == $passPhrase ? 0 : 1);
        }
    } catch (NoGuestException $ignore) {
        exit(1);
    }
}

try {
    $user = new User();
    $user->fetch($userName);

    $loginOk = false;
    $mschap = false;

    if ( !empty($passPhrase) ) {
        $loginOk = $user->checkMOTP($passPhrase);
    } elseif ( !empty($mschapPeerChallenge) && !empty($mschapAuthChallenge) && !empty($mschapResponse) ) {
        $loginOk = $user->checkMOTPmschap($mschapPeerChallenge, $mschapAuthChallenge, $mschapResponse);
        $mschap = true;
    }

    if ( $loginOk ) {
        $posixGroupUser = posix_getgrnam($groupUser);
        if ( ! in_array( $userName, $posixGroupUser['members'] ) ) {
            // User not member of access group
            $user->invalidLogin();
            $user->log("Invalid login. Not member of ${groupUser}.");
        } elseif ( ! $user->hasPin() ) {
            // Account has no PIN
            $user->invalidLogin();
            $user->log("Invalid login. No pin registered to this user.");
        } elseif ( ! $user->hasToken() ) {
            // Account has no token
            $user->invalidLogin();
            $user->log("Invalid login. No token registered to this user.");
        } elseif ( $user->invalidLogins > 4 ) {
            // Account locked out
            $user->invalidLogin();
            $user->log("Invalid login. Account locked out.");
        } elseif ( $user->replayAttack()) {
            // Replay attack
            $user->invalidLogin();
            $user->log("Invalid login. OTP replay");
        } else {
            $user->validLogin();
            if ($mschap) {
                echo "Cleartext-Password := \"" . $user->passPhrase . "\"";
            }
            exit(0);
        }
    } else {
        $user->invalidLogin();
        $user->log("Invalid login");
    }
} catch (NoSuchUserException $ignore) {
}

exit(1);

/*

These are the mschapv2 failure codes as per the spec:
    646 ERROR_RESTRICTED_LOGON_HOURS
    647 ERROR_ACCT_DISABLED
    648 ERROR_PASSWD_EXPIRED
    649 ERROR_NO_DIALIN_PERMISSION
    691 ERROR_AUTHENTICATION_FAILURE
    709 ERROR_CHANGING_PASSWORD
*/

?>
