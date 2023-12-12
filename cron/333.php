<?
$GLOBALS['a'] = [];
$GLOBALS['f'] = [];

function glob_recursive($dir){
  foreach(glob($dir."*", GLOB_NOSORT) as $filename){
    if(is_dir($filename)) glob_recursive($filename."/*");
    else{
      foreach(glob($dir."*.[jJ][pP][gG]", GLOB_NOSORT) as $filename){

#/usr/local/www/whitegoods.ru/data/upload/transfer/originals/abat/1013/abat_4_id89752_7488_1.jpg<

		if(preg_match('/^.*?_id(\d+)_(\d+)_(\d+)(.*)$/', $filename, $matches)){

			if (!isset($GLOBALS['a'][$matches[1]]) || $GLOBALS['a'][$matches[1]] > $matches[3]) {
				$GLOBALS['a'][$matches[1]] = $matches[3] ;
				$GLOBALS['f'][$matches[1]] = $filename ;

//			 	echo "  file " . $filename . " id  " . $matches[1]. " num  " . $matches[3] . "\n";

			}

//		 	echo "  id " . $matches[1] . " num  " . $matches[3] . "\n";

		}  else {
		 	echo "NO\n";
		}

//      echo $filename."<br>\n";
      }
    }
  }
}
$dir = "/usr/local/www/whitegoods.ru/data/upload/transfer/originals/";
glob_recursive($dir);



if (!$_SERVER['DOCUMENT_ROOT']) {
        chdir(dirname(__FILE__));
        $_SERVER['DOCUMENT_ROOT'] = '/usr/local/www/whitegoods.ru/data/';
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('main');




foreach ($GLOBALS['a']  as $k => $v){


//echo "  file " . $GLOBALS['f'][$k] . " id  " .  $k . " num  " . $GLOBALS['a'][$k] . "\n";


$CODE_ID = $k;

$PRODUCT_ID = CIBlockElement::GetList(Array(),Array('CODE' => $CODE_ID, 'IBLOCK_ID' => 17), false, false, Array('ID'))->fetch()['ID'];


$el = new CIBlockElement;
$arLoadProductArray = Array(
  "DETAIL_PICTURE" => CFile::MakeFileArray($GLOBALS['f'][$k])
  );

$res = $el->Update($PRODUCT_ID, $arLoadProductArray, false, false, true);

echo $PRODUCT_ID . " " . $CODE_ID . " " . $n . "AAA\n";

if ($res) $n++;
//if ($n==3) exit;

}



?>