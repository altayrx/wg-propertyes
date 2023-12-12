<?


if (!$_SERVER['DOCUMENT_ROOT']) {
        chdir(dirname(__FILE__));
        $_SERVER['DOCUMENT_ROOT'] = '/usr/local/www/whitegoods.ru/data/';
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('main');

$CODE_ID = 76926;

$PRODUCT_ID = CIBlockElement::GetList(Array(),Array('CODE' => $CODE_ID, 'IBLOCK_ID' => 17), false, false, Array('ID'))->fetch()['ID'];

echo $PRODUCT_ID . "AAA\n";

?>
