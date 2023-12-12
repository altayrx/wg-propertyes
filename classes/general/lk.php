<?php
class CWGLK
{
	public $person;
	public $closestDeliveryDate = 'не ожидается';
	public $closestDeliveryDateTest = '';
	public $test = 'nope';//null;

	public function get($field)
	{
		if (empty($this->person[$field])) {
			return '';
		}
		return $this->person[$field];
	}

	function __construct()
	{
		$this->person = \CWGUser::getInstance();
		//$this->person->fav = \CWGFav::getInstance()->getFav();
	}

	public function isLogged()
	{
		if ($this->person) {
			return true;
		}
		return false;
	}

	public function ordersFilter()
	{
		$years = Array();
		$orders = $this->orders();

		foreach ($orders as $order) {
			$year = $order['year'];
			//$deliveryDate = $order['order']['DELIVERY_DATE'];
			if (!in_array($year, $years)) {
				$years[] = intval($year);
			}
		}
		sort($years);
		return Array(
			'years' => $years,
		);
	}

	public function orders($filter = [])
	{
		$goodsDeclension = new \Bitrix\Main\Grid\Declension('товар', 'товара', 'товаров');

		$filter['USER_ID'] = $this->person['ID'];
		$orders = \Bitrix\Sale\Internals\OrderTable::getList([
			'order' => Array('ID' => 'DESC'),
			'filter' => $filter,
		]);
		$ordersList = [];

		foreach ($orders->fetchAll() as $order) {
			$deliveryPropValue = '';
			$orderData = \Bitrix\Sale\Order::load($order['ID']);
			$orderProps = $orderData->getPropertyCollection();
			if ($deliveryProp = $orderProps->getItemByOrderPropertyId(53)) {
				$this->closestDeliveryDateTest .= ' | [' . $deliveryProp->getValue() . ']';
				if (!empty($deliveryProp->getValue())) {
					$deliveryPropValue = $deliveryProp->getValue();
				}
			} elseif ($deliveryProp = $orderProps->getItemByOrderPropertyId(54)) {
				$this->closestDeliveryDateTest .= ' | [' . $deliveryProp->getValue() . ']';
				if (!empty($deliveryProp->getValue())) {
					$deliveryPropValue = $deliveryProp->getValue();
				}
			}

			$basket = $orderData->getBasket();
			$basketQuantity = count($basket->getQuantityList());

			$status = \CSaleStatus::GetList(
				[],
				['ID' => $order['STATUS_ID'], 'LID' => 'ru'],
				false,
				false,
				['NAME', 'COLOR']
			)->fetch();
			if (!empty($status['COLOR'])) {
				$status['COLOR'] = ' style=\'color:' . $status['COLOR'] . '\'';
			}

			$date = $order['DATE_INSERT']->toString(new \Bitrix\Main\Context\Culture(array("FORMAT_DATETIME" => "d.m.Y")));
			$year = explode('.', $date)[2];


			$this->test = 'test' . $deliveryPropValue;
			$ordersList[] = [
				'id' => $order['ID'],
				'date' => $date,
				'year' => $year,
				'goods' => $basketQuantity . ' ' . $goodsDeclension->get($basketQuantity),
				'price' => number_format($order['PRICE'], 0, '.', ' '),
				'status' => $status,
				'order' => $order,
				'delivery_date' => (!empty($deliveryPropValue) ? MakeTimeStamp($deliveryPropValue, 'DD.MM.YYYY') : null),
			];
		}

		if (!empty($ordersList)) {
			foreach ($ordersList as $order) {
				$deliveryDate = $order['delivery_date'];

				if (!is_null($deliveryDate) && (is_null($this->closestDeliveryDate) || $deliveryDate < $this->closestDeliveryDate)) {
					$this->closestDeliveryDate = $deliveryDate;
				}
			}

			if ($this->closestDeliveryDate && $this->closestDeliveryDate != 'не ожидается') {
				$this->closestDeliveryDate = FormatDate("j F Y", $this->closestDeliveryDate);
			}
		}

		return $ordersList;
	}

	public function order($oid)
	{
		$goodsDeclension = new \Bitrix\Main\Grid\Declension('товар', 'товара', 'товаров');

		$orders = \Bitrix\Sale\Internals\OrderTable::getList([
			'filter' => [
				'USER_ID' => $this->person['ID'],
				'ID' => $oid
			]
		]);
		$ordersList = [];
		$psName = '';
		foreach ($orders->fetchAll() as $order) {
			$orderData = \Bitrix\Sale\Order::load($order['ID']);

			$paymentCollection = $orderData->getPaymentCollection();
			if (!$paymentCollection[0]) {
				continue;
			}
			$psName = $paymentCollection[0]->getField('PAY_SYSTEM_NAME');

			$basket = $orderData->getBasket();
			$basketQuantity = count($basket->getQuantityList());
			$basketItems = $basket->getBasketItems();
			$basketData = Array();

			foreach ($basketItems as $bi) {
				$productOb = \CIBlockElement::getList(
					[],
					['IBLOCK_ID' => 17, 'ID' => $bi->getField('PRODUCT_ID')],
					false,
					false,
					['PREVIEW_PICTURE', 'DETAIL_PICTURE', 'CODE']
				)->fetch();

				$basketData[$bi->getId()] = Array(
					'ID' => $bi->getId(),
					'NAME' => $bi->getField('NAME'),
					'CODE' => $productOb['CODE'],
					'QUANTITY' => $bi->getQuantity(),
					'PRICE' => number_format($bi->getPrice(), 0, '.', ' '),
					'TOTAL_PRICE' => number_format(intval($bi->getQuantity()) * floatval($bi->getPrice()), 0, '.', ' '),
					'PICTURE' => (
						!empty($productOb['PREVIEW_PICTURE'])
						? \CFile::getPath($productOb['PREVIEW_PICTURE'])
						: (
							!empty($productOb['DETAIL_PICTURE'])
							? \CFile::getPath($productOb['DETAIL_PICTURE'])
							: ''
						)
					)
				);
			}

			$status = \CSaleStatus::GetList(
				[],
				['ID' => $order['STATUS_ID'], 'LID' => 'ru'],
				false,
				false,
				['NAME', 'COLOR']
			)->fetch();
			if (!empty($status['COLOR'])) {
				$status['COLOR'] = ' style=\'color:' . $status['COLOR'] . '\'';
			}

			$date = $order['DATE_INSERT']->toString(new \Bitrix\Main\Context\Culture(array("FORMAT_DATETIME" => "d.m.Y")));
			$year = explode('.', $date)[2];

			$delivery = $order['PRICE_DELIVERY'];

			$pay = Array(
				'TITLE' => ($order['PAYED'] == 'N' ? 'Не оплачено' : 'Оплачено'),
				'BY' => $psName,
				'SUM' => $order['PRICE'] - $order['PRICE_DELIVERY'],
				'SUM_FORMATED' => number_format($order['PRICE'] - $order['PRICE_DELIVERY'], 0, '.', ' ') . ' &#8381;',
			);

			$deliveryType = '';
			switch ($orderData->getDeliverySystemId()) {
				case 5:
					$deliveryType = 'Доставка в пункт выдачи';
					break;
				default:
					$deliveryType = 'Доставка по адресу';
			}

			$ordersList[] = [
				'id' => $order['ID'],
				'date' => $date,
				'year' => $year,
				'goods' => $basketQuantity . ' ' . $goodsDeclension->get($basketQuantity),
				'price' => $order['PRICE'],
				'price_formated' => number_format($order['PRICE'], 0, '.', ' ') . ' &#8381;',
				'status' => $status,
				'order' => $order,
				'order_contact' => $orderData->getAvailableFields(),
				'basket' => $basketData,
				'delivery' => ($delivery > 0 ? number_format($delivery, 0, '.', ' ') : 'Бесплатно'),
				'delivery_type' => $deliveryType,
				'delivery_address' => $orderData->getPropertyCollection()->getItemByOrderPropertyId(26)->getValue() . $orderData->getPropertyCollection()->getItemByOrderPropertyId(38)->getValue(),
				'fio' => $orderData->getPropertyCollection()->getItemByOrderPropertyId(20)->getValue() . $orderData->getPropertyCollection()->getItemByOrderPropertyId(32)->getValue(),
				'email' => $orderData->getPropertyCollection()->getItemByOrderPropertyId(21)->getValue() . $orderData->getPropertyCollection()->getItemByOrderPropertyId(33)->getValue(),
				'phone' => $orderData->getPropertyCollection()->getItemByOrderPropertyId(22)->getValue() . $orderData->getPropertyCollection()->getItemByOrderPropertyId(34)->getValue(),
				'pay' => $pay,
			];
		}
		return $ordersList[0];
	}
}
