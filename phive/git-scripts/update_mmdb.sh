#!/usr/bin/env bash
set -e

paths=("/var/www" "phive" "utils/download_external_files.php")
folder_names=("${@}")

if [[ -n "${folder_names[*]}" ]]; then
    echo "[ UPDATING MAXMIND DB ON HOST: ${HOSTNAME} ]"
    for i in "${!folder_names[@]}"; do
        folder_name="${paths[0]}/${folder_names[$i]}"
        working_path="${folder_name}/${paths[1]}"
        if [[ -d "${working_path}" ]]; then
            cd "${working_path}" || (echo "FAILED CHANGING TO FOLDER: ${working_path}" && exit 1)
            if command -v composer &>/dev/null; then
                if [[ -f "composer.json" ]]; then
                    echo "  >> UPDATING FOLDER: ${folder_name}"
                    sudo php "${paths[2]}" || (echo "UPDATE FAILED ON FOLDER: ${folder_name}" && exit 1)
                fi
            else
                echo "  >> MAXMIND DB UPDATE NOT NEEDED ON THIS HOST"
            fi
        fi
    done
    echo "[ DONE ]"
else
    echo "PROD FOLDER NAME NOT SPECIFIED. MAXMIND DB UPDATE NOT DONE."
fi
