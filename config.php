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

// The POSIX group which has admin rights
$groupAdmin = "dadm";

// The POSIX group to which users must belong
$groupUser = "imtec-world";

// Database configuration
$dbServer = 'localhost';
$dbName = 'mossad';
$dbUser = 'mossad';
$dbPassword = 'SuperSecret99';

try {
    $dbh = new PDO("mysql:host=${dbServer};dbname=${dbName}", $dbUser, $dbPassword);
} catch (Exception $ignore) {
    echo "Database error.";
    exit();
}

?>
