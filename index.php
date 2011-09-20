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
        Header( "Location:index.php" );
        exit;
    }
} else {
    $userName = $_SESSION['currentUser'];
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
        $_SESSION['msgInfo'] = "Changes saved";
    } else {
        $_SESSION['msgWarning'] = $user->getErrors();
    }
}

include 'header.php';
?>

<h1>User settings</h1>

<div class="qrcode">
    <img src="images/droidotp-qr.png" />
</div>
<p>In order to use the Mobile OTP service, you must register a 
<a href="http://motp.sourceforge.net/">Mobile OTP</a> soft token
to your user account. Please use one of the following apps:
    <ul>
        <li>Android: <a href="http://www.androidpit.com/en/android/market/apps/app/net.marinits.android.droidotp/DroidOTP">DroidOTP</a></li>
        <li>iPhone: <a href="http://itunes.apple.com/us/app/mobile-otp/id328973960&mt=8">iOTP</a></li>
        <li>JAVA MIDlet compatible phones (older non-smartphone SonyEricsson and Nokias, for instance): <a href="http://motp.sourceforge.net/MobileOTP.jar">MobileOTP.jar</a> or 
            <a href="http://motp.sourceforge.net/MobileOTP.jad">MobileOTP.jad</a></li>
    </ul>
    If you're unable to get any of these clients to work, please check the 
<a href="http://motp.sourceforge.net/">Mobile OTP</a> project page for 
additional clients.
</p>

        <p>Once you have properly installed and configured your app, initialize it
and register its secret here.</p>


<script type="text/javascript">
function toggleVisibility( sName ) {
    var domName = document.getElementById( sName );
    domName.style.display = ( domName.style.display=="none" ? "block" : "none" );
}
</script>

<?php
if ( $user->hasToken() ) {
    echo '<div id="infoSecret">' . "\n";
    echo "A token is already registered to this account.\n";
    echo '<input type="button" name="Reset" value="Change token secret..." onclick="toggleVisibility(\'secret\'); toggleVisibility(\'infoSecret\'); document.getElementById(\'focusSecret\').focus(); return(false); "/>' . "\n";
    echo "</div>\n";
}
?>

<div id="secret" <?php echo $user->hasToken() ? 'style="display: none;"' : '' ?>>
    <form method="post" action="index.php?userName=<?php echo htmlentities($user->userName) ?>"> 
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
    echo '<input type="button" name="Change pin..." value="Change pin..." onclick="toggleVisibility(\'pin\'); toggleVisibility(\'infoPin\'); document.getElementById(\'focusPin\').focus(); return(false); "/>' . "\n";
    echo "</div>\n";
}
?>
<div id="pin" <?php echo ($user->hasPin() || !$user->hasToken()) ? 'style="display: none;"' : '' ?>>
    <form method="post" action="index.php?userName=<?php echo htmlentities($user->userName) ?>"> 
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
