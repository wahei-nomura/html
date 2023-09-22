# GitHub Actions

## deployログ自動追記
pullrequest内でmainブランチへmergeする際にGitHub Actionsのイベントを実行  
merge時のコメントタイトルと説明文がdocbase記事に自動で追記される

<hr>

### workflow内のシークレット変数について
```
DOCBASE_API_KEY        => docbaseの読み込み、書き込み両権限のAPI KEY
DOCBASE_TEAMS          => docbaseのチーム名
MACHINERN_GITHUB_TOKEN => machinernユーザーのトークン
```

### 処理の流れ
1. pull_requestのclosedでjob(if_merged)実行
2. pull_requestからのmergeかつブランチはmainのみ次の処理に
3. 環境変数にシークレットの内容やGitHubの情報を格納
4. /.github/script.sh（GitHubとdocbaseから情報取得、最後にdocbase更新）を実行
