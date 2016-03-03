<?php
#セッション管理クラス
#文字コード:UTF-8
$GLOBALS["Frame"]["Load"]["class"][] = __FILE__;

class Session{
	protected $KeyName  = 'xxxxx'; // キーの名前
	protected $KeyHash  = 'MVCSIDKey'; // キー生成のハッシュ
	protected $KeyNum   = 20; // キーの保持数

	protected $Mode = null;     // 動作モード
	public $Session = null;  // セッション変数

	protected $OTSID = true;

/**
 * コンストラクタ
 * @param string $SessionName セッション名文字列
 */
	public function __construct($SessionName = null,$Mode = null,$OTSID = true,$CookiePath = "/",$KeyName = null){
		global $Config;
		ini_set("session.cookie_domain",$_SERVER["HTTP_HOST"]);

		if($CookiePath != ""){ ini_set("session.cookie_path",$CookiePath); }
		if($KeyName){ $this->KeyName = $KeyName; }

		if($OTSID){ $this->OTSID = true; }
		else{ $this->OTSID = false; }

		if(preg_match('/\w/',$SessionName)){ session_name($SessionName); }
		else{ $SessionName = session_name(); }

		if(preg_match('/form/i',$Mode)){ $this->Mode = "form"; }

#SID受け渡し方法
		if(class_exists("Net_UserAgent_Mobile")){ $UID = Net_UserAgent_Mobile::factory()->getUID(); }

		if($UID){
			session_id(preg_replace('/[^a-zA-Z0-9\,\-]/','-',$UID));
			$this->OTSID = false;
		}elseif(is_null($this->Mode) && ini_get('session.use_trans_sid')){
			$this->Mode = 'trans_sid';
			if(isset($_GET[$this->KeyName])){ $OTKey = $_GET[$this->KeyName]; }
		}elseif(is_null($this->Mode) && ini_get('session.use_cookies')){
			$this->Mode = 'cookie';
			if(isset($_COOKIE[$this->KeyName])){ $OTKey = $_COOKIE[$this->KeyName]; }
		}else{
			$this->Mode = 'form';
			if(isset($_GET[$this->KeyName])){ $OTKey = $_GET[$this->KeyName]; }
			elseif(isset($_POST[$this->KeyName])){ $OTKey = $_POST[$this->KeyName]; }
		}

		session_start();
		$this->Session = &$_SESSION;
		if(!$this->Session["OTKeys"]){ $this->Session["OTKeys"] = array(); }

		if($this->OTSID){
	#セッションの有効性チェック
			if(count($this->Session["OTKeys"]) > 0){
				$OTSIDError = true;

				if(is_array($OTKey)){
					foreach($OTKey as $Value){ if($Value != "" && in_array($Value,$this->Session["OTKeys"])){ $OTSIDError = false; } }
				}elseif($OTKey != "" && in_array($OTKey,$this->Session["OTKeys"])){ $OTSIDError = false; }

				if($OTSIDError){ session_destroy(); unset($this->Session); return false; }
			}
			$OTKeyOld = $OTKey;
	#One Time SIDの生成
			if($OTKey == ""){ $OTKey = $this->GenSID($this->KeyHash); }

//			$OTKey = $this->GenSID($this->KeyHash);
			array_unshift($this->Session["OTKeys"],$OTKey);
			$this->Session["OTKeys"] = array_slice($this->Session["OTKeys"],0,$this->KeyNum);

	#$_sessionの準備
			if($this->Mode == 'cookie'){
				$SessionCookiParam = session_get_cookie_params();

				if(is_numeric($Config["Session"]["LifeTime"]) && $Config["Session"]["LifeTime"] > 0){ $OTKeyLT = time() + ( $Config["Session"]["LifeTime"] * 60 ); }
				elseif($Config["Session"]["LifeTime"] == 0){ $OTKeyLT = 0; }
				elseif($SessionCookiParam["lifetime"] === 0){ $OTKeyLT = 0; }
				else{ $OTKeyLT = time() + $SessionCookiParam["lifetime"]; }
				if($OTKeyLT == 0){$OTKeyLT = null; }
/*
				if(is_array($OTKeyOld)){ foreach($OTKeyOld as $Key => $Value){ if($Key!=getmypid()){ setcookie($this->KeyName."[$Key]","",$OTKeyLT); } } }
				setcookie($this->KeyName."[".getmypid()."]",$OTKey,$OTKeyLT);
*/
				setcookie($this->KeyName,$OTKey,$OTKeyLT,$CookiePath);

			}
		}

		$_SESSION["Visit"]["Count"]++;
		$_SESSION["Visit"]["LastTime"] = $_SESSION["Visit"]["NowTime"];
		$_SESSION["Visit"]["NowTime"] = time();

		if($this->Mode == 'form'){ $this->Session[$SessionName] = $SID; }
	}

/**
 * SIDの生成
 * @param string $Key 生成用キー
 * @return string 生成したSID
 **/
	protected function GenSID($Key){
		return sha1($_SERVER['HTTP_HOST'].$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR'].$Key.uniqid(microtime()));
	}

/**
 * $Modeの取得
 * @return string $Mode
 **/

	public function getMode(){
		return $this->Mode;
	}

/**
 * 保護セッションへ保存
 * @param string $Name 識別子 mix $Data データ
 * @return string $Key キー
 **/

	public function Save($Name,$Data){
		$Key = md5(serialize($Data));
		$this->Session["Save"][$Name] = array(
			"Key"  => $Key,
			"Data" => $Data,
		);
		return $Key;
	}

/**
 * 保護セッション読出
 * @param string $Name 識別子,string $Key キー,$Return エラー時にfalseを返却
 * @return mix $Data データ
 **/

	public function Load($Name,$Key,$Return = false){
		global $Config,$Object,$Work;
		if($this->Session["Save"][$Name]["Key"] == $Key){ return $this->Session["Save"][$Name]["Data"]; }
		else{
			if($Return){ return false; }
			else{ Error::View("Session","WrongTransition",array_merge(array("Session"=>$this->Session["Save"][$Name]["Key"],"POST"=>$Key,"Load"),eval($GLOBALS["ErrorEval"]))); }
		}
	}

/**
 * 保護セッション消去
 * @param string $Name 識別子,string $Key キー,$Return エラー時にfalseを返却
 **/

	public function Clear($Name,$Key,$Return = false){
		global $Config,$Object,$Work;
		if($this->Session["Save"][$Name]["Key"] == $Key){
			unset($this->Session["Save"][$Name]);
			return true;
		}else{
			if($Return){ return false; }
			else{ Error::View("Session","WrongTransition",array_merge(array("Session"=>$this->Session["Save"][$Name]["Key"],"POST"=>$Key,"Clear"),eval($GLOBALS["ErrorEval"]))); }
		}
	}

#############################
# form 埋め込みセッション用 #※ 未実装
#############################

/**
 * 暗号化セッションデータの復元(form用)
 * @param string $EncryptData 暗号化データ
 * @return array 復元したセッションデータ
 **/
	protected function DecryptSession($EncryptData){
		return unserialize(urldecode($EncryptData));
	}

/**
 * セッションデータの暗号化(form用)
 * @param array $SessionData セッションデータ
 * @return string 暗号化したセッションデータ
 **/
	protected function EncryptSession($SessionData){
		return urlencode(serialize($SessionData));
	}
}

#エラー防止のため、PHP閉じタグ未記載
#?>