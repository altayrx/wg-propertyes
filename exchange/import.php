<?php
chdir(dirname(__FILE__));
//file_put_contents('i2.log', $_SERVER["DOCUMENT_ROOT"] . PHP_EOL, FILE_APPEND);

$start = microtime(true);
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define("DEV_CRON",true);
//echo $_SERVER["DOCUMENT_ROOT"];
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require($_SERVER["DOCUMENT_ROOT"].'/vendor/autoload.php');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
	ShowError('Модуль iblock не установлен');
	return;
}

if (!\Bitrix\Main\Loader::includeModule('main')) {
	ShowError('Модуль main не установлен');
	return;
}

$manufacturers = CWGExchange::getManufacturersCodeList();

global $DB;
$sql = "SELECT * FROM import_queue WHERE status=1";
$res = $DB->Query($sql, false, $err_mess.__LINE__);

$queue = $res->selectedRowsCount();
echo "queue: ", $queue, "\n";
echo "running: ", $running, "\n";
if ($queue == 1 && $running === 1) {
	$queue = $res->fetch();
	$DB->PrepareFields("import_queue");
	$arFields = array(
		"status" => 3
	);
	$DB->StartTransaction();
	$DB->Update("import_queue", $arFields, "WHERE id=" . $queue['id'], $err_mess.__LINE__);
	if (strlen($strError) <= 0) {
		$DB->Commit();
		$queue = 0;
	}
	else {
		$DB->Rollback();
	}
}

if ($queue == 0) {
	$sql = "SELECT * FROM import_queue WHERE status=0 ORDER BY upload_datetime ASC LIMIT 1";
	$res = $DB->Query($sql, false, $err_mess . __LINE__);
	if ($res->selectedRowsCount() == 1) {
		$queue = $res->fetch();
		$DB->PrepareFields("import_queue");
		$arFields = array(
			"status" => 1
		);
		$DB->StartTransaction();
		$DB->Update("import_queue", $arFields, "WHERE id=" . $queue['id'], $err_mess.__LINE__);
		if (strlen($strError) <= 0) {
			$DB->Commit();
		}
		else {
			$DB->Rollback();
		}
		CWGExchange::import($queue);
	}
}
