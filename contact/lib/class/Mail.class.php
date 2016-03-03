<?php
# メールクラス
#文字コード:UTF-8
$GLOBALS["Frame"]["Load"]["class"][] = __FILE__;

class Mail{
	static public function Send($MailData,$Attachments = null,$Encoding = "UTF-8"){
		global $Config,$Object,$Work;

		if($Config["System"]["AdminEmail"] != ""){ $MailFrom = " -f ".$Config["System"]["AdminEmail"]; }

		if(!is_null($Attachments)){
			foreach($Attachments as &$Attachment){
				if($Attachment["Name"] != ""){
					if($Attachment["Path"] != "" && is_readable($Attachment["Path"])){ $AttachmentNum++; }
					elseif(isset($Attachment["Body"])){ $AttachmentNum++; }
					else{ unset($Attachment); }
				}else{ unset($Attachment); }
			}
			$Boundary = "------_".uniqid($PID)."_MULTIPART_MIXED_";
		}

		$MailLines = preg_split("/\n/",$MailData);
		$Header = True;

		if(($MAIL = popen($Config["Program"]["Sendmail"]." -t$MailFrom", "w")) === false){ return false; }
		foreach($MailLines as $Line){
			rtrim($Line);
			if($Header){
//				$Line = preg_match('/^Content-Type:(.*)charset="(.*)"/i','Content-Type:'.$1.'charset="'.$Encoding.'"',$Line);

				if($Line == ""){
					$Header = False;
					if($AttachmentNum > 0){
						fwrite($MAIL,"Content-Type: multipart/mixed; boundary=\"$Boundary\"\n\n");
						fwrite($MAIL,"--$Boundary\n");
						foreach($BodyHeaders as $BodyHeader){ fwrite($MAIL,"$BodyHeader\n"); }
						fwrite($MAIL,"\n");
					}
				}elseif(preg_match("/^Subject:/i",$Line) || preg_match("/^From:/i",$Line) || preg_match("/^To:/i",$Line) || preg_match("/^Cc:/i",$Line) || preg_match("/^Bcc:/i",$Line)){
						$Line = self::mb_encode_mimeheader($Line,$Encoding);
				}elseif($AttachmentNum > 0 && (preg_match("/^Content-Type:/i",$Line) || preg_match("/^Content-Transfer-Encoding:/i",$Line))){
					$BodyHeaders[] = $Line;
					continue;
				}
			}else{ $Line = mb_convert_encoding($Line,$Encoding,mb_internal_encoding()); }
			fwrite($MAIL,"$Line\n");
		}

		if($AttachmentNum > 0){
			foreach($Attachments as $Attachment){
				if($Attachment["Name"] != ""){
					$AttachmentName = self::mb_encode_mimeheader($Attachment["Name"]);
					fwrite($MAIL,"\n--$Boundary\n");
					fwrite($MAIL,"Content-Type: application/octet-stream;\n");
					fwrite($MAIL," name=\"$AttachmentName\"\n");
					fwrite($MAIL,"Content-Disposition: attachment;\n");
					fwrite($MAIL," filename=\"$AttachmentName\"\n");
					fwrite($MAIL,"Content-Transfer-Encoding: base64\n\n");
					if($Attachment["Path"] != "" && is_readable($Attachment["Path"])){ fwrite($MAIL,base64_encode(file_get_contents($Attachment["Path"]))); }
					elseif($Attachment["Body"] != ""){ fwrite($MAIL,base64_encode($Attachment["Body"])); }
				}
			}
			fwrite($MAIL,"\n--$Boundary--\n");
		}

		if(pclose($MAIL) != 0){ return false; }
		return true;
	}

	#mb_encode_mimeheaderのバグ修正版
	static public function mb_encode_mimeheader($String,$Encoding = "UTF-8"){
		$StringArray = array();
		$Pos = 0;
		$Row = 0;
		$Mode = 0;

		$String = mb_convert_encoding($String,$Encoding,mb_internal_encoding());
		$DefEncodeing = mb_internal_encoding();
		mb_internal_encoding($Encoding);

		while($Pos < mb_strlen($String)){
			$Word = mb_strimwidth($String,$Pos,1);
			if($Word != "0" && !$Word){ $Word = mb_strimwidth($String, $Pos, 2); }
			if(mb_ereg_match("[ -~]",$Word)){ // ascii
				 if($Mode != 1){
					$Row++;
					$Mode = 1;
					$StringArray[$Row] = NULL;
				}
			}else{ // multibyte
				 if($Mode != 2){
					$Row++;
					$Mode = 2;
					$StringArray[$Row] = NULL;
				}
			}
			$StringArray[$Row] .= $Word;
			$Pos++;
		}

		foreach($StringArray as $Key => $Value){
			$Value = mb_convert_encoding($Value,$Encoding);
			$Values = mb_split("_",$Value);
			for($i = 0; $i < count($Values); $i++){ $Values[$i] = mb_encode_mimeheader($Values[$i],$Encoding); }
			$StringArray[$Key] = implode("_",$Values);
		}

		mb_internal_encoding($DefEncodeing);
		return implode("",$StringArray);
	}
}
#エラー防止のため、PHP閉じタグ未記載
#?>