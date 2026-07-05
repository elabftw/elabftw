#!/usr/bin/env bash
# login, set PHPSESSID env var to your php session cookie value and run
set -euo pipefail

url='https://elab.local:3148/api/v2/experiments?scope=3&limit=10000'
cookie="PHPSESSID=$PHPSESSID"
runs="${1:-30}"

tmp="$(mktemp)"
trap 'rm -f "$tmp"' EXIT

for i in $(seq 1 "$runs"); do
  time_total="$(curl -k -sS -o /dev/null -w '%{time_total}' --cookie "$cookie" "$url")"
  printf '%s\n' "$time_total" >> "$tmp"
  printf '%02d/%02d  %ss\n' "$i" "$runs" "$time_total"
done

awk '
  {
    n += 1
    sum += $1
    sumsq += $1 * $1
  }

  END {
    mean = sum / n
    stddev = n > 1 ? sqrt((sumsq - (sum * sum / n)) / (n - 1)) : 0

    printf "\nRuns:    %d\n", n
    printf "Mean:    %.6fs\n", mean
    printf "Std dev: %.6fs\n", stddev
  }
' "$tmp"
