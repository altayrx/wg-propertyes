<?php
/**
 * 
 */
class CWGFav
{
	protected static $instance = null;

	private $logged = false;
	private $status = 'error';
	private $count = 0;
	private $user;

	private function __construct($isLogged = false)
	{
		$this->user = \CWGUser::getInstance();
		if ($this->user || $isLogged) {
			$this->logged = true;
		}
		//$this->user->fav = $this->getFav();
	}

	function __call($method, $args)
	{
		$method .= 'Action';
		$this->$method($args[0]);
		return $this->fav();
	}

	public function addAction($id = 0)
	{
		if ($id == 0) {
			return;
		}
		if ($this->logged) {
			$this->addFavItem($id);
			$this->delCookies();
		} else {
			$this->addCookieItem($id);
		}
		return $this->fav();
	}

	public function getFav()
	{
		if ($this->logged) {
			$favList = explode('|', $this->user['UF_FAVORITES']);
		} else {
			$favList = explode('|', $_COOKIE['WhitegoodsFavList']);
		}
		if (count($favList) === 1 && $favList[0] === '') {
			return null;
		}
		return $favList;
	}

	public function delAction($id = 0)
	{
		if ($id == 0) {
			return;
		}
		if ($this->logged) {
			$this->delFavItem($id);
			$this->delCookies();
		} else {
			$this->delCookieItem($id);
		}
		return $this->fav();
	}

	/*public function initAction($id = 0)
	{
		if ($id == 0) {
			return;
		}
		if ($this->logged) {
			$this->delFavItem($id);
			$this->delCookies();
		} else {
			$this->delCookieItem($id);
		}
		return $this->fav();
	}*/

	private function addCookieItem($id)
	{
		if (isset($_COOKIE['WhitegoodsFavList'])) {
			$favList = explode('|', $_COOKIE['WhitegoodsFavList']);
			$oldCount = count($favList);
			if (!in_array($id, $favList)) {
				$favList[] = $id;
			}
			if (count($favList) > $oldCount) {
				$this->status = 'ok';
				$this->count = count($favList);
			} else {
				$this->status = 'error';
				$this->count = count($favList);
			}
		} else {
			$favList = Array($id);
			$this->status = 'ok';
			$this->count = count($favList);
		}
		setrawcookie('WhitegoodsFavList', implode('|', $favList), time() + (3600 * 24 * 30), "/");
	}

	private function addFavItem($id)
	{
		if (!empty($this->user['UF_FAVORITES'])) {
			$favList = explode('|', $this->user['UF_FAVORITES']);
			$oldCount = count($favList);
			if (!in_array($id, $favList)) {
				$favList[] = $id;
			}
			if (count($favList) > $oldCount) {
				$this->status = 'ok';
				$this->count = count($favList);
				$user = new \CUser;
				$fields = Array(
					"UF_FAVORITES" => implode('|', $favList),
				);
				$user->Update($this->user['ID'], $fields);
			} else {
				$this->status = 'error';
				$this->count = count($favList);
			}
		} else {
			$favList = Array($id);
			$this->status = 'ok';
			$this->count = count($favList);
			$user = new \CUser;
			$fields = Array(
				"UF_FAVORITES" => implode('|', $favList),
			);
			$user->Update($this->user['ID'], $fields);
		}
	}

	private function delCookieItem($id)
	{
		if (isset($_COOKIE['WhitegoodsFavList'])) {
			$favList = explode('|', $_COOKIE['WhitegoodsFavList']);
			$oldCount = count($favList);
			if (in_array($id, $favList)) {
				unset($favList[array_search($id, $favList)]);
				setrawcookie('WhitegoodsFavList', implode('|', $favList), time() + (3600 * 24 * 30), "/");
				if ($oldCount > count($favList)) {
					$this->status = 'ok';
					$this->count = count($favList);
				} else {
					$this->status = 'error';
					$this->count = count($favList);
				}
			} else {
				$this->status = 'error';
				$this->count = count($favList);
			}
		} else {
			$this->status = 'error';
			$this->count = 0;
		}
	}

	private function delFavItem($id)
	{
		if (!empty($this->user['UF_FAVORITES'])) {
			$favList = explode('|', $this->user['UF_FAVORITES']);
			$oldCount = count($favList);
			if (in_array($id, $favList)) {
				unset($favList[array_search($id, $favList)]);
			}
			if (count($favList) < $oldCount) {
				$this->status = 'ok';
				$this->count = count($favList);
				$user = new \CUser;
				$fields = Array(
					"UF_FAVORITES" => implode('|', $favList),
				);
				$user->Update($this->user['ID'], $fields);
			} else {
				$this->status = 'error';
				$this->count = count($favList);
			}
		} else {
			$this->status = 'error';
			$this->count = 0;
		}
	}

	private function delCookies()
	{
		setrawcookie('WhitegoodsFavList', '', time() - 3600);
	}

	public function fav()
	{
		return json_encode(Array(
			$this->status,
			$this->count,
		));
	}

	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	private function __clone()
	{
	}

	private function __wakeup()
	{
	}
}
