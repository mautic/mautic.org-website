#!/bin/bash

QA_ENV=$1
PR_NUMBER=$2

cd main
git config user.name github-actions
git config user.email github-actions@github.com
git checkout -b internal/qa-${QA_ENV}/PR-${PR_NUMBER}/${GITHUB_HEAD_REF}
cd ..
rsync -avz --delete --exclude=.git --exclude=.github --exclude=.gitlab-ci.yml pr/ main
cd main
git add .
git commit -m "Created QA branch for PR ${PR_NUMBER}"
git push -f --set-upstream origin internal/qa-${QA_ENV}/PR-${PR_NUMBER}/${GITHUB_HEAD_REF}
