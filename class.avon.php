<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}

class Avon {
	
	private $modx;
	private $dir_permissions;
	private $file_permissions;
	private $company_path = "";
	private $cdnPath = "";
	
	public function __construct(&$modx) {
		$this->modx = $modx;
		$this->dir_permissions = octdec($this->modx->config['new_folder_permissions']);
		$this->file_permissions = octdec($this->modx->config['file_permissions']);
		@mkdir(MODX_BASE_PATH."assets/images/catalogs/", $this->dir_permissions, true);
	}
	
	private function getCURL($url)
	{
		$ch = curl_init();
		$headers = array();
		$headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
		$headers[] = 'Accept-Encoding: gzip, deflate, sdch';
		$headers[] = 'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4';
		$headers[] = 'Cache-Control: no-cache';
		$headers[] = 'Connection: keep-alive';
		$headers[] = 'Pragma: no-cache';
		$headers[] = 'Host: my.avon.ru';
		$headers[] = 'Referer: https://my.avon.ru/jelektronnyj-katalog/';
		$headers[] = 'User-Agent: '.$_SERVER['HTTP_USER_AGENT'];
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$output = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		$return = ($output === FALSE) ? FALSE : $output;
		if($httpCode != 404)
			return $return;
		else
			return false;
	}
	
	private function formatCount($count, $form1, $form2, $form3){
		$count = abs($count) % 100;
		$lcount = $count % 10;
		if ($count >= 11 && $count <= 19) return($form3);
		if ($lcount >= 2 && $lcount <= 4) return($form2);
		if ($lcount == 1) return($form1);
		return $form3;
	}
	
	private function catalog($company, $year, $month, $days, $next){
		$result = array();
		$path_summary = 0;
		$imageold = false;
		$path_broshures = array();
		
		/*$year = 2016;
		$month = "04";*/
		
		$company = $year.$month;
		$company_path = "assets/images/catalogs/".$year."/".$month."/";
		$bjs = 'https://my.avon.ru/api/brochureapi/BrochureSummariesJson?campaignNumber=' . $company . '&language=ru&market=RU';
		//$bjs = "https://my.avon.ru/media/brochure/ru-ru/".$company."/BrochureSummaries.json";
		//$bjson = $this->getCURL($bjs);
		$bjson = @file_get_contents($bjs);
		$dropdown = "";
		$tplthumb = "";
		if($bjson){
			if(!is_dir(MODX_BASE_PATH.$company_path)){
				$mp = MODX_BASE_PATH."files/catalog/modals";
				if(is_dir($mp)){
					$this->removeFolder($mp);
				}
			}
			@mkdir(MODX_BASE_PATH.$company_path, $this->dir_permissions, true);
			@file_put_contents(MODX_BASE_PATH.$company_path."BrochureSummaries.json", $bjson);
			$path_summary = $company_path."BrochureSummaries.json";
			$bjson = json_decode($bjson);
			if($bjson){
				$data_id = 0;
				$tplthumb .= '<div class="catalogouter_wrapper">
					<div class="catalogouter_wrapper_jsblock">
						<div class="book">
							<div id="canvas" class="select-none">
								<div class="book-avoncatalog">
									<div id="book-zoom">
										<div class="sj-book"></div>
									</div>
									<div id="slider-bar" class="turnjs-slider">
										<div id="sliderbar"></div>
									</div>
									<div class="book-nav">
										<div class="pull-left left icon-arrow-left-sml"></div>
										<div class="pull-right right icon-arrow-right-sml"></div>
										<div class="clearfix"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="catalogouter_wrapper_products">
						
					</div>
					<div class="catalogouter_wrapper_thumbnails">';
				$dropdown .= '<div class="catalogouter_dropdown text-center"><div class="dropdown">
					<button class="btn btn-avon btn-avon-transparent-red dropdown-toggle" type="button" id="dropLabel" data-toggle="dropdown">
						<span class="labelCatalog">Выберите каталог</span>
						<b class="line"></b>
						<b class="caret"></b>
					</button>
					<ul class="dropdown-menu catalogDropDown" role="menu" aria-labelledby="dropLabel">';
				foreach($bjson as $value){
					$published = $value->Published;
					$folder = $value->Folder;
					$title = $value->Title;
					if($published){
						$brochure = new stdClass;
						$media = $this->cdnPath.$company."/".$folder."/";
						$patch = $media."p000.jpg";
						if(!$imageold){
							$imageold = $company_path.$folder."/p000.jpg";
							$this->modx->toPlaceholder("title", $beforeTitle.$title.$afterTitle, "catalog.");
						}
						@mkdir(MODX_BASE_PATH.$company_path.$folder."/", $this->dir_permissions, true);
						if(!is_file(MODX_BASE_PATH.$company_path.$folder."/p000.jpg") || filesize(MODX_BASE_PATH.$company_path.$folder."/p000.jpg")<92160){
							$p0 = $this->cdnPath.$company."/".$folder."/p000.jpg";
							$this->modx->toPlaceholder("imagecatalog", $p0, "catalog.");
							//$jpg = $this->getCURL($p0);
							$jpg = @file_get_contents($p0);
							if($jpg){
								@file_put_contents(MODX_BASE_PATH.$company_path.$folder."/p000.jpg", $jpg);
								
								/*if(function_exists('chmod')){
									@chmod(MODX_BASE_PATH.$company_path.$folder."/p000.jpg", $this->file_permissions);
								}*/
							}
						}
						if(!is_file(MODX_BASE_PATH.$company_path.$folder."/brochure.json")){
							$reqjson = "https://my.avon.ru/media/brochure/ru-ru/".$company."/".$folder."/brochure.json";
							//$cjson = $this->getCURL($reqjson);
							$reqjson = "https://my.avon.ru/api/brochureapi/BrochureFolderJson?campaignNumber=" . $company . "&language=ru&market=RU&folder=" . $folder;
							$cjson = @file_get_contents($reqjson);
							if($cjson){
								@file_put_contents(MODX_BASE_PATH.$company_path.$folder."/brochure.json", $cjson);
								/*if(function_exists('chmod')){
									@chmod(MODX_BASE_PATH.$company_path.$folder."/brochure.json", $this->file_permissions);
								}*/
							}
						}
						//$res_brodhure = json_decode(file_get_contents(MODX_BASE_PATH.$company_path.$folder."/brochure.json"));
						$dropdown .= '<li><a href="javascript:;" data-toggle="dropdown" data-folder="'.$data_id.'" data-title="'.$title.'">'.$title.'</a></li>';
						$brochure->json = $company_path.$folder."/brochure.json";
						$brochure->image = $company_path.$folder."/p000.jpg";
						//[[phpthumb? &input=`assets/img/phpthumb4.png` &options=`w=200,h=200,f=png`]]
						$thumbnail = $this->modx->runSnippet('phpthumb', array('input'=>$company_path.$folder."/p000.jpg",'options'=>'w=400,h=400'));
						$brochure->thumbnail = $thumbnail."";
						$brochure->title = $title;
						$brochure->media = $media;
						$brochure->key = $data_id;
						//$brochure->pages = $res_brodhure;
						$path_broshures[] = $brochure;
						$tplthumb .= '<div class="catalogouter_wrapper_thumbnails_item text-center">
							<a class="select_catalog catalogouter_wrapper_thumbnails_item_link" href="[~[*id*]~]#catalog_'.$data_id.'" data-folder="'.$data_id.'" data-title="'.$title.'">
							<span class="catalogouter_wrapper_thumbnails_item_link_title">'.$title.'</span>
							<img src="'.$thumbnail.'" class="catalogouter_wrapper_thumbnails_item_link_image img-responsive" alt="'.$title.'" />
							</a>
						</div>';
						++$data_id;
					}
				}
				$tplthumb .= '</div></div>';
				$dropdown .= '</ul></div></div>'.$tplthumb;
			}
			if(!$imageold){
				$imageold = "assets/images/noimage.jpg";
			}
			$offdays = ($next == 'current' ? 'До конца действия каталога осталось ' : 'До начало действия каталога осталось ').$days.$this->formatCount($days, ' день', ' дня', ' дней');
			$this->modx->toPlaceholder("image", $imageold, "avon.");
			$this->modx->toPlaceholder("days", $days, "catalog.");
			$this->modx->toPlaceholder("year", $year, "catalog.");
			$this->modx->toPlaceholder("month", $month, "catalog.");
			$this->modx->toPlaceholder("company", $company, "catalog.");
			$this->modx->toPlaceholder("path_summary", $path_summary, "catalog.");
			$this->modx->toPlaceholder("path_broshures", $path_broshures, "catalog.");
			//$this->modx->toPlaceholder("pagetitle", $page_title, "catalog.");
			$this->modx->toPlaceholder("offdays", $offdays, "catalog.");
			$this->modx->toPlaceholder("output", $next, "catalog.");
			$this->modx->toPlaceholder("dropdown", $dropdown, "catalog.");
			$path_broshures = json_encode($path_broshures);
			$script = "<script type=\"text/javascript\">
			window.CatalogAvon = {
				image: \"{$imageold}\",
				days: \"{$days}\",
				year: \"{$year}\",
				month: \"{$month}\",
				company: \"{$company}\",
				summary: \"{$path_summary}\",
				broshures: {$path_broshures}
			};\n</script>";
			$this->modx->toPlaceholder("script", $script, "catalog.");
		}
		clearstatcache();
	}
	
	private function broshures($id, $year, $month){
		$company = $year.$month;
		$company_path = "assets/images/catalogs/".$year."/".$month."/";
		
		$bjs = "https://my.avon.ru/media/brochure/ru-ru/".$company."/BrochureSummaries.json";
		$bjs = 'https://my.avon.ru/api/brochureapi/BrochureSummariesJson?campaignNumber=' . $company . '&language=ru&market=RU';
		//$bjson = $this->getCURL($bjs);
		$bjson = @file_get_contents($bjs);
		$result = array();
		if($bjson){
			if(!is_dir(MODX_BASE_PATH.$company_path)){
				$mp = MODX_BASE_PATH."files/catalog/modals";
				if(is_dir($mp)){
					$this->removeFolder($mp);
				}
			}
			@mkdir(MODX_BASE_PATH.$company_path, $this->dir_permissions, true);
			@file_put_contents(MODX_BASE_PATH.$company_path."BrochureSummaries.json", $bjson);
			$path_summary = $company_path."BrochureSummaries.json";
			$bjson = json_decode($bjson);
			if(is_array($bjson)){
				foreach($bjson as $value){
					$published = $value->Published;
					$folder = $value->Folder;
					$title = $value->Title;
					if($published){
						$patch = $this->cdnPath.$company."/".$folder."/p000.jpg";
						$this->modx->toPlaceholder("imagepath", $patch, "catalog.");
						$image = $company_path.$folder."/p000.jpg";
						@mkdir(MODX_BASE_PATH.$company_path.$folder."/", $this->dir_permissions, true);
						if(!is_file(MODX_BASE_PATH.$image)){
							$p0 = $this->cdnPath.$company."/".$folder."/p000.jpg";
							//$jpg = $this->getCURL($p0);
							$jpg = @file_get_contents($p0);
							if($jpg){
								@file_put_contents(MODX_BASE_PATH.$image, $jpg);
								/*if(function_exists('chmod')){
									@chmod(MODX_BASE_PATH.$image, $this->file_permissions);
								}*/
							}
						}
						$brochure = new stdClass;
						$brochure->title = $title;
						$brochure->image = $image;
						$brochure->url = $this->modx->makeUrl($id);
						$result[] = $brochure;
					}
				}
			}
		}
		clearstatcache();
		return $result;
	}
	
	private function removeFolder($path)
	{
		$path = rtrim($path, '/').'/';
		$handle = opendir($path);
		while(false !== ($file = readdir($handle))) {
			if($file != '.' and $file != '..' ) {
				$fullpath = $path.$file;
				if(is_dir($fullpath)) $this->removeFolder($fullpath); else @unlink($fullpath);
			}
		}
		closedir($handle);
		@rmdir($path);
	}
	
	public function getCatalogImages($cat="current"){
		set_time_limit(0);
		$temp = time();
		//$html = $this->getCURL("https://my.avon.ru/jelektronnyj-katalog/");
		$html = @file_get_contents("https://my.avon.ru/jelektronnyj-katalog/");
		$re = "/var _ShopContext=({.+});/";
		$output = "";
		$result = array();
		if($html){
			preg_match_all($re, $html, $matches);
			if($matches[1][0]){
				$json = json_decode($matches[1][0]);
				$company = $json->CampaignNumber;
				$year = substr($company, 0, 4);
				$month = substr($company, 4);
				$company = $year.$month;
				$company_path = "assets/files/catalogs/".$company."/";
				//'https://my.avon.ru/api/brochureapi/BrochureSummariesJson?campaignNumber=' . $$company . '&language=ru&market=RU'
				$bjs = "https://my.avon.ru/media/brochure/ru-ru/".$company."/BrochureSummaries.json";
				$bjs = 'https://my.avon.ru/api/brochureapi/BrochureSummariesJson?campaignNumber=' . $company . '&language=ru&market=RU';
				//$bjson = $this->getCURL($bjs);
				$bjson = @file_get_contents($bjs);
				$result = array();
				if(!is_dir(MODX_BASE_PATH.$company_path)){
					$this->removeFolder(MODX_BASE_PATH."assets/files/catalogs/");
				}
				if($bjson){
					$bjson = json_decode($bjson);
					$folders = array();
					$int = 0;
					//@unlink(MODX_BASE_PATH."assets/files/catalogs/".$company.".zip");
					foreach($bjson as $value){
						$folder = $value->Folder;
						$count = $value->PageCount;
						$folders[$int] = array("folder"=>$company."/".$folder."/", "files"=>array());
						@mkdir(MODX_BASE_PATH.$company_path.$folder."/", $this->dir_permissions, true);
						$patch = "https://my.avon.ru/media/brochure/ru-ru/".$company."/".$folder."/p";
						for($i = 0; $i < $count; ++$i){
							$pre = str_pad($i, 3, "0", STR_PAD_LEFT).".jpg";
							$link = $patch.$pre;
							$folders[$int]["files"][] = $pre;
							if(!is_file(MODX_BASE_PATH.$company_path.$folder."/".$pre)){
								//$image = $this->getCURL($link);
								$image = @file_get_contents($link);
								if($image){
									@file_put_contents(MODX_BASE_PATH.$company_path.$folder."/".$pre, $image);
								}
							}
						}
						++$int;
					}
					// Архивация
					if(!is_file(MODX_BASE_PATH."assets/files/catalogs/".$company.".zip")){
						$zip = new ZipArchive;
						if ($zip -> open(MODX_BASE_PATH."assets/files/catalogs/".$company.".zip", ZipArchive::CREATE) === TRUE) 
						{
							foreach($folders as $key=>$value)
							{
								$zip -> addEmptyDir($value['folder']);
								foreach($value['files'] as $fkey=>$fvalue)
								{
									$zip -> addFile(MODX_BASE_PATH."assets/files/catalogs/".$value['folder'].$fvalue, $value['folder'].$fvalue);
								}
							}
							$zip -> close();
						}
					}
					/*
					$this->removeFolder(MODX_BASE_PATH."assets/files/catalogs/".$year."/");
					
					header ("Content-Type: application/octet-stream");
					header ("Content-Length: ".filesize(MODX_BASE_PATH."assets/files/catalogs/".$company.".zip"));
					header ("Content-Disposition: attachment; filename=".$company.".zip");
					
					if(is_file(MODX_BASE_PATH."assets/files/catalogs/".$company.".zip")){
						$file_handler = @fopen(MODX_BASE_PATH."assets/files/catalogs/".$company.".zip", "rb" );
						while ( ! feof( $file_handler ) ) {
							echo fread( $file_handler, 100 );
						}
						@fclose($file_handler);
						unlink(MODX_BASE_PATH."assets/files/catalogs/".$company.".zip");
						exit();
					}
					*/
					$output = "<!DOCTYPE html>
<html lang=\"ru\">
	<head>
	<meta http-equiv=\"refresh\" content=\"0; url=[(site_url)]assets/files/catalogs/".$company.".zip\">
	</head>
	<body>
		<a href=\"[(site_url)]assets/files/catalogs/".$company.".zip\">Arhive</a>
	</body>
</html>";
				}
			}
		}
		return $output;
	}
	
	public function run($id="1,4", $next="current", $beforeTitle="", $afterTitle=""){
		//$html = $this->getCURL("https://my.avon.ru/jelektronnyj-katalog/");
		$html = @file_get_contents("https://my.avon.ru/jelektronnyj-katalog/");
		$re = "/var _ShopContext=({.+});/";
		$output = "";
		$result = array();
		if($html){
			preg_match_all($re, $html, $matches);
			if($matches[1][0]){
				$json = json_decode($matches[1][0]);
				$company = $json->CampaignNumber;
				$year = substr($company, 0, 4);
				$month = substr($company, 4);
				$days = $json->CampaignDaysLeft;
				$cdn = $json->CdnPaths[0]."/";
				$this->cdnPath = $cdn;
				if($next=="next"){
					if((int)$month==17){
						++$year;
						$month = "01";
					}else{
						$month = str_pad((intval($month)+1), 2, "0", STR_PAD_LEFT);
					}
					$this->catalog($company, $year, $month, $days, $next);
				}elseif($next=="current"){
					$this->catalog($company, $year, $month, $days, $next);
				}else{
					$ids = explode(",", preg_replace("/\s+/", "", $id.""));
					if(is_array($ids) && $ids){
						// CURRENT
						$doc = $this->modx->getDocument($ids[0]);
						if($doc){
							$rts = $this->broshures($ids[0], $year, $month);
							if($rts){
								$result['current'] = $rts;
							}
						}
						if(count($ids)>1){
						// NEXT
							$next_year = $year;
							if((int)$month==17){
								++$next_year;
								$next_month = "01";
							}else{
								$next_month = str_pad((intval($month)+1), 2, "0", STR_PAD_LEFT);
							}
							$doc = $this->modx->getDocument($ids[1]);
							if($doc){
								$rts = $this->broshures($ids[1], $next_year, $next_month);
								if($rts){
									$result['next'] = $rts;
								}
							}
						}
						foreach($result as $key=>$value){
							$output .= "\n<h3 class=\"text-center catalogs_home_title\">Каталог №".($key=='current' ? $month : $next_month)." ".($key=='current' ? $year : $next_year)."</h3>\n";
							$output .= "<div class=\"catalogs_home_flex\">\n";
							foreach($value as $k=>$v){
								$size = @getimagesize(MODX_BASE_PATH.$v->image);
								$output .= "\t<div class=\"catalogs_home_flex_block\">\n";
								$output .= "\t\t<a href=\"".$v->url."\" class=\"catalogs_home_flex_block_link text-center\">\n";
								$output .= "\t\t\t<img class=\"img-responsive lazy\" src=\"assets/snippets/avoncompany/lazy.png\" data-original=\"".$v->image."\" alt=\"".$v->title."\" {$size[3]} />\n";
								$output .= "\t\t\t<span class=\"catalogs_home_flex_block_link_title\">".$v->title."</span>\n";
								$output .= "\t\t</a>\n";
								$output .= "\t</div>\n";
							}
							$output .= "</div>";
						}
					}
				}
				$page_title = $next == 'current' ? 'Действующий каталог AVON №'.$month."/".$year : ($next == 'next' ? 'Следующий кталог AVON №'.$month."/".$year : '');
				$this->modx->toPlaceholder("output", $output, "catalog.");
				$this->modx->toPlaceholder("printtitle", $page_title, "catalog.");
				$sdn = "<script>window.avonCdn = \"".$cdn."\";</script>";
				$this->modx->toPlaceholder("cdnpath", $sdn, "catalog.");
			}else{
				$output = "<div class=\"text-center catalog_error_pads col-xs-12\"><h3 class=\"catalog_error\">Каталог не доступен.</h3>";
				$output .= "<p class=\"catalog_error_pads_p1\">Некоторое время каталог будет недоступен в связи с обновлениями. Приносим извинения за доставленные неудобства.</p><p class=\"catalog_error_pads_p2\">Попробуйте зайти позже.</p>";
				$page_title = $next == 'current' ? 'Действующий каталог AVON' : ($next == 'next' ? 'Следующий каталог AVON' : '');
				$this->modx->toPlaceholder("printtitle", $page_title, "catalog.");
				$this->modx->toPlaceholder("output", $output, "catalog.");
			}
		}
		clearstatcache();
		//return $output;
	}
	
	public function getModal(){
		$req = $_REQUEST["m"]."";
		$req = explode(",", $req);
		$mm = array();
		$output = "";
		if($req && is_array($req)){
			foreach($req as $key=>$value){
				$val = IntVal($value);
				if($val){
					$mm[] = $val;
				}
			}
		}
		if(count($mm)){
			$url = "https://my.avon.ru/product/productlistmodal/".implode(",",$mm);
			$modal_file = implode(".",$mm).".inc.tpl";
			$modal_path = MODX_BASE_PATH."files/catalog/modals";
			if(!is_dir($modal_path)){
				@mkdir(MODX_BASE_PATH."files/catalog/modals", $this->dir_permissions, true);
			}
			if(is_file($modal_path."/".$modal_file)){
				$url = $modal_path."/".$modal_file;
			}
			//$content = $this->getCURL($url);
			//https://my.avon.ru/product/productlistmodal/13017,12533,10717,8332,13509
			//https://my.avon.ru/product/productlistmodal/9436,11933,11934,3356,9764,5305,10684,8124,12489,10412,10395,13786,13791,4594
			$content = @file_get_contents($url);
			$re1 = '/<script\s+type=".+">\s+(?:var _ProductListModal_ProductList=)(.+);\s+<\/script>/Us';
			$re2 = '/<script\s+type=".+">\s+(?:AppModule\.RootScope\.ShopContext\.ProductViewModel=)(.+);\s+<\/script>/Us';
			if($content){
				preg_match_all($re1, $content, $matches);
				if(count($matches[1])){
					$output = $matches[1][0];
				}else{
					preg_match_all($re2, $content, $matches1);
					if(count($matches1)[1]){
						$output = "[".$matches1[1][0]."]";
					}
				}
				if(!is_file($modal_path."/".$modal_file)){
					file_put_contents($modal_path."/".$modal_file, $content);
				}
			}
				
		}
		//return print_r($output, true);
		$dec = json_decode($output."");
		$prod = array();
		if($dec){
			foreach($dec as $key=>$value){
				$product = new stdClass;
				$product->description = $value->Description;
				$product->name = $value->Product->Name;
				$product->price = $value->Product->PriceFormatted;
				$product->sale = $value->Product->SalePriceFormatted;
				$product->profile = $value->Product->ProfileNumber;
				$product->thumb = "assets/ru-ru/images/product/prod_".$value->Product->ProfileNumber."_1_310x310.jpg";
				$product->image = "assets/ru-ru/images/product/prod_".$value->Product->ProfileNumber."_1_613x613.jpg";
				$product->code = $value->Product->SingleVariantFsc;
				$product->hashVariants = false;
				$product->variants = array();
				if($value->HasShadeVariants){
					$product->hashVariants = 1;
					foreach($value->AllVariants as $kv=>$vv){
						if($vv->IsAvailable){
							$variant = new stdClass;
							$variant->name = $vv->Name;
							$variant->code = $vv->DisplayLineNumber;
							$variant->hashImage = true;
							$variant->image = $vv->Image;
							$product->variants[] = $variant;
						}
					}
				}
				if($value->HasNonShadeVariants){
					$product->hashVariants = 2;
					$valvar = "0";
					$variants = $value->Product->VariantGroups[0];
					$product->variants[] = $variants;
				}
				//VariantGroups
				$prod[] = $product;
			}
		}
		$return = json_encode($prod);
		clearstatcache();
		return print_r($return, true);
	}
}
?>