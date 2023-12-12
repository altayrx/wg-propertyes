<?php
class Accusative
{
	public $arItems = Array();
	private $arHLBlock;
	private $obEntity;
	private $strEntityDataClass;

	function __construct($filter = NULL)
	{
		if (CModule::IncludeModule('highloadblock')) {
			$this->arHLBlock = Bitrix\Highloadblock\HighloadBlockTable::getById(5)->fetch();
			$this->obEntity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($this->arHLBlock);
			$this->strEntityDataClass = $this->obEntity->getDataClass();

			if (!is_null($filter)) {
				$this->getList($filter);
			}
		}
	}

	public function getList($filter = NULL)
	{
		$arFilter = array(
			'select' => array('ID', 'UF_NAME', 'UF_GENITIVE', 'UF_ACCUSATIVE'),
			'order' => array('ID' => 'ASC'),
			'limit' => 2000
		);
		if (!is_null($filter)) {
			$arFilter['filter'] = $filter;
		}
		$rsData = $this->strEntityDataClass::getList($arFilter);
		while ($arItem = $rsData->Fetch()) {
			$this->arItems[] = Array(
				'id' => $arItem['ID'],
				'name' => $arItem['UF_NAME'],
				'genitive' => $arItem['UF_GENITIVE'],
				'accusative' => $arItem['UF_ACCUSATIVE'],
			);
		}
		return count($this->arItems);
	}

	public function getCount()
	{
		return count($this->arItems);
	}

	public function testItem($name)
	{
		$index = array_search($name, array_column($this->arItems, 'name'));
		if (is_numeric($index)) {
			return $this->arItems[$index];
		} else {
			return false;
		}
	}

	public function setItem($data)
	{
		$arFields = Array(
			'name' => 'UF_NAME',
			'genitive' => 'UF_GENITIVE',
			'accusative' => 'UF_ACCUSATIVE',
		);
		$arElementFields = Array();
		foreach ($arFields as $key => $val) {
			if (isset($data[$key])) {
				$arElementFields[$val] = $data[$key];
			}
		}
		$test = $this->testItem($data['name']);
		if (!$test) {
			$obResult = $this->strEntityDataClass::add($arElementFields);
			return $obResult->isSuccess();
		} else {
			$obResult = $this->strEntityDataClass::update($test['id'], $arElementFields);
			return $obResult->isSuccess();
		}
	}

	/*
	заполнение одного склонениея значениями другого
	необходимо убрать или увеличить limit в getList до количества записей в таблице склонений
	public function copyGenitiveToAccusative()
	{
		foreach ($this->arItems as $item) {
			$data = array(
				"UF_ACCUSATIVE" => $item['genitive'],
			);
			$result = $this->strEntityDataClass::update($item['id'], $data);
		}
	}*/
}
