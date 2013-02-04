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

if ( !empty($_POST['passPhrase1']) && 
     !empty($_POST['passPhrase2']) && 
     !empty($_POST['passPhrase3']) ) {

    if ( $user->isHOTP() ) {
        $offset = $user->hotpResync($_POST['passPhrase1'], $_POST['passPhrase2'], $_POST['passPhrase3']);
        if ( $offset == 0 ) {
            $_SESSION['msgWarning'] = "Unable to sync.";
        } else {
            $_SESSION['msgInfo'] = "Token counter synced by " . $offset;
            $user->log( array("message"=>"Token counter resynced by " . $offset));
        }
    } else {
        $_SESSION['msgWarning'] = "FAIL! No HOTP token registered to this account.";
    }
}


$page->printHeader();
?>

<h1>HOTP sync</h1>
<p>If a HOTP token (such as a yubikey) gets out of sync, you can use this web interface to resync it. Use
your HOTP token to generate three consecutive passphrases, and enter them here:</p>

<form method="post" action="<?php echo $page->getUrl("hotpsync.php")  ?>" autocomplete="off" onsubmit="return(sanityCheckForm());"> 
    <table> 
        <tr> 
            <th>Username:</th> 
            <td><?php echo htmlentities($user->getUserName()) ?></td> 
        </tr> 
        <tr> 
            <th>Passphrase 1:</th> 
            <td><input id="passPhrase1" type="text" name="passPhrase1" value="" size="20" maxlength="16" /> (do not prefix the passphrase with pin)</td> 
        </tr> 
        <tr>
        <tr> 
            <th>Passphrase 2:</th> 
            <td><input id="passPhrase2" type="text" name="passPhrase2" value="" size="20" maxlength="16" /> (do not prefix the passphrase with pin)</td> 
        </tr> 
        <tr>
        <tr> 
            <th>Passphrase 3:</th> 
            <td><input id="passPhrase3" type="text" name="passPhrase3" value="" size="20" maxlength="16" /> (do not prefix the passphrase with pin)</td> 
        </tr> 
        <tr>
            <td></td>
            <td><input id="submit" type="submit" value="Sync token"></td>
        </tr> 
    </table>
</form>

<script type="text/javascript">
    function sanityCheckForm() {
        if (domPP1.value == "") {
            domPP1.focus();
            return(false);
        }
        if (domPP2.value == "") {
            domPP2.focus();
            return(false);
        }
        if (domPP3.value == "") {
            domPP3.focus();
            return(false);
        }
        return(true);
    }

    domPP1 = document.getElementById("passPhrase1");
    domPP2 = document.getElementById("passPhrase2");
    domPP3 = document.getElementById("passPhrase3");

    domPP1.focus();

</script>


<?php
$page->printFooter();
?>
