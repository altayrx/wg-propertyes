<?php
class CWGGift
{
	public static function getByProductId($productId)
	{
		$cache = new CPHPCache();
		$cache_dir = 'product_gift';
		$cache_id = $cache_dir . '_' . $productId;
		$cache_time = 1800 + intval($productId);
		if ($cache->InitCache($cache_time, $cache_id, $cache_dir)) {
			$db_list = $cache->GetVars();
			$giftProducts[0] = $db_list[$cache_id];
		} else {

		if (!\Bitrix\Main\Loader::includeModule('sale')) {
			ShowError('Модуль main не установлен');
			return;
		}

		global $USER;
		$giftBasket = \Bitrix\Sale\Basket::create(SITE_ID); // создаем пустую корзину
		$giftManager = \Bitrix\Sale\Discount\Gift\Manager::getInstance(); // получаем менеджера
		$giftManager->setUserId($USER->GetID()); // отбор подарков для текущего пользователя
		$giftManager->enableExistenceDiscountsWithGift(); // он не знает зачем это, но работает как включатель "существования" подарков для товаров, душит
		$collections = $giftManager->getCollectionsByProduct($giftBasket, [
			'ID' => $productId,
			'MODULE' => 'catalog',
			'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
			'QUANTITY' => 1,
			'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
			'LID' => \Bitrix\Main\Context::getCurrent()->getSite(),
		]);
		$giftProducts = Array();
		foreach ($collections as $collection) {
			/** @var \Bitrix\Sale\Discount\Gift\Gift $gift */
			foreach ($collection as $gift) {
				$giftRes = \CIBlockElement::getList(
					[],
					['IBLOCK_ID' => 17, 'ID' => $gift->getProductId()],
					false,
					false,
					['ID', 'CODE', 'NAME', 'SCALED_PRICE_1', 'PROPERTY_MANUFACTURER', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'PROPERTY_SUBNAME']
				);
				if ($giftOb = $giftRes->fetch()) {
					if ($giftOb['PREVIEW_PICTURE']) {
						$giftOb['PICTURE'] = \CFile::getPath($giftOb['PREVIEW_PICTURE']);
					} else if ($giftOb['DETAIL_PICTURE']) {
						$giftOb['PICTURE'] = \CFile::getPath($giftOb['DETAIL_PICTURE']);
					} else {
						$giftOb['PICTURE'] = '/images/empty.jpg';
					}
					$giftOb['MANUFACTURER'] = \CIBlockElement::getList([], ['IBLOCK_ID' => 18, 'ID' => $giftOb['PROPERTY_MANUFACTURER_VALUE'], false, false, ['NAME']])->fetch()['NAME'];
					$giftProducts[] = Array(
						'ID' => $giftOb['ID'],
						'CODE' => $giftOb['CODE'],
						'PARENT_ID' => $productId,
						'PARENT_CODE' => \CIBlockElement::getList([], ['IBLOCK_ID' => 17, 'ID' => $productId], false, false, ['CODE'])->fetch()['CODE'],
						'NAME' => implode(' ', [$giftOb['PROPERTY_SUBNAME_VALUE'], $giftOb['MANUFACTURER'], $giftOb['NAME']]),
						'URL' => '/goods/' . $giftOb['CODE'] . '.htm',
						'PICTURE' => $giftOb['PICTURE'],
						'PRICE' => $giftOb['SCALED_PRICE_1'],
						'PRICE_FORMATED' => '<span>' . number_format($giftOb['SCALED_PRICE_1'], 0, '.', ' ') . '</span> <span class="rubl">&#8381;</span>',
					);
				}
			}
		}

			$cache->StartDataCache($cache_time, $cache_id, $cache_dir);
			$cache->EndDataCache(array($cache_id => $giftProducts[0]));
		}
		return $giftProducts[0];
	}

	public static $currency;
	public static $lid;
	public static $giftBasket;
	public static $giftManager;

	public function __construct()
	{
		if (!\Bitrix\Main\Loader::includeModule('sale')) {
			ShowError('Модуль main не установлен');
			return;
		}

		self::$currency = \Bitrix\Currency\CurrencyManager::getBaseCurrency();
		self::$lid = \Bitrix\Main\Context::getCurrent()->getSite();

		file_put_contents(
			$_SERVER['DOCUMENT_ROOT'] . '/logs/gt.log',
			''
		);

		global $USER;
	}

	public static function getByProductId2($productId)
	{
		if (!\Bitrix\Main\Loader::includeModule('sale')) {
			ShowError('Модуль main не установлен');
			return;
		}

		global $USER;

		file_put_contents(
			$_SERVER['DOCUMENT_ROOT'] . '/logs/gt.log',
			$productId . ' > ' . '; memory usage: ' . memory_get_usage(),
			FILE_APPEND
		);

		self::$giftManager = \Bitrix\Sale\Discount\Gift\Manager::getInstance(); // получаем менеджера
		self::$giftManager->setUserId($USER->GetID()); // отбор подарков для текущего пользователя
		self::$giftManager->enableExistenceDiscountsWithGift(); // он не знает зачем это, но работает как включатель "существования" подарков для товаров, душит
		self::$giftBasket = \Bitrix\Sale\Basket::create(SITE_ID); // создаем пустую корзину

/*$reflectionMethod = new ReflectionMethod('\Bitrix\Sale\Discount\Gift\Manager', 'getCollectionsByProduct');
var_dump(Array($reflectionMethod->getFileName(), $reflectionMethod->getStartLine()));
die();*/

		$collections = self::$giftManager->getCollectionsByProduct(self::$giftBasket, [
			'ID' => $productId,
			'MODULE' => 'catalog',
			'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
			'QUANTITY' => 1,
			'CURRENCY' => self::$currency,
			'LID' => self::$lid,
		]);

//return null;
		$giftProducts = Array();
		foreach ($collections as $collection) {
			/** @var \Bitrix\Sale\Discount\Gift\Gift $gift */
			foreach ($collection as $gift) {
				$giftRes = \CIBlockElement::getList(
					[],
					['IBLOCK_ID' => 17, 'ID' => $gift->getProductId()],
					false,
					false,
					['ID', 'CODE', 'NAME', 'SCALED_PRICE_1', 'PROPERTY_MANUFACTURER', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'PROPERTY_SUBNAME']
				);
				if ($giftOb = $giftRes->fetch()) {
					if ($giftOb['PREVIEW_PICTURE']) {
						$giftOb['PICTURE'] = \CFile::getPath($giftOb['PREVIEW_PICTURE']);
					} else if ($giftOb['DETAIL_PICTURE']) {
						$giftOb['PICTURE'] = \CFile::getPath($giftOb['DETAIL_PICTURE']);
					} else {
						$giftOb['PICTURE'] = '/images/empty.jpg';
					}
					$giftOb['MANUFACTURER'] = \CIBlockElement::getList([], ['IBLOCK_ID' => 18, 'ID' => $giftOb['PROPERTY_MANUFACTURER_VALUE'], false, false, ['NAME']])->fetch()['NAME'];
					$giftProducts[] = Array(
						'ID' => $giftOb['ID'],
						'CODE' => $giftOb['CODE'],
						'PARENT_ID' => $productId,
						'PARENT_CODE' => \CIBlockElement::getList([], ['IBLOCK_ID' => 17, 'ID' => $productId], false, false, ['CODE'])->fetch()['CODE'],
						'NAME' => implode(' ', [$giftOb['PROPERTY_SUBNAME_VALUE'], $giftOb['MANUFACTURER'], $giftOb['NAME']]),
						'URL' => '/goods/' . $giftOb['CODE'] . '.htm',
						'PICTURE' => $giftOb['PICTURE'],
						'PRICE' => $giftOb['SCALED_PRICE_1'],
						'PRICE_FORMATED' => '<span>' . number_format($giftOb['SCALED_PRICE_1'], 0, '.', ' ') . '</span> <span class="rubl">&#8381;</span>',
					);
				}
			}
		}
		$out = $giftProducts[0];
		unset($giftBasket);
		unset($giftManager);
		unset($collections);
		unset($giftProducts);
		return json_encode($out);
	}
}
