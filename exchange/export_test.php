<?php
error_reporting(E_ALL);

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
if (empty($_SERVER["DOCUMENT_ROOT"])) {
	chdir(dirname(__FILE__));
	$_SERVER["DOCUMENT_ROOT"] = preg_replace("/(.*data24).*/", "$1", dirname(__FILE__));
}

require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/vendor/spout/src/Spout/Autoloader/autoload.php";
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Common\Entity\Row;

file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/exp24.log', __LINE__ . ': start' . PHP_EOL);

$writer = WriterEntityFactory::createXLSXWriter();

$filePath = $_REQUEST['file_name'];
//var_export($filePath);die();
$writer->openToFile($filePath);

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
	ShowError('Модуль iblock не установлен');
	return;
}

if (!\Bitrix\Main\Loader::includeModule('main')) {
	ShowError('Модуль main не установлен');
	return;
}

if (!\Bitrix\Main\Loader::includeModule('wg')) {
	ShowError('Модуль wg не установлен');
	return;
}

$export_section = $_REQUEST['export_section'];
$export_manufacturer = $_REQUEST['export_manufacturer'];
$arFilter = Array(
	'IBLOCK_ID' => 17,
	'INCLUDE_SUBSECTIONS' => 'Y'
);
if ($export_section > 0) {
	$arFilter['SECTION_CODE'] = $export_section;
}
if ($export_manufacturer > 0) {
	$arFilter['PROPERTY_MANUFACTURER'] = $export_manufacturer;
}

$manufacturers = CWGExchange::getManufacturersList();
/**
 * ok
 */
if (!empty($_GET['file_name'])) {
	echo $resCount;
}

/*-=-*/
//$dbh = new PDO('mysql:host=localhost;dbname=whitegoods_c1;charset=utf8', 'whitegoods_c1', 'Hyrjkfd5f7hgds');
//$dbh = new PDO('mysql:host=localhost;dbname=host45;charset=utf8', 'nobody', '1234aaa');
global $DB;

if (intval($export_section) > 0) {
	$sql = '
SELECT LEFT_MARGIN, RIGHT_MARGIN
FROM b_iblock_section
WHERE CODE="' . intval($export_section) . '"';

	$res = $DB->query($sql);
	while ($ob = $res->fetch()) {
		$joinSection = 'LEFT JOIN b_iblock_section as s ON s.ID=p.IBLOCK_SECTION_ID';
		$filterSection = 'AND s.LEFT_MARGIN>=' . $ob['LEFT_MARGIN'] . ' AND s.RIGHT_MARGIN<=' . $ob['RIGHT_MARGIN'];
	}
} else {
	$joinSection = '';
	$filterSection = '';
}

if ($export_manufacturer > 0) {
	//$joinManufacturer = 'LEFT JOIN b_iblock_element_property as m ON p.ID=m.IBLOCK_ELEMENT_ID';
	//$filterManufacturer = 'AND m.IBLOCK_PROPERTY_ID IN (2) AND m.VALUE=' . $export_manufacturer;
	$joinManufacturer = 'LEFT JOIN b_iblock_element_prop_s17 as m ON p.ID=m.IBLOCK_ELEMENT_ID';
	$filterManufacturer = 'AND m.PROPERTY_111=' . $export_manufacturer;
} else {
	$joinManufacturer = '';
	$filterManufacturer = '';
}

$filterExtra = 'AND (e.extra_fields_name="Вес, кг" OR e.extra_fields_name IS NULL)';
$filterExtra = '';
/*
$propsAr = Array(48,   2, 1, 51, 52, 53,  66, 46, 1734, 50,  65,  55,  56,  59,  60, 61, 62,  63, 1775);
*/
$propsAr = Array(93, 111,    96, 97, 98, 109, 89,   90, 95, 108, 100, 101, 103, 104,    105, 106, 112, 184);
/*$sql = '
SELECT
	p.ID, p.NAME, p.CODE, p.ACTIVE, p.IBLOCK_SECTION_ID,
	GROUP_CONCAT(DISTINCT CONCAT_WS("@", r.IBLOCK_PROPERTY_ID, r.VALUE) SEPARATOR "$") AS PROPS,
	GROUP_CONCAT(DISTINCT CONCAT_WS("@", e.extra_fields_name, pe.products_extra_fields_value) SEPARATOR "$") AS EXTR
FROM b_iblock_element AS p
LEFT JOIN b_iblock_element_property as r ON p.ID=r.IBLOCK_ELEMENT_ID
' . $joinManufacturer . '
' . $joinSection . '
LEFT JOIN products_to_extra_fields as pe ON p.CODE=pe.products_id
LEFT JOIN extra_fields as e ON e.extra_fields_id=pe.products_extra_fields_id
WHERE
	p.IBLOCK_ID=17
--  AND r.IBLOCK_PROPERTY_ID IN (48,   2, 1, 51, 52, 53,  66, 46, 1734, 50,  65,  55,  56,  59,  60, 61, 62,  63, 1775)
	AND (
		r.IBLOCK_PROPERTY_ID IN (93, 111,    96, 97, 98, 109, 89,   90, 95, 108, 100, 101, 103, 104,    105, 106, 112)
		OR r.IBLOCK_PROPERTY_ID IS NULL)
	' . $filterManufacturer . '
	' . $filterSection . '
	' . $filterExtra . '
GROUP BY p.ID
ORDER BY p.ID
';*/
$concatAr = Array();
foreach ($propsAr as $p) {
	$concatAr[] = 'CONCAT_WS("@", ' . $p . ', r.PROPERTY_' . $p . ')';
}

$sql = '
SELECT
	p.ID, p.NAME, p.CODE, p.ACTIVE, p.IBLOCK_SECTION_ID,
	CONCAT_WS("$", ' . implode(',', $concatAr) . ') AS PROPS,
	GROUP_CONCAT(DISTINCT CONCAT_WS("@", e.extra_fields_name, pe.products_extra_fields_value) SEPARATOR "$") AS EXTR
FROM b_iblock_element AS p
LEFT JOIN b_iblock_element_prop_s17 as r ON p.ID=r.IBLOCK_ELEMENT_ID
' . $joinManufacturer . '
' . $joinSection . '
LEFT JOIN products_to_extra_fields as pe ON p.CODE=pe.products_id
LEFT JOIN extra_fields as e ON e.extra_fields_id=pe.products_extra_fields_id
WHERE
	p.IBLOCK_ID=17
	' . $filterManufacturer . '
	' . $filterSection . '
	' . $filterExtra . '
GROUP BY p.ID
ORDER BY p.ID
';

$res = $DB->query($sql);
/*-=-*/

file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/exp24.log', __LINE__ . ': h' . PHP_EOL, FILE_APPEND);

$export_cols = Array(
	"идентификатор",
	"название",
	"производитель",
	"модель",
	"цвет",
	"цена",
	"валюта",
	"маркет директ",
	"маркет карточка",
	"показывать на сайте",
	"показывать на маркете",
	"статус наличия",
	"стоимость доставки по МСК",
	"стоимость доставки до ТК",
	"код производителя",
	"категория",
	"срок гарантии(мес)",
	"необходима предоплата",
	"процент предоплаты",
	"Заказать на маркете",
	"Остаток на складе",
	"Закупочная цена",
	"Поставщик",
	"Самовывоз",
	"Вес (кг)",
	"Габариты (см)",
	"Самовывоз цена"
);
/*document header*/
$rowFromValues = WriterEntityFactory::createRowFromArray($export_cols);
$writer->addRow($rowFromValues);

$outcount = 0;
var_export($res->selectedRowsCount());
//die();
while ($ob = $res->fetch()) {
	file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/exp24.log', '#' . $outcount . ' [m:' . memory_get_usage() . '; c: ' . $ob['CODE'] . ']' . PHP_EOL, FILE_APPEND);
	$outcount++;
	/*if ($ob['CODE'] == '58466') {
		file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/exob.log', print_r($ob, 1));
	}*/
	$props_tmp = explode('$', $ob['PROPS']);
	$props = Array();
	foreach ($props_tmp as $tmp) {
		$tmpArr = explode("@", $tmp);
		if ($tmpArr[0] == 3) {
			if (!is_array($props[$tmpArr[0]])) {
				$props[$tmpArr[0]] = Array();
			}
			$props[$tmpArr[0]][] = $tmpArr[1];
		} else {
			$props[$tmpArr[0]] = $tmpArr[1];
		}
	}
	$ob['PROPS'] = $props;
	$extra_tmp = explode('$', $ob['EXTR']);
	$extra = Array();
	foreach ($extra_tmp as $tmp) {
		$tmpArr = explode("@", $tmp);
		$extra[$tmpArr[0]] = $tmpArr[1];
	}
	$ob['extra'] = $extra;
	$ob['SECTION_NAME'] = CWGExchange::getSectionName($ob['IBLOCK_SECTION_ID']);
	$rate = 1;

	$db_res = \CPrice::GetList(
		array(),
		array(
			"PRODUCT_ID" => $ob['ID'],
			"CATALOG_GROUP_ID" => 1
		)
	);
	if ($ar_res = $db_res->Fetch()) {
		$ob['PRICE'] = $ar_res['PRICE'];
		$ob['CURRENCY'] = $ar_res['CURRENCY'];
		$rate = CWGExchange::curRate($ar_res["CURRENCY"]);
		if ($rate == 1) {
			$ob['PRICE_RUB'] = $ar_res['PRICE'];
		} else {
			$ob['PRICE_RUB'] = $ar_res['PRICE'] * $rate;
		}
	} else {
		$ob['PRICE_RUB'] = 0;
	}

	$ob['ACTIVE'] = ($ob['ACTIVE'] == 'Y' ? 1 : 0);
	//$ob['PROPS'][55] = ($ob['PROPS'][55] == 10 ? 1 : 0);
	$ob['PROPS'][100] = ($ob['PROPS'][100] == 83 ? 1 : 0);
	//$ob['PROPS'][63] = ($ob['PROPS'][63] == 12 ? 1 : 0);
	$ob['PROPS'][106] = ($ob['PROPS'][106] == 85 ? 1 : 0);
	//$ob['PROPS'][59] = ($ob['PROPS'][59] == 11 ? 1 : 0);
	$ob['PROPS'][103] = ($ob['PROPS'][103] == 84 ? 1 : 0);
	//$ob['PROPS'][53] = ($ob['PROPS'][53] == 7?1:($ob['PROPS'][53] == 8?2:0));
	$ob['PROPS'][98] = ($ob['PROPS'][98] == 80?1:($ob['PROPS'][98] == 81?2:0));
	if (!empty($_GET['file_name'])) {
		echo $ob['CODE'], "; ";
	}
	foreach ($ob['extra'] as $key => $val) {
		if (preg_match("/Вес.+/", $key)) {
			$ob['weight'] = $val;
		}
	}
	//switch ($ob['PROPS'][66]) {
	switch ($ob['PROPS'][109]) {
		//case 15:
		case 87:
			$ob['PROPS'][109] = 1;
			break;
		//case 16:
		case 88:
			$ob['PROPS'][109] = 2;
			break;
		//case 17:
		case 90:
			$ob['PROPS'][109] = 3;
			break;
		//case 18:
		case 89:
			$ob['PROPS'][109] = 4;
			break;
		//case 14:
		case 92:
			$ob['PROPS'][109] = 5;
			break;
		default:
			$ob['PROPS'][109] = 0;
			break;
	}

	$item = $ob;
	$rowData = Array(
		$item['CODE'],
		$item['PROPS'][93],
		$manufacturers[$item['PROPS'][111]]['NAME'],//$item['PROPS'][111],
		$item['NAME'],
		'',
		$item['PRICE_RUB'],//$item['PRICE'],
		$item['CURRENCY'],//'RUB',
		$item['PROPS'][96],
		$item['PROPS'][97],
		$item['ACTIVE'],
		$item['PROPS'][98],
		$item['PROPS'][109],
		$item['PROPS'][89],
		$item['PROPS'][90],
		$item['PROPS'][95],
		$item['SECTION_NAME'],
		$item['PROPS'][108],
		$item['PROPS'][100],
		$item['PROPS'][101],
		$item['PROPS'][103],
		$item['PROPS'][104],
		$item['PROPS'][184],
		$item['PROPS'][105],
		$item['PROPS'][106],
		$item['weight'],
		$item['sizes'],
		$item['PROPS'][112]
	);
	$rowFromValues = WriterEntityFactory::createRowFromArray($rowData);
	$writer->addRow($rowFromValues);
}

$writer->close();
/*
(48,   2, 1, 51, 52, 53,  66, 46, 1734, 50,  65,  55,  56,  59,  60, 61, 62,  63, 1775)
(93, 111,    96, 97, 98, 109, 89,   90, 95, 108, 100, 101, 103, 104,    105, 106, 112)
*/
