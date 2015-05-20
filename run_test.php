#!/usr/bin/php -q
<?php
require_once('autoload.php');
$dbh = new PDO($DB['DSN'],$DB['DB_USER'], $DB['DB_PWD'],
	array( PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
	PDO::ATTR_PERSISTENT => false));
$ItemInfoDB = new ItemInfo($dbh);
