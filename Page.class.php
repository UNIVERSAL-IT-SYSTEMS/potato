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


class Page {
    public $bMenu = true;

    function printHeader() {
        global $currentUser;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"> 
 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> 
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
        <meta http-equiv="X-UA-Compatible" content="IE=9" /> 
        <link href="style.css?version=11" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="main.js?version=7"></script>
        <title>One-time password configuration</title>
    </head>

    <body>
<?php
        if ( isset($currentUser) ) {
            echo '    <script type="text/javascript">LogoutTimer.start();</script>';
        }

        if ( $this->bMenu ) {
            $this->printMenu();
        }

        echo '    <div id="content">';
        $this->printMessageBox();
       
    }

    private function printMenu() {
        global $currentUser;
        echo '    <div id="menu">';
        if ( isset($currentUser) ) {
            echo '<a href="index.php">Home</a>' . "\n";
            echo '<a href="testtoken.php">Test token</a>' . "\n";
            echo isset($wifiGuestSSID) ? '<a href="wifiguest.php">Wifi guest</a>' . "\n" : "";
            echo '<a href="help.php">Help</a>' . "\n";
            if ( $currentUser->isAdmin() ) {
                echo '<br />';
                echo '<a href="accountadd.php">Add account</a>';
                echo '<a href="accountadmin.php">Account admin</a>';
                echo '<a href="hotpsync.php">HOTP sync</a>';
                echo '<a href="syslog.php">System log</a>';
                echo '<br />';
            }
            echo '<a href="logout.php">Logout</a>';
        }
        echo '    </div>';
    }


    function printMessageBox() {
        if ( isset($_SESSION['msgWarning']) ) {
            echo '<div class="msgWarning">';
            echo $_SESSION['msgWarning'] . '</div>';
            unset( $_SESSION['msgWarning'] );
        }
        if ( isset($_SESSION['msgInfo']) ) {
            echo "<div class=\"msgInfo\">";
            echo $_SESSION['msgInfo'] . '</div>';
            unset( $_SESSION['msgInfo'] );
        }
    }


    function printFooter() {
        global $currentUser;
        if ( isset( $currentUser ) ) {
?>
    	    <div id="footer">
                Logged in as: <?php echo htmlentities($currentUser->getUserName()) ?><br />
                Server epoch: <?php echo intval(gmdate("U")/10) ?><br/>
                <a href="http://kelvin.nu/software/potato/">Server side potato</a> is free software.
            </div>
<?php
        }
?>
        </div>
    </body>
</html>
<?php
    }
}
?>
