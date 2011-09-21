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

include 'header.php';

print "<h1>Add account</h1>\n";
?>

<form method="get" action="index.php"> 
    <table> 
        <tr> 
            <th>Username:</th> 
            <td><input id="focusme" type="text" name="userName" value="" size="20" maxlength="16" /></td> 
            <td><input id="submit" type="submit" value="Add account"></td>
        </tr> 
    </table>
</form>

<script type="text/javascript">
    function setfocus() {
        domUsername = document.getElementById("focusme");
        domUsername.focus();
    }
    window.onload=setfocus;
</script>


<?php
include 'footer.php';
?>
