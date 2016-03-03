<?php
#文字コード:UTF-8
#改行コード:LF

//error_reporting(0);
define(APP_ROOT,dirname(dirname(__FILE__)));
define(MODE,"");

# エラー時配列
$ErrorEval = 'return array(
	"FILE" => preg_replace('."'/\(.*$/'".',"",__FILE__),
	"LINE" => preg_replace('."'/^.*\((\d+?)\).*$/','$1'".',__FILE__),
	"MODE"  => MODE,
	"APP_ROOT"  => APP_ROOT,
	"BASE_URI"  => BASE_URI,
	"ACTION"  => ACTION,
);';

$Config["Path"]["Data"] =  APP_ROOT."/data";
$Config["Log"]["PHPError"] = $Config["Path"]["Data"] ."/Log";
$Config["Template"]["Path"] = APP_ROOT."/template";
$Config["Template"]["PageConfig"] = $Config["Template"]["Path"];
$Config["Debug"]["Enable"] = true;
$Config["App"]["Identity"] = "f2m";
$Config["Error"]["MessageFile"] = APP_ROOT."/template/error.ini";

$Frame["BaseURI"] = $_SERVER["HTTP_HOST"];
if(preg_match('/'.preg_quote(basename($_SERVER["SCRIPT_NAME"]),'/').'/',$_SERVER["SCRIPT_URI"])){
	$Frame["BaseURI"] = preg_replace('/'.preg_quote($_SERVER["SCRIPT_NAME"],'/').'.*/','',$_SERVER["SCRIPT_URI"]).dirname($_SERVER["SCRIPT_NAME"]);
}else{
	$Frame["BaseURI"] = preg_replace('/'.preg_quote(dirname($_SERVER["SCRIPT_NAME"]),'/').'.*/','',$_SERVER["SCRIPT_URI"]).dirname($_SERVER["SCRIPT_NAME"]);
}

$Work["Input"]["GET"]   =& $_GET;
$Work["Input"]["POST"]  =& $_POST;
$Work["Input"]["FILES"] =& $_FILES;

$Work["SYSTEM"]["TimeCode"] = date("YmdHis-".getmypid());
$Work["SYSTEM"]["DateTime"] = date("Y/m/d H:i:s");

require_once(APP_ROOT."/lib/config.php");

set_include_path(APP_ROOT."/lib".PATH_SEPARATOR.APP_ROOT."/lib/bundle".PATH_SEPARATOR.get_include_path());
spl_autoload_register("NPAutoLoader");

//session_start();
include_once(APP_ROOT."/lib/bundle/Net/UserAgent/Mobile.php");

$Object["Session"] = new Session($Config["App"]["Identity"],null,false,$_SERVER["SCRIPT_NAME"],md5($Config["App"]["Identity"]));

function SimpleError($Message,$Status = null){
	if(is_numeric($Status)){ header("HTTP/1.0 $Status"); }
	print "$Message";
	exit;
}

function PrePrint($Data,$Title = "",$DebugConsole = true){
	Global $PrePrintNum;
	$PrePrintNum++;

	if($Title != ""){ $Title = "$Title($PrePrintNum)"; }
	else{ $Title = "($PrePrintNum)"; }

	//	if(function_exists("dc_dump") && $DebugConsole){
	//		dc_dump($Data,$Title);
	//	}else{
	print("$Title<br />");
	print("<pre>\n");
	print_r($Data);
	print("</pre>\n");
	//	}
}

function NPAutoLoader($ClassName){
	if(preg_match('/Base$/',$ClassName)){ $Type = "baseclass"; }
	else{ $Type = "class"; }

	if((include_once("$Type/$ClassName.$Type.php")) === false){
		Error::View("System","ClassLoadFail",array_merge(array("ClassName"=>$ClassName),eval($GLOBALS["ErrorEval"])));
	}
}

function SendMail($Method,$Form){
	global $Config,$Work,$Object,$PageConfig;

	$File = new File();
	$Templates = Template::GetTemplateFile("$Method.*.eml","/".$Form);
	if(Template::CheckTemplateFile("$Method.eml",$Form)){ $Templates[] = "$Method.eml"; }
	if(is_array($Templates)){
		foreach($Templates as $Template){
			$Template = basename($Template);
			$AttachmentsList = $PageConfig->Method[$Method]["Attachments"][$Template];
			unset($Attachments);
			if($AttachmentsList){
				foreach($AttachmentsList as $Attachment){
					$FileID = Utility::GetVarPath($Work["Form"],"$Attachment/Value");
					$FileInfo = $File->GetInfo($FileID);
					if($FileInfo){
						$Attachments[] = array(
								Name => $FileInfo["Name"],
								Body => $File->GetFile($FileID),
						);
					}
				}
			}

			if(!Mail::Send(Template::Get($Template,$Work,$Form),$Attachments)){ Error::View("Form","MailFail",array_merge(array("Template"=>$Template),eval($GLOBALS["ErrorEval"]))); }
		}
	}
}

function CSVOut($Method,$Form){
	global $Config,$Work,$Object,$PageConfig,$Form;
	$CSVConfig = $PageConfig->Method[$Method]["CSV"];
	$CSVPath = $Config["Path"]["Data"]."/$Form/".date("Ymd").".csv";

	$NewFile = !is_file($CSVPath);
	$CSV = fopen($CSVPath,'a');
	if($NewFile){
		if(!$CSVConfig["ColumnName"]){ $CSVConfig["ColumnName"] = $Config["Column"]; }

		fwrite($CSV,b"\xEF\xBB\xBF");
		fputcsv($CSV,$CSVConfig["ColumnName"]);
	}
	fwrite($CSV,Template::Get("$Method.csv",$Work,$Form));
	fclose($CSV);
	chmod($CSVPath,0666);
}


