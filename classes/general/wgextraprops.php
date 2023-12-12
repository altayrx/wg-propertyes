<?php
class CWGEP
{
	private static $toDelete = Array();
	private static $newProp = Array();
	private static $elementProp = Array();
	private static $elementCopy = false;

	const PRODUCTS_IBLOCK_ID = 17;

	public static function extraFields(&$extraFieldsSet)
	{
		foreach ($extraFieldsSet as &$extraFieldsSetItem) {
			if (!$extraFieldsSetItem || $extraFieldsSetItem == '') {
				unset($extraFieldsSetItem);
			}
			$extraFieldsSetItem = trim($extraFieldsSetItem);
		}
	}

	public static function getSectionExtraFields($iblockId, $sectionId)
	{
		\Bitrix\Main\Loader::includeModule("highloadblock");
		//use Bitrix\Highloadblock as HL;
		//use Bitrix\Main\Entity;

		$hlbl = 6;
		$hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($hlbl)->fetch(); 

		$entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock); 
		$entity_data_class = $entity->getDataClass(); 

		$rsData = $entity_data_class::getList(array(
			"select" => array("*"),
			"order" => array("ID" => "ASC"),
			"filter" => array("UF_SECTIONS"=>$sectionId)
		));

/*while($arData = $rsData->Fetch()){
   var_dump($arData);
}*/
		return $rsData;

		global $DB;
		$sql = '
SELECT *
FROM extra_fields
WHERE extra_fields_categories_id=' . $sectionId . '
ORDER BY card_order;';
		$res = $DB->Query($sql, false, $err_mess.__LINE__);
		return $res;
	}

	public static function getSectionExtraFieldsCount($sectionId)
	{
		\Bitrix\Main\Loader::includeModule("highloadblock");
		//use Bitrix\Highloadblock as HL;
		//use Bitrix\Main\Entity;

		$hlbl = 6;
		$hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($hlbl)->fetch(); 

		$entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock); 
		$entity_data_class = $entity->getDataClass(); 

		$rsData = $entity_data_class::getList(array(
			"select" => array("ID"),
			"filter" => array("UF_SECTIONS"=>$sectionId)
		));
		return $rsData->getSelectedRowsCount();

		global $DB;
		$sql = '
SELECT count(extra_fields_categories_id) AS sectionsCount
FROM extra_fields
WHERE extra_fields_categories_id='.$sectionId.'
ORDER BY card_order;';
		$res = $DB->Query($sql, false, $err_mess.__LINE__);
		return $res->GetNext()['sectionsCount'];
	}

	public static function addSectionExtraFields($sectionId, $extraFieldsName, $extraFieldsSet, $extraFieldsRange, $options)
	{
		return;
		global $DB;
		self::extraFields($extraFieldsSet);
		$DB->PrepareFields("extra_fields");
		$DB->StartTransaction();
		$update = Array(
			'extra_fields_categories_id' => $sectionId,
			'extra_fields_name' => '"'.htmlentities($extraFieldsName).'"',
			'extra_fields_set' => '"'.implode("|", $extraFieldsSet).'"',
			'extra_fields_range' => '"'.implode("|", $extraFieldsRange).'"',
			'last_modified' => '"'.date('Y-m-d H:i:s').'"'
		);
		$update = array_merge($update, $options);
		$res = $DB->Insert(
			"extra_fields",
			$update,
			$err_mess.__LINE__
		);
		$res = intval($res);
		if (strlen($strError)<=0) {
			$DB->Commit();
		} else {
			$DB->Rollback();
		}
		return $res;
	}

	/**
	 * Изменение привязки экстраполей при изменении символьного кода раздела
	 */
	public static function changeSectionExtraFields($sectionIdOld, $sectionIdNew)
	{
		return;
		global $DB;
		$DB->PrepareFields("extra_fields");
		$DB->StartTransaction();
		$DB->Update(
			"extra_fields",
			Array(
				'extra_fields_categories_id' => $sectionIdNew
			),
			"WHERE extra_fields_categories_id=".$sectionIdOld,
			$err_mess.__LINE__
		);
		if (strlen($strError)<=0) {
			$DB->Commit();
			return true;
		} else {
			$DB->Rollback();
			return false;
		}
	}

	public static function setSectionExtraFields($sectionId, $extraFieldsId, $extraFieldsName, $extraFieldsSet, $extraFieldsRange, $options)
	{
		return;
		global $DB;
		self::extraFields($extraFieldsSet);
		$DB->PrepareFields("extra_fields");
		$DB->StartTransaction();
		$update = Array(
			'extra_fields_name' => '"'.htmlentities($extraFieldsName).'"',
			'extra_fields_set' => '"'.implode("|", $extraFieldsSet).'"',
			'extra_fields_range' => '"'.implode("|", $extraFieldsRange).'"',
			'last_modified' => '"'.date('Y-m-d H:i:s').'"'
		);
		$update = array_merge($update, $options);
		$DB->Update(
			"extra_fields",
			$update,
			"WHERE extra_fields_id=".$extraFieldsId." AND extra_fields_categories_id=".$sectionId,
			$err_mess.__LINE__
		);
		if (strlen($strError)<=0) {
			$DB->Commit();
			return true;
		} else {
			$DB->Rollback();
			return false;
		}
	}

	public static function delSectionExtraFields($extraFieldsId)
	{
		return;
		global $DB;
		$DB->PrepareFields("extra_fields");
		$DB->StartTransaction();
		$sql = '
DELETE FROM extra_fields
WHERE extra_fields_id='.$extraFieldsId.';';
		$DB->Query($sql, false, $err_mess.__LINE__);
		if (strlen($strError)<=0) {
			$DB->Commit();
			return true;
		} else {
			$DB->Rollback();
			return false;
		}
	}

	public static function getElementExtraFields($elementId, $sectionId)
	{
		return;
		global $DB;
		$sql = '
SELECT
	e.extra_fields_id AS PROPERTY_ID,
	e.extra_fields_name AS NAME,
	e.extra_fields_set AS LIST,
	p.products_id AS PRODUCT_ID,
	p.products_extra_fields_value AS VALUE
FROM extra_fields AS e
LEFT JOIN products_to_extra_fields AS p
ON p.products_extra_fields_id=e.extra_fields_id AND p.products_id='.$elementId.'
WHERE
	e.extra_fields_categories_id='.$sectionId.'
ORDER BY e.card_order;';
		$res = $DB->Query($sql, false, $err_mess.__LINE__);
		return $res;
	}

	public static function getProps2($elementId, $sectionId)
	{
		return;
		if (!\Bitrix\Main\Loader::includeModule('iblock')) {
			ShowError('Модуль iblock не установлен');
			return;
		}

		global $DB;
		$props = Array();
		$sectionId = CIBlockSection::GetByID($sectionId)->GetNext()['CODE'];
		$sql = '
SELECT
	e.extra_fields_id AS PROPERTY_ID,
	e.extra_fields_name AS NAME,
	e.extra_fields_set AS LIST,
	p.products_id AS PRODUCT_ID,
	p.products_extra_fields_value AS VALUE
FROM extra_fields AS e
LEFT JOIN products_to_extra_fields AS p
ON p.products_extra_fields_id=e.extra_fields_id AND p.products_id='.$elementId.'
WHERE
	e.extra_fields_categories_id='.$sectionId.'
	AND e.display_on_card=1
ORDER BY card_order;';
		$res = $DB->Query($sql, false, $err_mess.__LINE__);
		while ($ob = $res->GetNext()) {
			if ($ob['VALUE']) {
				$props['PARAM'.$ob['PROPERTY_ID']] = Array(
					'NAME' => $ob['NAME'],
					'VALUE' => $ob['VALUE']
				);
			}
		}
		return $props;
	}

	public static function getProps($elementId, $sectionId)
	{
		return;
		if (!\Bitrix\Main\Loader::includeModule('iblock')) {
			ShowError('Модуль iblock не установлен');
			return;
		}

		$props = Array();
		$cache = new CPHPCache();
		$cache_id = 'goods_props_' . $elementId . $sectionId;
		if ($cache->InitCache(9000, $cache_id, 'goods_props')) {
			$db_list = $cache->GetVars();
			if (is_array($db_list[$cache_id]) && (count($db_list[$cache_id]) > 0)) {
				$props = $db_list[$cache_id]['RESULT'];
			}
		} else {
			global $DB;
			$sectionId = CIBlockSection::GetByID($sectionId)->GetNext()['CODE'];
			$sql = '
SELECT
	e.extra_fields_id AS PROPERTY_ID,
	e.extra_fields_name AS NAME,
	e.extra_fields_set AS LIST,
	p.products_id AS PRODUCT_ID,
	p.products_extra_fields_value AS VALUE
FROM extra_fields AS e
LEFT JOIN products_to_extra_fields AS p
ON p.products_extra_fields_id=e.extra_fields_id
WHERE
	e.extra_fields_categories_id='.$sectionId.'
	AND e.display_on_card=1
	AND p.products_id='.$elementId.'
ORDER BY card_order;';
			$res = $DB->Query($sql, false, $err_mess.__LINE__);
			while ($ob = $res->GetNext()) {
				if ($ob['VALUE']) {
					$props['PARAM'.$ob['PROPERTY_ID']] = Array(
						'NAME' => $ob['NAME'],
						'VALUE' => $ob['VALUE']
					);
				}
			}
			$cache->StartDataCache(9000, $cache_id, 'goods_props');
			$cache->EndDataCache(array($cache_id => Array(
				'RESULT' => $props,
			)));
		}
		return $props;
	}

	public static function props(&$items, $addName = false, $ignoreDisplay = false)
	{
		return;
		if (!\Bitrix\Main\Loader::includeModule('iblock')) {
			ShowError('Модуль iblock не установлен');
			return;
		}

		global $DB;
		foreach ($items as &$item) {
			$item_code = $item['CODE'];
			$item_section_id = $item['IBLOCK_SECTION_ID'];
			$item_section_code = CIBlockSection::GetByID($item_section_id)->GetNext()['CODE'];

			$ignoreDisplayQuery = '';
			if (!$ignoreDisplay) {
				$ignoreDisplayQuery = 'AND e.display_on_listing=1';
			}
			$sql = '
SELECT
	e.extra_fields_id AS PROPERTY_ID,
	e.extra_fields_name AS NAME,
	e.extra_fields_set AS LIST,
	p.products_id AS PRODUCT_ID,
	p.products_extra_fields_value AS VALUE,
	e.display_on_card AS DISPLAY_CARD,
	e.display_on_listing AS DISPLAY_LIST
FROM extra_fields AS e
LEFT JOIN products_to_extra_fields AS p
ON p.products_extra_fields_id=e.extra_fields_id AND p.products_id='.$item_code.'
WHERE
	e.extra_fields_categories_id='.$item_section_code.'
	' . $ignoreDisplayQuery . '
ORDER BY listing_order;';
			$res = $DB->Query($sql, false, print_r($item, 1). __LINE__);
			while ($ob = $res->GetNext()) {
				if ($addName) {
					$item['PROPERTY_PARAM'.$ob['PROPERTY_ID']] = Array(
						'NAME' => $ob['NAME'],
						'VALUE' => $ob['VALUE'],
						'DISPLAY_CARD' => $ob['DISPLAY_CARD'],
						'DISPLAY_LIST' => $ob['DISPLAY_LIST']
					);
				} else if ($ob['VALUE']) {
					$item['PROPERTY_PARAM'.$ob['PROPERTY_ID'].'_VALUE'] = $ob['VALUE'];
				}
			}

		}
	}

	public static function prop($item, $propName)
	{
		return;
		if (!\Bitrix\Main\Loader::includeModule('iblock')) {
			ShowError('Модуль iblock не установлен');
			return;
		}

		global $DB;
		$item_code = $item['CODE'];
		$item_section_id = $item['IBLOCK_SECTION_ID'];
		$item_section_code = CIBlockSection::GetByID($item_section_id)->GetNext()['CODE'];

		$sql = '
SELECT
	e.extra_fields_id AS PROPERTY_ID,
	e.extra_fields_name AS NAME,
	e.extra_fields_set AS LIST,
	p.products_id AS PRODUCT_ID,
	p.products_extra_fields_value AS VALUE
FROM extra_fields AS e
LEFT JOIN products_to_extra_fields AS p
ON p.products_extra_fields_id=e.extra_fields_id AND p.products_id='.$item_code.'
WHERE
	e.extra_fields_categories_id='.$item_section_code.'
	AND e.extra_fields_name="' . $propName . '"
ORDER BY listing_order;';
		$res = $DB->Query($sql, false, $err_mess.__LINE__);
		while ($ob = $res->GetNext()) {
			if ($ob['VALUE']) {
				return $ob['VALUE'];
			}
		}
	}

	public static function getRanges($sectionId, $productsIds = Array())
	{
		return;
		if (!\Bitrix\Main\Loader::includeModule('iblock')) {
			ShowError('Модуль iblock не установлен');
			return;
		}

		global $DB;
		$filter = Array();
		$sql = '
SELECT
	extra_fields_id AS PROPERTY_ID,
	extra_fields_name AS NAME,
	extra_fields_range AS LIST,
	filter_order AS SORT
FROM extra_fields
WHERE
	extra_fields_categories_id='.$sectionId.'
	AND display_on_filter=1
ORDER BY filter_order;';
		$res = $DB->Query($sql, false, $err_mess.__LINE__);
		while ($ob = $res->GetNext()) {
			if (preg_match('/\|/', $ob['LIST'])) {
				$filter[$ob['PROPERTY_ID']] = Array(
					'SORT' => $ob['SORT'],
					'NAME' => $ob['NAME'],
					'LIST' => explode("|", $ob['LIST'])
				);
			}
		}
		return $filter;
	}

	public static function getFilter($sectionId, $productsIds = Array())
	{
		return;
		if (!\Bitrix\Main\Loader::includeModule('iblock')) {
			ShowError('Модуль iblock не установлен');
			return;
		}

		if (!empty($productsIds)) {
			array_walk(
				$productsIds,
				function(&$item, $key) {
					$item = '"' . $item . '"';
				}
			);
		}

		global $DB;
		$filter = Array();
		$sql = '
SELECT
	extra_fields_id AS PROPERTY_ID,
	extra_fields_name AS NAME,
	extra_fields_set AS LIST,
	extra_fields_range AS RANGES,
	filter_order AS SORT
FROM extra_fields
WHERE
	extra_fields_categories_id='.$sectionId.'
	AND display_on_filter=1
ORDER BY filter_order;';
		$res = $DB->Query($sql, false, $err_mess.__LINE__);
		while ($ob = $res->GetNext()) {
			if (preg_match('/\|/', $ob['LIST']) && !preg_match('/\|/', $ob['RANGES'])) {
				$filter[$ob['PROPERTY_ID']] = Array(
					'SORT' => $ob['SORT'],
					'NAME' => $ob['NAME'],
					'LIST' => explode("|", $ob['LIST'])
				);
			} else if (!preg_match('/\|/', $ob['LIST']) && !empty($productsIds) && !preg_match('/\|/', $ob['RANGES'])) {
				$sql2 = '
SELECT
	products_extra_fields_value AS VALUE
FROM products_to_extra_fields
WHERE
	products_id IN (' . implode(', ', $productsIds) . ')
	AND products_extra_fields_id=' . $ob['PROPERTY_ID'] . '
';
				$res2 = $DB->Query($sql2, false, $err_mess.__LINE__);
				while ($ob2 = $res2->GetNext()) {
					if (!empty($ob2['VALUE'])) {
						if (!isset($filter[$ob['PROPERTY_ID']])) {
							$filter[$ob['PROPERTY_ID']] = Array(
								'NAME' => $ob['NAME'],
								'LIST' => Array($ob2['VALUE'])
							);
						} else {
							if (!in_array($ob2['VALUE'], $filter[$ob['PROPERTY_ID']]['LIST'])) {
								$filter[$ob['PROPERTY_ID']]['LIST'][] = $ob2['VALUE'];
							}
						}
					}
				}
			}
		}
		return $filter;
	}

	public static function getBXFilter($sectionId, $requestFilter = Array())
	{
		return;
		if (!\Bitrix\Main\Loader::includeModule('iblock')) {
			ShowError('Модуль iblock не установлен');
			return;
		}

		global $DB;
		$filter = Array();
		$req = array_column($requestFilter, 0);
		$req0 = $req;
		array_walk(
			$req,
			function(&$item, $key) {
				$item = '"' . htmlentities($item) . '"';
			}
		);

		$sql = '
SELECT
	extra_fields_id,
	extra_fields_name,
	extra_fields_set,
	extra_fields_range
FROM extra_fields
WHERE
	extra_fields_name IN ('.implode(",", $req).')
	AND extra_fields_categories_id='.$sectionId.';';
		$res = $DB->Query($sql, false, $err_mess.__LINE__);
		if ($res->SelectedRowsCount() == 0) {
			$filter = Array(0);
			return $filter;
		}
		while ($ob = $res->GetNext()) {
			$countSum = 0;
			$filter_tmp = Array();
			$req2 = $requestFilter[array_search(html_entity_decode($ob['extra_fields_name']), $req0)][1];
			$req3 = $req2;
			array_walk(
				$req2,
				function(&$item, $key) {
					$item = '"'.$item.'"';
				}
			);
			$sql2 = '
SELECT products_id
FROM products_to_extra_fields AS e
LEFT JOIN b_iblock_element AS b ON b.CODE=e.products_id
WHERE
	e.products_extra_fields_id='.$ob['extra_fields_id'].'
	AND e.products_extra_fields_value IN ('.implode(",", $req2).')
	AND b.ACTIVE="Y"
ORDER BY products_id';
			$res2 = $DB->Query($sql2, false, $err_mess.__LINE__);
			$countSum += $res2->selectedRowsCount();
			while ($ob2 = $res2->GetNext()) {
				if (!in_array($ob2['products_id'], $filter_tmp)) {
					$filter_tmp[] = $ob2['products_id'];
				}
			}

			if (!empty($filter_tmp)) {
				if (count($filter) == 0) {
					$filter = $filter_tmp;
				} else {
					$filter = array_intersect($filter, $filter_tmp);
					if (empty($filter)) {
						return Array(0);
					}
				}
			}
			$filter_tmp2 = Array();
			if (preg_match('/\|/', $ob['extra_fields_range'])) {
				$sql2 = '
				SELECT
					extra_fields_id
				FROM extra_fields
				WHERE
					extra_fields_id='.$ob['extra_fields_id'].'
					AND extra_fields_name IN ('.implode(",", $req).')
					AND extra_fields_categories_id='.$sectionId.';';
				$res2 = $DB->Query($sql2, false, $err_mess.__LINE__);
				while ($ob2 = $res2->GetNext()) {
					if (preg_match('/([0-9]+)-([0-9]+)/ui', $req3[0], $matches) > 0) {
						$query = "
SELECT e.products_id
FROM products_to_extra_fields AS e
LEFT JOIN b_iblock_element AS b ON b.CODE=e.products_id
WHERE
	b.ACTIVE='Y'
	AND REPLACE(e.products_extra_fields_value,',','.') BETWEEN ".$matches[1]."
	AND ".$matches[2]."
	AND e.products_extra_fields_id = '" . $ob2['extra_fields_id'] . "'";
					} else if (preg_match('/^меньше ([0-9]+)/ui', $req3[0], $matches) > 0) {
						$query = "
SELECT e.products_id
FROM products_to_extra_fields AS e
LEFT JOIN b_iblock_element AS b ON b.CODE=e.products_id
WHERE
	b.ACTIVE='Y'
	AND REPLACE(e.products_extra_fields_value,',','.') < ".$matches[1]."
	AND e.products_extra_fields_id = '" . $ob2['extra_fields_id'] . "'";
					} else if (preg_match('/^больше ([0-9]+)/ui', $req3[0], $matches) > 0) {
						$query ="
SELECT e.products_id
FROM products_to_extra_fields AS e
LEFT JOIN b_iblock_element AS b ON b.CODE=e.products_id
WHERE
	b.ACTIVE='Y'
	AND REPLACE(e.products_extra_fields_value,',','.') > ".$matches[1]."
	AND e.products_extra_fields_id = '" . $ob2['extra_fields_id'] . "'";
					} else {
						continue;
					}
					$r = $DB->Query($query, false, $err_mess.__LINE__);
					$countSum += $r->selectedRowsCount();
					while ($q = $r->fetch()) {
						if (!in_array($q['products_id'], $filter_tmp2)) {
							$filter_tmp2[] = $q['products_id'];
						}
					}
				}
			}
			if (!empty($filter_tmp2)) {
				if (count($filter) == 0) {
					$filter = $filter_tmp2;
				} else {
					$filter = array_intersect($filter, $filter_tmp2);
					if (empty($filter)) {
						return Array(0);
					}
				}
			}
			if ($countSum == 0) {
				return Array(0);
			}
		}
		if (count($filter) == 0 || empty($filter)) {
			$filter = Array(0);
		}
		return $filter;
	}

	public static function checkElementExtraFields($productId)
	{
		return true;
		global $DB;
		$sql = '
SELECT products_id
FROM products_to_extra_fields
WHERE products_id=' . $productId;
		$res = $DB->Query($sql, false, $err_mess.__LINE__);
		if ($res->SelectedRowsCount() > 0) {
			return true;
		} else {
			return false;
		}
	}

	public static function setElementExtraFieldsByName($productId, $extraName, $extraValue, $debug = false)
	{
		return;
		global $DB;
		$sql = '
SELECT e.extra_fields_id
FROM extra_fields AS e
LEFT JOIN products_to_extra_fields AS p
ON e.extra_fields_id=p.products_extra_fields_id
WHERE
	e.extra_fields_name="' . $extraName . '"
	AND p.products_id=' . $productId . ';';
if ($debug == true) {
	return $sql;
}
		$res = $DB->Query($sql, false, $err_mess.__LINE__);
		$products_extra_fields_id = $res->GetNext()['extra_fields_id'];
		if (!$products_extra_fields_id) {
			$sql = '
SELECT s.CODE
FROM b_iblock_element AS e
LEFT JOIN b_iblock_section AS s
ON e.IBLOCK_SECTION_ID=s.ID
WHERE e.CODE = ' . $productId . '';
			$res = $DB->Query($sql, false, $err_mess.__LINE__);
			$products_extra_fields_id = $res->GetNext()['CODE'];
			if (!$products_extra_fields_id) {
				return false;
			}
		}
		$product = Array(
			'products_id' => $productId,
			'products_extra_fields_id' => $products_extra_fields_id,
			'products_extra_fields_value' => '"' . $DB->forSql($extraValue) . '"',
			'last_modified' => '"'.date('Y-m-d H:i:s').'"'
		);
		self::setElementExtraFields($product);
	}

	public static function setElementExtraFields($product)
	{
		return;
		global $DB;
		$sql = '
SELECT products_id
FROM products_to_extra_fields
WHERE
	products_id='.$product['products_id'].'
	AND products_extra_fields_id='.$product['products_extra_fields_id'].';';
		$res = $DB->Query($sql, false, $err_mess.__LINE__);
		$products_id = $res->GetNext()['products_id'];
		if ($products_id > 0) {
			$update = $product;
			unset($update['products_id']);
			unset($update['products_extra_fields_id']);
			$DB->PrepareFields("products_to_extra_fields");
			$DB->StartTransaction();
			$DB->Update(
				"products_to_extra_fields",
				$update,
				"WHERE products_id=".$product['products_id']." AND products_extra_fields_id=".$product['products_extra_fields_id'],
				$err_mess.__LINE__
			);
			if (strlen($strError)<=0) {
				$DB->Commit();
				return true;
			} else {
				$DB->Rollback();
				return false;
			}
		} else {
			$DB->PrepareFields("extra_fields");
			$DB->StartTransaction();
			$res = $DB->Insert(
				"products_to_extra_fields",
				$product,
				$err_mess.__LINE__
			);
			$res = intval($res);
			if (strlen($strError)<=0) {
				$DB->Commit();
			} else {
				$DB->Rollback();
			}
			return $res;
		}
	}

	public function OnSectionInit()
	{
/*		CJSCore::RegisterExt('wgep', array(
			'js' => '/local/admin/wg.js',
			'css' => '/local/admin/wg.css',
			'rel' => Array('jquery')
		));
		CJSCore::Init(array("wgep"));*/

		if (
			isset($_GET['IBLOCK_ID']) && $_GET['IBLOCK_ID'] == self::PRODUCTS_IBLOCK_ID
			&& (
				(isset($_GET['ID']) && intval($_GET['ID']) > 0)
				|| (isset($_REQUEST['wgsection']) && intval($_REQUEST['wgsection']) > 0)
			)
		) {
			return array(
				"TABSET" => "WGEXTRAPROPS",
				"GetTabs" => array("CWGEP", "GetSectionTabs"),
				"ShowTab" => array("CWGEP", "ShowSectionTab"),
				"Action" => array("CWGEP", "SectionAction"),
				"Check" => array("CWGEP", "SectionCheck")
			);
		}
	}

	public function SectionAction($arArgs)
	{
		return;
		// Основные данные сохранены. Делаем тут действия. 
		// Возвращаем True в случае успеха и False - в случае ошибки
		// В случае ошибки делаем так же $GLOBALS["APPLICATION"]-> ThrowException("Ошибка!!!", "ERROR");
		//$GLOBALS["APPLICATION"]-> ThrowException("Ошибка!!!", "ERROR");
		foreach (self::$newProp as $prop) {
			self::addSectionExtraFields($_REQUEST['wgsection'], $prop[0], $prop[1], $prop[2], $prop[3]);
		}
		$res = true;
		foreach ($_REQUEST['wgpropname'] as $index => $prop) {
			$options = Array(
				'card_order' => $_REQUEST['wg_card_order'][$index],
				'display_on_card' => isset($_REQUEST['wg_display_on_card'][$index]) ? 1 : 0,
				'is_color' => isset($_REQUEST['wg_is_color'][$index]) ? 1 : 0,
				'is_material' => isset($_REQUEST['wg_is_material'][$index]) ? 1 : 0,
				'listing_order' => $_REQUEST['wg_listing_order'][$index],
				'display_on_listing' => isset($_REQUEST['wg_display_on_listing'][$index]) ? 1 : 0,
				'filter_order' => $_REQUEST['wg_filter_order'][$index],
				'display_on_filter' => isset($_REQUEST['wg_display_on_filter'][$index]) ? 1 : 0
			);
			if (!self::setSectionExtraFields($_REQUEST['wgsection'], $index, $prop, $_REQUEST['wgprop'][$index], $_REQUEST['wgrange'][$index], $options)) {
				$res = false;
			}
		}
		foreach (self::$toDelete as $prop) {
			self::delSectionExtraFields($prop);
		}
		return $res;
	}

	public function SectionCheck($arArgs)
	{
		return;
		$old = self::getSectionExtraFields(1, $_REQUEST['wgsection']);
		self::$toDelete = Array();
		while ($item = $old->GetNext()) {
			if (!isset($_REQUEST['wgpropname'][$item['extra_fields_id']])) {
				self::$toDelete[] = $item['extra_fields_id'];
			} else if (
				isset($item['extra_fields_set']) && $item['extra_fields_set'] == implode("|", $_REQUEST['wgprop'][$item['extra_fields_id']])
				&& isset($item['extra_fields_range']) && $item['extra_fields_range'] == implode("|", $_REQUEST['wgrange'][$item['extra_fields_id']])
				&& isset($item['extra_fields_name']) && $item['extra_fields_name'] == $_REQUEST['wgpropname'][$item['extra_fields_id']]
				&& isset($item['card_order']) && $item['card_order'] == $_REQUEST['wg_card_order'][$item['extra_fields_id']]
				&& isset($item['display_on_card']) && $item['display_on_card'] == (isset($_REQUEST['wg_display_on_card'][$item['extra_fields_id']]) ? 1 : 0)
				&& isset($item['is_color']) && $item['is_color'] == (isset($_REQUEST['wg_is_color'][$item['extra_fields_id']]) ? 1 : 0)
				&& isset($item['is_material']) && $item['is_material'] == (isset($_REQUEST['wg_is_material'][$item['extra_fields_id']]) ? 1 : 0)
				&& isset($item['listing_order']) && $item['listing_order'] == $_REQUEST['wg_listing_order'][$item['extra_fields_id']]
				&& isset($item['display_on_listing']) && $item['display_on_listing'] == (isset($_REQUEST['wg_display_on_listing'][$item['extra_fields_id']]) ? 1 : 0)
				&& isset($item['filter_order']) && $item['filter_order'] == $_REQUEST['wg_filter_order'][$item['extra_fields_id']]
				&& isset($item['display_on_filter']) && $item['display_on_filter'] == (isset($_REQUEST['wg_display_on_filter'][$item['extra_fields_id']]) ? 1 : 0)
			) {
				unset($_REQUEST['wgprop'][$item['extra_fields_id']]);
				unset($_REQUEST['wgrange'][$item['extra_fields_id']]);
				unset($_REQUEST['wgpropname'][$item['extra_fields_id']]);
				unset($_REQUEST['wg_card_order'][$item['extra_fields_id']]);
				unset($_REQUEST['wg_display_on_card'][$item['extra_fields_id']]);
				unset($_REQUEST['wg_is_color'][$item['extra_fields_id']]);
				unset($_REQUEST['wg_is_material'][$item['extra_fields_id']]);
				unset($_REQUEST['wg_listing_order'][$item['extra_fields_id']]);
				unset($_REQUEST['wg_display_on_listing'][$item['extra_fields_id']]);
				unset($_REQUEST['wg_filter_order'][$item['extra_fields_id']]);
				unset($_REQUEST['wg_display_on_filter'][$item['extra_fields_id']]);
			}
		}
		self::$newProp = Array();
		foreach ($_REQUEST['newwgpropname'] as $index => $newpropname) {
			if ($newpropname) {
				self::$newProp[$index] = Array(
					$newpropname,
					$_REQUEST['wgnewprop'][$index],
					$_REQUEST['wgnewrange'][$index],
					Array(
						'card_order' => $_REQUEST['new_wg_card_order'][$index],
						'display_on_card' => (isset($_REQUEST['new_wg_display_on_card'][$index]) ? 1 : 0),
						'listing_order' => $_REQUEST['new_wg_listing_order'][$index],
						'display_on_listing' => (isset($_REQUEST['new_wg_display_on_listing'][$index]) ? 1 : 0),
						'filter_order' => $_REQUEST['new_wg_filter_order'][$index],
						'display_on_filter' => (isset($_REQUEST['new_wg_display_on_filter'][$index]) ? 1 : 0)
					)
				);
			}
		}
		return true;
		// Основные данные ещё не сохранялись. Делаем тут разные чеки. 
		// Возвращаем True, если можно все схранять, иначе False
		// В случае False делаем так же $GLOBALS["APPLICATION"]-> ThrowException("Ошибка!!!", "ERROR");
	}

	public function GetSectionTabs($arArgs)
	{
		$arTabs = array(
			array("DIV" => "wgsectionpropsedit", "TAB" => "...", "ICON" => "sale", "TITLE" => "Свойства товаров", "SORT" => 2),
		);
		return $arTabs;
	}

	public function ShowSectionTab($divName, $arArgs, $bVarsFromForm)
	{
		if ($divName == "wgsectionpropsedit") {
			if (!\Bitrix\Main\Loader::includeModule('iblock')) {
				ShowError('Модуль iblock не установлен');
				return;
			}

			$section_id = $arArgs['ID'];
			//$section_code = CIBlockSection::GetByID($section_id)->GetNext()['CODE'];

			$res = self::getSectionExtraFields(1, $section_id);
			$tab_content = '
<tr class="wg-heading">
	<th rowspan="2">Свойство</th>
	<th rowspan="2">Тип</th>
	<th rowspan="2">Значения</th>
	<th colspan="2">Карточка</th>
	<th colspan="2">Листинг</th>
	<th colspan="2">Подбор</th>
</tr>
<tr class="wg-heading">
	<th>Сорт-ка</th><th>Показ</th><th>Сорт-ка</th><th>Показ</th><th>Сорт-ка</th><th>Показ</th>
</tr><!--' . $section_id . '--><!--' . var_export($res, 1) . '-->';
			while ($ob = $res->fetch()) {
$tab_content .= '<!--' . var_export($ob, 1) . '-->';
				/*$select = '';
				if (preg_match('/\|/', $ob['extra_fields_set'])) {
					foreach (explode('|', $ob['extra_fields_set']) as $set) {
						$select .= '<input class="field-set" name="wgprop['.$ob['extra_fields_id'].'][]" type="text" value="'.$set.'" /><br/>';
					}
				}
				$range = '';
				if (preg_match('/\|/', $ob['extra_fields_range'])) {
					foreach (explode('|', $ob['extra_fields_range']) as $set2) {
						$range .= '<input class="field-range" name="wgrange['.$ob['extra_fields_id'].'][]" type="text" value="'.$set2.'" /><br/>';
					}
				}
				$tab_content .= '
<tr class="adm-detail-file-row adm-wg-section-row">
	<td class="adm-detail-content-cell-l">
		<label class="name-to-edit">
			<span>'.$ob['extra_fields_name'].'</span>
			<input type="text" placeholder="Название свойства" value="'.$ob['extra_fields_name'].'" name="wgpropname['.$ob['extra_fields_id'].']" />:
		</label>
	</td>
	<td class="adm-detail-content-cell-r" style="vertical-align: top">
		'.$select.'<hr/><input type="text"><br/><button class="add-variant" data-prop="'.$ob['extra_fields_id'].'">Добавить вариант</button>
	</td>
	<td class="adm-detail-content-cell-r" style="vertical-align: top">
		'.$range.'<hr/><input type="text"><br/><button class="add-range" data-prop="'.$ob['extra_fields_id'].'">Добавить вариант</button>
	</td>
	<td><input type="text" class="short-field" value="'.$ob['card_order'].'" name="wg_card_order['.$ob['extra_fields_id'].']" /></td>
	<td><input type="checkbox"'.($ob['display_on_card']?' checked="checked"':'').' name="wg_display_on_card['.$ob['extra_fields_id'].']" /></td>
	<td><input type="checkbox"'.($ob['is_color']?' checked="checked"':'').' name="wg_is_color['.$ob['extra_fields_id'].']" /></td>
	<td><input type="checkbox"'.($ob['is_material']?' checked="checked"':'').' name="wg_is_material['.$ob['extra_fields_id'].']" /></td>
	<td><input type="text" class="short-field" value="'.$ob['listing_order'].'" name="wg_listing_order['.$ob['extra_fields_id'].']" /></td>
	<td><input type="checkbox"'.($ob['display_on_listing']?' checked="checked"':'').' name="wg_display_on_listing['.$ob['extra_fields_id'].']" /></td>
	<td><input type="text" class="short-field" value="'.$ob['filter_order'].'" name="wg_filter_order['.$ob['extra_fields_id'].']" /></td>
	<td><input type="checkbox"'.($ob['display_on_filter']?' checked="checked"':'').' name="wg_display_on_filter['.$ob['extra_fields_id'].']" /></td>
</tr>';*/
			}
			$tab_content2 .= '
<tr class="adm-detail-file-row adm-wg-section-row penult-row">
	<td colspan="11">
		<button class="add-prop">Добавить свойство</button>
		<input type="hidden" name="wgsection" value="'.$section_code.'" />
	</td>
</tr>';
			$tab_content2 .= '
<tr class="adm-detail-file-row adm-wg-section-row">
	<td colspan="11">
		<button class="clone-prop">Скопировать свойства</button>
	</td>
</tr>';

			echo $tab_content;
		}
	}

	public function OnElementInit()
	{
		if (
			isset($_GET['IBLOCK_ID']) && $_GET['IBLOCK_ID'] == self::PRODUCTS_IBLOCK_ID
			&& (
				(isset($_GET['ID']) && intval($_GET['ID']) > 0)
				|| (isset($_REQUEST['wg_element_code']) && intval($_REQUEST['wg_element_code']) > 0)
			)
		) {
			return array(
				"TABSET" => "WGELEMENTPROPS",
				"GetTabs" => array("CWG", "GetElementTabs"),
				"ShowTab" => array("CWG", "ShowElementTab"),
				"Action" => array("CWG", "ElementAction"),
				"Check" => array("CWG", "ElementCheck")
			);
		}
	}

	public function ElementAction($arArgs)
	{
		// Основные данные сохранены. Делаем тут действия. 
		// Возвращаем True в случае успеха и False - в случае ошибки
		// В случае ошибки делаем так же $GLOBALS["APPLICATION"]-> ThrowException("Ошибка!!!", "ERROR");
		if (!self::$elementCopy) {
			foreach (self::$elementProp as $product) {
				self::setElementExtraFields($product);
			}
		}
		return true;
	}

	public function ElementCheck($arArgs)
	{
		global $DB;
		$res = self::getElementExtraFields($_REQUEST['wg_element_code'], $_REQUEST['wg_section_code']);
		while ($ob = $res->GetNext()) {
			if ($_REQUEST['wgprop'][$ob['PROPERTY_ID']] == html_entity_decode($ob['VALUE'])) {
				unset($_REQUEST['wgprop'][$ob['PROPERTY_ID']]);
			}
		}
		self::$elementProp = Array();
		foreach ($_REQUEST['wgprop'] as $index => $prop) {
			$element_id = $arArgs['ID'];
			$element = CIBlockElement::GetByID($element_id)->GetNext();
			if ($element) {
				$element_code = $element['CODE'];
				self::$elementProp[] = Array(
					'products_id' => $element_code,
					'products_extra_fields_id' => $index,
					'products_extra_fields_value' => '"' . $DB->forSql($prop) . '"',
					'last_modified' => '"'.date('Y-m-d H:i:s').'"'
				);
			} else {
				self::$elementCopy = true;
				$element_code = $element['CODE'];
				self::$elementProp[] = Array(
					'products_id' => 0,
					'products_extra_fields_id' => $index,
					'products_extra_fields_value' => '"' . $DB->forSql($prop) . '"',
					'last_modified' => '"'.date('Y-m-d H:i:s').'"'
				);
			}
		}
		return true;
		// Основные данные ещё не сохранялись. Делаем тут разные чеки. 
		// Возвращаем True, если можно все схранять, иначе False
		// В случае False делаем так же $GLOBALS["APPLICATION"]-> ThrowException("Ошибка!!!", "ERROR");
	}

	public function GetElementTabs($arArgs)
	{
		// SORT - после какого стандартного таба вставлять. Не установлено - после последнего
		$arTabs = array(
			array("DIV" => "wgelementedit", "TAB" => "Свойства продукта", "ICON" => "sale", "TITLE" => "Свойства продукта", "SORT" => 1),
		);
		return $arTabs;
	}

	public function ShowElementTab($divName, $arArgs, $bVarsFromForm)
	{
		if ($divName == "wgelementedit") {
			if (!\Bitrix\Main\Loader::includeModule('iblock')) {
				ShowError('Модуль iblock не установлен');
				return;
			}
			$element_id = $arArgs['ID'];
			$element = CIBlockElement::GetByID($element_id)->GetNext();
			$element_code = $element['CODE'];
			$section_id = $element['IBLOCK_SECTION_ID'];
			$section_code = CIBlockSection::GetByID($section_id)->GetNext()['CODE'];
			$res = self::getElementExtraFields($element_code, $section_code);
			$tab_content = '';

			while ($ob = $res->GetNext()) {
				$select = '';
				if (preg_match('/\|/', $ob['LIST'])) {
					$select = '<select class="wgprop" data-propid="'.$ob['PROPERTY_ID'].'" name="wgprop['.$ob['PROPERTY_ID'].']" style="width: 100%"><option></option>';
					foreach (explode('|', $ob['LIST']) as $set) {
						$selected = '';
						if ($set == $ob['VALUE']) {
							$selected = ' selected="selected"';
						}
						$select .= '<option'.$selected.'>'.$set.'</option>';
					}
					$select .= '</select>';
					$tab_content .= '<tr class="adm-detail-file-row"><td class="adm-detail-content-cell-l"><label>'.$ob['NAME'].':</label></td><td class="adm-detail-content-cell-r"> '.$select.'</td></tr>';
				} else {
					$propName = trim(preg_replace(Array("/, см/", "/\(.+\)/"), "", $ob['NAME']));
					switch ($propName) {
						case 'Габариты':
							$demen = ' data-demen="demen" ';
							break;
						case 'Высота':
							$demen = ' data-demen="height" onKeyUp="Synchronizer()" onKeyPress="Synchronizer()" onchange="Synchronizer()" ';
							break;
						case 'Ширина':
							$demen = ' data-demen="width" onKeyUp="Synchronizer()" onKeyPress="Synchronizer()" onchange="Synchronizer()" ';
							break;
						case 'Глубина':
							$demen = ' data-demen="depth" onKeyUp="Synchronizer()" onKeyPress="Synchronizer()" onchange="Synchronizer()" ';
							break;
						default:
							$demen = '';
					}
					$tab_content .= '
<tr class="adm-detail-file-row" data-test="' . $propName . '">
	<td style="width: 40%;" class="adm-detail-content-cell-l"><label>'.$ob['NAME'].':</label></td>
	<td style="width: 60%;" class="adm-detail-content-cell-r"><input ' . $demen . ' class="wgprop" data-propid="'.$ob['PROPERTY_ID'].'" name="wgprop['.$ob['PROPERTY_ID'].']" type="text" value="'.$ob['VALUE'].'" /></td>
</tr>';
				}
			}

			$tab_content .= '
<tr>
	<td colspan="2">
		'.$element_code.'|'.$section_code.'
	</td>
</tr>
<tr style="display:none">
	<td colspan="2">
		<input type="hidden" name="wg_element_code" value="'.$element_code.'" />
		<input type="hidden" name="wg_section_code" value="'.$section_code.'" />
	</td>
</tr>';
			if (isset($_GET['action']) && $_GET['action'] == 'copy') {
				?><script>
				window.onload = function() {
					var wgprops = document.querySelectorAll('.wgprop');
					var changeField = function() {
						var extraField = document.querySelector('[name="PROP[181][n0]"]');
						var tmp = [];
						for (var prop in wgprops) {
							if (typeof wgprops[prop] === 'object') {
								tmp.push([wgprops[prop].getAttribute('data-propid'), wgprops[prop].value]);
							}
						}
						tmpStr = JSON.stringify(tmp);
						extraField.value = tmpStr;
					}
					changeField();

					for(var prop in wgprops) {
						wgprops[prop].onkeyup = changeField;
						wgprops[prop].onblur = changeField;
					}
				}
				</script><?
			}
			echo $tab_content;
		}
	}
}
