<?
$GLOBALS['files'] = array();

function glob_recursive($dir){
  foreach(glob($dir."*", GLOB_NOSORT) as $filename){
    if(is_dir($filename)) glob_recursive($filename."/*");
    else{
      foreach(glob($dir."*.[jJ][pP][gG]", GLOB_NOSORT) as $filename){

#/usr/local/www/whitegoods.ru/data/upload/transfer/originals/abat/1013/abat_4_id89752_7488_1.jpg<

		if(preg_match('/^.*?_id(\d+)_(\d+)_(\d+)(.*)$/', $filename, $matches)){

//			if (!isset($GLOBALS['a'][$matches[1]]) || $GLOBALS['a'][$matches[1]] > $matches[3]) {
//				$GLOBALS['a'][$matches[1]] = $matches[3] ;
				$GLOBALS['files'][$matches[1]][$matches[3]] = $filename ;

//			 	echo "  file " . $filename . " id  " . $matches[1]. " num  " . $matches[3] . "\n";

//			}

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








foreach($files as $id => $massiv)
{

	$PRODUCT_ID = CIBlockElement::GetList(Array(),Array('CODE' => $id, 'IBLOCK_ID' => 17), false, false, Array('ID'))->fetch()['ID'];

	if(!$PRODUCT_ID) continue;


	ksort($massiv,SORT_NUMERIC);


			$gallery = Array();
			$n = 0;
			$flag = true;
			foreach($massiv  as  $inner_key => $value)
			{

				if ($flag)
			                {
			                    $flag = false;
			                }
		                else {
				$gallery['n' . $n] = CFile::MakeFileArray($value);
				$n++;
				echo "[$PRODUCT_ID][$id][$inner_key] = $value \n";
		                }


			}


			if (count($gallery) > 0) {
				$db_props = CIBlockElement::GetProperty(1, $PRODUCT_ID, "sort", "asc", Array("CODE"=>"GALLERY"));
				while($ar_props = $db_props->Fetch()) { 
					if ($ar_props["VALUE"]) {
						$ar_val = $ar_props["VALUE"];
						$ar_val_id = $ar_props["PROPERTY_VALUE_ID"];
				
						$arr[$ar_props['PROPERTY_VALUE_ID']] = Array("VALUE" => Array("del" => "Y"));
						CIBlockElement::SetPropertyValueCode($PRODUCT_ID, "GALLERY", $arr );
						CFile::Delete($ar_props['VALUE']);
					}
				}

				CIBlockElement::SetPropertyValueCode(
					$PRODUCT_ID,
					'GALLERY',
					$gallery
				);
			}





	$nnn++;
//	if ($nnn==10) {
//		exit;
//	}



}

?>