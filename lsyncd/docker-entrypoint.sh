#!/bin/sh
set -eu

: "${SOURCE_DIR:=/source}"
: "${DEST_DIR:=/target}"
: "${LSYNC_DELAY:=5}"
: "${LSYNC_DELETE:=true}"
: "${LSYNC_EXCLUDES:=}"

mkdir -p "$SOURCE_DIR" "$DEST_DIR" /tmp

LSYNC_EXCLUDE_ARGS=""

if [ -n "$LSYNC_EXCLUDES" ]; then
  OLD_IFS=$IFS
  IFS=,
  for exclude_pattern in $LSYNC_EXCLUDES; do
    exclude_pattern=$(printf '%s' "$exclude_pattern" | sed 's/^ *//;s/ *$//')

    if [ -n "$exclude_pattern" ]; then
      LSYNC_EXCLUDE_ARGS="$LSYNC_EXCLUDE_ARGS
            \"--exclude=$exclude_pattern\","
    fi
  done
  IFS=$OLD_IFS
fi

export SOURCE_DIR DEST_DIR LSYNC_DELAY LSYNC_DELETE LSYNC_EXCLUDE_ARGS
envsubst '${SOURCE_DIR} ${DEST_DIR} ${LSYNC_DELAY} ${LSYNC_DELETE} ${LSYNC_EXCLUDE_ARGS}' \
  < /etc/lsyncd/lsyncd.conf.lua.template \
  > /etc/lsyncd/lsyncd.conf.lua

rsync -a --delete --omit-dir-times --no-perms --no-owner --no-group \
  $(printf '%s' "$LSYNC_EXCLUDE_ARGS" | sed -n 's/.*"--exclude=\([^"]*\)".*/--exclude=\1/p') \
  "$SOURCE_DIR/" "$DEST_DIR/"

exec lsyncd -nodaemon /etc/lsyncd/lsyncd.conf.lua