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

$aFilter = empty($_GET['filter']) ? array() : $_GET['filter'];

include 'NavigationBar.class.php';
include 'header.php';
global $dbh;

?>

<h1>System log</h1>

<a href="#" onclick="toggleVisibility('filterNAS'); return(false);">Filter on NAS</a>
<div class="popup" id="filterNAS">
Select which NAS's to show logs from:<br />
<form action="syslog.php">

<?php
# Retrieve list of all NAS
$ps = $dbh->query("SELECT DISTINCT idNAS from Log where idNAS is not null");
while ($row = $ps->fetch()) {
    print( '<label><input type="checkbox" name="filter[]" value="' . $row[0] . '"');
    print( in_array($row[0], $aFilter) || empty($aFilter) ? ' checked="checked"' : '' );
    print( '/> ' . $row[0] . "</label><br />\n");
}
print '<br/>';
print '<input type="submit" value="Apply filter">';
print "</form>";
print "</div>";

$navBar = new NavigationBar();
if (empty($aFilter)) {
    // View everything
    $filterCondition = " ";
    $ps = $dbh->query("SELECT COUNT(*) from Log");
} else {
    # Filter log view
    # Generate an array of question marks to be used as placeholders
    $psPlaceholder = array_fill(0, count($aFilter), '?');
    $filterCondition = "WHERE idNAS in (" . implode(',', $psPlaceholder) . ") or idNAS is null";
    $ps = $dbh->prepare("SELECT COUNT(*) FROM Log " . $filterCondition);
    $ps->execute($aFilter);

    foreach($aFilter as $getAttr) {
        $navBar->addGetParam("filter[]", $getAttr);
    }
}
$row = $ps->fetch();

$navBar->setRowsPerPage(10);
$navBar->setNumRows($row[0]);
$navBar->setPageCurrent( empty($_GET['page']) ? 1 : $_GET['page'] );



$navBar->printNavBar();

?>
<table class="userlist" cellpadding="0" cellspacing="0">

<?php

if(empty($aFilter)) {
    $ps = $dbh->prepare("SELECT time, userName, passPhrase, idClient, idNAS, status, message from Log order by time DESC limit " . $navBar->getRowsPerPage() . " offset " . $navBar->getRowsOffset());
    $ps->execute();
} else {
    $ps = $dbh->prepare("SELECT time, userName, passPhrase, idClient, idNAS, status, message from Log " . $filterCondition . " order by time DESC limit " . $navBar->getRowsPerPage() . " offset " . $navBar->getRowsOffset());
    $ps->execute($aFilter);
}

while ($row = $ps->fetch()) {
?>
    <tr>
        <td><?php echo $row['time'] ?></td>
        <td><a href="logviewer.php?userName=<?php echo urlencode($row['userName']) ?>"><?php echo htmlentities($row['userName']) ?></a></td>
        <td><?php echo $row['passPhrase'] ?></td>
        <td><?php echo $row['idClient'] ?></td>
        <td><?php echo $row['idNAS'] ?></td>
        <td><?php echo $row['status'] ?></td>
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
