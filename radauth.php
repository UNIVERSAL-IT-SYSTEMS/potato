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

$options = getopt("u:p:s:");

$userName = $options["u"];
$passPhrase = $options["p"];
$clientShortName = $options["s"];

if (empty($passPhrase)) {
    exit(8);
}

if ( strtolower(substr( $userName, -6 )) == ".guest" ) {
    // Guest login
    $guestName = substr( $userName, 0, -6 );
    $guest = new Guest();

    try {
        $guest->fetch($guestName);
        # Cleartext password available; see if it's the correct one
        if ($guest->getPassword() == $passPhrase) {
            exit(0);
        }
    } catch (NoGuestException $ignore) {
    }
    // Delay for three seconds before exiting with fail
    sleep(3);
    exit(1);
}

try {
    $user = new User();
    $user->fetch($userName);
    $user->verifySanity();

    if ( $user->checkOTP($passPhrase) ) {
        if ( ! $user->isMemberOf($groupUser) ) {
            // User not member of access group
            $user->invalidLogin();
            $user->log("Valid login, but user is not a member of ${groupUser}. [ " . $clientShortName . " ]");
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
            exit(0);
        }
    } else {
        $user->invalidLogin();
        $user->log("Invalid login [ " . $clientShortName . " ]");
    }
} catch (NoSuchUserException $ignore) {
}

// Delay for three seconds before exiting with fail
sleep(3);
exit(1);

?>
