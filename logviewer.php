<?php
/**
 * Mobile OTP self-service station and administration console
 * Version 1.0
 * 
 * PHP Version 5 with PDO, MySQL, LDAP support
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

include 'NavigationBar.class.php';
include 'header.php';
echo "<h1>Log viewer: " . htmlentities($_GET['userName']) . "</h1>\n";

global $dbh;
$ps = $dbh->prepare("SELECT COUNT(*) from Log where userName=:userName");
$ps->execute(array(":userName"=>$_GET['userName']));
$row = $ps->fetch();

$navBar = new NavigationBar();
$navBar->setNumRows($row[0]);
$navBar->setUserName($_GET['userName']);
$navBar->setPageCurrent($_GET['page']);
$navBar->printNavBar();

?>
<table class="userlist" cellpadding="0" cellspacing="0">

<?php

$ps = $dbh->prepare("SELECT time, passPhrase, message from Log where userName=:userName order by time DESC limit :rowsPerPage offset :rowsOffset");
$ps->bindValue(':userName', $_GET['userName']);
$ps->bindValue(':rowsPerPage', $navBar->getRowsPerPage(), PDO::PARAM_INT);
$ps->bindValue(':rowsOffset', $navBar->getRowsOffset(), PDO::PARAM_INT);
$ps->execute();

while ($row = $ps->fetch()) {
?>
    <tr>
        <td><?php echo $row['time'] ?></td>
        <td><?php echo $row['passPhrase'] ?></td>
        <td><?php echo $row['message'] ?></td>
    </tr> 
<?php
}
?>
</table>

<?php
$navBar->printNavBar();

include 'footer.php';
?>
