<?php
/*
* 文字コード:UTF-8
* ページライブラリ用タイプ定義
*/

$GLOBALS["Frame"]["Load"]["class"][] = __FILE__;

class ItemTypeBase{
	public $Value;

	public function __construct($Config){
		$this->Message = array_merge((array)$this->MessageDefault,(array)$this->Message);
		$this->SQL = array_merge((array)$this->SQLDefault,(array)$this->SQL);
		foreach($Config as $Key => $Value){
			if(is_array($this->$Key) && is_array($Value)){ $this->$Key = array_merge($this->$Key,$Value); }
			else{ $this->$Key = $Value; }
		}
		unset($this->MessageDefault);
		foreach(get_object_vars($this) as $Key => $Value){
			if($Value == ""){ unset($this->$Key); }
		}
	}

	public function Validate(){
		Form::Validate($this);
	}

	public function GetAll(){
		$Vars = get_object_vars($this);
		unset($Vars["Message"]);
		return $Vars;
	}
}


#エラー防止のため、PHP閉じタグ未記載
#?>