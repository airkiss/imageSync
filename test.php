<?php
require_once('autoload.php');
$dbh = new PDO($DB['DSN'],$DB['DB_USER'], $DB['DB_PWD'],
	array( PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
	PDO::ATTR_PERSISTENT => false));
$ItemInfoDB = new ItemInfo($dbh);
//                              $FILEPATH = "./data/";
/*
$numArray = $ItemInfoDB->ReadDB("m_4juniors0013");
echo $numArray['max'] . ' : ' . $numArray['title'] . "\n";
$numArray = $ItemInfoDB->ReadDB("s_75055-1");
echo $numArray['max'] . ' : ' . $numArray['title'] . "\n";
$numArray = $ItemInfoDB->ReadDB("P_Animal00428_34");
echo $numArray['max'] . ' : ' . $numArray['title'] . "\n";
*/
$numArray = $ItemInfoDB->ReadDB("m_castle0020");
echo $numArray['max'] . ' : ' . $numArray['title'] . "\n";
