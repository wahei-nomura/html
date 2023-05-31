#!/bin/sh

DOCBASE_POST_ID=2958885
DATE=$(date "+%Y/%m/%d %H:%M")
PULL_NUMBER=$( $GITHUB_REF | sed -e 's/[^0-9]//g' )

MERGE_COMMIT_MESSAGE=$(curl -s -H "Authorization: token $GITHUB_TOKEN" https://api.github.com/repos/$REPOSITORY/commits/$MERGE_COMMIT_SHA | jq -r .commit.message)

echo "merge comment '$MERGE_COMMIT_MESSAGE'"

DOCBASE_BODY=$(curl -s -H "X-DocBaseToken: $DOCBASE_API_KEY" https://api.docbase.io/teams/$DOCBASE_TEAMS/posts/$DOCBASE_POST_ID | jq -r .body)

curl \
  -H "X-DocBaseToken: $DOCBASE_API_KEY" \
  -H 'Content-Type: application/json' \
  -X PATCH \
  -d "{
        \"body\": \"\r\n\r\n### 【$DATE】$MERGE_COMMIT_MESSAGE \n[該当プルリク](https://github.com/steamships/$REPOSITORY/pull/$PULL_NUMBER) \r\n\r\n $DOCBASE_BODY\"
      }" \
  https://api.docbase.io/teams/$DOCBASE_TEAMS/posts/$DOCBASE_POST_ID