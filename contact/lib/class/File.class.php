<?php
#文字コード:UTF-8
$GLOBALS["Frame"]["Load"]["class"][] = __FILE__;

class File{
	protected $Mode = "File";
	protected $Config = array(
		Path     => "",
		LifeTime => 86400,
		MIME     => "application/octet-stream",
	);

	CONST STATUS_TEMPORARY = 2;
	CONST STATUS_OPEN = 1;
	CONST STATUS_CLOSE = -1;

	public function __construct($Config = null){
		global $Config,$Object,$Work;
		if(!$_SESSION["FileStore"]){ $_SESSION["FileStore"] = array(); }
	}

	public function Save($Path,$Info = null,$Copy = false){
		global $Config,$Object,$Work;
		if($Path == "" || !is_readable($Path)){ return false; }
		return $this->SaveData(file_get_contents($Path),$Info);
	}

	public function SaveData($FileData,$Info = null){
		global $Config,$Object,$Work;

		$ID = preg_replace("/\./","",microtime(true));
		$_SESSION["FileStore"][$ID] = array(
			File => $FileData,
			Info => $Info,
		);

		return $ID;
	}

	public function SetInfo($ID,$InfoNew){
		if(!$_SESSION["FileStore"][$ID]){ return false; }
		$_SESSION["FileStore"][$ID]["Info"] = $InfoNew;
		return true;
	}

	public function GetInfo($ID){
		if(!$_SESSION["FileStore"][$ID]){ return false; }
		return $_SESSION["FileStore"][$ID]["Info"];
	}

	public function GetFile($ID){
		if(!$_SESSION["FileStore"][$ID]){ return false; }
		return $_SESSION["FileStore"][$ID]["File"];
	}

	public function GetInfos($TargetName,$TargetID,$Status = null,$Convert = true){
		global $Config,$Object,$Work;
		return false;
	}

	public function GetPath($ID){
		return false;
	}

	public function Copy($TargetName,$TargetID,$Status = null,$Info = array()){
		return false;
	}

	public function CopyID($ID,$Info = array()){
		return false;
	}

/*
	public function GetFileID($TargetName,$Identifier,$TargetID,$Status){
		return $ID;
	}
*/

	public function Output($ID,$Download = false){
		if(!$_SESSION["FileStore"][$ID]){ return false; }
		$Row = $_SESSION["FileStore"][$ID]["Info"];
		if($Download){
			if($Row["Name"] == ""){ $Row["Name"] = $ID; }
			DownloadHeader($Row["Name"]);
		}
		if($Row["Type"] == ""){ $Row["Type"] = $this->Config["MIME"]; }
		Utility::ContentType($Row["Type"]);

		print($_SESSION["FileStore"][$ID]["File"]);
		return true;
	}

	public function ImageOutput($ID,$Width = null,$Height = null,$Type = "",$Quality = 85){
		if(!$_SESSION["FileStore"][$ID]){ return false; }
		$Row = $_SESSION["FileStore"][$ID]["Info"];
//		if(is_numeric($Width) && is_numeric($Height)){ Utility::ImageResize($Path,$Width,$Height,null,$Type,$Quality); }
//		else{
			if($Row["Type"] == ""){ $Row["Type"] = $this->Config["MIME"]; }
			Utility::ContentType($Row["Type"]);
//		}
		print($_SESSION["FileStore"][$ID]["File"]);
		return true;
	}

	public function Restore($ID,$Path){
		return true;
	}

	protected function Info2Table($Info){
		if(isset($Info["Target"])){ $Data["file_target"] = $Info["Target"]; }
		if(isset($Info["TargetID"])){ $Data["file_target_id"] = $Info["TargetID"]; }
		if(isset($Info["Identifier"])){ $Data["file_identifier"] = $Info["Identifier"]; }
		if(isset($Info["Status"])){ $Data["file_status"] = $Info["Status"]; }
		if(isset($Info["Name"])){ $Data["file_name"] = $Info["Name"]; }
		if(isset($Info["Type"])){ $Data["file_type"] = $Info["Type"]; }
		if(isset($Info["LifeTime"])){ $Data["file_lifetime"] = $Info["LifeTime"]; }
		return $Data;
	}

	protected function Table2Info($Data){
		$Info["FileID"] = $Data["file_id"];
		$Info["Target"] = $Data["file_target"];
		$Info["TargetID"] = $Data["file_target_id"];
		$Info["Identifier"] = $Data["file_identifier"];
		$Info["Status"] = $Data["file_status"];
		$Info["Name"] = $Data["file_name"];
		$Info["Type"] = $Data["file_type"];
		$Info["LifeTime"] = $Data["file_lifetime"];
		return $Info;
	}

	public function Deletes($TargetName,$TargetID,$Identifier,$Status = null){
		global $Config,$Object,$Work;
		return true;
	}

	public function Delete($ID){
		global $Config,$Object,$Work;
		if($_SESSION["FileStore"][$ID]){
			unset($_SESSION["FileStore"][$ID]);
			return true;
		}else{ return false; }
	}

	public function Clean(){
		global $Config,$Object,$Work;
		return true;
	}
}

#エラー防止のため、PHP閉じタグ未記載
#?>