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

$user = new User();
if ( isset($_GET['userName']) ) {
    $userName = $_GET['userName'];

    # Only allow edit of oneself, unless you're an admin
    if ( ! $currentUser->isAdmin() 
         && $userName != $currentUser->getUserName() ) {
        $_SESSION['msgWarning'] = "You are not an administrator. You are only allowed to edit your own account.";
        $userName = $currentUser->getUserName();
    }
} else {
    $userName = $currentUser->getUserName();
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
                $user->log( array("message"=>"PIN initialized"));
                break;
            case "updateSecret":
                $_SESSION['msgInfo'] = "New token secret saved";
                $user->log( array("message"=>"Token initialized"));
                break;
        }
    } else {
        $_SESSION['msgWarning'] = $user->getErrors();
    }
}

include 'header.php';
?>


<?php

if ( $currentUser->getUserName() != $user->getUserName() ) {
    echo "<h1>User settings: " . htmlentities($user->getUserName()) . "</h1>";
    echo "<p><b>User fullname: </b>";
    echo '<a href="logviewer.php?userName=' . urlencode($user->getUserName()) . '" title="View user log">';
    echo htmlentities($user->getFullName());
    echo ' <img src="images/logviewer.png" alt="View logs" title="View logs" /></a>';
    echo "</p>\n";
} else {
    echo "<h1>User settings</h1>";
}

?>


<p>In order to use the Mobile OTP service, you must configure your mobile.
Click for detailed instructions for your phone:
    <ul>
        <li><a href="#" onclick="toggleVisibility('android'); return(false);">Android</a>
            <div class="instructions" id="android">
              <div class="closebutton" onclick="toggleVisibility('android');"></div>
              <div class="instructioncontents">
                <h2>Android</h2>
                <p>Install the <a href="https://play.google.com/store/apps/details?id=nu.kelvin.potato">Potato</a> 
                  client app from Google play. If you're unable to locate the 
                  app, simply input this link <a href="http://bit.ly/NZo8dK">bit.ly/NZo8dK</a> 
                  in your Android web browser, and you'll be redirected to the 
                  app. Once installed, follow these steps:</p>
                <ol>
                  <li>Click add, and choose a memorable name for your token. For example: "Work"</li>
                  <li>Your phone will also display a 16 character "Secret". Enter the secret in this web interface. Do not write this secret down anywhere else.</li>
                  <li>Click "Save profile"</li>
                  <li>Register a PIN in this web interface</li>
                </ol>
                <p>Setup is now complete. Proceed to the <a href="testtoken.php">token testing area</a> to test your new token</p>
              </div>
            </div>
        </li>

        <li><a href="#" onclick="toggleVisibility('iphone'); return(false);">iPhone</a>
            <div class="instructions" id="iphone">
              <div class="closebutton" onclick="toggleVisibility('iphone');"></div>
              <div class="instructioncontents">
                <h2>iPhone</h2>
                <p>Install the <a href="http://itunes.apple.com/us/app/mobile-otp/id328973960&mt=8">iOTP</a> app from app store, and follow these steps:</p>
                <ol>
                  <li>On first start of the app, you will be prompted to create an account</li>
                  <li>Select a memorable name for your account; for example "Work"</li>
                  <li>Click "Generate secret", and select either 16 or 32 characters</li>
                  <li>Your phone will now display a 16 (or 32) character "Secret". Enter the secret in this web interface. Do not write this secret down anywhere else.</li>
                  <li>Register a PIN in this web interface</li>
                </ol>
                <p>Setup is now complete. Proceed to the <a href="testtoken.php">token testing area</a> to test your new token</p>
              </div>
            </div>
        </li>

        <li><a href="#" onclick="toggleVisibility('midlet'); return(false);">JAVA MIDlet compatible phone</a> (such as older non-smartphone SonyEricsson or Nokias)
            <div class="instructions" id="midlet">
              <div class="closebutton" onclick="toggleVisibility('midlet');"></div>
              <div class="instructioncontents">
                <h2>Java MIDlet</h2>
                <p>Start the web browser on your phone, and enter the following address:<br />
                <pre>http://motp.sf.net/MobileOTP.jar</pre>
                <em>(note: capitalization matters)</em></p>

                <p>Confirm that you want to install the program. Once installed, the app will be prompting you for your PIN code. In order to initialize the app for use, follow these steps:</p>
                <ol>
                  <li>Enter PIN code <strong>"0000"</strong></li>
                  <li>The token should now prompt you for <strong>"25 random keys"</strong>. Go ahead and enter 25 random numbers and klick "Ok"</li>
                  <li>The token now presents your 16 character "Init-Secret" in the display. Enter that secret into this web interface. Do not write this secret down anywhere else.</li>
                  <li>Once you have entered a "Secret", you must also select a PIN. This action is also performed in this web interface</li>
                </ol>
                <p>Setup is now complete. Proceed to the <a href="testtoken.php">token testing area</a> to test your new token</p>
              </div>
            </div>
        </li>
        <li><a href="#" onclick="toggleVisibility('yubikey'); return(false);">Yubikey dongle</a>
            <div class="instructions" id="yubikey">
              <div class="closebutton" onclick="toggleVisibility('yubikey');"></div>
              <div class="instructioncontents">

                <h2>Yubikey administration</h2>
                <p>For users that do not have a mobile phone for use as a token, 
                  standard HOTP tokens are also supported; for instance the
                  <a href="http://www.yubico.com/">Yubikey</a>. Here are 
                  step-by-step instructions for initializing your Yubikey
                  for first time use.</p>

                <ol>
                  <li>Download and install the <a href="http://www.yubico.com/personalization-tool">Yubikey personalization tool from the Yubico site</a></li>
                  <li>Start the tool and select the OATH-HOTP mode</li>
                  <li>Select the "Quick" programming mode<br />
                    <div id="yubikeyDetail" class="photobox" onclick="setVisibility('yubikeyDetail', false);">
                      <img src="images/yubikey.png" />
                    </div>
                    <a href="images/yubikey.png" onclick="setVisibility('yubikeyDetail', true); return(false);"><img src="images/yubikey_thumb.jpg"><br />
                    (click for larger picture)</a>
                  </li>
                  <li>Select "Configuration Slot 1"</li>
                  <li>Deselect the "OATH Token Identifier"</li>
                  <li>Set "HOTP Length" to "6 Digits"</li>
                  <li>Deselect "Hide secret"</li>
                  <li>Click "Regenerate"</li>
                  <li>Click "Write Configuration"</li>
                  <li>Copy-paste the "Secret Key (20 bytes Hex)" as your token secret in this web interface</li>
                </ol>

                <h2>Important</h2>
                <p>As the HOTP tokens don't have PIN-codes, you need to enter your
                  PIN as the first part of your passphrase when logging in.
                  For example: if your PIN-code is <strong>"1234"</strong>, you need to focus
                  the password field, and enter "1234" before pressing the Yubikey button for a total
                  password length of 10 characters.
                </p>
              </div>
            </div>
        </li>

        <li><a href="#" onclick="toggleVisibility('winphone'); return(false);">Windows Phone</a>
            <div class="instructions" id="winphone">
              <div class="closebutton" onclick="toggleVisibility('winphone');"></div>
              <div class="instructioncontents">
                <h2>Windows Phone</h2>
                <p>Install the <a href="http://www.windowsphone.com/en-US/apps/4eca404c-4936-4295-819a-8b4b85bcd592">Yamotp</a> app from the Windows Phone Marketplace, and follow these steps:</p>
                <ol>
                  <li>Select "add profile"</li>
                  <li>Select a memorable name for your token. For example: "Work"</li>
                  <li>Your phone will auto-initialize the 16 character "Secret Key". Enter the secret in this web interface. Do not write this secret down anywhere else.</li>
                  <li>Once your secret is safely registered here, click the "Save" button" on your phone.</li>
                  <li>Register a PIN in this web interface</li>
                </ol>
                <p>Setup is now complete. Proceed to the <a href="testtoken.php">token testing area</a> to test your new token</p>
              </div>
            </div>
        </li>

    </ul>
</p>

<?php

if ( $user->hasToken() ) {
    echo '<div id="infoSecret">' . "\n";
    // echo "A token is already registered to this account.\n";
    echo '<input type="button" name="Reset" value="Change your token secret..." onclick="setVisibility(\'secret\', true); setVisibility(\'infoSecret\', false); document.getElementById(\'focusSecret\').focus(); return(false); "/>' . "\n";
    echo "</div>\n";
}
?>

<div id="secret" <?php echo $user->hasToken() ? 'style="display: none;"' : '' ?>>
    <form method="post" action="index.php?userName=<?php echo urlencode($user->getUserName()) ?>"> 
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
    // echo "A pin is already registered to this account.\n";
    echo '<input type="button" name="Change your pin..." value="Change your pin..." onclick="setVisibility(\'pin\', true); setVisibility(\'infoPin\', false); document.getElementById(\'focusPin\').focus(); return(false); "/>' . "\n";
    echo "</div>\n";
}
?>
<div id="pin" <?php echo ($user->hasPin() || !$user->hasToken()) ? 'style="display: none;"' : '' ?>>
    <form method="post" action="index.php?userName=<?php echo urlencode($user->getUserName()) ?>"> 
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
