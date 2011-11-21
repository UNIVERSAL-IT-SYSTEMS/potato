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
        if ( $user->checkOTP($testPassPhrase) ) {
            if ( ! $user->isMemberOf($groupUser) ) {
                $_SESSION['msgWarning'] = "FAIL! User is not a member of the \"${groupUser}\" access group.";
                $user->invalidLogin();
                $user->log("Invalid login. User is not a member of ${groupUser}.");
            } elseif ( ! $user->hasPin() ) {
                $_SESSION['msgWarning'] = "FAIL! Account has no PIN.";
                $user->invalidLogin();
                $user->log("Invalid login. Account has no pin.");
            } elseif ( ! $user->hasToken() ) {
                $_SESSION['msgWarning'] = "FAIL! Account has no token.";
                $user->invalidLogin();
                $user->log("Invalid login. Account has no token.");
            } elseif ( $user->isLockedOut() ) {
                $_SESSION['msgWarning'] = "FAIL! Account locked out due to too many incorrect login attempts. Please contact the helpdesk to reset your account.";
                $user->invalidLogin();
                $user->log("Invalid login. Account locked out.");
            } elseif ( $user->replayAttack($testPassPhrase)) {
                $_SESSION['msgWarning'] = "FAIL! Passphrase has been used before, and is no longer valid.";
                $user->invalidLogin();
                $user->log("Invalid login. OTP replay.");
            } else {
                $_SESSION['msgInfo'] = "ACCEPT! Login was successful.";
                $user->validLogin("token testing area");
            }
        } else {
            $_SESSION['msgWarning'] = "FAIL! Login was unsuccessful.";
            $user->invalidLogin();
            $user->log("Invalid login");
        }
    } catch (NoSuchUserException $ignore) {
        $_SESSION['msgWarning'] = "FAIL! No token registered for this user.";
        $user->invalidLogin();
        $user->log("Invalid login. Account has no token.");
    }
}

include 'header.php';

?>
<h1>Token testing area</h1>
<p>Use this area to test your token. Please be aware that five consecutive incorrect authentication attempts will result in the account being locked out.</p>
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
