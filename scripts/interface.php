<?php

$res = 0;

// Les Users sont chargés avec main.inc. pas avec master.inc
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (!$res) die("Include of master fails");

require_once __DIR__ . '/../class/clienjoyholidays.class.php';

$action = GETPOST('action');
$data = GETPOSTINT('data');

switch ($action) {
	case 'getdefaultPrice':
		$amount =  CliEnjoyHolidays::getDefaultPrice($data);
		echo json_encode($amount);

		break;
	default:
		break;
}
