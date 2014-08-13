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
$page->prepTabBar();

if ( ! empty($_POST['testPassPhrase']) ) {
    $testPassPhrase = $_POST['testPassPhrase'];

    try {
        $user->verifySanity();
        if ( $user->checkOTP($testPassPhrase) ) {
            if ( ! $user->isMemberOf($groupUser) ) {
                $_SESSION['msgWarning'] = "Valid login, but the user is not a member of the \"${groupUser}\" access group.";
                $user->invalidLogin( array( "message"=>"Valid login, but user is not a member of ${groupUser}", "idNAS"=>"token testing area"));
            } elseif ( $user->isLockedOut() ) {
                $_SESSION['msgWarning'] = "Valid login, but the account is locked out due to too many incorrect login attempts. Please contact the helpdesk to reset your account.";
                $user->invalidLogin( array( "message"=>"Valid login, but account locked out", "idNAS"=>"token testing area"));
            } elseif ( $user->isThrottled() ) {
                $_SESSION['msgWarning'] = "Valid login, but there have been too many failed login attempts from this account lately. Please wait " . $throttleLoginTime . " seconds before trying again.";
                $user->invalidLogin( array( "message"=>"Valid login, but login denied due to throttling", "idNAS"=>"token testing area"));
            } elseif ( $user->replayAttack($testPassPhrase)) {
                $_SESSION['msgWarning'] = "Passphrase has been used before, and is no longer valid.";
                $user->invalidLogin( array( "message"=>"OTP replay", "idNAS"=>"token testing area"));
            } else {
                $_SESSION['msgInfo'] = "Authentication was successful.";
                $user->validLogin( array("idNAS"=>"token testing area"));
            }
        } else {
            // Check for the most common cause of failed logins: clock diff
            if ($diff = $user->checkClockDiff($testPassPhrase)) {
                // Make a nicely formatted time diff to tell the user what's wrong
                $offset = ($diff>0?"-":"") . gmdate( "H:i:s", abs($diff) );
                $_SESSION['msgWarning'] = "Authentication failed due to incorrect time on user token. Adjust the token clock by " . $offset . ", and try again.";
                $user->invalidLogin( array( "idNAS"=>"token testing area",
                                            "message"=>"Failed due to incorrect time on user token (" . $offset . ")"));
            } else {
                $_SESSION['msgWarning'] = "Authentication failed.";
                $user->invalidLogin( array( "idNAS"=>"token testing area"));
            }
        }
    } catch (NoSuchUserException $ignore) {
        $_SESSION['msgWarning'] = "FAIL! No token and/or PIN registered for this user.";
    }
}

$page->printHeader();

echo "<p>Use this area to test your token.\n";

if ( isset($invalidLoginLimit) ) {
    echo "Please be aware that " . $invalidLoginLimit . " consecutive incorrect authentication attempts will result in the account being locked out.";
}
?>
</p>
<p>If you are using a time-based token, it is imperative that the clock on your mobile device is correct, and in the right time zone. If you are having problems
getting your token to work, this is the first thing you should check.</p>

<form method="post" action="<?php echo $page->getUrl("testtoken.php")  ?>" autocomplete="off"> 
    <table>
        <tr>
            <th>Username:</th> 
            <td><?php echo htmlentities($user->getUserName()) ?></td> 
        </tr>

        <tr>
            <th>Passphrase:</th>
            <td><input type="text" name="testPassPhrase" value="" size="10" id="focusme" autofocus="autofocus" /></td>
        </tr>
        <tr>
            <td></td>
            <td><button type="submit">Test</button></td>
        </tr> 
    </table>
</form>

<script type="text/javascript">
    document.getElementById("focusme").focus();
</script>

<?php
$page->printFooter();
?>
