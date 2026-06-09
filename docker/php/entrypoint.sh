#!/bin/sh
set -e

# storage/・bootstrap/cache/・SQLite はホストからバインドマウントされ root 所有で渡るため、
# www-data の FPM プールが書き込めるよう起動時に権限を緩める（1人利用のローカル開発用）。
chmod -R 0777 storage bootstrap/cache 2>/dev/null || true

# SQLite は本体ファイルに加え、-wal/-journal を作成するため database ディレクトリ自体も書き込み可能にする。
chmod 0777 database 2>/dev/null || true
[ -f database/database.sqlite ] && chmod 0666 database/database.sqlite 2>/dev/null || true

exec "$@"
