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
session_start();
include "User.class.php";
# include "demo.php";

if ( !empty($_POST['loginUserName']) ) {
    $loginUserName = $_POST['loginUserName'];
    $loginPassword = $_POST['loginPassword'];

    try {
#        if ($demo && in_array($loginUserName, array_keys($demoUsers)) && $demoUsers{$loginUserName}==$loginPassword) {
#            $posixGroupUser = array();
#            $posixGroupUser['members'] = array_keys($demoUsers);
        if( pam_auth( $loginUserName, $loginPassword ) ) {
            $posixGroupUser = posix_getgrnam($groupUser);
            if ( in_array( $loginUserName, $posixGroupUser['members'] ) ) {
                $_SESSION['currentUser'] = $loginUserName;
                $_SESSION['timeActivity'] = gmdate( "U" );
                header("Location: index.php");
                exit;
            } else {
                $_SESSION['msgWarning'] = "Access denied. You are not a member of the access group \"" . $groupUser . "\"";
            }
        } else {
            $_SESSION['msgWarning'] = "Login incorrect.";
        }
    } catch (adLDAPException $e) {
        $_SESSION['msgCritical'] = "Unable to contact the LDAP server for authentication. Please contact the helpdesk.";
    }
}

include 'header.php';

if ($demo) {
    echo "<p><strong>DEMO MODE</strong></p>\n";
    echo "<p>Login with one of the following accounts:\n";
    echo "<ul>\n";
    foreach ( array_keys($demoUsers) as $u ) {
        echo "    <li><strong>" . $u . "</strong>, pw: \"" . $demoUsers[$u] . "\"</li>\n";
    }
    echo "</ul>\n";
    echo "<hr />\n";
}
?>
<p>Login with your regular Windows username and password.</p>
<form method="post" action="login.php"> 
<table> 
    <tr> 
        <th>Username:</th> 
        <td><input type="text" name="loginUserName" size="10" maxlength="16" value="<?php echo isset($loginUserName) ? htmlentities($loginUserName) : "" ?>" id="focusUserName" class="iesux" /></td> 
    </tr>
    <tr>
        <th>Password:</th>
        <td><input type="password" name="loginPassword" value="" size="10" class="iesux" id="focusPassword" /></td>
    </tr> 
    <tr> 
        <td></td>
        <td><input id="submit" type="submit" value="Login"></td>
    </tr> 
</table>
</form>

<script type="text/javascript">
    function setfocus() {
        domUserName = document.getElementById("<?php echo isset($loginUserName) ? "focusPassword" : "focusUserName" ?>");
        domUserName.focus();
    }
    window.onload=setfocus;
</script>

<?php

include 'footer.php';
?>
