<?php
#文字コード:UTF-8
#改行コード:LF

require_once("lib/shared.php");

$PathInfo = preg_split('|/|',$_SERVER["PATH_INFO"]);

array_shift($PathInfo);

if(count($PathInfo) >= 2){ $Form = array_shift($PathInfo); }

if($Form && is_readable($Config["Template"]["Path"]."/$Form/pageconf.php")){  }
elseif(is_readable($Config["Template"]["Path"]."/form/pageconf.php")){ $Form = "form"; }
else{ Error::View("Config","TemplateDirNotFound"); }

$Method = array_shift($PathInfo);

$Frame["FormURI"] = $Frame["BaseURI"]."/index.php/$Form";

if(!is_file($Config["Path"]["Data"]."/$Form/setup") || md5($Frame["FormURI"]) != file_get_contents($Config["Path"]["Data"]."/$Form/setup")){ Error::View("Setup","TemplateNotAllow"); }
if(is_file($Config["Path"]["Data"]."/setup.php") || !is_file($Config["Path"]["Data"]."/.htaccess")){ Error::View("Setup","Access"); }

$PageConfig = Utility::ClassLoad($Config["Template"]["Path"]."/$Form/pageconf.php","PageConfig",$Method);
$PageConfig->FormLoad();

ErrorCheck($Work["Error"]["Page"]);

switch($Method){
	case "File":
		$ID = array_shift($PathInfo);
		$ID = preg_replace('/^\[(.*)\]$/','$1',$ID);
		$File = new File();
		$File->ImageOutput($ID);
		exit;
		break;
	case "Result":
		break;
	case "Review":
		break;
	case "Modify":
	case "Input":
	default:
		$Method = "Input";
}

$PageConfig->FormLoad();
ErrorCheck($Work["Error"]["Page"]);

$PageConfig->FormRecive($Method);
ErrorCheck($Work["Error"]["Page"]);

if($Method != "Result"){ $PageConfig->FormSave(); }
else{
	if($PageConfig->Method[$Method]["CSV"]){ CSVOut($Method,$Form);}
	Sendmail($Method,$Form);
	$PageConfig->FormClear();
}

View($Method);



function View($Page){
	global $Work,$Frame,$Work,$Form;

	$Work["Frame"] =& $Frame;
	Template::View("$Page.html",$Work,$Form,true);
//	preprint(array(form=>$Form,Method=>$Method,PathInfo=>$PathInfo));
//	preprint($Work);
	exit;
}
function ErrorCheck($Page){
	if($Page){ View($Page); }
}

