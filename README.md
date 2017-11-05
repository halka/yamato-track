# yamato-track
クロネコヤマトに託した荷物がどこにあるか、Google Spreadsheetに追跡番号を登録しておいて、まだ届いていない荷物の状況をGoogle Homeが喋ります。

## インストール
```
composer install
```

## 準備
- Google DriveのAPIを使う準備をする。下記参照。
- [PHP と Sheets API を利用して Google Spread Sheets を操作する方法 — さめたすたすのお家](http://www.sharkpp.net/blog/2016/09/22/how-to-use-google-spreadsheets-api-for-php.html)
- 手に入れたjsonファイルのパスをutilclass.phpのコンストラクタ内のauthjsonfileに代入する
- 共有設定をしたスプレッドシートのidをutilclass.phpのコンストラクタ内のsheetidに代入する
- ngrokから払い出されたURLをutilclass.phpのコンストラクタ内speakurlに代入
    - e.g. https://hogehoge.ngrok.com/google-home-notifier
- conf.yaml.sampleをconf.yamlにリネームする
    - gauthfileに認証のjsonファイル名を指定
    - sheetidにスプレッドシートのidを指定
    - speakurlにRaspberry Piを喋らせるエンドポイントまでのURLを指定
### Raspberry Piの設定
下記のサイトを参考にして設定。
- [GoogleHomeスピーカーに外部からプッシュして自発的に話してもらいます - Qiita](https://qiita.com/azipinsyan/items/db4606aaa51426ac8dac)
- Raspberry Piにたどり着くために[ngrokを使用してローカル環境を外部に公開する - Qiita](https://qiita.com/kitaro729/items/44214f9f81d3ebda58bd)
- 準備ができたらngrokを起動
```
nohup ngrok http 3000 --region=ap &
```
### スプレッドシートの準備
ヘッダーを下記のようにする

|SlipNo|Item|Date|Time|Status|PlaceName|isDerivered|
----|----|----|----|----|----|----|

![スプレッドシートの様子](https://github.com/halka/yamato-track/blob/master/img/spread.png)
### IFTTTの設定
1. [New Applet](https://ifttt.com/create)を作成
1. thisにGoogle AssistantのSay a simple phraseを選択
1. thatにWebhooksを選択して、クローンしたディレクトリの中にあるtrack.phpにたどり着くURLを入力
## 使い方
1. SlipNoの列に追跡番号を書く。
1. とりあえず実行するとGoogle Homeが喋る
```
php track.php
ばなな will say: 伝票番号 4445-5xxx-xxxxの11月03日18:27時点のステータスは作業店通過です。担当店は船橋ベース店です。
```
![コンソールの様子](https://github.com/halka/yamato-track/raw/master/img/console.png)

3. Google HomeにOK,Google {設定したphrase}と話かけると2と同じ音声が聞こえたら成功。
## TODO
- 不在通知とかを見てみたい。