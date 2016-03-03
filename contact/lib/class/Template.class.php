<?php
# 文字コード:UTF-8
# テンプレート処理クラス
$GLOBALS["Frame"]["Load"]["class"][] = __FILE__;

class Template{
	#テンプレートからHTMLを出力
	static function View($TemplateFile,$DATA,$TemplatePATH = "",$NotConvert = true,$Encoding = null){
		TemplateMain::FilePrint(self::TemplateFileBase($TemplateFile,$TemplatePATH),self::DataBase($DATA,$NotConvert),$Encoding);
	}

	#テンプレートからHTMLを生成
	static function Get($TemplateFile,$DATA,$TemplatePATH = "",$NotConvert = true){
		return TemplateMain::FileReturn(self::TemplateFileBase($TemplateFile,$TemplatePATH),self::DataBase($DATA,$NotConvert));
	}

	#変数からHTMLを出力
	static function ViewF($TemplateDATA,$DATA,$NotConvert = true,$Encoding = null){
		print TemplateMain::DataPrint($TemplateDATA,self::DataBase($DATA,$NotConvert),$Encoding);
	}

	#変数からHTMLを生成
	static function GetF($TemplateDATA,$DATA,$NotConvert = true){
		return(TemplateMain::DataReturn($TemplateDATA,self::DataBase($DATA,$NotConvert)));
	}

	static function DATABase($DATA,$NotConvert = true){
		global $Config,$DB,$Session,$Work;

		$DATA["SERVER"] = $_SERVER;
		$DATA["Config"] = $Config;

		if(function_exists("AddDATA")){ AddDATA($DATA); }
		if(function_exists("AddDATASub")){ AddDATASub($DATA); }

		if($_SERVER["HTTP_HOST"] != ""){ $DATA["SYSTEM"]["HTTP_HOST"] = $_SERVER["HTTP_HOST"]; }
		else{ $DATA["SYSTEM"]["HTTP_HOST"] = $DefHTTPHost; }

		if(!$NotConvert){ return Utility::HTMLSymbol($DATA); }
		else{ return $DATA; }
	}

	static function TemplateFileBase($TemplateFile,$TemplatePATH = ""){
		global $Config,$Work;
	#	if($TemplatePATH == ""){ $TemplatePATH = preg_replace("/^".preg_quote($BaseHTTP,"/")."/","",dirname($_SERVER["SCRIPT_NAME"])); }
	#	if($TemplatePATH == "" && $_SESSION["template_dir"] != "" && is_readable("$HTMLTemplatePATH".$_SESSION["template_dir"]."/$TemplateFile")){ $TemplatePATH = $_SESSION["template_dir"]; }

		$TemplateType = "Template";
		if($TemplateFile == ""){ Error("View","TemplateFileBlank"); }
		if($Work["Frame"]["Template"]["Add"]){ $TemplateFile = preg_replace('/(\.[^\.]+)$/','.'.$Work["Frame"]["Template"]["Add"]."$1",$TemplateFile); }
		if($Work["Frame"]["Template"]["Ext"]){
			$TemplateFile = preg_replace('/\.[^\.]+$/','.'.$Work["Frame"]["Template"]["Ext"],$TemplateFile);
/*
			if(function_exists("mime_content_type")){
				$ContentType = mime_content_type($TemplateFile);
				if($ContentType){ Utility::ContentType($ContentType); }
			}
*/
		}

		$TemplateFile = $Config[$TemplateType]["Path"]."/".MODE.$TemplatePATH."/$TemplateFile";
		if(!is_readable($TemplateFile)){ Error::View("View","TemplateFileNotFound",array("Template"=>$TemplateFile)); }

		if($_GLOBAL["Debug"]){ $_GLOBAL["Debug"]->_Data["Templates"][] = array("Path"=>"$HTMLTemplatePATH$TemplatePATH/$TemplateFile"); }
		return $TemplateFile;
	}

	#テンプレートファイルの有無を確認
	static function CheckTemplateFile($TemplateFile,$TemplatePATH = ""){
		global $Config;
		$TemplateType = "Template";
		if($TemplateFile == ""){ Error("View","TemplateFileBlank"); }
		if($Work["Frame"]["Template"]["Add"]){ $TemplateFile = preg_replace('/(\.[^\.]+)$/','.'.$Work["Frame"]["Template"]["Add"]."$1",$TemplateFile); }
		if($Work["Frame"]["Template"]["Ext"]){
			$TemplateFile = preg_replace('/\.[^\.]+$/','.'.$Work["Frame"]["Template"]["Ext"],$TemplateFile);
/*
			 if(function_exists("mime_content_type")){
				$ContentType = mime_content_type($TemplateFile);
				if($ContentType){ Utility::ContentType($ContentType); }
			}
*/
		}
		if($TemplateFile == ""){ Error::View("View","TemplateFileBlank"); }
		$TemplateFile = $Config[$TemplateType]["Path"]."/".MODE.$TemplatePATH."/$TemplateFile";
		if(is_readable($TemplateFile)){ return true; }
		else{ return false; }
	}

	#テンプレートファイルのリストを取得
	static function GetTemplateFile($Pattern = "",$TemplatePATH = ""){
		global $Config;

		$TemplateFile = $Config["Template"]["Path"]."/".MODE.$TemplatePATH."/$Pattern";
		return glob($TemplateFile);
	}
	#外部テンプレートを読み込み
	static function Load($TemplateFile){
		global $Config,$Work;
		$TemplateFilePath = $Config["Template"]["Include"]."/$TemplateFile";
		if($TemplateFile != "" && is_readable($TemplateFilePath)){ self::View($TemplateFile,$Work,"/Include");  }

	}

}


#エラー防止のため、PHP閉じタグ未記載
#?>