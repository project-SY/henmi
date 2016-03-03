<?php
#文字コード:UTF-8
$GLOBALS["Frame"]["Load"]["class"][] = __FILE__;

class Form{

	public function Search($MethodConfig){
		global $Config,$Object,$Work;

		if($Work["Search"]["QueryType"] == "OR"){ $QueryType = "OR"; }
		else{ $QueryType = "AND"; }

		$QueryData["QueryType"] = $QueryType;
		$Work["Search"]["SearchStr"] .= "&Search[QueryType]=$QueryType";

		if($MethodConfig["Form"]["Column"][$Work["Search"]["SortKey"]]){
			$Work["Search"]["SearchStr"] .= "&Search[SortKey]=".$Work["Search"]["SortKey"];
			$Work["Search"]["SearchStr"] .= "&Search[SortType]=".$Work["Search"]["SortType"];

			$Column = $MethodConfig["Form"]["Column"][$Work["Search"]["SortKey"]];

			if($Column->SQL["Column"] != ""){ $Key = $Column->SQL["Column"]; }
			else{ $Key = preg_replace('/.*\//',"",$Work["Search"]["SortKey"]); }

			switch(get_class($Column)){
				case "ItemType_PointCircle":
					if(preg_match('/\( *[\d\.]+ *\, *[\d\.]+ *\)/',$Work["Search"]["SortPoint"],$Matches)){ $Type = "<-> POINT".$Matches[0]; }
					elseif(!isset($Column->Error) && preg_match('/\( *[\d\.]+ *\, *[\d\.]+ *\)/',$Column->Value,$Matches)){
						$Type = "<-> POINT".$Matches[0];
					}
					break;
				case "ItemType_PointBox":
					if(preg_match('/\( *[\d\.]+ *\, *[\d\.]+ *\)/',$Work["Search"]["SortPoint"],$Matches)){ $Type = "<-> POINT".$Matches[0]; }
					elseif(!isset($Column->Error) && preg_match('/\( *\( *([\d\.]+) *\, *([\d\.]+) *\) *\, *\( *([\d\.]+) *\, *([\d\.]+) *\) *\)/',$Column->Value,$Matches)){
						$Type = sprintf("<-> POINT(%f,%f)",(($Matches[1]+$Matches[3])/2),(($Matches[2]+$Matches[4])/2));
					}
					break;
				default:
					$Type = "";
			}
			if(isset($Type)){
				if(preg_match('/ASC|DESC/i',$Work["Search"]["SortType"])){ $Type .= " ".$Work["Search"]["SortType"]; }
				else{ $Type .= " ASC"; }

				if(isset($MethodConfig["Sort"]["Key"])){
					$MethodConfig["Sort"] = array(array(
						Key => $MethodConfig["Sort"]["Key"],
						Type => $MethodConfig["Sort"]["Type"],
					));
				}
				array_unshift(
					$MethodConfig["Sort"],
					array(
						Key => $Key,
						Type => $Type,
					)
				);
			}
		}

		$QueryData["Sort"] = $MethodConfig["Sort"];

		if(is_array($MethodConfig["Form"]["Column"])){
//			preprint($MethodConfig["Form"]["Column"]);
			foreach($MethodConfig["Form"]["Column"] as $Key => $Column){
				$Work["Search"]["SearchStr"] .= "&".self::Path2Query($Key)."=".$Column->Value;
//				preprint($Column->SQL);
				if($Column->Value != "" && $Column->SQL["Operator"] != ""){
					$ColumnName = preg_replace('/.*\//',"",$Key);
					$QueryData["Column"][$ColumnName] = $Column->SQL;
					$QueryData["Column"][$ColumnName]["Value"] = $Column->Value;
					$Work["Search"]["SearchStr"] .= "&".self::Path2Query($Key)."=".$Column->Value;
				}
			}
		}

		return $QueryData;
	}

	static private function Init($FormConfig){
		global $Config,$Object,$Work;

		if(!is_array($FormConfig)){ return false; }
		if($FormConfig["Method"] != "POST" && $FormConfig["Method"] != "LOAD"){ $FormConfig["Method"] = "GET"; }

		if($FormConfig["Method"] == "LOAD"){
			if($Work["Input"]["POST"]["Key"] != ""){ $Form = $Object["Session"]->Load($Work["Frame"]["Identifier"],$Work["Input"]["POST"]["Key"]); }
		}elseif(is_array($Work["Input"][$FormConfig["Method"]])){
			$Form = $Work["Input"][$FormConfig["Method"]];
		}else{ return false; }

		if(!class_exists("ItemType_Default",false)){
			$ClassFile = $Config["Template"]["PageConfig"]."/ItemType.php";
			Utility::ClassLoad($ClassFile);
		}
		if(!class_exists("ItemGroupType_Default",false)){
			$ClassFile = $Config["Template"]["PageConfig"]."/ItemGroupType.php";
			Utility::ClassLoad($ClassFile);
		}
		return $Form;
	}

	static public function Recive(&$FormConfig){
		global $Config,$Object,$Work;

		$Form = self::Init($FormConfig);
		if($FormConfig["Method"] == "LOAD"){
			$Work["Form"] = self::Value2Form($Form);
			$Work["Form"]["Key"] = $Work["Input"]["POST"]["Key"];
		}

		if(is_array($FormConfig["Group"])){
			foreach($FormConfig["Group"] as $Key => &$Value){
				if(!$Value["Type"]){ continue; }
				$ClassName = "ItemGroupType_".$Value["Type"];
				if(!class_exists($ClassName,false)){ Error::View("Form","ItemGroupTypeNotFound",array_merge(array("Class"=>$ClassName),eval($GLOBALS["ErrorEval"]))); }
				$Value = new $ClassName($Value);
				if(is_array($Value->EnableCheck)){
					switch($Value->EnableCheck["Operator"]){
						case "<":
							if(self::GetVarPath($Form,$Value->EnableCheck["Key"]) > $Value->EnableCheck["Value"]){ continue; }
							break;
						case ">":
							if(self::GetVarPath($Form,$Value->EnableCheck["Key"]) < $Value->EnableCheck["Value"]){ continue; }
							break;
						case "!=":
							if(self::GetVarPath($Form,$Value->EnableCheck["Key"]) == $Value->EnableCheck["Value"]){ continue; }
							break;
						case "==":
						default:
							if(self::GetVarPath($Form,$Value->EnableCheck["Key"]) != $Value->EnableCheck["Value"]){ continue; }
					}
				}

				if(!$Value->Key){ $Value->Key = $Key; }
				$Value->Validate($Form);
				self::SetVarPath($Work["Form"],$Key,$Value->GetAll());
				self::SetVarPath($Form,$Key,$Value->Value);

				if($Value->Error){ $Error = true; }
			}
		}
		if(is_array($FormConfig["Column"])){
			foreach($FormConfig["Column"] as $Key => &$Value){
				if(!$Value["Type"]){ continue; }
				$ClassName = "ItemType_".$Value["Type"];

				if(!class_exists($ClassName,false)){ Error::View("Form","ItemTypeNotFound",array_merge(array("Class"=>$ClassName),eval($GLOBALS["ErrorEval"]))); }
				$Value = new $ClassName($Value);

				if(is_array($Value->EnableCheck)){
					self::SetVarPath($Work["Form"],$Key,null);
					switch($Value->EnableCheck["Operator"]){
						case "<":
							if(self::GetVarPath($Form,$Value->EnableCheck["Key"]) > $Value->EnableCheck["Value"]){ continue(2); }
							break;
						case ">":
							if(self::GetVarPath($Form,$Value->EnableCheck["Key"]) < $Value->EnableCheck["Value"]){ continue(2); }
							break;
						case "!=":
							if(self::GetVarPath($Form,$Value->EnableCheck["Key"]) == $Value->EnableCheck["Value"]){ continue(2); }
							break;
						case "==":
						default:
							if(self::GetVarPath($Form,$Value->EnableCheck["Key"]) != $Value->EnableCheck["Value"]){ continue(2); }
					}
				}
				if(!$Value->Key){ $Value->Key = $Key; }

				$Value->Value = preg_replace("/\r\n/","\n",self::GetVarPath($Form,$Key));
				$Value->Form =& $Form;

				$Value->Validate();
				self::SetVarPath($Work["Form"],$Key,$Value->GetAll());

				if($Value->Error){ $Error = true; }
			}
		}
	}

	static public function Form2Value($Form,$KeyName = null,$EmptyPass = false){
		if(!$KeyName){ $KeyName = "Value"; }
		if(!is_array($Form)){ return false; }
		else{
			if(isset($Form[$KeyName])){
				if($Form[$KeyName] == $Form["File"]["FileID"] || $Form["File"]["Delete"]){ $Value = $Form["File"]; }
				elseif(!($EmptyPass && $Form[$KeyName] == "")){ $Value = $Form[$KeyName]; }
			}else{
				foreach(array_keys($Form) as $Key){
					if(is_array($Form[$Key])){
						$tmpValue = self::Form2Value($Form[$Key],$KeyName,$EmptyPass);
						if(!($EmptyPass && $tmpValue == "")){ $Value[$Key] = $tmpValue; }
					}
				}
			}
		}
		return $Value;
	}

	static public function Value2Form($Form,$KeyName = "Value"){
		if(!is_array($Form)){ return false; }
		else{
			foreach(array_keys($Form) as $Key){
				if(is_array($Form[$Key]) && !Utility::is_assoc($Form[$Key])){
					if(is_numeric($Form[$Key]["FileID"]) || $Form[$Key]["Delete"]){
						$Value[$Key]["File"] = $Form[$Key];
						if(is_numeric($Form[$Key]["FileID"])){ $Value[$Key][$KeyName] = $Form[$Key]["FileID"]; }
						else{$Value[$Key][$KeyName] = -1; }
					}elseif(is_numeric($Form[$Key]["File"]["FileID"])){
//						$Value[$Key] = $Form[$Key];
						$Value[$Key][$KeyName] = 0;
					}else{ $Value[$Key] = self::Value2Form($Form[$Key],$KeyName); }
				}else{ $Value[$Key][$KeyName] = $Form[$Key]; }
			}
		}
		return $Value;
	}

	static private function GetVarPath(&$Var,$Path){
		if($Path == "" || $Path == "/"){ return $Var; }
		$KeyPath = '["'.preg_replace('/\//','"]["',$Path).'"]';
		$ParentPath = preg_Replace('/\[[^\[]*?$/','',$KeyPath);
		 eval('if(is_array($Var'."$ParentPath".')){ $Ref = $Var'."$KeyPath; }");
		 return $Ref;
	}

	static private function SetVarPath(&$Var,$Path,$Value){
		if($Path == "" || $Path == "/"){ $Var = $Value; }
		else{
			$KeyPath = '["'.preg_replace('/\//','"]["',$Path).'"]';
			 eval('$Var'."$KeyPath = ".'$Value;');
		}
	}

	static private function Path2Query($Path){
		if($Path == ""){ return false; }
		list($Query,$Path) = preg_split('/\//',$Path,2);
		if($Path != ""){ $Query .= '['.preg_replace('/\//','][',$Path).']'; }
		return $Query;
	}

	static private function FileRecive(){
		global $Config,$Object,$Work;

		if(is_array($Work["Input"]["FILES"])){
			foreach($Work["Input"]["FILES"] as $ArrayName => $Upload){
				foreach($Upload as $FieldName => $Field){
					foreach($Field as $Key => $Value){ $Form[$ArrayName][$Key][$FieldName] = $Value; }
				}
			}
		}
	}

	static public function GroupValidate(&$ConfigObject,&$Form){
		if($ConfigObject->Match){
			$Count = 0;
			if(is_array($ConfigObject->Column)){
				foreach($ConfigObject->Column as $Key){
					$Count++;
					if($Count == 1){ $ConfigObject->Value = self::GetVarPath($Form,$Key); }
					elseif($ConfigObject->Value != self::GetVarPath($Form,$Key)){
						self::SetError($ConfigObject,"Match");
						break;
					}
				}
			}
			if($Count < 2){ Error::View("Validate","ColumnNotSet",array_merge(array("Config"=>get_object_vars($ConfigObject)),eval($GLOBALS["ErrorEval"]))); }
		}

		if(strlen($ConfigObject->Glue) > 0){
			$Count = 0;
			if(is_array($ConfigObject->Column)){
				foreach($ConfigObject->Column as $Key){
					$Count++;
					if($Count == 1){ $ConfigObject->Value = self::GetVarPath($Form,$Key); }
					else{ $ConfigObject->Value .= $ConfigObject->Glue . self::GetVarPath($Form,$Key); }
				}
			}
			if($Count < 2){ Error::View("Validate","ColumnNotSet",array_merge(array("Config"=>get_object_vars($ConfigObject)),eval($GLOBALS["ErrorEval"]))); }
		}
	}

	static public function FileValidate(&$ConfigObject){
		global $Config,$Object,$Work;

		if(!$ConfigObject->File){ return false; }
		$ConfigObject->File = array();
		list($Root,$Path) = preg_split('/\//',$ConfigObject->Key,2);
		if(is_array($Work["Input"]["FILES"][$Root] )){
			foreach($Work["Input"]["FILES"][$Root] as $Key => $Value){ $ConfigObject->File[$Key] = self::GetVarPath($Value,$Path); }
		}
//		preprint($ConfigObject);
		$Value =& $ConfigObject->File;
		if(is_array($Value) && $Value["error"] === UPLOAD_ERR_OK){
			if(!is_uploaded_file($Value["tmp_name"])){ Error::View("Form","NotUploadFile",array_merge(array("Config"=>get_object_vars($ConfigObject),"Path"=>$Value["tmp_name"]),eval($GLOBALS["ErrorEval"]))); }
			else{
				$Value["ImageInfo"] = getimagesize($Value["tmp_name"]);
				if($Value["ImageInfo"]["mime"] != ""){ $Value["type"] = $Value["ImageInfo"]["mime"]; }

				$Value["type"] = strtolower($Value["type"]);
				if(is_array($ConfigObject->FileType) && !in_array($Value["type"],$ConfigObject->FileType)){
					self::SetError($ConfigObject,"UnknowFileType");
					return $Value;
				}

				$Length = filesize($Value["tmp_name"]);
				if($Length > 0){
					if(is_numeric($ConfigObject->Length)    && $Length != $ConfigObject->Length  ){ self::SetError($ConfigObject,"Length"); return $Value; }
					if(is_numeric($ConfigObject->MinLength) && $Length < $ConfigObject->MinLength){ self::SetError($ConfigObject,"MinLength"); return $Value; }
					if(is_numeric($ConfigObject->MaxLength) && $Length > $ConfigObject->MaxLength){ self::SetError($ConfigObject,"MaxLength"); return $Value; }
				}

				if(is_array($Value["ImageInfo"])){
					if(is_numeric($ConfigObject->ImageHeight) && is_numeric($ConfigObject->ImageWidth)){ Utility::ImageResize($Value["tmp_name"],$ConfigObject->ImageWidth,$ConfigObject->ImageHeight,$Value["tmp_name"]); }
				}elseif($ConfigObject->ImageOnly || (is_numeric($ConfigObject->ImageHeight) && is_numeric($ConfigObject->ImageWidth))){
					self::SetError($ConfigObject,"UnknowImage");
					return $Value;
				}
				$File = new File();

				$FileInfo = array(
					Status => File::STATUS_TEMPORARY,
					Name => $Value["name"],
					Type => $Value["type"],
					Target => $Root,
					Identifier => $Path,
				);
				$Value["FileID"] = $File->Save($Value["tmp_name"],$FileInfo);
				if(is_numeric($ConfigObject->FileStatus)){ $Value["Status"] = $ConfigObject->FileStatus; }
				$_SESSION["File"][] = 	$Value["FileID"];
//				if($ConfigObject->Value != ""){ $ConfigObject->Value = $Value["FileID"]; }
				$ConfigObject->Value = $Value["FileID"];
			}
			/*
		}elseif(is_numeric($ConfigObject->Value) && $ConfigObject->Value > 0){
				$Value["FileID"] = $ConfigObject->Value;
		}elseif(is_numeric($ConfigObject->Value) && $ConfigObject->Value == 0){
				$Value = "";
		}elseif(is_numeric($ConfigObject->Value) && $ConfigObject->Value == -1){
				$Value = "";
				$Value["Delete"] = true;
*/
//		}elseif($Value["error"] == 4){
//			$Value = "";
		}elseif($Value["name"] == "" || $Value["error"] == 4){
			if(is_numeric($ConfigObject->Value) && $ConfigObject->Value > 0){
					$Value["FileID"] = $ConfigObject->Value;
			}elseif(is_numeric($ConfigObject->Value) && $ConfigObject->Value == 0){
					$Value = "";
			}elseif(is_numeric($ConfigObject->Value) && $ConfigObject->Value == -1){
					$Value = "";
					$Value["Delete"] = true;
			}else{ $Value = ""; }

		}else{
			switch($Value["error"]){
				case UPLOAD_ERR_INI_SIZE:
					self::SetError($ConfigObject,"UPLOAD_ERR_INI_SIZE");
					break;
				case UPLOAD_ERR_FORM_SIZE:
					self::SetError($ConfigObject,"UPLOAD_ERR_FORM_SIZE");
					break;
				case UPLOAD_ERR_PARTIAL:
					self::SetError($ConfigObject,"UPLOAD_ERR_PARTIAL");
					break;
				case UPLOAD_ERR_NO_TMP_DIR:
					self::SetError($ConfigObject,"UPLOAD_ERR_NO_TMP_DIR");
					break;
				case UPLOAD_ERR_CANT_WRITE:
					self::SetError($ConfigObject,"UPLOAD_ERR_CANT_WRITE");
					break;
				case UPLOAD_ERR_EXTENSION:
					self::SetError($ConfigObject,"UPLOAD_ERR_EXTENSION");
					break;
				case UPLOAD_ERR_NO_FILE:
				default:
					if($ConfigObject->NotEmpty){ self::SetError($ConfigObject,"NotEmpty"); }
			}
			$Value = "";
		}

		return true;
	}

	static public function Validate(&$ConfigObject){
		global $Config,$Object,$Work;
		$Value =& $ConfigObject->Value;

		if($ConfigObject->File){ self::FileValidate($ConfigObject); }
		else{
			if($ConfigObject->Glue && is_array($Value)){ $Value = implode($ConfigObject->Glue,$Value); }
			if($ConfigObject->Convert && !is_array($Value)){ $Value = mb_convert_kana($Value,$ConfigObject->Convert); }
			if($ConfigObject->Replace && is_array($ConfigObject->Replace)){ $Value = preg_replace($ConfigObject->Replace["From"],$ConfigObject->Replace["To"],$Value); }
			if($ConfigObject->PassGenPath && is_numeric(self::GetVarPath($ConfigObject->Form,$ConfigObject->PassGenPath)) && self::GetVarPath($ConfigObject->Form,$ConfigObject->PassGenPath) > 0){ $Value = Utility::GenPass(self::GetVarPath($ConfigObject->Form,$ConfigObject->PassGenPath)); }
			if($ConfigObject->PassGenNum && is_numeric($ConfigObject->PassGenNum) && $ConfigObject->PassGenNum > 0){ $Value = Utility::GenPass($ConfigObject->PassGenNum); }
			if(isset($ConfigObject->DefaultValue) && is_null(self::GetVarPath($ConfigObject->Form,$ConfigObject->Key))){ $Value = $ConfigObject->DefaultValue; }

			#文字長関係
			if(is_array($Value)){ $Length = count($Value); }
			elseif($ConfigObject->Binary){ $Length = strlen($Value); }
			else{ $Length = mb_strlen($Value); }

			if($Length > 0){
				if(is_numeric($ConfigObject->Length)    && $Length != $ConfigObject->Length  ){ self::SetError($ConfigObject,"Length"); }
				if(is_numeric($ConfigObject->MinLength) && $Length < $ConfigObject->MinLength){ self::SetError($ConfigObject,"MinLength"); }
				if(is_numeric($ConfigObject->MaxLength) && $Length > $ConfigObject->MaxLength){ self::SetError($ConfigObject,"MaxLength"); }
			}
/*
			#Unique
			if($ConfigObject->Unique && $Value != ""){
				if(!self::Table){ Error::View("Form","TableNotSet",array_merge(array("Config"=>get_object_vars($ConfigObject)),eval($GLOBALS["ErrorEval"]))); }
				if(!self::Table->UniqueCheck($ConfigObject->ItemName,$Value)){ self::SetError($ConfigObject,"Unique"); }
			}
*/
			#必須入力
			if($ConfigObject->NotEmpty && $Value == ""){ self::SetError($ConfigObject,"NotEmpty"); }

			#正規表現
			if($ConfigObject->Regex != "" && $Value != "" && !preg_match('/'.$ConfigObject->Regex.'/u',$Value)){ self::SetError($ConfigObject,"Regex"); }

			#関数チェック
			if($ConfigObject->CallBack != "" && $Value != ""){
				$ConfigObject->Row = call_user_func($ConfigObject->CallBack,$Value);
				if(!$ConfigObject->Row){ self::SetError($ConfigObject,"CallBack"); }
			}

			#ダイレクト関数チェック
			if($ConfigObject->Function != "" && $Value != ""){
				$Function = $ConfigObject->Function;
				$ConfigObject->Row = $Function($Value);
				if(!$ConfigObject->Row){ self::SetError($ConfigObject,"Function"); }
			}

			#DayAfter
			if($ConfigObject->DayAfter != "" && Utility::is_datetime($Value,false)){
				if(Utility::is_datetime($ConfigObject->DayAfter,false) && date_create($ConfigObject->DayAfter) > date_create($Value)){ self::SetError($ConfigObject,"DayAfter"); }
				elseif($ConfigObject->DayAfter == "today" && date_create("today") > date_create($Value)){ self::SetError($ConfigObject,"DayAfter"); }
				elseif($ConfigObject->DayAfter == "now" && date_create("now") > date_create($Value)){ self::SetError($ConfigObject,"DayAfter"); }
			}
			#DayBefore
			if($ConfigObject->DayBefore != "" && Utility::is_datetime($Value,false)){
				if(Utility::is_datetime($ConfigObject->DayBefore,false) && date_create($ConfigObject->DayBefore) < date_create($Value)){ self::SetError($ConfigObject,"DayBefore"); }
				elseif($ConfigObject->DayBefore == "today" && date_create("today") < date_create($Value)){ self::SetError($ConfigObject,"DayBefore"); }
				elseif($ConfigObject->DayBefore == "now" && date_create("now") < date_create($Value)){ self::SetError($ConfigObject,"DayBefore"); }
			}

		}
	}

	static private function SetError(&$ConfigObject,$CheckType){
		global $Config,$Object,$Work;
		$Vars = get_object_vars($ConfigObject);
		unset($Vars["Function"]);
		$ConfigObject->Error = Utility::SetError($Work["Form"]["Error"],$ConfigObject->Key,$CheckType,Utility::VariableReplace($ConfigObject->Message[$CheckType],$Vars));
	}

}



#エラー防止のため、PHP閉じタグ未記載
#?>