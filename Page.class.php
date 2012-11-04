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
    public $bTabBar = false;

    /**
     * print the html page header
     */
    function printHeader() {
        global $currentUser;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"> 
 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> 
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
    <meta http-equiv="X-UA-Compatible" content="IE=9" /> 
    <link href="style.css?version=12" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="main.js?version=7"></script>
    <title>One-time password configuration</title>
  </head>

  <body>
<?php
        if ( isset($currentUser) ) {
            echo '    <script type="text/javascript">LogoutTimer.start();</script>' . "\n";
        }

        $this->printSideBar();

        # The main content area starts here. Everything is wrapped in a div#metacontent
        echo '<div id="metacontent">' . "\n";
        if ( $this->bTabBar ) {
            $this->printTabBar();
        }

        echo '<div id="content">' . "\n";
        $this->printMessageBox();
       
    }

    /**
     * prepTabBar()
     * retrieve userName from request and do sanity checking
     */
    public function prepTabBar() {
        global $user, $currentUser;
        $this->bTabBar=true;
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
    }

    /**
     * print the user tabs
     */
    private function printTabBar() {
        global $user, $wifiGuestSSID;
        echo "<h1>User settings</h1>\n";
        echo "<table>\n";
        echo "  <tr>\n";
        echo "    <th>Username: </th><td>" . htmlentities($user->getUserName()) . "</td>\n";
        echo "  </tr>\n";
        echo "  <tr>\n";
        echo "    <th>Full name: </th><td>";
        echo htmlentities($user->getFullName());
        echo "</td>\n";
        echo "  </tr>\n";
        echo "</table>\n";
        echo "<br />\n";

        echo '<div id="tabs">' . "\n";
        $this->printTab('index.php', 'Home');
        $this->printTab('testtoken.php', 'Test token');
        $this->printTab('logviewer.php', 'Logs');
        if (isset($wifiGuestSSID)) {
            $this->printTab('wifiguest.php', 'Wifi guest');
        }
        echo '</div>' . "\n";
    }

    private function printTab($url, $title) {
        echo '  <a href="' . $this->getUrl($url) . '"';
        echo ( $url==substr($_SERVER["PHP_SELF"], -strlen($url)) ? ' class="tabSelected">' : '>');
        echo $title . "</a>\n";
    }

    /**
     * Return a proper url with or without userName attached
     * depending on whether we're looking at ourselves or not
     */
    public function getUrl($url) {
        global $user, $currentUser;
        return $url . ($user->getUserName()==$currentUser->getUserName() ? '' : '?userName=' . urlencode($user->getUserName()));
    }

    private function printSideBar() {
        echo '<div id="sidebar">' . "\n";
        echo '<a href="index.php"><img src="images/server-side-potato.png" alt="Server side potato logo" id="logo"></a>' . "\n";
        if ( $this->bMenu ) {
            $this->printMenu();
        }
        echo "</div>\n";
    }

    /**
     * print the page menu
     */
    private function printMenu() {
        global $currentUser;
        echo '<div id="menu">' . "\n";
        echo '  <a href="index.php">Home</a>' . "\n";
        echo "  <br />\n";
        if ( $currentUser->isAdmin() ) {
            echo '  <a href="accountadd.php">Add account</a>' . "\n";
            echo '  <a href="accountadmin.php">Account admin</a>' . "\n";
            echo '  <a href="hotpsync.php">HOTP sync</a>' . "\n";
            echo '  <a href="syslog.php">System log</a>' . "\n";
            echo '  <br />';
        }
        echo '  <a href="help.php">Help</a>' . "\n";
        echo '  <a href="logout.php">Logout</a>' . "\n";
        echo "</div>\n";
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
    </div>
  </body>
</html>
<?php
    }
}
?>
