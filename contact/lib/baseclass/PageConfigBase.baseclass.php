<?php
#文字コード:UTF-8

class PageConfigBase{
	public $Table;
	public $Method;

	public function __construct($Method = ""){
		global $Config,$Object,$Work;
		$this->Method[$Method]["SubPage"] = array_merge((array)$this->Method["Default"]["SubPage"],(array)$this->Method[$Method]["SubPage"]);
		if($Method && is_array($this->Method[$Method]["SubPage"])){				#SubPage確認
			foreach($this->Method[$Method]["SubPage"] as $Key => $Value){
				if(!$Value["Page"]){ $Value["Page"] = $Key; }
				$Value["Key"] = $Key;

				$Page = $Config["SubPage"]["Path"]."/".$Value["Page"].".subpage.php";
				$ClassName = $Value["Page"]."SubPage";
				if(!class_exists($ClassName,false)){
					if(!is_readable($Page)){ Error::View("Frame","PageNotFound",array_merge(array("Page"=>$Page),eval($GLOBALS["ErrorEval"]))); }
					if(!include_once($Page)){ Error::View("Frame","PageNotFound",array_merge(array("Page"=>$Page),eval($GLOBALS["ErrorEval"]))); }
					if(!class_exists($ClassName,false)){ Error::View("Frame","ClassNotFound",array_merge(array("Page"=>$Page,"Class"=>$Frame["Class"]),eval($GLOBALS["ErrorEval"]))); }
				}
				$SubPage = new $ClassName($Value);
			}
		}
	}

	public function Search($Method){
		global $Config,$Object,$Work;
		return Form::Search($this->Method[$Method]);
	}

	public function FormRecive($Method){
		global $Config,$Object,$Work;
		Form::Recive($this->Method[$Method]["Form"]);

		if(isset($Work["Form"]["Error"])){
			$Work["Error"]["Page"] = $this->Method[$Method]["ErrorPage"];
			return false;
		}else{ return true; }
	}

	public function FormSave($Identifier = ""){
		global $Config,$Object,$Work;
		if($Identifier == ""){ $Identifier = $Work["Frame"]["Identifier"]; }
		$Work["Form"]["Key"] = $Object["Session"]->Save($Identifier,Form::Form2Value($Work["Form"]));
	}

	public function FormLoad(){
		global $Config,$Object,$Work;
		if(!$Work["Input"]["POST"]["Key"]){
//			$this->FormClear();
			return false;
		}
		$this->Method["Default"]["FormLoad"]["Method"] = "LOAD";
		Form::Recive($this->Method["Default"]["FormLoad"]);

		if(isset($Work["Form"]["Error"])){
			$Work["Error"]["Page"] = $this->Method["Default"]["ErrorPage"];
			return false;
		}else{ return true; }
	}

	private $FormLoadConfig = array(
		Method => "LOAD",

	);
	public function FormClear(){
		global $Config,$Object,$Work;
		if(!$Work["Input"]["POST"]["Key"]){
			return false;
		}
		$Object["Session"]->Clear($Work["Frame"]["Identifier"],$Work["Input"]["POST"]["Key"]);
		return true;
	}
}



#エラー防止のため、PHP閉じタグ未記載
#?>