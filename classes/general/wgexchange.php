<?php
ini_set('memory_limit', '2048M');
require($_SERVER["DOCUMENT_ROOT"] . '/vendor/autoload.php');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CWGExchange
{
	private static $manufacturersList = NULL;
	private static $manufacturersCodeList = NULL;
	private static $sectionsCodeByIdCache = Array();
	private static $sectionsNameByIdCache = Array();
	private static $rates = Array();

	public static $statuses = Array(
		0 => 'ожидание',
		1 => '<span style="color:#f00">импортируется</span>',
		2 => '<span style="color:#888">завершен</span>',
		3 => '<span style="color:#f00">ошибка импорта</span>',
		4 => '<span style="color:#880">отменён</span>'
	);

	private static $import_cols = Array(
		'A' => 'CODE',//идентификатор
		'B' => 'PROPERTY_SUBNAME',//название
		'C' => 'MANUFACTURER',//производитель
		'D' => 'NAME',//модель
		'E' => 'PROPERTY_COLORS',//цвет
		'F' => 'PRICE',//цена
		'G' => 'CURRENCY',//валюта
		'H' => 'PROPERTY_YANDEX_BID',//маркет директ
		'I' => 'PROPERTY_YANDEX_CBID',//маркет карточка
		'J' => 'ACTIVE',//показывать на сайте
		'K' => 'PROPERTY_XML',//показывать на маркете
		'L' => 'PROPERTY_AVAIBLE_STATUS',//статус наличия
		'M' => 'PROPERTY_DELIVERY',//стоимость доставки мск
		'N' => 'PROPERTY_DELIVERY_DC',//стоимость доставки тк
		'O' => 'PROPERTY_VENDORCODE',//код производителя
		'P' => 'SECTION_NAME',//категория
		'Q' => 'PROPERTY_GUARANTEE_PERIOD',//срок гарантии(мес)
		'R' => 'PROPERTY_PREPAYMENT_KEY',//необходима предоплата
		'S' => 'PROPERTY_PREPAYMENT',//процент предоплаты
		'T' => 'PROPERTY_YAN_CPA',//Заказать на маркете
		'U' => 'PROPERTY_NASKLADE',//Остаток на складе
		'V' => 'PROPERTY_ZAKUPKAPRICE',//Закупочная цена
		'W' => 'PROPERTY_POSTAVSHIK',//Поставщик
		'X' => 'PROPERTY_SAMOVIVOZ',//Самовывоз
		'Y' => 'PROPERTY_WEIGHT',//Вес (кг)
		'Z' => 'PROPERTY_DIMENSIONS',//Габариты (см)
		'AA' => 'PROPERTY_PVZ_PRICE',//Самовывоз цена
	);
	private static $price_import_cols = Array(
		'A' => 'CODE',//идентификатор
		'B' => 'PRICE',//цена
		'C' => 'CURRENCY',//валюта
		'D' => 'ACTIVE',//показывать на сайте
		'E' => 'PROPERTY_XML',//показывать на маркете
		'F' => 'PROPERTY_AVAIBLE_STATUS',//статус наличия
		'G' => 'PROPERTY_ZAKUPKAPRICE',//Закупочная цена
		'H' => 'PROPERTY_NASKLADE',//Остаток на складе
		'I' => 'PROPERTY_POSTAVSHIK',//Поставщик
		'J' => 'PROPERTY_VENDORCODE',//код производителя
		'K' => 'PROPERTY_PRESENCE_TEXT',//Текст наличия на сайте
		'L' => 'PROPERTY_DELIVERY',//Доставка по Москве
		'M' => 'PROPERTY_DELIVERY_DC'//Доставка до ТК
	);
	private static $provider_import_cols = Array(
		'A' => 'CODE',//Артикул
		'B' => 'PROPERTY_SUBNAME',//Название
		'C' => 'ACTIVE',//Бренд
		'D' => 'PROPERTY_PROVIDER',//Поставщик
		'E' => 'PROPERTY_PROVIDER_PRICE',//Цена поставщика
		'F' => 'PROPERTY_PROVIDER_PRESENTS',//Текст наличия
		'G' => 'PROPERTY_PROVIDER_RRC'//РРЦ
	);
	private static $filters = Array(
		'F' => 1,//'Цена'
		'J' => 2,//'Показывать на сайте'
		'K' => 3,//'Показывать на маркете'
		'L' => 4,//'Статус наличия'
		'T' => 5,//'Остаток на складе'
		'U' => 6,//'Цена закупки'
		'V' => 7,//'Поставщик'
		'W' => 8//'Самовывоз'
	);
	private static $price_filters = Array(
		'B' => 1,//'Цена'
		'C' => 2,//'Показывать на сайте'
		'D' => 3,//'Показывать на маркете'
		'E' => 4,//'Статус наличия'
		'G' => 5,//'Остаток на складе'
		'F' => 6,//'Цена закупки'
		'H' => 7//'Поставщик'
	);
	private static $provider_filters = Array(
	);

	public static function curRate($code)
	{
		if ($code == 'RRC') {
			$code = 'RUB';
		}
		if (!isset(self::$rates[$code])) {
			\CModule::IncludeModule("currency");

			$arFilter = array(
				"CURRENCY" => $code,
			);
			$by = "date";
			$order = "desc";
			$row = CCurrencyRates::GetList($by, $order, $arFilter);
			if ($row->selectedRowsCount() > 0) {
				$col = $row->Fetch()['RATE'];
			} else {
				$col = CCurrency::getById($code)['AMOUNT'];
			}

			if (intval($col) == 1) {
				$col = 1;
			}

			self::$rates[$code] = $col;
		}
		return self::$rates[$code];
	}

	public static function getSectionCode($sectionId)
	{
		if (isset(self::$sectionsCodeByIdCache[$sectionId])) {
			return self::$sectionsCodeByIdCache[$sectionId];
		} else {
			if ($section_code_res = CIBlockSection::GetByID($sectionId)) {
				if ($section_code_ob = $section_code_res->GetNext()) {
					$section_code = $section_code_ob['CODE'];
					self::$sectionsCodeByIdCache[$sectionId] = $section_code;
					return $section_code;
				} else {
					return 'error2';
				}
			} else {
				return 'error1';
			}
		}
	}

	public static function getSectionName($sectionId)
	{
		if (isset(self::$sectionsNameByIdCache[$sectionId])) {
			return self::$sectionsNameByIdCache[$sectionId];
		} else {
			if ($section_name_res = CIBlockSection::GetByID($sectionId)) {
				if ($section_name_ob = $section_name_res->GetNext()) {
					$section_name = preg_replace('/\t/', '', $section_name_ob['NAME']);
					self::$sectionsNameByIdCache[$sectionId] = $section_name;
					return $section_name;
				} else {
					return 'error2';
				}
			} else {
				return 'error1';
			}
		}
	}

	public static function getManufacturersList()
	{
		if (!self::$manufacturersList) {
			self::fillManufacturersList();
		}
		return self::$manufacturersList;
	}

	public static function getManufacturersCodeList($name = '')
	{
		if (!self::$manufacturersCodeList) {
			self::fillManufacturersCodeList();
		}
		if (!empty($name)) {
			return self::$manufacturersCodeList[$name];
		}
		return self::$manufacturersCodeList;
	}

	private static function fillManufacturersList()
	{
		self::$manufacturersList = Array();
		$arFilter = Array('IBLOCK_ID' => 18);
		//$arSelect = Array('IBLOCK_ID', 'ID', 'NAME', 'PROPERTY_CUR');
		$arSelect = Array('IBLOCK_ID', 'ID', 'NAME');
		$res = CIBlockElement::GetList(
			Array(),
			$arFilter,
			false,
			false,
			$arSelect
		);
		while ($ob = $res->GetNext()) {
			//self::$manufacturersList[$ob['ID']] = Array('NAME' => $ob['NAME'], 'CUR' => $ob['PROPERTY_CUR_VALUE']);
			self::$manufacturersList[$ob['ID']] = Array('NAME' => $ob['NAME']);
		}
	}

	private static function fillManufacturersCodeList()
	{
		self::$manufacturersCodeList = Array();
		$arFilter = Array('IBLOCK_ID' => 18);
		//$arSelect = Array('IBLOCK_ID', 'ID', 'NAME', 'PROPERTY_CUR');
		$arSelect = Array('IBLOCK_ID', 'ID', 'NAME');
		$res = CIBlockElement::GetList(
			Array(),
			$arFilter,
			false,
			false,
			$arSelect
		);
		while ($ob = $res->GetNext()) {
			//self::$manufacturersCodeList[$ob['NAME']] = Array('ID' => $ob['ID'], 'CUR' => $ob['PROPERTY_CUR_VALUE']);
			self::$manufacturersCodeList[$ob['NAME']] = Array('ID' => $ob['ID']);
		}
	}

	private static function addImport($args = array())
	{}

	private static function execScript($url, $params = array())
	{
		$parts = parse_url($url);
		$returned_data = '';
		$data = http_build_query($params, '', '&');
		if ($parts['scheme'] == 'http') {
			$fp = @fsockopen($parts['host'], 80, $errnum, $errstr, 30);
		} else {
			$fp = @fsockopen('ssl://'.$parts['host'], 443, $errnum, $errstr, 30);
		}
		if ($fp) {
			fputs($fp, "POST ".$parts['path']." HTTP/1.1\r\n");
			fputs($fp, "Host: ".$parts['host']."\r\n");
			fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
			fputs($fp, "Content-Length: " . strlen($data) . "\r\n");
			fputs($fp, "Connection: close\r\n\r\n");
			fputs($fp, $data."\r\n\r\n");
			stream_set_timeout($fp, 1000);
			stream_set_blocking($fp, false);
	
			while (!feof($fp)) {
				$fp_tmp= fgets($fp, 4096);
				$returned_data .= fgets($fp, 4096);
			}
			fclose($fp);
		}
		return $returned_data;
	}

	public static function actionExport($args)
	{
		if (isset($args['export_section']) && $args['export_section'] > 0) {
			$export_section = $args['export_section'] . 's';
		} else {
			$export_section = '';
		}
		if (isset($args['export_manufacturer']) && $args['export_manufacturer'] > 0) {
			$export_manufacturer = $args['export_manufacturer'] . 'm';
		} else {
			$export_manufacturer = '';
		}
		$file_name = 'export(' . date('d-m-Y') . ')' . $export_section . $export_manufacturer . '.xls';
		$file_name_full = $_SERVER['DOCUMENT_ROOT'] . '/upload/export/' . 'export('.date('d-m-Y').')'.$args['export_section'].$args['export_manufacturer'].'.xls';
		$returned_data = self::execScript(
			'https://www.whitegoods.ru/local/modules/wg/exchange/export.php',
			//'http://host45/local/modules/wg/exchange/export.php',
			//'https://24.interno.ru/local/modules/wg/exchange/export.php',
			Array(
				'export_section' => $args['export_section'],
				'export_manufacturer' => $args['export_manufacturer'],
				'file_name' => $file_name_full
			)
		);

		ob_end_clean();
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="'.$file_name.'x"');
		header('Cache-Control: max-age=0');
		echo file_get_contents($file_name_full);
		die();
	}

	public static function actionImport($args)
	{
		//echo '<pre>', print_r($args, 1), '</pre>';
		global $DB;
		$DB->PrepareFields("import_queue");
		$DB->StartTransaction();
		$f = CFile::MakeFileArray(
			$args['file_import_uploaded']
		);
		file_put_contents('/usr/local/www/whitegoods.ru/data24/logs/ftest.log', print_r($f, 1), FILE_APPEND);
		//if (!preg_match("/.*\.xlsx/", $f['name']) || !preg_match("/.*excel.*/", $f['type'])) {
		if (!preg_match("/.*\.xlsx/", $f['name']) || $f['type'] != 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
			?><div><p style="display: inline-block; padding: 7px; border: 1px solid #f00; border-radius: 5px;">!Ошибка формата файла: <?=$f['name']?></p></div><?
		} else {
			$importFileId = CFile::SaveFile($f, "import/import");
			$importFile = CFile::GetFileArray($importFileId);
			$insert = Array(
				'upload_datetime' => $DB->GetNowFunction(),
				'file' => '"' . $DB->forSql(json_encode($importFile)) . '"',
				'type' => '"import"',
				'status' => 0
			);
			if ($args['import_field']) {
				$insert['filter'] = '"' . $DB->forSql(json_encode($args['import_field'])) . '"';
			}
			$res = $DB->Insert(
				"import_queue",
				$insert,
				$err_mess.__LINE__
			);

			if (strlen($strError)<=0) {
				$DB->Commit();
				?><div><p style="display: inline-block; padding: 7px; border: 1px solid #00f; border-radius: 5px;">Загружен файл: <?=$importFile['ORIGINAL_NAME']?></p></div><?
			} else {
				$DB->Rollback();
				?><div><p style="display: inline-block; padding: 7px; border: 1px solid #f00; border-radius: 5px;">Ошибка загрузки: <?=$importFile['ORIGINAL_NAME']?></p></div><?
			}
		}

		return true;

		$returned_data = self::execScript(
			'https://www.whitegoods.ru/local/modules/wg/exchange/import.php',
			//'https://24.interno.ru/local/modules/wg/exchange/import.php',
			$args
		);
		?><div><p style="display: inline-block; padding: 7px; border: 1px solid #0f0; border-radius: 5px;">Изменено товаров: <?=preg_replace("/.*\[total.([0-9]*)\].*/s", "$1", $returned_data);?></p></div><?
		?><div><p style="display: inline-block; padding: 7px; border: 1px solid #f00; border-radius: 5px;">Ошибок: <?=preg_replace("/.*\[errors.([0-9]*)\].*/s", "$1", $returned_data);?></p></div><?
	}

	public static function actionPrices($args)
	{
		global $DB;
		$DB->PrepareFields("import_queue");
		$DB->StartTransaction();
		$f = CFile::MakeFileArray(
			$args['file_price_import_uploaded']
		);
		$importFileId = CFile::SaveFile($f, "import/price");
		$importFile = CFile::GetFileArray($importFileId);
		$insert = Array(
			'upload_datetime' => $DB->GetNowFunction(),
			'file' => '"' . $DB->forSql(json_encode($importFile)) . '"',
			'type' => '"price"',
			'status' => 0
		);
		if ($args['price_import_field']) {
			$insert['filter'] = '"' . $DB->forSql(json_encode($args['price_import_field'])) . '"';
		}
		$res = $DB->Insert(
			"import_queue",
			$insert,
			$err_mess.__LINE__
		);

		if (strlen($strError)<=0) {
			$DB->Commit();
			?><div><p style="display: inline-block; padding: 7px; border: 1px solid #00f; border-radius: 5px;">Загружен файл: <?=$importFile['ORIGINAL_NAME']?></p></div><?
		} else {
			$DB->Rollback();
			?><div><p style="display: inline-block; padding: 7px; border: 1px solid #f00; border-radius: 5px;">Ошибка загрузки: <?=$importFile['ORIGINAL_NAME']?></p></div><?
		}

		return true;

		$returned_data = self::execScript(
			'https://www.whitegoods.ru/local/modules/wg/exchange/import.php',
			//'https://24.interno.ru/local/modules/wg/exchange/import.php',
			$args
		);
		?><div><p style="display: inline-block; padding: 7px; border: 1px solid #0f0; border-radius: 5px;">Изменено товаров: <?=preg_replace("/.*\[total.([0-9]*)\].*/s", "$1", $returned_data);?></p></div><?
		?><div><p style="display: inline-block; padding: 7px; border: 1px solid #f00; border-radius: 5px;">Ошибок: <?=preg_replace("/.*\[errors.([0-9]*)\].*/s", "$1", $returned_data);?></p></div><?
	}

	public static function actionProviders($args)
	{
		global $DB;
		$DB->PrepareFields("import_queue");
		$DB->StartTransaction();
		$f = CFile::MakeFileArray(
			$args['file_provider_import_uploaded']
		);
		$importFileId = CFile::SaveFile($f, "import/provider");
		$importFile = CFile::GetFileArray($importFileId);
		$insert = Array(
			'upload_datetime' => $DB->GetNowFunction(),
			'file' => '"' . $DB->forSql(json_encode($importFile)) . '"',
			'type' => '"provider"',
			'status' => 0
		);
		$res = $DB->Insert(
			"import_queue",
			$insert,
			$err_mess.__LINE__
		);

		if (strlen($strError)<=0) {
			$DB->Commit();
			?><div><p style="display: inline-block; padding: 7px; border: 1px solid #00f; border-radius: 5px;">Загружен файл: <?=$importFile['ORIGINAL_NAME']?></p></div><?
		} else {
			$DB->Rollback();
			?><div><p style="display: inline-block; padding: 7px; border: 1px solid #f00; border-radius: 5px;">Ошибка загрузки: <?=$importFile['ORIGINAL_NAME']?></p></div><?
		}

		return true;

	}

	public static function update($item, $type = '')
	{
/*
(48,   2, 1, 51, 52, 53,  66, 46, 1734, 50,  65,  55,  56,  59,  60, 61, 62,  63, 1775)
(93, 111,    96, 97, 98, 109, 89,   90, 95, 108, 100, 101, 103, 104,    105, 106, 112)
*/
		$arSelect = Array("ID", "IBLOCK_ID");
		$arFilter = Array("IBLOCK_ID" => CWG::PRODUCTS_IBLOCK_ID, "CODE" => $item['CODE']);
		$res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize" => 1), $arSelect);
		if ($ob = $res->GetNextElement()) {
			$PROP = Array();
			$PRODUCT_ID = $ob->GetFields()['ID'];
			$PROPS = $ob->GetProperties();
			foreach ($PROPS as $p) {
				if ($p['PROPERTY_TYPE'] != 'F') {
					if ($p['PROPERTY_TYPE'] == 'L') {
						$PROP[$p['ID']] = $p['VALUE_ENUM_ID'];
					} else if (in_array($p['CODE'], Array('VIDEO', 'PRESENCE_TEXT', 'IMINFO'))) {
						$PROP[$p['ID']] = $p['~VALUE'];
					} else {
						$PROP[$p['ID']] = $p['VALUE'];
					}
				}
			}
			$el = new CIBlockElement;
			global $USER;
			$arLoadProductArray = Array(
				"MODIFIED_BY" => $USER->GetID()
			);
			if (isset($item['PROPERTY_SUBNAME'])) {
				$PROP[93] = $item['PROPERTY_SUBNAME'];
			}
			$cur = 1;
			if (isset($item['MANUFACTURER'])) {
				//echo 'm: ', $item['MANUFACTURER'], '; ', self::$manufacturersCodeList[$item['MANUFACTURER']]['ID'];
				//echo 'm: [', print_r($item['MANUFACTURER'], 1), ']; [', self::getManufacturersCodeList(trim($item['MANUFACTURER']))['ID'], ']';
				//$PROP[111] = self::$manufacturersCodeList[$item['MANUFACTURER']]['ID'];
				$PROP[111] = self::getManufacturersCodeList(trim($item['MANUFACTURER']))['ID'];
				//$cur = self::curRate(self::$manufacturersCodeList[$item['MANUFACTURER']]['CUR']);
			/*} else if (isset($PROP[111]) && isset(self::getManufacturersList()[$PROP[111]]['CUR'])) {
				$cur = self::curRate(self::getManufacturersList()[$PROP[111]]['CUR']);*/
			}
			if (isset($item['NAME'])) {
				$arLoadProductArray['NAME'] = $item['NAME'];
			}
			/*if (isset($item['PRICE_RUB'])) {
				if ($cur == 1) {
					$PROP[1] = $item['PRICE_RUB'];
				} else {
					$PROP[1] = round($item['PRICE_RUB'] / $cur, 2);
				}
			}*/
			if (isset($item['PRICE'])) {
				$res = CPrice::GetList(
					array(),
					array(
						"PRODUCT_ID" => $PRODUCT_ID,
						"CATALOG_GROUP_ID" => 1
					)
				);
				$arr = $res->Fetch();
				$default_cur = 'RUB';
				if ($arr) {
					$default_cur = $arr['CURRENCY'];
				}
				$cur = trim($item['CURRENCY'] ? $item['CURRENCY'] : $default_cur);
				$price = (intval($cur) == 1 ? $item['PRICE'] : $item['PRICE'] / self::curRate($cur));
//echo "cp", print_r([$cur, $price], 1), "\n";
				$arFields = Array(
					"PRODUCT_ID" => $PRODUCT_ID,
					"CATALOG_GROUP_ID" => 1,
					"PRICE" => $price,
					"CURRENCY" => $cur,
				);

				if ($arr) {
					CPrice::Update($arr["ID"], $arFields);
				} else {
					CPrice::Add($arFields);
				}
				//$PROP[1] = $item['PRICE_RUB'];
			}
			if (isset($item['PROPERTY_YANDEX_BID'])) {
				$PROP[96] = $item['PROPERTY_YANDEX_BID'];
			}
			if (isset($item['PROPERTY_YANDEX_CBID'])) {
				$PROP[97] = $item['PROPERTY_YANDEX_CBID'];
			}
			if (isset($item['ACTIVE'])) {
				$arLoadProductArray['ACTIVE'] = ($item['ACTIVE'] == 1 ? "Y" : "N");
			}
			if (isset($item['PROPERTY_XML'])) {
				$PROP[98] = ($item['PROPERTY_XML'] == 1?Array('VALUE' => 80):($item['PROPERTY_XML'] == 2?Array('VALUE' => 81):Array('VALUE' => 79)));
				//file_put_contents('importP9.log', $item['PROPERTY_XML'] . PHP_EOL . print_r($PROP[53], 1) . PHP_EOL, FILE_APPEND);
			}
			if (isset($item['PROPERTY_AVAIBLE_STATUS'])) {
				switch ($item['PROPERTY_AVAIBLE_STATUS']) {
					case 0:
						$PROP[109] = Array('VALUE' => 92);//'Не показывать'
						break;
					case 1:
						$PROP[109] = Array('VALUE' => 87);//'В наличии'
						break;
					case 2:
						$PROP[109] = Array('VALUE' => 88);//'Под заказ'
						break;
					case 3:
						$PROP[109] = Array('VALUE' => 90);//'Ожидается'
						break;
					case 4:
						$PROP[109] = Array('VALUE' => 89);//'Уточняйте у менеджера'
						break;
					case 5:
						$PROP[109] = Array('VALUE' => 91);//'Нет в наличии'
						break;
					default:
						$PROP[109] = false;
						break;
				}
			}
			if (isset($item['PROPERTY_DELIVERY'])) {
				$PROP[89] = $item['PROPERTY_DELIVERY'];
			}
			if (isset($item['PROPERTY_DELIVERY_DC'])) {
				$PROP[90] = $item['PROPERTY_DELIVERY_DC'];
			}
			if (isset($item['PROPERTY_PVZ_PRICE'])) {
				$PROP[112] = $item['PROPERTY_PVZ_PRICE'];
			}
			if (isset($item['PROPERTY_VENDORCODE'])) {
				$PROP[95] = $item['PROPERTY_VENDORCODE'];
			}
			if (isset($item['PROPERTY_GUARANTEE_PERIOD'])) {
				$PROP[108] = $item['PROPERTY_GUARANTEE_PERIOD'];
			}
			if (isset($item['PROPERTY_PREPAYMENT_KEY'])) {
				$PROP[100] = ($item['PROPERTY_PREPAYMENT_KEY']==1?Array('VALUE' => 83):false);
			}
			if (isset($item['PROPERTY_PREPAYMENT'])) {
				$PROP[101] = $item['PROPERTY_PREPAYMENT'];
			}
			if (isset($item['PROPERTY_YAN_CPA'])) {
				$PROP[103] = ($item['PROPERTY_YAN_CPA']==1?Array('VALUE' => 84):false);
			}
			if (isset($item['PROPERTY_NASKLADE'])) {
				$PROP[104] = $item['PROPERTY_NASKLADE'];
			}
			if (isset($item['PROPERTY_ZAKUPKAPRICE'])) {
				$PROP[184] = $item['PROPERTY_ZAKUPKAPRICE'];
			}
			if (isset($item['PROPERTY_POSTAVSHIK'])) {
				$PROP[105] = $item['PROPERTY_POSTAVSHIK'];
			}
			if (isset($item['PROPERTY_SAMOVIVOZ'])) {
				$PROP[106] = ($item['PROPERTY_SAMOVIVOZ']==1?Array('VALUE' => 85):false);
			}
			if (isset($item['PROPERTY_WEIGHT'])) {
				CWG::setElementExtraFieldsByName($item['CODE'], 'Вес, кг', $item['PROPERTY_WEIGHT']);
			}
			if (isset($item['PROPERTY_PRESENCE_TEXT'])) {
				$PROP[113] = $item['PROPERTY_PRESENCE_TEXT'];
			} else if ($type == 'price' && !empty(self::$headers['J'])) {
				$PROP[113] = '';
			}
			/*if (isset($item['PROPERTY_WEIGHT'])) {
				$PROP[39] = $item['PROPERTY_WEIGHT'];
			}
			if (isset($item['PROPERTY_DIMENSIONS'])) {
				$PROP[34] = $item['PROPERTY_DIMENSIONS'];
			}*/

			if (isset($item['PROPERTY_PROVIDER'])) {
				$tmp1737 = Array();
				foreach ($item['PROPERTY_PROVIDER'] as $item1737) {
					$tmp1737[] = implode('|', [$item1737[0], $item1737[1], $item1737[2], $item1737[3]]);
				}
				$PROP[114] = implode(';', $tmp1737);
				//file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/prim2.log', print_r($item['PROPERTY_PROVIDER'], 1) . PHP_EOL . print_r($PROP[1737], 1));
			}
	
			$arLoadProductArray["PROPERTY_VALUES"] = $PROP;
			$res = $el->Update($PRODUCT_ID, $arLoadProductArray);
			return $res;
		} else {
			return 'no';
		}
	}

	public static $headers = Array();

	public static function import($queue)
	{
		global $DB;
		$items = Array();
		if (isset($queue['filter'])) {
			$filter = $queue['filter'];
		} else {
			$filter = false;
		}
		$inputFileType = 'Xlsx';
		$inputFileName = $_SERVER['DOCUMENT_ROOT'] . json_decode($queue['file'], 1)['SRC'];
	
		try {
			$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
		} catch (Throwable $ert) {
			echo $ert->getMessage();
			die();
		}
		$total = 0;
		$errors = 0;
		$errorsArr = Array();
		self::$headers = Array();
		foreach ($spreadsheet->getActiveSheet()->getRowIterator() as $row) {
			if ($row->getRowIndex() == 1 && $queue['type'] == 'price') {
				$cellIterator = $row->getCellIterator();
				foreach ($cellIterator as $cell) {
					if (isset(self::$price_import_cols[$cell->getColumn()]) && !empty($cell->getValue())) {
						self::$headers[$cell->getColumn()] = true;
					}
				}
			}
			if ($row->getRowIndex() == 1) {
				continue;
			}
			$cellIterator = $row->getCellIterator();
			$item = Array();
			switch ($queue['type']) {
				case 'provider':
					foreach ($cellIterator as $cell) {
						$item[self::$provider_import_cols[$cell->getColumn()]] = $cell->getValue();
						//echo $cell->getValue(), ', ';
					}
					break;
				case 'price':
					foreach ($cellIterator as $cell) {
						if (isset(self::$price_import_cols[$cell->getColumn()])) {
							if ($filter) {
								if (!in_array(self::$price_filters[$cell->getColumn()], $filter) && $cell->getColumn() != 'A') {
									continue;
								}
							}
							$item[self::$price_import_cols[$cell->getColumn()]] = $cell->getValue();
						}
					}
					break;
				case 'import':
					foreach ($cellIterator as $cell) {
						if ($filter) {
							if (!in_array(self::$filters[$cell->getColumn()], $filter) && $cell->getColumn() != 'A') {
								continue;
							}
						}
						$item[self::$import_cols[$cell->getColumn()]] = $cell->getValue();
					}
					break;
			}
			if (!$item['CODE']) {
				continue;
			}
			$items[] = $item;
		}

		if ($queue['type'] == 'provider') {
			$itemsArr = Array();
			foreach ($items as $item) {
				if (!isset($itemsArr[$item['CODE']])) {
					$itemsArr[$item['CODE']] = Array(
						'CODE' => $item['CODE'],
						'PROPERTY_PROVIDER' => Array(
							Array(
								$item['PROPERTY_PROVIDER'],
								$item['PROPERTY_PROVIDER_PRICE'],
								$item['PROPERTY_PROVIDER_PRESENTS'],
								$item['PROPERTY_PROVIDER_RRC']
							)
						)
					);
				} else {
					$itemsArr[$item['CODE']]['PROPERTY_PROVIDER'][] = Array(
						$item['PROPERTY_PROVIDER'],
						$item['PROPERTY_PROVIDER_PRICE'],
						$item['PROPERTY_PROVIDER_PRESENTS'],
						$item['PROPERTY_PROVIDER_RRC']
					);
				}
			}
			$items = $itemsArr;
		}
		foreach ($items as $key => $item) {
			echo " ", $item['CODE'], ", ";
			//file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/ni2.log', print_r($item, 1) . PHP_EOL, FILE_APPEND);
			if ($queue['type'] == 'price') {
				$up = self::update($item, 'price');
			} else {
				$up = self::update($item);
			}
			if ($up) {
				if ($up === 'no') {
					$errors++;
					$errorsArr[] = Array(
						'code' => $item['CODE'],
						'error' => 'nope',
						'res' => $up
					);
				} else {
					$total++;
				}
			} else {
				$errors++;
				$errorsArr[] = Array(
					'code' => $item['CODE'],
					'error' => 'error'
				);
			}
		}

		if (!empty($errorsArr)) {
			file_put_contents(
				'errors' . date('Ymd_His') . '.log',
				print_r($errorsArr, 1)
			);
		}

		$DB->PrepareFields("import_queue");
		$arFields = array(
			"status" => 2,
			"uploaded" => $total,
			"errors" => $errors,
			"errors_data" => '"' . $DB->forSql(json_encode($errorsArr)) . '"'
		);
		$DB->StartTransaction();
		$DB->Update("import_queue", $arFields, "WHERE id=" . $queue['id'], $err_mess.__LINE__);
		if (strlen($strError) <= 0) {
			$DB->Commit();
		} else {
			$DB->Rollback();
		}
	}

	public static function cancelQueue($queue = 0)
	{
		if ($queue == 0) {
			return;
		}
		global $DB;
		$DB->PrepareFields("import_queue");
		$arFields = array(
			"status" => 4
		);
		$DB->StartTransaction();
		$DB->Update("import_queue", $arFields, "WHERE id=" . $queue, $err_mess.__LINE__);
		if (strlen($strError) <= 0) {
			$DB->Commit();
		} else {
			$DB->Rollback();
		}
	}

	public static function queueAdminTableImportImport()
	{
		global $DB;
		$sql = 'SELECT * FROM import_queue WHERE type="import" ORDER BY upload_datetime DESC LIMIT 50';
		$res = $DB->query($sql);
		$outTable = Array();
		$outTable[] = '
<tr>
	<th rowspan="2">id</th>
	<th rowspan="2">Дата</th>
	<th rowspan="2">Файл</th>
	<th rowspan="2">Статус</th>
	<th rowspan="2">Импортировано</th>
	<th colspan="2">Ошибок</th>
</tr>
<tr>
	<th>Нет</th>
	<th>Ошибка</th>
</tr>
		';
		while ($ob = $res->fetch()) {
			$errorsData = json_decode($ob['errors_data'], 1);
			$errorsOut = Array(
				'nope' => 0,
				'errors' => 0
			);
			foreach ($errorsData as $error) {
				switch ($error['error']) {
					case 'nope':
						$errorsOut['nope']++;
						break;
					case 'error':
						$errorsOut['errors']++;
						break;
				}
			}
			$outTable[] = '
<tr>
	<td rowspan="2">' . $ob['id'] . '</td>
	<td rowspan="2">' . $ob['upload_datetime'] . '</td>
	<td rowspan="2">' . json_decode($ob['file'], 1)['ORIGINAL_NAME'] . '</td>
	<td rowspan="2">' . self::$statuses[$ob['status']] . ($ob['status'] < 2 ? '<br/><span class="cancel-import" onclick="cancelImport(' . $ob['id'] . ')">отменить</span>' : '') . '</td>
	<td rowspan="2">' . $ob['uploaded'] . '</td>
	<td colspan="2">' . $ob['errors'] . '</td>
</tr>
<tr>
	<td>' . $errorsOut['nope'] . '</td>
	<td>' . $errorsOut['errors'] . '</td>
</tr>
';
		}
		return '<table class="adm-wg-table">' . implode("", $outTable) . '</table>';
	}

	public static function queueAdminTableImportPrice()
	{
		global $DB;
		$sql = 'SELECT * FROM import_queue WHERE type="price" ORDER BY upload_datetime DESC LIMIT 50';
		$res = $DB->query($sql);
		$outTable = Array();
		$outTable[] = '
<tr>
	<th rowspan="2">id</th>
	<th rowspan="2">Дата</th>
	<th rowspan="2">Файл</th>
	<th rowspan="2">Статус</th>
	<th rowspan="2">Импортировано</th>
	<th colspan="2">Ошибок</th>
</tr>
<tr>
	<th>Нет</th>
	<th>Ошибка</th>
</tr>
		';
		while ($ob = $res->fetch()) {
			$errorsData = json_decode($ob['errors_data'], 1);
			$errorsOut = Array(
				'nope' => 0,
				'errors' => 0
			);
			foreach ($errorsData as $error) {
				switch ($error['error']) {
					case 'nope':
						$errorsOut['nope']++;
						break;
					case 'error':
						$errorsOut['errors']++;
						break;
				}
			}
			$outTable[] = '
<tr>
	<td rowspan="2">' . $ob['id'] . '</td>
	<td rowspan="2">' . $ob['upload_datetime'] . '</td>
	<td rowspan="2">' . json_decode($ob['file'], 1)['ORIGINAL_NAME'] . '</td>
	<td rowspan="2">' . self::$statuses[$ob['status']] . ($ob['status'] < 2 ? '<br/><span class="cancel-import" onclick="cancelImport(' . $ob['id'] . ')">отменить</span>' : '') . '</td>
	<td rowspan="2">' . $ob['uploaded'] . '</td>
	<td colspan="2">' . $ob['errors'] . '</td>
</tr>
<tr>
	<td>' . $errorsOut['nope'] . '</td>
	<td>' . $errorsOut['errors'] . '</td>
</tr>
';
		}
		return '<table class="adm-wg-table">' . implode("", $outTable) . '</table>';
	}

	public static function queueAdminTableImportProvider()
	{
		global $DB;
		$sql = 'SELECT * FROM import_queue WHERE type="provider" ORDER BY upload_datetime DESC LIMIT 50';
		$res = $DB->query($sql);
		$outTable = Array();
		$outTable[] = '
<tr>
	<th rowspan="2">id</th>
	<th rowspan="2">Дата</th>
	<th rowspan="2">Файл</th>
	<th rowspan="2">Статус</th>
	<th rowspan="2">Импортировано</th>
	<th colspan="2">Ошибок</th>
</tr>
<tr>
	<th>Нет</th>
	<th>Ошибка</th>
</tr>
		';
		while ($ob = $res->fetch()) {
			$errorsData = json_decode($ob['errors_data'], 1);
			$errorsOut = Array(
				'nope' => 0,
				'errors' => 0
			);
			foreach ($errorsData as $error) {
				switch ($error['error']) {
					case 'nope':
						$errorsOut['nope']++;
						break;
					case 'error':
						$errorsOut['errors']++;
						break;
				}
			}
			$outTable[] = '
<tr>
	<td rowspan="2">' . $ob['id'] . '</td>
	<td rowspan="2">' . $ob['upload_datetime'] . '</td>
	<td rowspan="2">' . json_decode($ob['file'], 1)['ORIGINAL_NAME'] . '</td>
	<td rowspan="2">' . self::$statuses[$ob['status']] . ($ob['status'] < 2 ? '<br/><span class="cancel-import" onclick="cancelImport(' . $ob['id'] . ')">отменить</span>' : '') . '</td>
	<td rowspan="2">' . $ob['uploaded'] . '</td>
	<td colspan="2">' . $ob['errors'] . '</td>
</tr>
<tr>
	<td>' . $errorsOut['nope'] . '</td>
	<td>' . $errorsOut['errors'] . '</td>
</tr>
';
		}
		return '<table class="adm-wg-table">' . implode("", $outTable) . '</table>';
	}

	public static function errorTest($queue)
	{
	}
}
