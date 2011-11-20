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

if ( ! $currentUser->isAdmin() ) {
    header("Location: index.php");
    exit;
}

$userName = empty($_POST['userName']) ? $currentUser->getUserName() : $_POST['userName'];

$user = new User();

try {
    $user->fetch($userName);
    if ( !empty($_POST['passPhrase']) ) {
        $offset = $user->hotpResync($_POST['passPhrase']);
        if ( $offset == 0 ) {
            $_SESSION['msgWarning'] = "Unable to sync.";
        } else {
            $_SESSION['msgInfo'] = "Token counter synced by " . $offset;
            $user->log("Token counter resynced by " . $offset);
        }
    }
} catch (NoSuchUserException $e) {
    if (!empty($_POST['passPhrase'])) {
        $_SESSION['msgWarning'] = "FAIL! No token registered to account.";
    }
}


include 'header.php';
?>

<h1>HOTP sync</h1>
<p>If a HOTP token (such as a yubikey) gets out of sync, you can use this web interface to resync it.</p>

<form method="post" action="hotpsync.php"> 
    <table> 
        <tr> 
            <th>Username:</th> 
            <td><input type="text" name="userName" value="<?php echo htmlentities($user->getUserName()) ?>" size="20" maxlength="16" /></td> 
        </tr> 
        <tr> 
            <th>Passphrase:</th> 
            <td><input id="focusme" type="text" name="passPhrase" value="" size="20" maxlength="16" /> (do not prefix the passphrase with pin)</td> 
        </tr> 
        <tr>
            <td></td>
            <td><input id="submit" type="submit" value="Sync token"></td>
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
