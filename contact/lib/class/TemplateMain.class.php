<?php
# 文字コード:UTF-8
# テンプレート処理クラス
$GLOBALS["Frame"]["Load"]["class"][] = __FILE__;

/*
TemplateMain Version 1.1

-----  Class diagram ------------------

+----------+     (use)
| Template |<----------  Client
+----------+
      < >
       | (use to Parse documents)
       V
+----------------+
| StandardParser |
+----------------+
       |
       |
      - -
       V
+----------------+
| TemplateParser |
+----------------+
       < >
        |
        | (use to Parse each Tags)
        |
        V
   +----------+     +-----------+         +--------------+
   | TagBasis |<|---| SimpleTag |---------| ConcreteTags |
   +----------+     | MultiTag  |         +---+----------+
                    +-----------+             | tag_val,tag_each,,,,etc.
                                              |
                       +-----------------+    |
                       | <<ShouldClose>> |<|--+
                       +-----------------+

*/


/*
* Tag definition
*/

/* the interface of Tags used in pair form
    like {each ***} ... {/each}
 */
interface ShouldClose{}

/* the origine class of all Tags */
abstract class TagBasis{
	protected $MatchRegexp;
	protected $ToString;
	protected $CloseString;

	public function Parse($Str,$MultiLabels){
//print "<textarea>";
		while(preg_match($this->MatchRegexp,$Str,$Match)){
			$m = preg_replace('/^\$/','',$Match[1],1,$Count);

			if($Count > 0){ $Index = "\$val".$this->GetIndex($m,$MultiLabels); }
			else{ $Index = "'$m'"; }
			$m = "'$m'";

			if(is_callable(array($this,"Processing"))){ $this->Processing($Match); }

//print "from:".$Match[0]."\n";
//print "to:".sprintf($this->ToString,$Index,$m)."\n";
//print "to-str:".$this->ToString."\n";
//print "to-Index:".$Index."\n";
//print "to-m2:".$m2."\n--------------------------\n";

			$Str = str_replace(
				$Match[0],
				sprintf($this->ToString,$Index,$m),
				$Str
			);

		}
//print "</textarea><br>";
		$Str = $this->CloseTag($Str);
		return $Str;
	}

	protected function CloseTag($Str){
		if($this Instanceof ShouldClose){ $Str = str_replace($this->CloseString,"<?php } ?>",$Str); }
		return $Str;
	}

	abstract protected function GetIndex($m,$MultiLabels);
}

class SimpleTagArray extends SimpleTag{
	public function Parse($Str,$MultiLabels){
//print "<textarea>";
		while(preg_match($this->MatchRegexp,$Str,$Matches)){
//print_r($Matches);
			$Replace = array();

			foreach($Matches as $Match){
				$Match = preg_replace('/^\$/','',$Match,1,$Count);
				if($Count > 0){ $Match = "\$val".$this->GetIndex($Match,$MultiLabels); }
				else{ $Match = '"' . $Match . '"'; }
				array_push($Replace,$Match);
			}

			if(is_callable(array($this,"Processing"))){ $this->Processing($Matches); }

//print "from:".$Matches[0]."\n";
//print "to:".vsprintf($this->ToString,array_merge(array_slice($Replace,1),array_slice($Matches,1)))."\n";
//print "to-str:".$this->ToString."\n\n";

			$Str = str_replace(
				$Matches[0],
				vsprintf($this->ToString,array_merge(array_slice($Replace,1),array_slice($Matches,1))),
				$Str
			);

		}
//print "</textarea><br>";
		$Str = $this->CloseTag($Str);
		return $Str;
	}
}

/* the super class of Tags which handle non-array data  */
class SimpleTag extends TagBasis{
	protected function GetIndex($m,$MultiLabels){
		$ar  = split("/",$m);
		$Index = "";
		$rui = array();
		$mattan = 0;


		foreach($ar as $x){
			$mattan++;
			array_push($rui,$x);

			if(preg_match('/^\$/',$x)){ $Index .= "[$x]"; }
			else{ $Index .= "[\"$x\"]"; }

			if(count($ar) > $mattan && in_array(join("/",$rui),$MultiLabels)){ $Index .= "[\$cnt[\"".join("/",$rui)."\"]]"; }
		}
		return $Index;
	}
}

/* the super class of Tags which handle array structure like {each *}  */
class MultiTag extends TagBasis{
	public function GetLabelArray($Str){
		$Ans = array();
		preg_match_all($this->MatchRegexp,$Str,$RegAns,PREG_SET_ORDER);
		foreach($RegAns as $x){ $Ans[] = preg_replace('/^\$/','',$x[1]); }
		return $Ans;
	}

	protected function GetIndex($m,$MultiLabels){
		$ar = split("/",$m);
		$Index = "";
		$rui = array();
		$mattan = 0;

		foreach($ar as $x){
			array_push($rui,$x);

			if(preg_match('/^\$/',$x)){ $Index .= "[$x]"; }
			else{ $Index .= "[\"$x\"]"; }

			if($mattan != count($ar) - 1 && in_array(join("/",$rui),$MultiLabels)){ $Index .= "[\$cnt[\"".join("/",$rui)."\"]]"; }
			$mattan++;
		}
		return $Index;
	}
}

/*
*  Parser classes
*/

/* main definition of Parser */
class TemplateParser{
	private $Tags = array(
		"Simple" => array(),
		"Multi"  => array(),
	);

	function Add(TagBasis $Tag){
		if($Tag instanceof SimpleTag){ $this->Tags["Simple"][] = $Tag; }
		elseif($Tag instanceof MultiTag){ $this->Tags["Multi"][] = $Tag; }
		else{ throw new Exception("Tag class is not well defined."); }

		return $this;
	}

	function Parse($Str){

		reset($this->Tags["Multi"]);
		$MultiLabels = array();
		foreach($this->Tags["Multi"] as $x){ $MultiLabels = array_merge($MultiLabels,$x->GetLabelArray($Str)); }

		reset($this->Tags["Multi"]);
		foreach($this->Tags["Multi"] as $x){ $Str = $x->Parse($Str,$MultiLabels); }

		reset($this->Tags["Simple"]);
		foreach($this->Tags["Simple"] as $x){ $Str = $x->Parse($Str,$MultiLabels); }

		return $Str;
	}
}


////////////////////////////////////////////////////


/*
*   Standard tag classes
*   these Tags are defined as previous version of Template
*/

class tag_print extends SimpleTag{

	protected $MatchRegexp = '/\{print ([^\{\}\:]+)\:?([^\{\}]+)?\}/i';
	protected $ToString    = "<?php print %s; ?>";
	protected function Processing($Maches){
		if(!$this->ToStringBK){ $this->ToStringBK = $this->ToString; }
		else{ $this->ToString = $this->ToStringBK; }
		$TargetStr = "%s";
		if(preg_match('/DUMP/i',$Maches[2])){ $TargetStr = "print_r($TargetStr,true)"; }
		if(preg_match('/NUMBER/i',$Maches[2])){ $TargetStr = "number_format($TargetStr)"; }
		if(preg_match('/FORMAT\((.+)\)/i',$Maches[2],$MatchesSub)){ $TargetStr = "sprintf('".preg_replace('/%/','%%',$MatchesSub[1])."',$TargetStr)"; }
		if(preg_match('/REPLACE\((.+),(.*)\)/i',$Maches[2],$MatchesSub)){ $TargetStr = "preg_replace('$MatchesSub[1]','$MatchesSub[2]',$TargetStr)"; }
		if(preg_match('/HTML/i',$Maches[2])){ $TargetStr = "htmlspecialchars($TargetStr,ENT_QUOTES)"; }
		if(preg_match('/BR/i',$Maches[2])){ $TargetStr = "nl2br($TargetStr)"; }
		if(preg_match('/JSON/i',$Maches[2])){ $TargetStr = 'Utility::JSONEscape('.$TargetStr.')'; }
		if(preg_match('/EMOJI/i',$Maches[2])){
			if(!is_object($GLOBALS["EmojiObject"])){ $GLOBALS["EmojiObject"] = new Emoji(); }
			$TargetStr = "\$GLOBALS['EmojiObject']->Convert($TargetStr)";
		}
		if(preg_match('/LINK/i',$Maches[2])){ $TargetStr = "Utility::SetHref($TargetStr)"; }
//		if(preg_match('/EVAL/i',$Maches[2])){ $this->ToString = eval(sprintf($this->ToString,$TargetStr)); }
//		else{ $this->ToString = sprintf($this->ToString,$TargetStr); }
		if(preg_match('/EVAL/i',$Maches[2])){ $this->ToString = $TargetStr; }
		else{ $this->ToString = sprintf($this->ToString,$TargetStr); }
	}
}

class tag_if extends SimpleTagArray implements ShouldClose{
	protected $MatchRegexp = '/<!--\{if ([^\=\!\>\<]+)(==|!=|>=|<=|>|<)([^\}]+)\}-->/i';
	protected $ToString    = "<?php if(%1\$s %5\$s %3\$s){ ?>";
	protected $CloseString = "<!--{/if}-->";
}

class tag_else extends SimpleTag{
	protected $MatchRegexp = '/<!--\{else\}-->/i';
	protected $ToString    = "<?php }else{ ?>";
}

class tag_comment extends SimpleTag implements ShouldClose{
	protected $MatchRegexp = '/<!--\{comment\}-->/i';
	protected $ToString    = "<?php if(FALSE){ ?>";
	protected $CloseString = "<!--{/comment}-->";
}

class tag_def extends SimpleTag implements ShouldClose{
	protected $MatchRegexp = '/<!--\{def ([^\}]+)\}-->/i';
	protected $ToString    = "<?php if((gettype(%1\$s) != 'array' && (%1\$s != \"\" || %1\$s === 0)) || (gettype(%1\$s) == 'array' && count(%1\$s) > 0)){ ?>";
	protected $CloseString = "<!--{/def}-->";
}

class tag_ndef extends SimpleTag implements ShouldClose{
	protected $MatchRegexp = '/<!--\{ndef ([^\}]+)\}-->/i';
	protected $ToString    = "<?php if(!((gettype(%1\$s) != 'array' && (%1\$s != \"\" || %1\$s === 0)) || (gettype(%1\$s) == 'array' && count(%1\$s) > 0))){ ?>";
	protected $CloseString = "<!--{/ndef}-->";
}

class tag_loop extends MultiTag implements ShouldClose{
	protected $MatchRegexp = '/<!--\{loop ([^\}]+)\}-->/i';
	protected $ToString    = "<?php for(\$cnt[%2\$s]=0; \$cnt[%2\$s]<count(%1\$s); \$cnt[%2\$s]++){ %1\$s[\$cnt[%2\$s]]['TemplateLoopCount'] = \$cnt[%2\$s] + 1; %1\$s[\$cnt[%2\$s]]['Before'] = %1\$s[\$cnt[%2\$s] - 1]; %1\$s[\$cnt[%2\$s]]['After'] = %1\$s[\$cnt[%2\$s] + 1]; ?>";
	protected $CloseString = "<!--{/loop}-->";
}

class tag_find extends SimpleTagArray implements ShouldClose{
	protected $MatchRegexp = '/<!--\{find ([^\,]+),([^\}]+)\}-->/i';
	protected $ToString    = "<?php if((!is_array(%2\$s) && preg_match(%1\$s,%2\$s)) || (is_array(%2\$s) && in_array(%1\$s,%2\$s))){ ?>";
	protected $CloseString = "<!--{/find}-->";
}

class tag_match extends SimpleTagArray implements ShouldClose{
	protected $MatchRegexp = '/<!--\{match ([^\,]+),([^\}]+)\}-->/i';
	protected $ToString    = "<?php if(preg_match(%1\$s,%2\$s)){ ?>";
	protected $CloseString = "<!--{/match}-->";
}

class tag_in extends SimpleTagArray implements ShouldClose{
	protected $MatchRegexp = '/<!--\{in ([^\,]+),([^\}]+)\}-->/i';
	protected $ToString    = "<?php if((is_array(%2\$s) && in_array(%1\$s,%2\$s)) || (!is_array(%2\$s) && stripos(%2\$s,%1\$s) !== false)){ ?>";
	protected $CloseString = "<!--{/in}-->";
}


/*
*   StandardParser
*   Parser defined with above Tags.
*   behave as previous Template
*/
class StandardParser extends TemplateParser{
	function StandardParser(){
		$this->Add(new tag_print());
		$this->Add(new tag_if());

		$this->Add(new tag_def());
		$this->Add(new tag_ndef());

		$this->Add(new tag_loop());

		$this->Add(new tag_else());
		$this->Add(new tag_comment());
		$this->Add(new tag_match());
		$this->Add(new tag_in());

	}
}

/*
*  Template
*  the APIs defined after the manner of Template for PHP4
*  tmp file generation has not been implemented yet.(2003-07-08)
*/

class TemplateMain{
	private $Parser;
	static private $Instance;

	private function TemplateMain(){
		$this->Parser = new StandardParser();
	}

	static public function GetInstance(){
		if(!TemplateMain::$Instance){ TemplateMain::$Instance = new TemplateMain(); }
		return TemplateMain::$Instance;
	}

	static public function Parse($Str){
		return TemplateMain::GetInstance()->Parser->Parse($Str);
	}

	static function FilePrint($FilePath,$Data,$Encoding = null){
		if($Encoding){ print mb_convert_encoding(TemplateMain::FileReturn($FilePath,$Data),$Encoding); }
		else{ print TemplateMain::FileReturn($FilePath,$Data); }
		return true;
	}

	static function FileReturn($FilePath,$Data){
		$TemplateData = fread(fopen($FilePath,"rb"),filesize($FilePath));
		return TemplateMain::DataReturn($TemplateData,$Data);
	}

	static function DataPrint($TemplateData,$Data,$Encoding = null){
		if($Encoding){ print mb_convert_encoding(TemplateMain::DataReturn($TemplateData,$Data),$Encoding); }
		else{ print TemplateMain::DataReturn($TemplateData,$Data); }
		return true;
	}

	static function DataReturn($TemplateData,$Data){
		$val = $Data;
		$Code = TemplateMain::Parse($TemplateData);

		ob_start();
		eval('?>'.$Code);
		$ReturnStr = ob_get_contents();
		ob_end_clean();

		if($ReturnStr === false){ Error::View("Template","SyntaxError",array("TemplateFile"=>$FilePath,"TemplateCode"=>$Code)); }

//PrePrint(htmlspecialchars($Code));
//PrePrint($Code);
//Error::View("Template","SyntaxError",array("TemplateFile"=>$FilePath,"TemplateCode"=>$Code));
		return $ReturnStr;
	}



}

#エラー防止のため、PHP閉じタグ未記載
#?>