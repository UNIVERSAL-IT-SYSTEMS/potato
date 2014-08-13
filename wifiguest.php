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

if (!isset($wifiGuestSSID)) {
    header("Location: index.php");
}

include "Guest.class.php";

$guest = new Guest();
$guest->setUserName($user->getUserName());

if ( isset($_POST['action']) ) {
    if ($currentUser->isValidCSRFToken($_POST['CSRFToken'])) {
        switch ($_POST['action']) {
            case "generate":
                $guest->generate();
                break;
            case "deactivate":
                $guest->deactivate();
                break;
        }
    } else {
        $_SESSION['msgWarning'] = "Invalid CSRF Token";
    }
}
$page->printHeader();

?>
<p>In order to provide wifi access to <?php echo $orgName; ?> guests, you can activate a
guest account which is only granted access to the external network.</p>

<?php
echo '<form method="POST" action="' . $page->getUrl("wifiguest.php") . '">';
echo '<input type="hidden" name="CSRFToken" value="' . $currentUser->getCSRFToken() . '" />', "\n";
echo "<p>\n";

try {
    $guest->fetch($user->getUserName());
    echo "The following guest account is active:";
    echo "<ul>\n";
    echo "<li>SSID: " . $wifiGuestSSID . "</li>\n";
    echo "<li>Username: " . htmlentities($guest->getUserName()) . "</li>";
    echo "<li>Password: " . $guest->getPassword() . "</li>\n";
    echo "<li>Valid until: " . $guest->getDateExpiration() . "</li>\n";
    echo "</ul>\n";
    echo "</p>\n";
    echo "<p>\n";
    echo "<input type=\"hidden\" name=\"action\" value=\"deactivate\" />\n";
    echo "<input type=\"submit\" value=\"De-activate guest account\" />\n";
} catch (NoGuestException $e) {
?>
There's no active guest account for your account.
<p>
<input type="hidden" name="action" value="generate" />
<input type="submit" value="Activate guest account" />
<?php
}
?>

</p>
</form>
<?php
$page->printFooter();
?>
