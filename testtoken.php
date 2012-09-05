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
include "session.php";

$testUserName = empty($_POST['testUserName']) ? $currentUser->getUserName() : $_POST['testUserName'];

if ( ! empty($_POST['testPassPhrase']) ) {
    $testPassPhrase = $_POST['testPassPhrase'];

    // Only admins can test other people's tokens
    if ( ! $currentUser->isAdmin() ) {
        $testUserName = $currentUser->getUserName();
    }

    try {
        $user = new User();
        $user->fetch($testUserName);
        $user->verifySanity();
        if ( $user->checkOTP($testPassPhrase) ) {
            if ( ! $user->isMemberOf($groupUser) ) {
                $_SESSION['msgWarning'] = "Valid login, but the user is not a member of the \"${groupUser}\" access group.";
                $user->log("FAIL! Valid login, but user is not a member of ${groupUser}", "token testing area");
            } elseif ( $user->isLockedOut() ) {
                $_SESSION['msgWarning'] = "Valid login, but the account is locked out due to too many incorrect login attempts. Please contact the helpdesk to reset your account.";
                $user->invalidLogin();
                $user->log("FAIL! Valid login, but account locked out", "token testing area");
            } elseif ( $user->isThrottled() ) {
                $_SESSION['msgWarning'] = "Valid login, but there have been too many failed login attempts from this account lately. Please wait " . $throttleLoginTime . " seconds before trying again.";
                $user->invalidLogin();
                $user->log("FAIL! Valid login, but login denied due to throttling", "token testing area");
            } elseif ( $user->replayAttack($testPassPhrase)) {
                $_SESSION['msgWarning'] = "FAIL! Passphrase has been used before, and is no longer valid.";
                $user->invalidLogin();
                $user->log("FAIL! Invalid login. OTP replay", "token testing area");
            } else {
                $_SESSION['msgInfo'] = "ACCEPT! Login was successful.";
                $user->validLogin("token testing area");
            }
        } else {
            $_SESSION['msgWarning'] = "FAIL! Login was unsuccessful.";
            $user->invalidLogin();
            $user->log("FAIL! Invalid login", "token testing area");
        }
    } catch (NoSuchUserException $ignore) {
        $_SESSION['msgWarning'] = "FAIL! No token and/or PIN registered for this user.";
    }
}

include 'header.php';

?>
<h1>Token testing area</h1>
<p>Use this area to test your token.

<?php
if ( isset($invalidLoginLimit) ) {
    echo "Please be aware that " . $invalidLoginLimit . " consecutive incorrect authentication attempts will result in the account being locked out.";
}
?>
</p>
<p>If you are using a time-based token, it is imperative that the clock on your mobile device is correct, and in the right time zone. If you are having problems
getting your token to work, this is the first thing you should check.</p>

<form method="post" action="testtoken.php"> 
    <table>
        <tr>
            <th>Username:</th> 
            <td><?php echo $currentUser->isAdmin() ? '<input type="text" name="testUserName" size="10" value="' . htmlentities($testUserName) . '" class="iesux" />' : htmlentities($testUserName) ?></td> 
        </tr>

        <tr>
            <th>Passphrase:</th>
            <td><input type="password" name="testPassPhrase" value="" size="10" id="focusme" class="iesux" /></td>
        </tr> 
        <tr> 
            <td></td>
            <td><input id="submit" type="submit" value="Test"></td>
        </tr> 
    </table>
</form>

<script type="text/javascript">
    function setfocus() {
        domUsername = document.getElementById("focusme");
        domUsername.focus();
    }
    window.onload=setfocus;
</script>

<?php

include 'footer.php';
?>
