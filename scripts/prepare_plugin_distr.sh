#!/bin/bash
ERROR_MSG=""

PLUGIN_NAME="plugin_execution_timer"

if [ "${ERROR_MSG}" = "" ]; then
    export SCRIPTS_DIR="$(dirname "$0")";
    cd "${SCRIPTS_DIR}"
    export SCRIPTS_DIR="$(pwd)";
    cd "${SCRIPTS_DIR}/.."
    export BASE_DIR="$(pwd)";
    if [ -f "${BASE_DIR}/.env" ]; then
        set -o allexport; . "${BASE_DIR}/.env" ; set +o allexport ;
    fi
fi

if [ "${PYTHON_VERSION}" == "" ]; then
    if [ -f "${BASE_DIR}/${PLUGIN_NAME}.zip" ]; then
        rm "${BASE_DIR}/${PLUGIN_NAME}.zip"
    fi
    cd "${BASE_DIR}/trunk"
    if ! zip "${BASE_DIR}/${PLUGIN_NAME}.zip" *
    then
        echo "ERROR: could not zip ${BASE_DIR}/${PLUGIN_NAME}.zip *"
    fi
fi

echo ""
if [ "${ERROR_MSG}" = "" ]; then
    echo "Done!"
else
    echo "${ERROR_MSG}" ;
fi
echo ""
