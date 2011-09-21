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

if ( ! $currentUser->isAdmin() ) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['action'])) {
    $user = new User();
    try {
        $user->fetch($_POST['userName']);
        switch ($_POST['action']) {
            case 'delete':
                $user->delete();
                $_SESSION['msgInfo'] = 'User account "' . htmlentities($user->userName) . '" deleted.';
                break;

            case 'unlock':
                $user->unlock();
                $_SESSION['msgInfo'] = 'User account "' . htmlentities($user->userName) . '" unlocked.';
                break;
        }

    } catch (NoSuchUserException $ignore) {
        $_SESSION['msgWarning'] = 'Nonexistent account.';
    }
}

include 'header.php';

?>
<h1>Account administration</h1>
<table class="userlist" cellpadding="0" cellspacing="0">

<?php
global $dbh;
$sql = "SELECT userName, invalidLogins from User order by userName";
foreach ($dbh->query($sql) as $row) {
?>
    <tr>
        <td>
            <a href="index.php?userName=<?php echo urlencode($row['userName']) ?>">
                <?php echo htmlentities($row['userName']) ?>
            </a>
        </td>
        <td>
<?php
    if ($row['invalidLogins']>4) {
        echo '<form action="accountadmin.php" method="post">' . "\n";
        echo '<input type="hidden" name="action" value="unlock">' . "\n";
        echo '<input type="hidden" name="userName" value="' . $row['userName'] . '">' . "\n";
        echo '<input type="image" src="images/lock.png" alt="Unlock account" title="Unlock account" />';
        echo '</form>' . "\n";
    } 
?>
        </td>
        <td>
            <a href="logviewer.php?userName=<?php echo urlencode($row['userName']) ?>">
                <img src="images/logviewer.png" alt="View logs" title="View logs" />
            </a>
        </td>

        <td>
<?php
    echo '<form action="accountadmin.php" method="post">' . "\n";
    echo '<input type="hidden" name="action" value="delete">' . "\n";
    echo '<input type="hidden" name="userName" value="' . $row['userName'] . '">' . "\n";
    echo '<input type="image" src="images/trashcan_empty.png" alt="Delete account" title="Delete account" />' . "\n";
    echo '</form>' . "\n";
?>
        </td>
    </tr> 
<?php
}
?>

</table>


<?php
include 'footer.php';
?>
