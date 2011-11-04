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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"> 
 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> 
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
        <meta http-equiv="X-UA-Compatible" content="IE=9" /> 
        <link href="style.css?version=6" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="main.js?version=5"></script>
        <title>One-time password configuration</title>
    </head>

    <body>
<?php
if ( isset($currentUser) ) {
    echo <<< LOGOUTTIMER
    <script type="text/javascript">
        LogoutTimer.start();
    </script>
LOGOUTTIMER;
}
?>

    <div id="menu">
<?php
if ( isset($currentUser) ) {
    echo '        <a href="index.php">Home</a>' . "\n";
    echo '        <a href="testtoken.php">Test token</a>' . "\n";
    echo $wifiSSID=="" ? "" : '        <a href="wifiguest.php">Wifi guest</a>' . "\n";
    if ( $currentUser->isAdmin() ) {
?>
        <br />
        <a href="accountadd.php">Add account</a>
        <a href="accountadmin.php">Account admin</a>
        <a href="syslog.php">System log</a>
        <br />
<?php
    }
?>
        <a href="logout.php">Logout</a>
<?php
}
?>
    </div>

    <div id="content">

<?php

if ( isset($_SESSION['msgWarning']) ) {
 	echo "<div class=\"msgWarning\">";
 	echo $_SESSION['msgWarning'];
    unset( $_SESSION['msgWarning'] );
 	echo "</div>";
}
if ( isset($_SESSION['msgInfo']) ) {
 	echo "<div class=\"msgInfo\">";
 	echo $_SESSION['msgInfo'];
    unset( $_SESSION['msgInfo'] );
 	echo "</div>";
}

?>
