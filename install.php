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
include "Page.class.php";
$page=new Page();
$page->bMenu = false;
$page->printHeader();
?>

<h1>Potato installation</h1>
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
    print "    <li>Creating User table...";

    $sql = <<<____SQL
CREATE TABLE `User` (
  `userName` char(16) NOT NULL,
  `secret` varchar(64) NULL DEFAULT NULL,
  `pin` char(8) NULL DEFAULT NULL,
  `hotpCounter` int(8) NOT NULL default '0',
  `invalidLogins` tinyint(1) NOT NULL default 0,
  PRIMARY KEY  (`userName`)
) ENGINE=InnoDB CHARSET=utf8;
____SQL;

    $return = $dbh->exec($sql);
    if ($dbh->errorCode() == "00000") {
        print "<span class=\"success\">Success!</span>";
    } elseif ($dbh->errorCode() == "42S01") {
        echo "<span class=\"success\">Table already exists!</span>";
    } else {
        print "<span class=\"failure\">Fail!</span><br />";
        $error = $dbh->errorInfo();
        print $error[2];
    }
    print "    </li>\n";

    print "    <li>Creating Guest table...";
    $sql = <<<____SQL
CREATE TABLE `Guest` (
  `userName` char(16) NOT NULL,
  `password` varchar(32) NOT NULL,
  `dateCreation` timestamp default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`userName`),
  CONSTRAINT `fkUserNameGuest` FOREIGN KEY (`userName`) references `User` (`userName`) on delete cascade
) ENGINE=InnoDB CHARSET=utf8;
____SQL;

    $return = $dbh->exec($sql);
    if ($dbh->errorCode() == "00000") {
        echo "<span class=\"success\">Success!</span>";
    } elseif ($dbh->errorCode() == "42S01") {
        echo "<span class=\"success\">Table already exists!</span>";
    } else {
        print "<span class=\"failure\">Fail!</span><br />";
        $error = $dbh->errorInfo();
        print $error[2];
    }
    print "    </li>\n";

    print "    <li>Creating Log table...";
    $sql = <<<____SQL
CREATE TABLE `Log` (
  `time` timestamp default CURRENT_TIMESTAMP,
  `userName` char(16) NOT NULL,
  `passPhrase` char(12),
  `idClient` char(32),
  `idNAS` char(32),
  `status` char(8),
  `message` varchar(256),
  KEY `idNAS_index` (`idNAS`),
  CONSTRAINT `fkUserName` FOREIGN KEY (`userName`) references `User` (`userName`) on delete cascade
) ENGINE=InnoDB CHARSET=utf8;
____SQL;
    $return = $dbh->exec($sql);

    if ($dbh->errorCode() == "00000") {
        echo "<span class=\"success\">Success!</span>";
    } elseif ($dbh->errorCode() == "42S01") {
        echo "<span class=\"success\">Table already exists!</span>";
    } else {
        echo "<span class=\"failure\">Fail!</span><br />";
        $error = $dbh->errorInfo();
        print $error[2];
    }
    print "    </li>\n";
    # print "<pre>";
    # print_r($dbh->errorInfo());
    # print "</pre>";

    print "    <li>Creating TokenCache table...";
    $sql = <<<____SQL
CREATE TABLE `TokenCache` (
  `time` timestamp default CURRENT_TIMESTAMP,
  `userName` char(16) NOT NULL,
  `token` char(14),
  `idClient` char(32),
  `idNAS` char(32),
  CONSTRAINT `fkUserNameTokenCache` FOREIGN KEY (`userName`) references `User` (`userName`) on delete cascade
) ENGINE=InnoDB CHARSET=utf8;
____SQL;
    $return = $dbh->exec($sql);

    if ($dbh->errorCode() == "00000") {
        echo "<span class=\"success\">Success!</span>";
    } elseif ($dbh->errorCode() == "42S01") {
        echo "<span class=\"success\">Table already exists!</span>";
    } else {
        echo "<span class=\"failure\">Fail!</span><br />";
        $error = $dbh->errorInfo();
        print $error[2];
    }
    print "    </li>\n";
    # print "<pre>";
    # print_r($dbh->errorInfo());
    # print "</pre>";

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
