#!/usr/bin/env bash
set -e
# Production deploy script
#  Script parameters:
#    none: update folder and run dependency handling
#  Exits with status 1 on error.

function log() {
    local message="${1}"
    echo -e "$(date +'%Y-%m-%d %H:%M:%S'): ${message}"
}

function handle_dependencies {
    local _folder_name="${1}"
    cd "${_folder_name}"
    if ! command -v composer &>/dev/null; then
        log "${cBlue}COMPOSER NOT INSTALLED${c0}"
        log "${fail_msg}" && exit 1
    fi

    composer_version=$(composer --version 2>/dev/null | awk '{print $3}' | cut -d. -f1)
    if [[ ${composer_version} -ne 2 ]]; then
        log "${cDRed}WARNING: Composer version 2 is required. Current version: $(composer --version)${c0}"
    fi

    if [[ ! -f "composer.json" || ! -L ".env" ]]; then
        log "${cBlue}MISSING composer.json or .env${c0}"
        log "${fail_msg}" && exit 1
    fi
    log "${cBlue}SETTING PROPER FOLDER PERMISSIONS${c0}"
    sudo bash ./git-scripts/set_permissions.sh || {
        log "${fail_msg}" && exit 1
    }
    log "${success_msg}"
    log "${cBlue}COMPOSER INSTALL AND AUTOLOAD RUNNING${c0}"
    sudo su -c "export COMPOSER_ALLOW_SUPERUSER=1 && composer install -a -n --no-dev" || {
        log "${fail_msg}" && exit 1
    }
    log "${success_msg}"
}

function deploy_main {
    local _folder_name="${1}"
    cd "${_folder_name}"
    log "${cDGreen}[ STARTING DEPLOY ON ${HOSTNAME}: ${_folder_name} ]${c0}"
    log "${cBlue}REVISION PRE-DEPLOY: ${_folder_name}${c0}"
    git show -q
    log "${cBlue}FETCHING/PULLING LATEST${c0}"
    for ((attempt = 1; attempt <= MAX_ATTEMPTS; attempt++)); do
        sudo git pull && break
    done
    if ((attempt > MAX_ATTEMPTS)); then
        log "${fail_msg}" && exit 1
    fi
    log "${success_msg}"
    log "${cBlue}FOLDER STATUS: ${_folder_name}${c0}"
    git status
    log "${cBlue}REVISION POST-DEPLOY: ${_folder_name}${c0}"
    git show -q
}

__ESC__="\033"
c0="${__ESC__}[0m"
cDRed="${__ESC__}[1;31m"
cDGreen="${__ESC__}[1;32m"
cBlue="${__ESC__}[1;34m"
folder_name_main="$(cd -P -- "$(dirname -- "${BASH_SOURCE[0]}")" && cd ../ &>/dev/null && pwd -P)"
success_msg="${cBlue}SUCCESS${c0}"
fail_msg="${cDRed}DEPLOY FAILED ON: ${HOSTNAME}${c0}"
MAX_ATTEMPTS=5

deploy_main "${folder_name_main}"
handle_dependencies "${folder_name_main}"
log "${cDGreen}[ FINISHED DEPLOY ON ${HOSTNAME} ]${c0}"
