<?php
/*
* 文字コード:UTF-8
* ページライブラリ用定義
*/
$GLOBALS["Frame"]["Load"]["pageconf"][] = __FILE__;

class PageConfig extends PageConfigBase{
	public $Method = array(
		Review => array(
			Form => array(
				Method => "POST",
				Column => array(
					"request" => array(
						Type => "String",
						Name => 'お問い合わせ内容',
						NotEmpty => True,
					),
					"fullname" => array(
						Type => "String",
						Name => 'お名前', // 項目名を入れると、エラーの時に表示される
						NotEmpty => True, // 必須項目
					),
					"kananame" => array(
						Type => "String", // 文字列の時に入れる値。
						Name => 'フリガナ',
						NotEmpty => True,
					),
					"tel" => array(
						Type => "String",
						Name => '電話番号',
						NotEmpty => True,
					),
					"subject" => array(
						Type => "String",
						Name => '件名',
						NotEmpty => True,
					),
					"company" => array(
						Type => "String",
						Name => '貴社名',
					),
					"post" => array(
						Type => "String",
						Name => '部署名',
					),
					"zip" => array(
						Type => "Numeric", // 半角英数の時に入れる値。エラー時に判定される。
						Name => '郵便番号',
					),
					"address1" => array(
						Type => "String",
						Name => '都道府県',
					),
					"address2" => array(
						Type => "String",
						Name => '住所1',
					),
					"address3" => array(
						Type => "String",
						Name => '住所2',
					),
					"email1" => array(
						Type => "Email",
						Name => 'メールアドレス',
						NotEmpty => True,
					),
					"email1_check" => array(
						Type => "Email",
						Name => 'メールアドレス(確認)',
					),
				),
				Group => array(
					"email1_match" => array(
						Type => 'Match',
						Column => array("email1","email1_check"),
						Name => 'メールアドレス確認',
						NotEmpty => True,
					),
				),
			),
			ErrorPage => "Input",
		),
		Result => array(
			"Attachments" => array( // メールの添付ファイル設定 ※不要な場合は項目毎削除
				"Result.eml" => array("image1"),
				"Result.admin.eml" => array("image1"),
			),
			"CSV" => array( // CSV書出し設定 ※不要な場合は項目毎削除
				ColumnName => array("TimeCode","送信日時","ラジオボタン","日付","日時","チェックボックス","文字列","メールアドレス","パスワード"),
			),
		),
	);
}

#エラー防止のため、PHP閉じタグ未記載
#?>