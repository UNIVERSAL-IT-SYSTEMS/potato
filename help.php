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

include 'config.php';
include 'session.php';
include 'header.php';
?>

<h1>Help</h1>
<p><a href="about.php">Click here</a> for basic information about one-time-passwords.</p>

<?php

if (!isset($wifiSSID)) {
    if (file_exists("localsite.php")) {
        readfile("localsite.php");
    }
    echo "<h2>More...</h2>\n";
    echo "<p>If you require detailed wifi instructions, you can read this <a href=\"winxp.php\">Windows XP wifi guide</a></p>\n";
}
?>

<?php
include 'footer.php';
?>
