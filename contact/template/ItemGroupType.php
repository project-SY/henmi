<?php
/*
* 文字コード:UTF-8
* ページライブラリ用タイプ定義
*/

$GLOBALS["Frame"]["Load"]["pageconf"][] = __FILE__;


class ItemGroupType_Default extends ItemGroupTypeBase{
	public $TypeName = 'フォーム項目';	# チェックグループ名
	public $Kind     = NULL;			# Utility or Check
	public $Priority = 10;				# 小さいものから順番(Utility->Check)
	public $Glue     = NULL;			# カラム内項目を結合文字列で結合
	public $Match    = False;			# カラム内項目が全て一致
	public $Message  = array(			# メッセージ設定
		Match  => '${Name}が一致しません。',
	);
	public $Column = array();
}

class ItemGroupType_Match extends ItemGroupType_Default{
	public $TypeName = '一致';
	public $Match = True;
	public $Kind = Check;
}

class ItemGroupType_Join extends ItemGroupType_Default{
	public $TypeName = '結合';
	public $Glue = NULL;
	public $Kind = Utility;
}


#エラー防止のため、PHP閉じタグ未記載
#?>