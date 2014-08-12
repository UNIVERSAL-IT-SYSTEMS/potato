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
$page->prepTabBar();

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

function addVisibilityToggle($control, $id) {
    global $page;
    $page->addJsOnLoad(sprintf('document.getElementById("%s").addEventListener("click", function(event) { toggleVisibility("%s"); event.preventDefault(); event.returnValue=false; });', $control, $id));
}

function getPopup($id, $linktext, $title, $contents) {
    addVisibilityToggle("ctr-$id", $id);
    addVisibilityToggle("btn-$id", $id);
    $retval = sprintf('<li><a href="#" id="ctr-%s">%s</a>' . "\n", $id, $linktext);
    $retval .= sprintf('<div class="instructions" id="%s">' . "\n", $id);
    $retval .= sprintf('<div class="closebutton" id="btn-%s"></div>' . "\n", $id);
    $retval .= '<div class="instructioncontents">' . "\n";
    $retval .= sprintf('<h2>%s</h2>'."\n", $title);
    $retval .= $contents;
    $retval .= '    <p>Setup is now complete. Proceed to the <a href="testtoken.php">token testing area</a> to test your new token</p>' . "\n";
    $retval .= "</div>\n";
    $retval .= "</div>\n";
    $retval .= "</li>\n";
    return $retval;
}

$instructions = getPopup('android', 'Android', 'Android',
'                <p>Install the <a href="https://play.google.com/store/apps/details?id=nu.kelvin.potato">Potato</a>
                  client app from Google play (previously Android Market). If you\'re unable to locate the
                  app, simply input this link <a href="http://bit.ly/NZo8dK">bit.ly/NZo8dK</a>
                  in your Android web browser, and you\'ll be redirected to the
                  app. Once installed, follow these steps:</p>
                <ol>
                  <li>Click the "Add profile" button (in Android 2.x, begin by clicking the menu button), and choose a memorable name for your token.
                    For example: "' . $orgName . '"</li>
                  <li>Your phone will also display a 16 character "Secret". Enter the secret in this web interface. Do not write this secret down anywhere else.</li>
                  <li>Click "Save profile"</li>
                  <li>Register a PIN in this web interface</li>
                </ol>');

$instructions .= getPopup('iphone', 'iPhone', 'iPhone',
'                <p>Install the <a href="http://itunes.apple.com/us/app/mobile-otp/id328973960&mt=8">iOTP</a> app from app store, and follow these steps:</p>
                <ol>
                  <li>On first start of the app, you will be prompted to create an account</li>
                  <li>Select a memorable name for your account; for example "' . $orgName . '"</li>
                  <li>Click "Generate secret", and select either 16 or 32 characters</li>
                  <li>Your phone will now display a 16 (or 32) character "Secret". Enter the secret in this web interface. Do not write this secret down anywhere else.</li>
                  <li>Register a PIN in this web interface</li>
                </ol>');

$instructions .= getPopup('midlet', 'Java MIDlet compatible phone (such as older non-smartphone SonyEricsson, Nokia, or Blackberry phones)', 'Java MIDlet',
'                <p>Install the <a href="http://itunes.apple.com/us/app/mobile-otp/id328973960&mt=8">iOTP</a> app from app store, and follow these steps:</p>
                <ol>
                  <li>On first start of the app, you will be prompted to create an account</li>
                  <li>Select a memorable name for your account; for example "' . $orgName . '"</li>
                  <li>Click "Generate secret", and select either 16 or 32 characters</li>
                  <li>Your phone will now display a 16 (or 32) character "Secret". Enter the secret in this web interface. Do not write this secret down anywhere else.</li>
                  <li>Register a PIN in this web interface</li>
                </ol>');


$page->addJsOnLoad('document.getElementById("yubikeyDetailShow").addEventListener("click", function(event) { setVisibility("yubikeyDetail", true); event.preventDefault(); event.returnValue=false; });');
$page->addJsOnLoad('document.getElementById("yubikeyDetailHide").addEventListener("click", function(event) { setVisibility("yubikeyDetail", false); });');
$instructions .= getPopup('yubikey', 'Yubikey dongle', 'Yubikey',
'                <p>For users that do not have a mobile phone for use as a token,
                  standard HOTP tokens are also supported; for instance the
                  <a href="http://www.yubico.com/">Yubikey</a>. Here are
                  step-by-step instructions for initializing your Yubikey
                  for first time use.</p>

                <ol>
                  <li>Download and install the <a href="http://www.yubico.com/personalization-tool">Yubikey personalization tool from the Yubico site</a></li>
                  <li>Start the tool and select the OATH-HOTP mode</li>
                  <li>Select the "Quick" programming mode<br />
                    <div id="yubikeyDetail" class="photobox">
                      <img src="images/yubikey.png" id="yubikeyDetailHide" alt="" />
                    </div>
                    <a href="images/yubikey.png" id="yubikeyDetailShow"><img src="images/yubikey_thumb.jpg" alt=""><br />
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
                <p>As the HOTP tokens don\'t have PIN-codes, you need to enter your
                  PIN as the first part of your passphrase when logging in.
                  For example: if your PIN-code is <strong>"1234"</strong>, you need to focus
                  the password field, and enter "1234" before pressing the Yubikey button for a total
                  password length of 10 characters.</p>
');

$instructions .= getPopup('winphone', 'Windows Phone', 'Windows Phone',
'                <p>Install the <a href="http://www.windowsphone.com/en-US/apps/4eca404c-4936-4295-819a-8b4b85bcd592">Yamotp</a> app from the Windows Phone Marketplace, and follow these steps:</p>
                <ol>
                  <li>Select "add profile"</li>
                  <li>Select a memorable name for your token. For example: "' . $orgName . '"</li>
                  <li>Your phone will auto-initialize the 16 character "Secret Key". Enter the secret in this web interface. Do not write this secret down anywhere else.</li>
                  <li>Once your secret is safely registered here, click the "Save" button" on your phone.</li>
                  <li>Register a PIN in this web interface</li>
                </ol>
');

####################################################################################
# Page output begins here
# all onload javascript must be loaded by this point

$page->printHeader();

echo '<p>In order to use the Mobile OTP service, you must configure your mobile.
Click for detailed instructions for your phone:
    <ul>';
echo $instructions;
echo "    </ul>\n";
echo "  </p>\n";

if ( $user->hasToken() ) {
    echo '<div id="infoSecret">' . "\n";
    echo '<button type="button" name="Reset" onclick="setVisibility(\'secret\', true); setVisibility(\'infoSecret\', false); document.getElementById(\'focusSecret\').focus(); return(false); ">Change your token secret...</button>' . "\n";
    echo "</div>\n";
}
?>

<div id="secret" <?php echo $user->hasToken() ? 'style="display: none;"' : '' ?>>
    <form method="post" action="index.php?userName=<?php echo urlencode($user->getUserName()) ?>" autocomplete="off"> 
        <input type="hidden" name="CSRFToken" value="FIXME" />
        <table>
            <tr>
                <th>Secret:</th>
                <td><input id="focusSecret" type="text" name="secret" value="" size="32" autocomplete="off" /></td>
                <td><input id="submit" type="submit" value="Save"></td>
            </tr>
        </table>
        <input type="hidden" name="action" value="updateSecret">
    </form>
</div>

<?php
if ( $user->hasPin() ) {
    echo '<div id="infoPin">' . "\n";
    echo '<button type="button" name="Change your pin..." value="Change your pin..." onclick="setVisibility(\'pin\', true); setVisibility(\'infoPin\', false); document.getElementById(\'focusPin\').focus(); return(false); ">Change your pin...</button>' . "\n";
    echo "</div>\n";
}
?>
<div id="pin" <?php echo ($user->hasPin() || !$user->hasToken()) ? 'style="display: none;"' : '' ?>>
    <form method="post" action="index.php?userName=<?php echo urlencode($user->getUserName()) ?>" autocomplete="off">
        <input type="hidden" name="CSRFToken" value="FIXME" />
        <table>
            <tr>
                <th>Pin:</th>
                <td><input id="focusPin" type="password" name="pin" value="" size="10" autocomplete="off" /></td>
                <td><input id="submit" type="submit" value="Save"></td>
            </tr>
        </table>
        <input type="hidden" name="action" value="updatePin">
    </form>
</div>

<?php
if (!$user->hasToken() || !$user->hasPin()) {
    echo '<script type="text/javascript">' . "\n";
    echo '    document.getElementById("' . ($user->hasToken() ? 'focusPin' : 'focusSecret') . '").focus();' . "\n";
    echo "</script>\n";
}

$page->printFooter();
?>
