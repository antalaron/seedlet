#!/usr/bin/env bash

# $rootDirectory = dirname(__DIR__);
# $timeFile = $rootDirectory.'/var/.assets-mtime';
# $watchFiles = [
#     '/assets',
#     '/webpack.config.js',
# ];

root_directory=$(dirname $(dirname $0))
time_file=$root_directory/var/.assets-mtime
watch_files=(
    /assets
    /webpack.config.js
)

latest_mtime=0
while IFS= read -r watch_file; do
    watch_file="$root_directory$watch_file"

    if [ -d "$watch_file" ]; then
        while IFS= read -r file; do
            if [ -f "$file" ]; then
                file_mtime=$(stat -c %Y "$file")
                latest_mtime=$((latest_mtime > file_mtime ? latest_mtime : file_mtime))
            fi
        done < <(find "$watch_file" -type f)
    else
        file_mtime=$(stat -c %Y "$watch_file")
        latest_mtime=$((latest_mtime > file_mtime ? latest_mtime : file_mtime))
    fi
done <<< "$(printf "%s\n" "${watch_files[@]}")"

mkdir -p "$(dirname "$time_file")"
touch -m -t "$(date -d "@$latest_mtime" +"%Y%m%d%H%M.%S")" "$time_file"
