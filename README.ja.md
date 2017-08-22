KSamplePHP
====

## 概要

小規模プロジェクト向けサンプルPHPアプリ。

## 説明

* 小規模向け。  
* フレームワークではない。（MVCモデルではない。）  
* 1ファイルにできるだけ詰め込む。  
* オブジェクト指向は可能な限り使わない。  
* 素のPHPなので学習コストが少ない。そしてきっとパフォーマンスがよい。  
* 多言語対応できる。  
* 権限管理できる。  
* 複数データベース対応できる。  

## 検証環境

Ubuntu 16.04.2 LTS  
Apache 2.4.18  
PHP 7.0.18  
MariaDB 10.0.29  
Redis 3.0.6  
Postfix 3.1.0  
  
jQuery 3.2.1  
Bootstrap 4.0.0b  

## インストール

Apacheのドキュメントルートにダウンロードしたファイルを展開する。  
ex. /var/www/html/ksamplephp/  
  
init.sqlをMariaDBのrootユーザで実行する。  
ex. mysql -u root -p[password] < init.sql  
  
access.confをApacheの設定ディレクトリに配置して、Apacheのサービスを再起動する。  
ex. /etc/apache2/conf-available/access.conf  
        sudo systemctl restart apache2.service  
  
php.iniを編集する。  
ex. /etc/php/7.0/apache2/php.ini  
  
つぎのファイルを展開したディレクトリから削除する。  
access.conf  
init.sql  
php.ini-sample  
README.ja.md  
README.md  
  
## ライセンス

[MIT](https://opensource.org/licenses/mit-license.html)

## 作者

[perlfreak](https://github.com/perlfreak)
