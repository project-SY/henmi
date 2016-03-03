<?php
#文字コード:UTF-8
$GLOBALS["Frame"]["Load"]["class"][] = __FILE__;

class Utility{
/*
	private $ErrorEvalAddfunc = 'return array(
		"FILE" => preg_replace('."'/\(.*$/'".',"",__FILE__),
		"LINE" => preg_replace('."'/^.*\((\d+?)\).*$/','$1'".',__FILE__),
	);';
*/
	##################
	## ログ出力関連 ##
	##################

	#通常ログ出力処理
	static function PrintLog(){
		global $LogFile;
		$argc = func_num_args();
		for($i = 0; $i < $argc; $i++){
			$Error["Msg".$i] = func_get_arg($i);
			$ErrStr .= $Error["Msg".$i].":";
		}

		if($_SERVER["PATH_TRANSLATED"] == ""){ $PATH_TRANSLATED = getcwd()."/".basename($_SERVER["argv"][0]);}
		else{ $PATH_TRANSLATED = $_SERVER["PATH_TRANSLATED"]; }

		if($_SERVER["REMOTE_ADDR"] == ""){ $REMOTE_ADDR = $_SERVER["HOSTNAME"]; }
		else{ $REMOTE_ADDR = $_SERVER["REMOTE_ADDR"]; }

		error_log(date("Y/m/d G:i:s")." $PATH_TRANSLATED ($REMOTE_ADDR) - $ErrStr\n",3,$LogFile);
		chmod($LogFile,0666);
		return true;
	}

	#変更ログ生成
	static function GenChangeLog($NewData,$OldData = "",$Fields = ""){
		global $LoginAdmin;
		if($Fields == ""){ $Fields = array_keys($NewData); }
		else{ $Fields = preg_split("/,/",$Fields); }

		if(is_array($OldData)){
			foreach($Fields as $Key){ if($NewData[$Key] != $OldData[$Key]){ $ChangeLog .= "\t[$Key]".$OldData[$Key]."->".$NewData[$Key]."\n"; } }
			return date("Y/m/d G:i:s")." [".$LoginAdmin["admin_id"]."@".$_SERVER["REMOTE_ADDR"]."] Edit\n$ChangeLog";
		}else{
			foreach($Fields as $Key){ $ChangeLog .= "\t[$Key]".$NewData[$Key]."\n"; }
			return date("Y/m/d G:i:s")." [".$LoginAdmin["admin_id"]."@".$_SERVER["REMOTE_ADDR"]."] New\n$ChangeLog";
		}

	}

	static function RapTime($Msg = "",$Print = true){
		global $RapTime;
		$Time = time(true);
		if(!is_array($RapTime)){
			$RapTime["Start"] = $Time;
			$RapTime["Rap"] = $Time;
			print "Rap Satrt.\n";
		}else{
			print "$Msg:".($Time-$RapTime["Start"]).":".($Time-$RapTime["Rap"])."\n";
			$RapTime["Rap"] = $Time;
		}
	}

	##############
	## HTTP関連 ##
	##############

	#ロケーション処理
	static function Location($LocationURI){
	//	if(!headers_sent($File,$Line)){ Error("システムエラー","すでにヘッダーが送出されています。",__FUNCTION__,__LINE__,"$File:$Line");  }
		if($LocationURI == ""){ Error::View("システムエラー","ロケーション先が見つかりません。",__FUNCTION__,__LINE__); }
		Header("Location: $LocationURI");
		exit;
	}

	#ファイルダウンロード処理
	static function DownloadHeader($FileName){
	//	if(!headers_sent($File,$Line)){ Error("システムエラー","すでにヘッダーが送出されています。",__FUNCTION__,__LINE__,"$File:$Line");  }
		if($FileName == ""){ return false; }
		header('Content-type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.$FileName.'"');
	}

	static function HTTPStatus($Code,$Str){
	//	if(!headers_sent($File,$Line)){ Error("システムエラー","すでにヘッダーが送出されています。",__FUNCTION__,__LINE__,"$File:$Line");  }
		if(!is_numeric($Code)){ $Code = 404; $Str = "Not Found"; }
		if($Str == ""){ $Str = $Code; }
		header(true,true,$Code);
		print $Str;
		exit;
	}

	static function ContentType($ContentType){
		global $Config;

	//	if(!headers_sent($File,$Line)){ Error("システムエラー","すでにヘッダーが送出されています。$File:$Line",__FUNCTION__,__LINE__,"$File:$Line");  }
		if($ContentType == ""){ $ContentType = "text/html"; }
		$Config["ContentType"] = $ContentType;
		header("Content-Type: $ContentType");
	}

	##############
	## HTML関連 ##
	##############


	#HTML記号処理
	static function HTMLSymbol($DATA){
		if(!is_array($DATA)){ $DATA = htmlspecialchars($DATA,ENT_QUOTES); }
		else{
			foreach(array_keys($DATA) as $Key){
				if(preg_match('/:HTML$/',$Key)){ continue; }
				if(is_array($DATA[$Key])){ $DATA[$Key] = self::HTMLSymbol($DATA[$Key]); }
				else{
	//				if($DATA["$Key:HTML"] == 1){ $DATA["$Key:HTML"] = $DATA[$Key]; }
					$DATA["$Key:HTML"] = $DATA[$Key];
					$DATA[$Key] = htmlspecialchars($DATA[$Key],ENT_QUOTES);
				}
			}
		}
		return $DATA;
	}

	#逆HTML記号処理
	static function HTMLSymbolRev($DATA){
		$TransTableRev = array_flip(get_html_translation_table(HTML_SPECIALCHARS,ENT_QUOTES));

		if(!is_array($DATA)){ $DATA = strtr($DATA,$TransTableRev); }
		else{
			foreach(array_keys($DATA) as $Key){
				if(is_array($DATA[$Key])){ $DATA[$Key] = HTMLSymbolRev($DATA[$Key]); }
				else{ $DATA[$Key] = strtr($DATA[$Key],$TransTableRev); }
			}
		}
		return $DATA;
	}

	#半角全角処理
	static function MBHZ($DATA,$Option = "KVas"){
		if(!preg_match('/^[rRnNaAsSkKhHcCV]+$/',$Option)){ return false; }

		if(!is_array($DATA)){ $DATA = mb_convert_kana($DATA,$Option);  }
		else{
			foreach(array_keys($DATA) as $Key){
				if(is_array($DATA[$Key])){ $DATA[$Key] = self::MBHZ($DATA[$Key]); }
				else{ $DATA[$Key] = mb_convert_kana($DATA[$Key],$Option); }
			}
		}

		return $DATA;
	}

	#内部文字コードに変換
	static function MBImport($DATA){
		if(!is_array($DATA)){ $DATA = mb_convert_encoding($DATA,mb_internal_encoding(),mb_detect_encoding($DATA));  }
		else{
			foreach(array_keys($DATA) as $Key){
				if(is_array($DATA[$Key])){ $DATA[$Key] = MBImport($DATA[$Key]); }
				else{ $DATA[$Key] = mb_convert_encoding($DATA[$Key],mb_internal_encoding(),mb_detect_encoding($DATA[$Key])); }
			}
		}
		return $DATA;
	}

	#文字列にリンク設定
	static function SetHref($Str,$Target = "_blank"){
		if($Target != ""){ $Target = " target='$Target'"; }
		return preg_replace("/(https?:\/\/[a-zA-Z0-9\.\/~_\-\?\%\=]+)/" , "<a href='\\1'$Target>\\1</a>",$Str);
	}

	############
	## その他 ##
	############

	#ページ計算
	static function PageCalc($Num,$OffsetNum,$DispNum = null){

		if(!is_numeric($Num)){ $Num = 0; }
		if($OffsetNum > $Num || !is_numeric($OffsetNum) || $OffsetNum < 1){ $OffsetNum = 1; }
		if(!is_numeric($DispNum) || $DispNum <= 0){ $DispNum = 10; }

		if($Num > ($DispNum + $OffsetNum - 1)){ $NextOffset = $DispNum + $OffsetNum; }
		if($OffsetNum > 1){ $PreviousOffset = $OffsetNum - $DispNum; }

		for($PageNo = 0; $Num > $DispNum * $PageNo; $PageNo++){
			unset($Page);
			$Page["No"] = $PageNo + 1;
			$Page["Offset"] =  $DispNum * $PageNo + 1;
			$Page["Start"] =  $DispNum * $PageNo + 1;

			if($PageNo > 0){ $Page["Previous"] = 1; }
			if($Num > $DispNum * ($PageNo + 1)){
				$Page["Next"] = 1;
				$Page["End"] =  $DispNum * ($PageNo + 1);
			}else{
				$Page["End"] =  $Num;
			}

			if($OffsetNum == $DispNum * $PageNo + 1){
				$Page["Current"] = 1;
				$CurrentPage = $Page["No"];
			}
			$Pages[] = $Page;
		}

		return array(
			"OffsetNum"      => $OffsetNum,
			"DispNum"        => $DispNum,
			"PreviousOffset" => $PreviousOffset,
			"NextOffset"     => $NextOffset,
			"PageNum"        => count($Pages),
			"CurrentPage"    => $CurrentPage,
			"Pages"          => $Pages
			);
	}

	static function SetError(&$ErrorArray,$Position,$Type,$Message = Null){
		if($Message != ""){
			$ErrorArray["Column"][$Position][$Type]["Message"] = $Message;
			$ErrorArray["Messages"][] = array(Message=>$Message,Position=>$Position,Type=>$Type,Code=> "$Position:$Type");
		}else{ $ErrorArray["Column"][$Position][$Type] = 1; }

		if(isset($ErrorArray["Errors"])){ $ErrorArray["Errors"] .= ",$Position:$Type"; }
		else{ $ErrorArray["Errors"] .= "$Position:$Type";  }

		return array(
			$Type => array(
				Message =>  $Message,
			),
		);

	}

	static function PrePrint($Data,$Title = ""){
		Global $PrePrintNum;
		$PrePrintNum++;

		if($Title != ""){ print($Title."($PrePrintNum)<br />"); }
		else{ print("($PrePrintNum)<br />"); }

		print("<pre>\n");
		print_r($Data);
		print("</pre>\n");
	}

	static function VariableReplace($String,$Array){
		return preg_replace_callback('/\$\{([^}]+)\}/',create_function('$Maches','$Array = unserialize(\''.str_replace('\\','\\\\',preg_replace("/'/","\\'",serialize($Array))).'\'); return $Array[$Maches[1]];'),$String);
	}

	static function array_fill_keys($Keys,$Value){
		if(!is_array($Keys)){ $Keys = array($Keys); }
		foreach($Keys as $Key){
			if($Key != ""){ $Array[$Key] = $Value; }
		}
		return $Array;
	}

	static function ImageResizeIM($Path,$Width,$Height,$OutPath = null,$OutFormat = null,$OutQuality = null){
		if(!class_exists("Imagick")){ Error::View("ImageResize","Imagickが使用できません。"); }

		if(!$Path || !is_readable($Path)){ Error::View("ImageResize","Path",array_merge(array("Path"=>$Path),eval($GLOBALS["ErrorEval"]))); }
		if(!is_numeric($Width) || $Width < 1){ Error::View("ImageResize","Width",array_merge(array("Width"=>$Width),eval($GLOBALS["ErrorEval"]))); }
		if(!is_numeric($Height) || $Height < 1){ Error::View("ImageResize","Height",array_merge(array("Height"=>$Height),eval($GLOBALS["ErrorEval"]))); }
		if(is_numeric($OutQuality) && $OutQuality < 1){ Error::View("ImageResize","OutQuality",array_merge(array("OutQuality"=>$OutQuality),eval($GLOBALS["ErrorEval"]))); }

		$Image = new Imagick($Path);
		$FileIngo = $Image->identifyImage();

		if(!$Image){ Error::View("ImageResize","UnknowType",eval($GLOBALS["ErrorEval"])); }

		if($Width < $FileIngo["geometry"]["width"] || $Height < $FileIngo["geometry"]["height"]){
			if($Width / $FileIngo["geometry"]["width"] < $Height / $FileIngo["geometry"]["height"]){ $Ratio = $Width / $FileIngo["geometry"]["width"]; }
			else{ $Ratio = $Height / $FileIngo["geometry"]["height"]; }

			$Width  = $FileIngo["geometry"]["width"]  * $Ratio;
			$Height = $FileIngo["geometry"]["height"] * $Ratio;

			$Image->thumbnailImage($Width,$Height);
			$FileOutput = true;
		}

		if($OutFormat != "" && strtolower($Image->getImageFormat()) != strtolower($OutFormat) && count($Image->queryFormats(strtoupper($OutFormat))) > 0){
			$Image->setImageFormat($OutFormat);
			$FileOutput = true;
		}
		if(is_numeric($OutQuality)){ $Image->setImageCompressionQuality($OutQuality); }

		if(is_null($OutPath)){
			Utility::ContentType("image/".strtolower($Image->getImageFormat()));
			header('Content-Disposition: filename="'.basename($Path).".".strtolower($Image->getImageFormat()).'"');
			print $Image;
		}elseif($FileOutput){ $Image->writeImage($OutPath); }
		$Image->destroy();
//		unset($Image);
	}


	static function ImageResize($inImagePath,$outWidth,$outHeight,$outImagePath = null,$outType = "",$Quality = 75){
		if(!function_exists("gd_info")){ Error::View("ImageResize","GDが使用できません。"); }

		if(!$inImagePath || !is_readable($inImagePath)){ Error::View("ImageResize","inImagePath",array_merge(array("inImagePath"=>$inImagePath),eval($GLOBALS["ErrorEval"]))); }
		if(!is_numeric($outWidth) || $outWidth < 1){ Error::View("ImageResize","outWidth",array_merge(array("outWidth"=>$outWidth),eval($GLOBALS["ErrorEval"]))); }
		if(!is_numeric($outHeight) || $outHeight < 1){ Error::View("ImageResize","outHeight",array_merge(array("outHeight"=>$outHeight),eval($GLOBALS["ErrorEval"]))); }
		if(!is_numeric($Quality) || $Quality < 1){ Error::View("ImageResize","Quality",array_merge(array("Quality"=>$Quality),eval($GLOBALS["ErrorEval"]))); }

		$inImageInfo = getimagesize($inImagePath);
		$inWidth  = $inImageInfo[0];
		$inHeight = $inImageInfo[1];

		switch($inImageInfo[2]){
			case IMAGETYPE_JPEG:
				$inImage = imagecreatefromjpeg($inImagePath);
				break;
			case IMAGETYPE_PNG:
				$inImage = imagecreatefrompng($inImagePath);
				break;
			case IMAGETYPE_GIF:
				$inImage = imagecreatefromgif($inImagePath);
				break;
			default:
				Error::View("ImageResize","UnknowType",array_merge(array("ImageType"=>$inImageInfo[2],"MIME"=>image_type_to_mime_type($inImageInfo[2])),eval($GLOBALS["ErrorEval"])));
		}

	#	list($inWidth,$inHeight) = getimagesize($inImagePath);

		if($outWidth < $inWidth || $outHeight < $inHeight){
			if($outWidth / $inWidth < $outHeight / $inHeight){ $outPercent = $outWidth / $inWidth; }
			else{ $outPercent = $outHeight / $inHeight; }

			$outWidth  = $inWidth  * $outPercent;
			$outHeight = $inHeight * $outPercent;

			$outImage = imagecreatetruecolor($outWidth,$outHeight);
			imagecopyresampled($outImage,$inImage,0,0,0,0,$outWidth,$outHeight,$inWidth,$inHeight);
			$FileOutput = true;
		}else{ $outImage = $inImage; }

		if(defined("IMAGETYPE_".strtoupper($outType))){ $outType = constant("IMAGETYPE_".strtoupper($outType)); }
		else{ $outType = $inImageInfo[2]; }
		if($outType != $inImageInfo[2]){ $FileOutput = true; }
		if(is_null($outImagePath)){ Utility::ContentType(image_type_to_mime_type($outType)); }

		if($inImagePath != $outImagePath || $FileOutput){
			switch($outType){
				case IMAGETYPE_JPEG:
					imagejpeg($outImage,$outImagePath,$Quality);
					break;
				case IMAGETYPE_PNG:
					imagepng($outImage,$outImagePath);
					break;
				case IMAGETYPE_GIF:
					imagegif($outImage,$outImagePath);
					break;
			}
		}

	}
	##################
	## ファイル処理 ##
	##################

	#再帰的ディレクトリー削除
	static function DeleteDir($dir,$EmptyOnly = False){
		if(!is_dir($dir)){ return False; }
		if($handle = opendir("$dir")){
			while(false !== ($item = readdir($handle))){
				if($item == "." || $item == "..") continue;
				if($EmptyOnly){
					closedir($handle);
					return False;
				}
				if(is_dir("$dir/$item")){ self::DeleteDir("$dir/$item"); }
				else{ unlink("$dir/$item"); }
			}
			closedir($handle);
		}
		return rmdir($dir) ;
	}

	static function FileDelete($FilePath){
		$re = false;
		$items = glob($FilePath);
		foreach($items as $item){
			if($item == "." || $item == "..") continue;
			if(is_dir($item)){ $re = self::DeleteDir($item); }
			else{ $re = unlink("$item"); }
		}
		return $re;
	}

	####################
	## パスワード処理 ##
	####################


	#OpenSSL形式パスワード認証
	static function OpenSSLAuth($Password,$PasswordEncrypt,$PlainPassword = false){
		if(preg_match('/^\{(.*)\}/',$PasswordEncrypt,$Maches)){
			$AuthType = $Maches[1];
			$PasswordBase64 = preg_replace("/^\{$AuthType\}/",'',$PasswordEncrypt);
		}elseif($PlainPassword){
			$AuthType = "plain";
			$PasswordBase64 = $PasswordEncrypt;
		}
		switch($AuthType){
			case "md5":
				if(base64_encode(md5($Password,true)) == $PasswordBase64){ return true; }
				break;
			case "sha1":
				$PasswordBinary = base64_decode($PasswordBase64);
				$SSHASalt = substr($PasswordBinary,20);
				if(sha1($Password.substr($PasswordBinary,20),true) == $PasswordBinary){ return true; }
				break;
			case "b64":
				if(base64_encode($Password) == $PasswordBase64){ return true; }
				break;
			case "plain":
				if($Password == $PasswordBase64){ return true; }
				break;
			default:
				Error::View("Password","UnknowEncrypt",array_merge(array("AuthType"=>$AuthType,"PasswordEncrypt"=>$PasswordEncrypt),eval($GLOBALS["ErrorEvalAddFunc"])));
		}
		return false;
	}

	#OpenSSL形式パスワード生成
	static function GenOpenSSLPass($Password,$AuthType = "md5"){
		switch($AuthType){
			case "md5":
				return "{md5}".base64_encode(md5($Password,true));
				break;
			case "sha1":
				return "{sha1}".base64_encode(sha1($Password,true));
				break;
			case "b64":
				return "{b64}".base64_encode($Password);
				break;
			case "plain":
				return "{plain}".$Password;
				break;
			default:
				Error::View("Password","UnknowEncrypt",array_merge(array("AuthType"=>$AuthType,"PasswordEncrypt"=>$PasswordEncrypt),eval($GLOBALS["ErrorEvalAddFunc"])));
		}
	}


	#パスワード生成
	static function GenPass($Length = 0){
		if($Length < 1){ $Length = 8; }

		$PassChar = 'abcdefghkmnprstuvwxyzABCDEFGHJKLMNPRSTUVWXYZ234567';
		$PassChars = preg_split("//",$PassChar,0,PREG_SPLIT_NO_EMPTY);
		$Pass = "";
		for($i=0; $i<$Length; $i++){ $Pass .= $PassChars[array_rand($PassChars,1)]; }
		return $Pass;
	}

	static function HashAuthCodeGen($Key = "",$LifeTimeDay = 0,$Len = null){
		if(!$Key){ $Key = uniqid(); }
		$Rand = rand(0,9);
		$Code = $Rand.sha1($Rand.$Key);
		if(is_numeric($Len) && $Len > 2){ $Code = substr($Code,0,$Len); }
		return $Code;
	}

	static function HashAuth($Key,$Hash,$Len = null){
		$Key = substr($Hash,0,1).$Key;
		$Hash = substr($Hash,1);
		$Code = sha1($Match[1].$Key);
		if(is_numeric($Len) && $Len > 2){ $Code = substr($Code,0,$Len); }
		if($Code == $Hash){ return true; }
		else{ return false; }
	}

	####################
	## 値チェック処理 ##
	####################

	#数値配列かどうか。
	static function is_assoc($arr){
		return (is_array($arr) && (!count($arr) || count(array_filter(array_keys($arr),'is_numeric')) == count($arr)));
	}

	#正しい書式のメールアドレスの場合はTrue、違う場合はFalseを返す
	static function is_email($Email){
		if(preg_match('/^(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|"[^\\\\\x80-\xff\n\015"]*(?:\\\\[^\x80-\xff][^\\\\\x80-\xff\n\015"]*)*")(?:\.(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|"[^\\\\\x80-\xff\n\015"]*(?:\\\\[^\x80-\xff][^\\\\\x80-\xff\n\015"]*)*"))*@(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\\\x80-\xff\n\015\[\]]|\\\\[^\x80-\xff])*\])(?:\.(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\\\x80-\xff\n\015\[\]]|\\\\[^\x80-\xff])*\]))*$/',$Email)){ return True; }
		return False;
	}

	#日付の妥当性確認(形式は2005/12/31)
	static function is_date($Date){
		list($DateYear,$DateMonth,$DateDay,$DateOther) = preg_split("/\//",$Date,4);
		if(!is_numeric($DateYear) || !is_numeric($DateMonth) || !is_numeric($DateDay) || $DateOther != ""){ return false; }
		else{ return checkdate($DateMonth,$DateDay,$DateYear); }
	}

	#日付の妥当性確認(形式は2005/12/31 23:59:59.99)
	static function is_datetime($Date,$RequireTime = true){
		list($Date,$Time) = preg_split("/\ /",$Date,2);
		if($RequireTime && ($Time == "" || !self::is_time($Time))){ return false; }
		elseif($Time != "" && !self::is_time($Time)){ return false; }
		return self::is_date($Date);
	}

	#時刻の妥当性確認(形式は23:59:59.99)
	static function is_time($Time,$MaxHour = 23){
		if(!is_numeric($MaxHour) || $MaxHour < 0){ $MaxHour = 23; }
		list($Time,$TimeFloat) = preg_split("/\./",$Time,2);
		list($TimeHour,$TimeMin,$TimeSec) = preg_split("/\:/",$Time,3);
		if(
			is_numeric($TimeHour) && is_numeric($TimeMin) && is_numeric($TimeSec) &&
			$TimeHour >= 0 && $TimeHour <= $MaxHour &&
			$TimeMin >= 0 && $TimeMin <= 59 &&
			$TimeSec >= 0 && $TimeSec <= 59 &&
			($TimeFloat == "" || is_numeric($TimeFloat))
		){ return true; }
		return false;
	}

	# IPアドレス(配列)がネットワークに含まれているか。
	static function in_ipaddrs($IP,$Networks){
		if(is_array($Networks)){
			foreach($Networks as $Network){ if(self::in_ipaddr($IP,$Network)){ return true; } }
		}else{ return self::in_ipaddr($IP,$Networks); }
		return false;
	}

	# IPアドレスがネットワークに含まれているか。
	static function in_ipaddr($IP,$Network){
		if($IP == "" || $Network == ""){ return false; }
		if(!self::is_ipaddr($IP)){ return false; }

		list($Subnet,$Netmask) = preg_split('/\//',$Network);
		if(!$Netmask){ $Netmask = 32; }
		if(self::is_ipaddr($Netmask)){ $Netmask = strpos(sprintf("%032b",ip2long($Netmask)),"0"); }
		if(!is_numeric($Netmask) || $Netmask < 0){ return false; }

		return (substr_compare(sprintf("%032b",ip2long($IP)),sprintf("%032b",ip2long($Subnet)),0,$Netmask) === 0);
	}

	# IPアドレスとして正しいか。
	static function is_ipaddr($IP){
		if(preg_match('/^(\d|[01]?\d\d|2[0-4]\d|25[0-5])\.(\d|[01]?\d\d|2[0-4]\d|25[0-5])\.(\d|[01]?\d\d|2[0-4]\d|25[0-5])\.(\d|[01]?\d\d|2[0-4]\d|25[0-5])$/',$IP)){ return true; }
		else{ return false; }
	}

	# IPアドレス/ネットワークとして正しいか。
	static function is_inet($INet){
		list($Subnet,$Netmask) = preg_split('/\//',$INet);
		if($Netmask != "" && (!is_numeric($Netmask) || $Netmask < 0 || $Netmask > 32)){ return false; }
		return self::is_ipaddr($Subnet);
	}

	####################
	## ロード関連 ##
	####################

	static function ClassLoad($Path,$Class = "",$Arg1 = null,$Arg2 = null){
		if(!is_readable($Path)){ Error::View("Class","FileNotFound",array_merge(array("Path"=>$Path),eval($GLOBALS["ErrorEval"]))); }
		if(!include_once($Path)){ Error::View("Class","LoadFail",array_merge(array("Path"=>$Path),eval($GLOBALS["ErrorEval"]))); }
		if($Class != ""){
			if(!class_exists($Class)){ Error::View("Class","NotFound",array_merge(array("Page"=>$Page,"Class"=>$Class),eval($ErrorEval))); }
			return new $Class($Arg1,$Arg2);
		}else{ return true; }
	}

	# 携帯判別
	static function MobileCheck(){
		$Agent = $_SERVER['HTTP_USER_AGENT'];
//		print $Agent;
		if(preg_match('{^DoCoMo/[12]\.0}',$Agent)){
			$Carrier = "DoCoMo";
		}elseif(preg_match('{^(J\-PHONE|Vodafone|MOT\-[CV]980|SoftBank)|Semulator/}',$Agent)){
			$Carrier = "SoftBank";
		}elseif(preg_match('/^KDDI\-|UP\.Browser/',$Agent)){
			$Carrier = "KDDI";
		}elseif(preg_match('{^PDXGW/|DDIPOCKET;|WILLCOM;}',$Agent)){
			$Carrier = "WILLCOM";
		}else {
		  $Carrier = false;
		}
		if(!defined("CARRIER")){ define("CARRIER",$Carrier); }
		if($Carrier){
			return array(
				Carrier => $Carrier,
			);
		}else{ return false; }
	}

	static function GetVarPath(&$Var,$Path){
		if($Path == "" || $Path == "/"){ return $Var; }
		$KeyPath = '["'.preg_replace('/\//','"]["',$Path).'"]';
		$ParentPath = preg_Replace('/\[[^\[]*?$/','',$KeyPath);
		 eval('if(is_array($Var'."$ParentPath".')){ $Ref = $Var'."$KeyPath; }");
		 return $Ref;
	}

	static function SetVarPath(&$Var,$Path,$Value){
		if($Path == "" || $Path == "/"){ $Var = $Value; }
		else{
			$KeyPath = '["'.preg_replace('/\//','"]["',$Path).'"]';
			 eval('$Var'."$KeyPath = ".'$Value;');
		}
	}

	static function Path2Query($Path){
		if($Path == ""){ return false; }
		list($Query,$Path) = preg_split('/\//',$Path,2);
		if($Path != ""){ $Query .= '['.preg_replace('/\//','][',$Path).']'; }
		return $Query;
	}

	####################
	## CSV関連 ##
	####################

	function fgetcsv(&$handle,$length = null,$d = ',',$e = '"'){
		$d = preg_quote($d);
		$e = preg_quote($e);
		$_line = "";
		while($eof != true){
			$_line .= (empty($length) ? fgets($handle) : fgets($handle, $length));
//			$_line .= mb_convert_encoding((empty($length) ? fgets($handle) : fgets($handle, $length)),mb_internal_encoding());
//preprint(mb_detect_encoding($_line));

			$itemcnt = preg_match_all('/'.$e.'/', $_line, $dummy);
			if ($itemcnt % 2 == 0) $eof = true;
		}
		$_line = mb_convert_encoding($_line,mb_internal_encoding(),mb_detect_encoding($_line));

		$_csv_line = preg_replace('/(?:\\r\\n|[\\r\\n])?$/', $d, trim($_line));
		$_csv_pattern = '/('.$e.'[^'.$e.']*(?:'.$e.$e.'[^'.$e.']*)*'.$e.'|[^'.$d.']*)'.$d.'/';
		preg_match_all($_csv_pattern, $_csv_line, $_csv_matches);
		$_csv_data = $_csv_matches[1];

		for($_csv_i=0;$_csv_i<count($_csv_data);$_csv_i++){
			$_csv_data[$_csv_i]=preg_replace('/^'.$e.'(.*)'.$e.'$/s','$1',$_csv_data[$_csv_i]);
			$_csv_data[$_csv_i]=str_replace($e.$e, $e, $_csv_data[$_csv_i]);
		}
		return empty($_line) ? false : $_csv_data;
	}
	function BOMRemove($File) {
		$BOM = fread($File, 3);
		if($BOM != b"\xEF\xBB\xBF"){ rewind($File); }
	}
	####################
	## JSON関連 ##
	####################
	function JSONEscape($Value){
		$Value = str_replace("\r\n","\n",$Value);
		$Value = str_replace("\r","\n",$Value);
		$Value = str_replace("\n","\\n",$Value);
		$Value = str_replace("'","\'",$Value);
		$Value = str_replace('"','\"',$Value);
		return $Value;
	}
	####################
	## Frame関連 ##
	####################
	static public function UniqueCheck($Target,$Column,$Value,$ID = null,$Where = ""){
		global $Config,$Object,$Work;
		$Table = GetTable($Target);
		if($ID){ $Table->Set("ID",$ID); }
		return $Table->UniqueCheck($Column,$Value,$Where);
	}

	static public function RowCheck($Target,$Column,$Value,$Where = "",$Ret = false){
		global $Config,$Object,$Work;
		if($Where != ""){
			$tmpConfig = $Config["Table"][$Target];
			$Config["Table"][$Target] = array(
				"Type" => "Path",
				"DB/Where/SELECT" => $Where,
			);
		}

		$Table = GetTable($Target);
		$Row = $Table->LoadRow($Value,$Column,$Where);

		if($Where != ""){ $Config["Table"][$Target] = $tmpConfig; }

		if($Ret && is_array($Row)){ return $Row; }
		else{ return is_array($Row); }
	}
}
#エラー防止のため、PHP閉じタグ未記載
#?>