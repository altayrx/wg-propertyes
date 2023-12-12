<?php
class CWGUser
{
	protected static $instance = array();
	protected static $persons = array();
	public $fav;

	private function __construct($uid)
	{
		$rsUser = \Bitrix\Main\UserTable::getList([
			'select' => ['*', 'UF_FAVORITES', 'UF_ADDRESSES', 'UF_SUBS'],
			'filter' => ["ID" => $uid],
		]);

		$arUser = $rsUser->fetchAll();

		if ($arUser) {
			self::$persons[$uid] = $arUser[0];
			self::$persons[$uid]['FIO'] = self::$persons[$uid]['NAME'] . ' ' . self::$persons[$uid]['SECOND_NAME'] . ' ' . self::$persons[$uid]['LAST_NAME'];
			if (empty(self::$persons[$uid]['EMAIL']) || strpos(self::$persons[$uid]['EMAIL'], 'noemail.sms') !== false) {
				self::$persons[$uid]['EMAIL'] = 'не указан';
			}
			if (empty(self::$persons[$uid]['PERSONAL_PHONE'])) {
				self::$persons[$uid]['PERSONAL_PHONE'] = 'не указан';
			}

			//$favList = explode('|', $_COOKIE['WhitegoodsFavList']);
			//$fav = \CWGFav::getInstance();
			$favList = $this->fav;
			if (count($favList) == 1 && $favList[0] == '') {
				unset($favList);
			}
			if (!empty($favList)) {
				self::$persons[$uid]['FAV_LIST_COUNT'] = count($favList);
			} else {
				self::$persons[$uid]['FAV_LIST_COUNT'] = 0;
			}
			if (!empty(self::$persons[$uid]['PERSONAL_PHOTO'])) {
				self::$persons[$uid]['PERSONAL_PHOTO'] = \CFile::getPath(self::$persons[$uid]['PERSONAL_PHOTO']);
			} else {
				self::$persons[$uid]['PERSONAL_PHOTO'] = '/images/icons/lk/no-photo-ic.svg';
			}
			if (!empty(self::$persons[$uid]['PERSONAL_BIRTHDAY'])) {
				//self::$persons[$uid]['PERSONAL_BIRTHDAY'] = FormatDate("j F Y", MakeTimeStamp(self::$persons[$uid]['PERSONAL_BIRTHDAY']));
				self::$persons[$uid]['PERSONAL_BIRTHDAY'] = FormatDate("d.m.Y", MakeTimeStamp(self::$persons[$uid]['PERSONAL_BIRTHDAY']));
			}
		} else {
			self::$persons[$uid] = false;
		}
	}

	/*public function set($field, $value)
	{
		
	}*/

	public static function getInstance($uid = 0)
	{
		global $USER;
		if ($uid > 0) {
			$userId = $uid;
		} else {
			$userId = $USER->getId();
		}
		if (is_null(self::$instance[$userId])) {
			self::$instance[$userId] = new self($userId);
		}
		return self::$persons[$userId];
	}

	private function __clone()
	{
	}

	private function __wakeup()
	{
	}
}
