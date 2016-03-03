<?php
/*
* 文字コード:UTF-8
* ページライブラリ用タイプ定義
*/

$GLOBALS["Frame"]["Load"]["pageconf"][] = __FILE__;

class ItemType_Default extends ItemTypeBase{
	public $TypeName     = 'フォーム項目';		# チェックグループ名
	public $DefaultValue = Null;				#
	public $Convert      = 'KVa';				# 全角半角変換等(KVa=半角カナ->全角、全角英数->半角)
//	public $Unique       = False;				# 重複チェック
//	public $Encrypt      = Null;				# 暗号化保存形式(md5,sha1,b64,plain)
	public $PassGen      = Null;				# パスワード生成フラグ付加フィールド名(フィールドには生成文字数設定)
	public $File         = False;				# ファイルアップロード
	public $Regex        = Null;				# 正規表現チェック
	public $CallBack     = Null;				# コールバック関数チェック
	public $NotEmpty     = False;				# 必須入力
	public $MinLength    = 0;					# 最小文字数(必須入力がない場合は、未記入はOK)
	public $MaxLength    = 10240;				# 最大文字数
	public $Length       = Null;				# 固定文字数
	public $Binary       = false;				# バイトでカウント
	public $EditCheck    = Null;				# 編集
	public $MinValue     = Null;				# 最小値
	public $MaxValue     = Null;				# 最大値
	public $DayAfter     = Null;				# 最小日(日時形式でかつ、この日時以降である) "2012/01/01 01:01:01" or "2012/01/01" or "today" or"now"
	public $DayBefore    = Null;				# 最大日(日時形式でかつ、この日時以前である) "2012/01/01 01:01:01" or "2012/01/01" or "today" or"now"
	public $SQLOperator  = "=";					# SQL検索用演算子
	public $EnableCheck  = Null;				# 項目を有効にする条件
	public $Glue         = Null;				# 値が配列の場合はGlueで結合する。
	public $SQLDefault   = array(
		Operator       => "=",
		Column         => "",
		ColumnFunction => "",
		ValueFunction  => "",
		Separate       => "",
	);
/*
	public $EnableCheck  = array(				# 項目を有効にする条件
		ItemName => "",
		Value    => "",
	);
*/

	public $MessageDefault = array(			# メッセージ設定
//		ReadOnly  => '${Name}は入力できません。',
//		Protect   => '${Name}は入力できません。',
		Regex     => '${Name}は${TypeName}で入力して下さい。',
		CallBack  => '${Name}は${TypeName}で入力して下さい。',
		NotEmpty  => '${Name}を入力して下さい。',
		MinLength => '${Name}は${MinLength}文字以上で入力して下さい。',
		MaxLength => '${Name}は${MaxLength}文字以下で入力して下さい。',
		Length    => '${Name}は${Length}文字で入力して下さい。',
		"Function"=> '${Name}を確認してください。',
//		Unique    => '${Name}はすでに登録されています。',
//		OtherUnique => '${Name}は他の項目で登録されています。',
//		AuthFail  => '認証に失敗しました。',
		# 以下はFile=Trueの時使用
		"UPLOAD_ERR_INI_SIZE"   => '${Name}にアップロードされたファイルは、システムで設定されたサイズを超えています。',
		"UPLOAD_ERR_FORM_SIZE"  => '${Name}にアップロードされたファイルは、フォームで指定されたサイズを超えています。',
		"UPLOAD_ERR_PARTIAL"    => '${Name}にアップロードされたファイルは一部のみしかアップロードされていません。',
		"UPLOAD_ERR_EXTENSION"  => '${Name}にアップロードされたファイルは、拡張モジュールによって停止されました。',
	);
}

class ItemType_String extends ItemType_Default{
	public $TypeName    = '文字列';
	public $SQL   = array(
		Operator => "LIKE",
		Separate => " ",
	);
}

class ItemType_Word extends ItemType_Default{
	public $TypeName = '半角英数';
	public $Regex    = '^\w*$';
	public $Message  = array(
		Request    => '半角英数で入力して下さい。',
		Indication => 'が指定された入力形式ではありません。',
	);
}

class ItemType_Numeric extends ItemType_Default{
	public $TypeName = '半角数字';
	public $Regex    = '^\-?\d*\.?\d*$';
	public $Message  = array(
		Request    => '半角数字で入力して下さい。',
		Indication => 'が指定された入力形式ではありません。',
	);
}

class ItemType_Date extends ItemType_Default{
	public $TypeName = '日付形式(YYYY/MM/DD)';
	public $CallBack = 'Utility::is_date';
	public $Message  = array(
		Request    => '日付を「YYYY/MM/DD」で入力して下さい。',
		Indication => 'が指定された入力形式ではありません。',
		DayAfter   => '${DayAfter}日以降の日付を入力して下さい。',// "2012/01/01" or "today"
		DayBefore  => '${DayBefore}日以前の日付を入力して下さい。',
	);
}

class ItemType_DateTime extends ItemType_Default{
	public $TypeName = '日時形式(YYYY/MM/DD HH:MM:SS)';
	public $CallBack = 'Utility::is_datetime';
	public $Message  = array(
		Request    => '日時を「YYYY/MM/DD HH:MM:SS」で入力して下さい。',
		Indication => 'が指定された入力形式ではありません。',
	);
}

class ItemType_Time extends ItemType_Default{
	public $TypeName = '時間形式(HH:MM:SS)';
	public $CallBack = 'Utility::is_time';
	public $Message  = array(
		Request    => '日時を「HH:MM:SS」で入力して下さい。',
		Indication => 'が指定された入力形式ではありません。',
	);
}

class ItemType_Email extends ItemType_Default{
	public $TypeName = 'Email形式';
	public $CallBack = 'Utility::is_email';
	public $Message  = array(
		Request    => 'メールアドレスを入力して下さい。',
		Indication => 'が指定された入力形式ではありません。',
	);
}

class ItemType_Serialize extends ItemType_Default{
	public $TypeName = '配列形式';
	public $Message  = array(
		NotEmpty  => '${Name}を選択して下さい。',
		MinLength => '${Name}は${MinLength}箇所以上選択して下さい。',
		MaxLength => '${Name}は${MaxLength}箇所以下選択して下さい。',
		Length    => '${Name}は${Length}箇所選択して下さい。',
	);
	public $SQL   = array(
		Operator => NULL,
	);
}

class ItemType_Password extends ItemType_Default{
	public $TypeName  = 'パスワード形式';
	public $SQL   = array(
		Operator => NULL,
	);
}

class ItemType_Point extends ItemType_Default{
	public $TypeName    = '緯度経度';
	public $Regex       = '^\( *[\d\.]+ *\, *[\d\.]+ *\)$';
	public $SQL   = array(
		Operator => NULL,
	);
}

class ItemType_PointBox extends ItemType_Default{
	public $TypeName    = '緯度経度Box';
	public $Regex       = '^\( *\( *[\d\.]+ *\, *[\d\.]+ *\) *\, *\( *[\d\.]+ *\, *[\d\.]+ *\) *\)$';
	public $SQL   = array(
		Operator => "<@",
		Column   => "",
		ColumnFunction => "",
		ValueFunction => "BOX(%s)",
	);
}

class ItemType_PointCircle extends ItemType_Default{
	public $TypeName    = '緯度経度Circle';
	public $Regex       = '^\( *\( *[\d\.]+ *\, *[\d\.]+ *\) *\, *[\d\.]+ *\)$';
	public $SQL   = array(
		Operator => "<@",
		Column   => "",
		ColumnFunction => "",
		ValueFunction => "CIRCLE(%s)",
	);
}

class ItemType_File extends ItemType_Default{
	public $TypeName = 'ファイル';
	public $File        = True;
	public $MaxLength   = 1048576;
	public $Message     = array(
		NotEmpty => '${Name}を選択して下さい。',
		MaxLength => '${Name}は${MaxLength}バイト以下で選択して下さい。',
	);
	public $SQL   = array(
		Operator => NULL,
	);
}

class ItemType_Image extends ItemType_File{
	public $TypeName = '画像';
	public $ImageOnly = true;
	public $ImageWidth  = 640;
	public $ImageHeight = 480;
	public $Message     = array(
		UnknowImage => '${Name}は認識出来ない画像形式です。',
		MaxLength => '${Name}は${MaxLength}バイト以下で選択して下さい。',
	);
}

class ItemType_ImageJPEG extends ItemType_File{
	public $TypeName = 'JPEG画像';
	public $ImageOnly = true;
	public $ImageWidth  = 640;
	public $ImageHeight = 480;
	public $FileType = array("image/jpeg");
	public $Message     = array(
			UnknowFileType => '${Name}はJPEG形式ではありません。',
	);
}

class ItemType_SUBQueryNumeric extends ItemType_Default{
	public $TypeName    = 'サブクエリー';
	public $SQLEscape = false;
	public $Regex = '^\-?\d*\.?\d*$';
	public $SQL   = array(
			Operator => "IN",
			Column   => "",
			ColumnFunction => "",
			ValueFunction => "",
			Escape => false,
	);
}


class ItemType_SUBQueryString extends ItemType_Default{
	public $TypeName    = 'サブクエリー';
	public $SQLEscape = false;
	public $SQL   = array(
			Operator => "IN",
			Column   => "",
			ColumnFunction => "",
			ValueFunction => "",
			Escape => true,
	);
}
#エラー防止のため、PHP閉じタグ未記載
#?>