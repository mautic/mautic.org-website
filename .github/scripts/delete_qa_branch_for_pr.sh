#!/bin/bash

QA_ENV=$1
PR_NUMBER=$2

git fetch --prune
for branch in $(git branch -r | grep -E "origin\/internal\/qa-${QA_ENV}/PR-${PR_NUMBER}\/.*" | sed 's/origin\///'); do 
  echo "Deleting branch $branch"
  git push origin --delete "$branch"
done
