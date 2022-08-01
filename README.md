# NEO NENG 通称 N2
NENGシステムを大幅リニューアルし、今後の生活を豊かにするツール。


## 拡張プラグイン
https://github.com/steamships/n2-develop


## コーディングルール

### プラグイン拡張用のhookについて
後からプラグインで変更できるようなメソッド全てにhookを設定する。

各クラスファイルのそれぞれのメソッド内の最後にhookを定義する場合のhook名を統一する。
 
hook名は全て小文字で
```
クラス名_メソッド名
```
とし、可読性を高めるために変数や定数は使用禁止。
 
メソッドの最後に
```
return apply_filters('クラス名_メソッド名', $変数);
```
とする場合に適用
 
デフォルトではない特殊なhookを定義する場合はこのルールは適応外
  
### フロントエンド（JS,CSS）まわりについて
`/src/ts/`以下に管理画面を`admin.ts`、表画面を`front.ts`として用意している。
これらのファイル内で別ディレクトリのｔｓファイルやｓｃｓｓファイルをimportすることで`/dist/`に`admin.js`、`admin.css`、`front.js`、`front.css`としてコンパイルされる。
それぞれ`/inc/class-n2-enqueuescript.php`内のhookにて読み込み制御している。
#### コマンド
tsもscssもこのコマンドだけでコンパイルできる
```
npm run webpack
```
