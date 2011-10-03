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

$userName = $argv[1];
$passPhrase = $argv[2];


try {
    $user = new User();
    $user->fetch($userName);
    if ( $user->checkMOTP($passPhrase) ) {
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
        } elseif ( $user->replayAttack($passPhrase)) {
            // Replay attack
            $user->invalidLogin();
            $user->log("Invalid login. OTP replay", $passPhrase);
        } else {
            $user->validLogin($passPhrase);
            echo "ACCEPT\n";
            exit(0);
        }
    } else {
        $user->invalidLogin();
        $user->log("Invalid login");
    }
} catch (NoSuchUserException $ignore) {
    // No such user
}

echo "FAIL\n";
exit(1);

?>
