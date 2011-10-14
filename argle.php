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
include "session.php";

$user = new User();
if ( isset($_GET['userName']) ) {
    $userName = $_GET['userName'];

    # Only allow edit of oneself, unless you're an admin
    if ( ! $currentUser->isAdmin() 
         && $userName != $currentUser->userName ) {
        $_SESSION['msgWarning'] = "You are not an administrator. You are only allowed to edit your own account.";
        $userName = $currentUser->userName;
    }
} else {
    $userName = $currentUser->userName;
}
try {
    $user->fetch($userName);
} catch ( NoSuchUserException $ignore ) {
}

if ( isset($_POST['action']) ) {
    switch ($_POST['action']) {
        case "updatePin":
            $user->setPin($_POST['pin']);
            break;
        case "updateSecret":
            $user->setSecret($_POST['secret']);
            break;
    }

    if ( empty($user->errors) ) {
        $user->save();
        switch ($_POST['action']) {
            case "updatePin":
                $_SESSION['msgInfo'] = "New PIN saved";
                $user->log("PIN initialized");
                break;
            case "updateSecret":
                $_SESSION['msgInfo'] = "New token secret saved";
                $user->log("Token initialized");
                break;
        }
    } else {
        $_SESSION['msgWarning'] = $user->getErrors();
    }
}

include 'header.php';
?>


<?php

if ( $currentUser->userName != $userName ) {
    // Editing someone else. Add some context, and ignore the detailed instructions
    echo "<h1>User settings: " . htmlentities($userName) . "</h1>";
} else {

?>


<h1>User settings</h1>
<p>In order to use the Mobile OTP service, you must configure your mobile.
What type of phone do you have? Click for instructions:
    <ul>
        <li><a href="#" onclick="setVisibility('android', true); return(false);">Android</a>
            <div class="instructions" id="android">
              <div class="closebutton" onclick="setVisibility('android', false);">[close]</div>
              <div class="instructioncontents">
                <h2>Android</h2>
                <p>Download the <a href="http://www.androidpit.com/en/android/market/apps/app/net.marinits.android.droidotp/DroidOTP">DroidOTP</a> app from Google Market</p>
                <p></p>
              </div>
            </div>
        </li>

        <li><a href="#" onclick="setVisibility('iphone', true); return(false);">iPhone</a>
            <div class="instructions" id="iphone">
              <div class="closebutton" onclick="setVisibility('iphone', false);">[close]</div>
              <div class="instructioncontents">
                <h2>iPhone</h2>
                <p>Download the <a href="http://itunes.apple.com/us/app/mobile-otp/id328973960&mt=8">iOTP</a> app from iTMS</p>
                <p></p>
              </div>
            </div>
        </li>

        <li><a href="#" onclick="setVisibility('midlet', true); return(false);">JAVA MIDlet compatible phone</a> (such as older non-smartphone SonyEricsson or Nokias)
            <div class="instructions" id="midlet">
              <div class="closebutton" onclick="setVisibility('midlet', false);">[close]</div>
              <div class="instructioncontents">
                <h2>Java MIDlet</h2>
                <p>Start the web browser on your phone, and enter the following address:<br />
                <strong><pre>http://motp.sf.net/MobileOTP.jar</pre></strong><br />
                (capitalization matters).</p>
                <p></p>
              </div>
            </div>
        </li>
    </ul>
</p>

<?php
}

if ( $user->hasToken() ) {
    echo '<div id="infoSecret">' . "\n";
    echo "A token is already registered to this account.\n";
    echo '<input type="button" name="Reset" value="Change token secret..." onclick="setVisibility(\'secret\', true); setVisibility(\'infoSecret\', false); document.getElementById(\'focusSecret\').focus(); return(false); "/>' . "\n";
    echo "</div>\n";
}
?>

<div id="secret" <?php echo $user->hasToken() ? 'style="display: none;"' : '' ?>>
    <form method="post" action="index.php?userName=<?php echo urlencode($user->userName) ?>"> 
        <table>
            <tr>
                <th>Secret:</th>
                <td><input id="focusSecret" type="text" name="secret" value="" size="32" /></td>
                <td><input id="submit" type="submit" value="Save"></td>
            </tr>
        </table>
        <input type="hidden" name="action" value="updateSecret">
    </form>
</div>

<?php
if ( $user->hasPin() ) {
    echo '<div id="infoPin">' . "\n";
    echo "A pin is already registered to this account.\n";
    echo '<input type="button" name="Change pin..." value="Change pin..." onclick="setVisibility(\'pin\', true); setVisibility(\'infoPin\', false); document.getElementById(\'focusPin\').focus(); return(false); "/>' . "\n";
    echo "</div>\n";
}
?>
<div id="pin" <?php echo ($user->hasPin() || !$user->hasToken()) ? 'style="display: none;"' : '' ?>>
    <form method="post" action="index.php?userName=<?php echo urlencode($user->userName) ?>"> 
        <table>
            <tr>
                <th>Pin:</th>
                <td><input id="focusPin" type="password" name="pin" value="" size="10" /></td>
                <td><input id="submit" type="submit" value="Save"></td>
            </tr>
        </table>
        <input type="hidden" name="action" value="updatePin">
    </form>
</div>

<script type="text/javascript">
    function setfocus() {
        idToFocus = "<?php echo $user->hasToken() ? 'focusPin' : 'focusSecret' ?>";
        domUsername = document.getElementById(idToFocus);
        domUsername.focus();
    }
    window.onload=setfocus;
</script>
<?php
include 'footer.php';
?>
