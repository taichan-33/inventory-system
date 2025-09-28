在庫管理システム (Inventory Management System)
概要
このアプリケーションは、Laravelフレームワークをベースに構築された在庫管理システムです。
商品、店舗、在庫の基本的なCRUD（作成、読み取り、更新、削除）機能に加え、売上登録、ダッシュボードでのデータ可視化、AIによる需要予測など、実践的な機能を備えています。

主な機能
認証機能:

ユーザー登録、ログイン、ログアウト機能

パスワードリセット機能

ダッシュボード:

売上高や販売数のKPIを期間（今日/今月/今年/カスタム）ごとに表示

前期間との比較グラフによる売上推移の可視化

在庫が発注点を下回った際の「在庫アラート」機能

グラフ分析ページ:

月別売上、店舗別シェア、商品別売上Top5、曜日別売上などをグラフで詳細に分析可能

在庫管理:

在庫情報の一覧、新規登録、編集、削除

商品名や店舗での絞り込み検索機能

売上登録:

在庫一覧から直接、販売数を入力して売上を登録

登録時に在庫数が自動で減少

AI需要予測:

発注点を下回った在庫に対し、AI（OpenAI APIを利用）が過去の売上データから将来の需要を予測

予測に基づいた適切な「推奨発注数」を提示

マスタ管理:

商品マスタ管理（一覧、新規登録）

ユーザー管理（一覧）

プロフィール管理:

ユーザー自身のプロフィール情報（名前、メールアドレス）の更新

パスワードの変更とアカウントの削除

データベース設計 (ER図)
このシステムの主要なエンティティ間の関係は以下の通りです。

コード スニペット

erDiagram
    USERS ||--o{ SALES : "records"
    STORES ||--o{ INVENTORIES : "has"
    PRODUCTS ||--o{ INVENTORIES : "is"
    PRODUCTS ||--o{ SALES : "is sold as"
    INVENTORIES ||--o{ SALES : "is sold from"
    INVENTORIES ||--o{ PURCHASE_ORDERS : "is ordered via"

    USERS {
        int id PK
        string name
        string email
        string password
    }
    STORES {
        int id PK
        string name
        string location
    }
    PRODUCTS {
        int id PK
        string name
        string description
        int price
    }
    INVENTORIES {
        int id PK
        int product_id FK
        int store_id FK
        int quantity
        int reorder_point
    }
    SALES {
        int id PK
        int inventory_id FK
        int user_id FK
        int quantity
        timestamp sale_date
    }
    PURCHASE_ORDERS {
        int id PK
        int inventory_id FK
        int quantity
        string status
    }
USERS: ログインしてシステムを操作するユーザー

STORES: 商品在庫を管理する店舗

PRODUCTS: 管理対象の商品マスタ

INVENTORIES: どの店舗にどの商品が何個あるかを示す在庫テーブル

SALES: 日々の売上記録

PURCHASE_ORDERS: 発注記録

システム依存関係
本プロジェクトは、主に以下のライブラリやフレームワークに依存しています。

コード スニペット

graph TD
    subgraph Backend (PHP)
        Laravel[Laravel Framework]
        Sail[Laravel Sail]
        OpenAI[OpenAI PHP Client]
    end

    subgraph Frontend (JavaScript)
        Bootstrap[Bootstrap]
        ChartJS[Chart.js]
        Vite[Vite]
    end

    subgraph Development Environment
        Docker[Docker]
        MySQL[MySQL]
    end

    InventorySystem[在庫管理システム] --> Laravel
    InventorySystem --> Sail
    InventorySystem --> Bootstrap
    InventorySystem --> ChartJS

    Laravel --> OpenAI
    Sail --> Docker
    Docker --> MySQL
    Vite --> Bootstrap
シーケンス図
1. ユーザーログイン
コード スニペット

sequenceDiagram
    participant User as ユーザー
    participant Browser as ブラウザ
    participant WebServer as Webサーバー (Laravel)
    participant Database as データベース

    User->>+Browser: メールアドレスとパスワードを入力しログインボタンをクリック
    Browser->>+WebServer: POST /login (ログインリクエスト)
    WebServer->>WebServer: バリデーションチェック
    WebServer->>+Database: usersテーブルからユーザー情報を検索
    Database-->>-WebServer: ユーザー情報を返す
    alt 認証成功
        WebServer->>WebServer: セッションを開始
        WebServer-->>-Browser: 302 Redirect to /dashboard
        Browser->>+User: ダッシュボードを表示
    else 認証失敗
        WebServer-->>-Browser: エラーメッセージと共にログイン画面を再表示
        Browser->>+User: エラーを表示
    end
2. 売上登録
コード スニペット

sequenceDiagram
    participant User as ユーザー
    participant Browser as ブラウザ
    participant WebServer as Webサーバー (Laravel)
    participant Database as データベース

    User->>+Browser: 在庫一覧画面で販売数を入力し「売上登録」ボタンをクリック
    Browser->>+WebServer: POST /sales (売上情報)
    WebServer->>WebServer: SalesController@store を実行
    WebServer->>+Database: salesテーブルに売上データをINSERT
    Database-->>-WebServer: 登録成功
    WebServer->>+Database: inventoriesテーブルの在庫数(quantity)をUPDATE
    Database-->>-WebServer: 更新成功
    WebServer-->>-Browser: 302 Redirect to /inventory (在庫一覧へリダイレクト)
    Browser->>User: 更新された在庫一覧を表示
使用技術
バックエンド: Laravel, PHP

データベース: MySQL

フロントエンド: Blade, Bootstrap, Chart.js

AI: OpenAI API

開発環境: Docker (Laravel Sail)

セットアップ手順
リポジトリをクローン

Bash

git clone https://github.com/taichan-33/inventory-system.git
cd inventory-system
環境ファイルの準備

Bash

cp .env.example .env
Laravel Sailの起動

Bash

./vendor/bin/sail up -d
依存パッケージのインストール

Bash

./vendor/bin/sail composer install
./vendor/bin/sail npm install
アプリケーションキーの生成

Bash

./vendor/bin/sail artisan key:generate
データベースのマイグレーションと初期データ投入

Bash

./vendor/bin/sail artisan migrate --seed
フロントエンドアセットのビルド

Bash

./vendor/bin/sail npm run build
アクセス
ブラウザで http://localhost にアクセスしてください。

使い方
ログイン情報:

メールアドレス: exsample@gmail.com

パスワード: test1234

ログイン後、ナビゲーションバーから各機能へアクセスしてください。

AI需要予測は、「AI予測」ページからバッチ処理を実行することで、発注点を下回っている商品の推奨発注数を確認できます。（※事前にOpenAIのAPIキーを.envファイルに設定する必要があります）