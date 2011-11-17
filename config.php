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

// The POSIX group which has admin rights
$groupAdmin = "sysadmins";

// The POSIX group to which users must belong
$groupUser = "potato-users";

// What organization should the UI be branded as
$orgName = "Potato";

// Amount of allowed login attempts before the account is locked
// Set to 0 to disable account locking.
$invalidLoginLimit = 7;

// What's the SSID of the regular wifi network?
// Set to empty string if you're not going to use potato for regular wifi access.
$wifiSSID = "Potato-Wifi";

// What SSID is the wireless guest account for?
// Set to empty string to disable wifi guest functionality
$wifiGuestSSID = "Potato-Guest";

// Database configuration
$dbServer = 'localhost';
$dbName = 'potato';
$dbUser = 'potato';
$dbPassword = 'SuperSecret99';

try {
    $dbh = new PDO("mysql:host=${dbServer};dbname=${dbName}", $dbUser, $dbPassword);
} catch (Exception $ignore) {
    echo "Database error.";
    exit();
}

?>
