#!/usr/bin/env bash

set -euo pipefail

echo "Event: ${GITHUB_EVENT_NAME}"
echo "Triggering git reference: ${GITHUB_REF}"
echo "Triggering git reference for push events: ${GH_REF}"
echo "Triggering git reference type: ${GH_REF_TYPE}"

function git-setup() {
  printf -v url "https://%s:%s@%s" \
    "${GITLAB_USERNAME}" \
    "${GITLAB_TOKEN}" \
    "${GITLAB_REPOSITORY#https://}"
  echo "git remote add mirror ${url}"
  git remote add mirror ${url}
  set -x
}

# @see https://github.com/xometry/gitlab-mirror-action/tree/master
if test "$GITHUB_EVENT_NAME" == "create"; then
    # Do nothing. Every "create" event *also* publishes a *push* event, even tags/branches created from github UI.
    # Duplicate events would race and sometimes cause spurious errors.
    echo "Ignoring create event, because the push event will handle updates."
elif test "$GITHUB_EVENT_NAME" == "push"; then
  git-setup
  git push mirror ${GITHUB_REF}:${GITHUB_REF} --force --tags
  git remote remove mirror
elif test "$GITHUB_EVENT_NAME" == "workflow_run"; then
  git-setup
  git push mirror ${GITHUB_REF}:${GITHUB_REF} --force --tags
  git remote remove mirror
elif test "$GITHUB_EVENT_NAME" == "workflow_dispatch"; then
  git-setup
  git push mirror ${GITHUB_REF}:${GITHUB_REF} --force --tags
  git remote remove mirror
elif test "$GITHUB_EVENT_NAME" == "delete"; then
  git-setup
  git push mirror :${GH_REF}
  git remote remove mirror
else
  echo "Got unexpected GITHUB_EVENT_NAME: ${GITHUB_EVENT_NAME}"
  exit 1
fi

echo "Done"
