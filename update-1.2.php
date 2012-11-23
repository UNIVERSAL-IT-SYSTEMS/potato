<?php
/**
 * Potato
 * One-time-password self-service and administration
 * Version 1.2
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
include "Page.class.php";
$page=new Page();
$page->printHeader();
?>

<h1>Potato database updater</h1>
<p>Updating database from pre-1.0 to 1.2 level.</p>
<ul>
    <li>Attempting to connect to database <strong>"<?php echo $dbName; ?>"</strong>... 
<?php
// Make sure we have a database handle
try {
    $dbh = new PDO("mysql:host=${dbServer};dbname=${dbName}", $dbUser, $dbPassword);
    print "<span class=\"success\">Success!</span>";
} catch (Exception $ignore) {
    print "<span class=\"failure\">Fail!</span><br />";
    print "Please make sure that the database connection settings in config.php are correct.";
}

print "    </li>\n";

if (isset($dbh)) {

    # Updating log table
    print "<li>Inserting idClient column in Log-table...";
    $dbh->exec( "ALTER TABLE `Log` ADD COLUMN `idClient` char(32) NULL DEFAULT NULL AFTER `passPhrase`" );
    if ($dbh->errorCode() == "00000") {
        echo "<span class=\"success\">Success!</span>";
    } elseif ($dbh->errorCode() == "42S21") {
        echo "<span class=\"success\">Column already exists!</span>";
    } else {
        echo "<span class=\"failure\">Fail!</span><br />";
        $error = $dbh->errorInfo();
        print $error[2];
    }
    print "    </li>\n";


    print "<li>Inserting idNAS column in Log-table...";
    $dbh->exec( "ALTER TABLE `Log` ADD COLUMN `idNAS` char(32) NULL DEFAULT NULL AFTER `idClient`" );
    if ($dbh->errorCode() == "00000") {
        echo "<span class=\"success\">Success!</span>";
    } elseif ($dbh->errorCode() == "42S21") {
        echo "<span class=\"success\">Column already exists!</span>";
    } else {
        echo "<span class=\"failure\">Fail!</span><br />";
        $error = $dbh->errorInfo();
        print $error[2];
    }
    print "    </li>\n";

    print "<li>Inserting status column in Log-table...";
    $dbh->exec( "ALTER TABLE `Log` ADD COLUMN `status` char(8) NULL DEFAULT NULL AFTER `idClient`" );
    if ($dbh->errorCode() == "00000") {
        echo "<span class=\"success\">Success!</span>";
    } elseif ($dbh->errorCode() == "42S21") {
        echo "<span class=\"success\">Column already exists!</span>";
    } else {
        echo "<span class=\"failure\">Fail!</span><br />";
        $error = $dbh->errorInfo();
        print $error[2];
    }
    print "    </li>\n";

    print "<li>Restructuring pre-existing data in Log table...";
    $dbh->exec( "UPDATE `Log` SET status='Fail' where message like 'FAIL%'" );
    if ($dbh->errorCode() == "00000") {
        echo "<span class=\"success\">Success!</span>";
    } else {
        echo "<span class=\"failure\">Fail!</span><br />";
        $error = $dbh->errorInfo();
        print $error[2];
    }
    print "    </li>\n";

    print "<li>Restructuring pre-existing data in Log table...";
    $dbh->exec( "UPDATE `Log` SET status='Success' where message like 'Success%'" );
    if ($dbh->errorCode() == "00000") {
        echo "<span class=\"success\">Success!</span>";
    } else {
        echo "<span class=\"failure\">Fail!</span><br />";
        $error = $dbh->errorInfo();
        print $error[2];
    }
    print "    </li>\n";

    print "<li>Adding idNAS index to Log table...";
    $dbh->exec( "create index  idNAS_index on `Log` (idNAS)" );
    if ($dbh->errorCode() == "00000") {
        echo "<span class=\"success\">Success!</span>";
    } else {
        echo "<span class=\"failure\">Fail!</span><br />";
        $error = $dbh->errorInfo();
        print $error[2];
    }
    print "    </li>\n";
}
?>
</ul>

<p>If there are access problems, you might need to grant the database user <strong><?php echo $dbUser; ?></strong>
more access rights:</p>
<?php
print <<<____PRE
<pre class="code">
grant all on `${dbName}`.* to '${dbUser}'@'${dbServer}';
flush privileges;
</pre>
____PRE;
?>

<p>In order to restore more restricted access rights to the dbUser, execute these commands:
<?php
print <<<____PRE
<pre class="code">
revoke all privileges, grant option from 'potato'@'localhost';
GRANT SELECT,INSERT,UPDATE,DELETE ON `${dbName}`.* TO '${dbUser}'@'${dbServer}';
flush privileges;
</pre>
____PRE;
?>

<p>If everything installed correctly, you can delete this file (install.php) and 
proceed to the <a href="login.php">Login page</a>, 
and start using Potato.</p>

<?php
$page->printFooter();
?>
