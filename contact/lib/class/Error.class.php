<?php 
# 文字コード:UTF-8
# エラー処理クラス
$GLOBALS["Frame"]["Load"]["class"][] = __FILE__;

/**
 * エラーメッセージ表示
 * @param string $Type エラー種別
 * @param string $Msg エラーメッセージ
 */
class Error{
	static protected $Instance;

	protected $MessageFile = "";
	protected $MessageList = array();

	public function __construct($MessageFile = ""){
		if($MessageFile != ""){ $this->MessageFile = $MessageFile; }
		$this->MessageLoad();
	}

	protected function MessageLoad(){
		global $Config,$DB,$Work;

		if($this->MessageFile == "" || !is_readable($this->MessageFile)){ $this->MessageFile = $Config["Error"]["MessageFile"]; }
		if(is_readable($this->MessageFile)){ $this->MessageList = parse_ini_file($this->MessageFile,"true"); }
	}

	static public function GetInstance(){
		if(!Error::$Instance){ Error::$Instance = new Error(); }
		return Error::$Instance;
	}

	static function View($Type,$Detail,$DebugHash = array(),$ErrorNoExit = false,$HTTPStatus = null){
		global $Config,$DB,$Work;

		if(!is_array($Work["Error"])){
			$tmpError = $Work["Error"];
			$Work["Error"] = array("default" => $tmpError);
		}

		if(isset($Work["Error"])){ $Work["Errors"][] = $Work["Error"]; }

		$Inst = Error::GetInstance();

		if($Inst->MessageList[$Type]["Title"] == ""){ $Inst->MessageList[$Type]["Title"] = $Type; }
		if($Inst->MessageList[$Type][$Detail] == ""){ $Inst->MessageList[$Type][$Detail] = $Detail; }

		$Work["Error"]["Title"] = $Inst->MessageList[$Type]["Title"];
		$Work["Error"]["Msg"]   = $Inst->MessageList[$Type][$Detail];

		$DebugHash["Type"] = $Type;
		$DebugHash["Detail"] = $Detail;

		foreach($DebugHash as $Name => $Value){
			if(is_array($Value)){ $Value = print_r($Value,true); }
			
			$Work["Error"]["Debugs"][] = array(Name=>$Name,Value=>$Value);
			$ErrStr .= ":$Name=$Value";
		}

		if(isset($Work["Error"]["Loop"])){
			print "エラー処理に失敗しました。";
			if($Object["Debug"]){ PrePrint($Work,"ErrorLoop"); }
			die($Work["Error"]["Title"].":".$Work["Error"]["Msg"]);
		}else{ $Work["Error"]["Loop"] = 1; }

		if($_SERVER["REMOTE_ADDR"] == ""){ $REMOTE_ADDR = $_SERVER["HOSTNAME"]; }
		else{ $REMOTE_ADDR = $_SERVER["REMOTE_ADDR"]; }

		if($_SERVER["SCRIPT_FILENAME"] != ""){ $PHPFile = $_SERVER["SCRIPT_FILENAME"]; }
		else{ $PHPFile = getcwd()."->".$_SERVER["PHP_SELF"]; }

		if(MODE == "Local"){ $Logfile = $Config["LogLocal"]["PHPError"]; }
		else{ $Logfile = $Config["Log"]["PHPError"]; }

		error_log(date("Y/m/d G:i:s")." $PHPFile ($REMOTE_ADDR) $Type:$Msg - $ErrStr\n",3,$Logfile);

		if(MODE == "Local"){ mail($Config["App"]["Email"],$Config["System"]["Name"]."/offline error",print_r($Work,true)); }
//		if(MODE == "Local"){ print_r($Work); }
		else{
	//		if($HTTPStatus >= 100 && $HTTPStatus <= 599 && function_exists("http_send_status")){ http_send_status($HTTPStatus); }
			if($HTTPStatus == "404"){ header("HTTP/1.0 404 Not Found"); }
			if(!$Config["Debug"]["Enable"]){ unset($Work["Error"]["Debugs"]); }
			Template::View("error.html",$Work["Error"],"/system");
		}
#PrePrint($Work["Error"]);
		if(function_exists("dc_dump")){ dc_dump($DebugHash,"$Type:$Detail"); }
		if(!$ErrorNoExit){ exit(1); }
		else{ return false; }

	}
}

#エラー防止のため、PHP閉じタグ未記載
#?>