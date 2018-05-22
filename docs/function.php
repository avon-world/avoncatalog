<?php
header("Content-type: application/json; charset=utf-8");
$object = new stdClass();
$root = dirname(__FILE__);
define('FILE_ROOT', $root);
function getCurl($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		"Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7",
		"Cache-Control: no-cache",
		"Connection: keep-alive",
		"Host: my.avon.ru",
		"Pragma: no-cache",
		"Referer: https://my.avon.ru/jelektronnyj-katalog/",
		"User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36 OPR/52.0.2871.64"
	));
	curl_setopt($ch, CURLOPT_HEADER, 0);
	$output = curl_exec($ch);
	$return = ($output === FALSE) ? FALSE : $output;
	curl_close($ch);
	return $return;
}

function getCatalogs($compaing) {
	global $object;
	$catalogs = array();
	$url = 'https://my.avon.ru/api/brochureapi/BrochureSummariesJson?campaignNumber=' . $compaing . '&language=ru&market=RU';
	$file = getCURL($url);
	$select = "";
	if($file):
		$avon = json_decode($file);
		if($avon):
			//@file_put_contents(FILE_ROOT . "/avon.json", json_encode($avon, JSON_PRETTY_PRINT));
			if($avon->Data):
				$select = "<select id=\"select_catalog\" class=\"select_catalog\"><option disabled selected>Выберите каталог</option>";
				foreach($avon->Data as $i=>$catalog):
					$tmpFile = getCURL("https://my.avon.ru/api/brochureapi/BrochureFolderJson?campaignNumber=" . $compaing . "&language=ru&market=RU&folder=" . $catalog->Folder);
					if($tmpFile):
						$avonCat = json_decode($tmpFile);
						$tempData = json_decode($avonCat->Data);
						$tempPages = $tempData->Pages;
						$title = $catalog->Title;
						$folder = $catalog->Folder;
						$count = $catalog->PageCount;
						$cdn = $object->cdnPath;
						//@file_put_contents(FILE_ROOT . "/" . $folder . ".json", json_encode($tempPages, JSON_PRETTY_PRINT));
						$pages = array();
						$select .= "<option value=\"{$i}\">$title</option>";
						foreach($tempPages as $key=>$value):
							$indexpage = $value->Index;
							$page = (object)array(
								'index'=>$indexpage,
								'image'=>strtolower($cdn . $folder."/p" . str_pad($indexpage, 3, "0", STR_PAD_LEFT) . ".jpg"),
								'thumb'=>strtolower($cdn . $folder."/t" . str_pad($indexpage, 3, "0", STR_PAD_LEFT) . ".jpg"),
								'width'=>$value->Width,
								'height'=>$value->Height,
								'products'=>$value->Hotspots['0']->Products
							);
							$pages[] = $page;
						endforeach;
						$tmpCat = (object)array(
							'title'=>$title,
							'folder'=>$folder,
							'pagescount'=>$count,
							'cdn'=>$cdn . $folder . "/",
							'pages'=>$pages
						);
						
						$catalogs[] = $tmpCat;
					endif;
				endforeach;
				$select .= "</select>";
				$object->select = $select;
			endif;
		endif;
	endif;
	return $catalogs;
}

function getShopContext(&$object){
	$re = '/var _ShopContext=(.*});/sUmi';
	$object->compaing = false;
	$object->leftDay = false;
	$object->cdnPath = false;
	$file = getCURL('https://my.avon.ru/jelektronnyj-katalog/');
	if($file):
		preg_match($re, $file, $matches);
		if($matches[1]):
			$avon = json_decode($matches[1]);
			if($avon):
				$cdn = "https://my.avon.ru/";
				$object->compaing = $avon->CampaignNumber;
				$object->leftDay = $avon->CampaignDaysLeft;
				$object->cdnRoot = $cdn;
				$object->cdnShade = $cdn . "assets/ru-ru/images/shade/";
				$object->cdnProduct = $cdn . "assets/ru-ru/images/product/";
				$re = '@https://(.+\.com)/@';
				$object->cdnPath = preg_replace($re, $cdn, $avon->BrochureViewData->BrochureRootUrlFormat);
				//$object->shop = $avon;
				return true;
			endif;
		endif;
	endif;
	return false;
}
if(isset($_GET["catalog"])):
	if(getShopContext($object)):
		//$object->compaing = '201808';
		$re_1 = '/\[CULTURE\]/i';
		$re_2 = '/\[CAMPAIGN\]/i';
		$object->cdnPath = strtolower(preg_replace($re_2, $object->compaing, preg_replace($re_1, 'ru-ru', $object->cdnPath, 1), 1));
		
		$catalogCompaing = FILE_ROOT . "/assets/files/compaing/" . $object->compaing . ".json";
		if(!is_file($catalogCompaing)):
			$object->catalogs = getCatalogs($object->compaing);
			@mkdir(FILE_ROOT . "/assets/files/compaing/", 0777, true);
			$catalogs = json_encode($object);
			file_put_contents(FILE_ROOT . "/assets/files/compaing/" . $object->compaing . ".json", $catalogs);
		else:
			$strCats = file_get_contents(FILE_ROOT . "/assets/files/compaing/" . $object->compaing . ".json");
			$object = json_decode($strCats);
		endif;
	endif;
endif;
if(isset($_POST['modal']) && isset($_POST['page']) && isset($_POST["compaing"]) && isset($_POST["index"])):
	$ids = explode(',', $_POST['modal']."");
	$page = intval($_POST['page']);
	$compaing = intval($_POST['compaing']);
	$index = intval($_POST['index']);
	if($page && count($ids) && $compaing && $index > -1):
		$type = count($ids) < 2 ? "detail" : "list";
		$dir = FILE_ROOT . "/assets/files/compaing/" . $compaing . "/" . $index . "/";
		$file = $dir . $page . "_" . $type . ".json";
		//if(is_file($files)):
		//	$content = @file_get_contents($file);
		//else:
			$id = implode(",", $ids);
			if($type == 'detail'):
				$id = $ids[0];
			endif;
			$content = getCURL("https://my.avon.ru/product/productlistmodal/" . $id);
		///endif;
		if($content):
			$re = '/_ProductListModal_ProductList=(\[\{.+\}\]);/';
			preg_match($re, $content, $matches);
			$object = json_decode($matches[1]);
			if(!$object):
				$object = new stdClass();
			endif;
			@mkdir($dir, 0777, true);
			@file_put_contents($file, json_encode($object, JSON_PRETTY_PRINT));
		endif;
	endif;
endif;


echo json_encode($object, JSON_PRETTY_PRINT);