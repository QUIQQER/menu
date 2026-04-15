#!/usr/bin/env bash

# Installs a package with the given name from the given folder into the QUIQQER system

if [ -z "$1" ]; then
    echo "Error: No package directory supplied as first argument"
    exit 1
fi

PACKAGE_FOLDER=$1

if [ ! -d "${PACKAGE_FOLDER}" ]; then
  echo "Given package directory '${PACKAGE_FOLDER}' does not exist."
  exit 1
fi

if [ -z "$2" ]; then
  PACKAGE_NAME=$(php -r "echo json_decode(file_get_contents('${PACKAGE_FOLDER}/composer.json'))->name;")
else
  PACKAGE_NAME=$2
fi

set -Eeuox pipefail

QUIQQER_ROOT=/var/www/html

#git config --global --add safe.directory "${PACKAGE_FOLDER}"
echo "Changing ownership of package directory to current user..."
sudo chown "$(whoami)" "${PACKAGE_FOLDER}" -R

#echo "Determining default branch of the package..."
#PACKAGE_DEFAULT_BRANCH=$(git -C "${PACKAGE_FOLDER}" symbolic-ref refs/remotes/origin/HEAD | sed 's@^refs/remotes/origin/@@')
#echo "Found: '${PACKAGE_DEFAULT_BRANCH}'"

echo "Determining latest version of the package to use as an alias..."
#PACKAGE_LATEST_VERSION=$(git -C "${PACKAGE_FOLDER}" describe --tags --abbrev=0 remotes/origin/"${PACKAGE_DEFAULT_BRANCH}")
#PACKAGE_LATEST_VERSION=$(git -C "${PACKAGE_FOLDER}" describe --tags --abbrev=0 || echo "1.0.0")
# Based on https://github.com/markchalloner/git-semver
git -C "${PACKAGE_FOLDER}" fetch --tags
PACKAGE_LATEST_VERSION=$(git -C "${PACKAGE_FOLDER}" tag | grep "^[0-9]\+\.[0-9]\+\.[0-9]\+" | uniq | sort -t '.' -k 1,1n -k 2,2n -k 3,3n | tail -n 1 || echo "1.0.0")
echo "Found: '${PACKAGE_LATEST_VERSION}'"

echo "Determining latest commit of the package..."
PACKAGE_LATEST_COMMIT=$(git -C "${PACKAGE_FOLDER}" rev-parse HEAD)
echo "Found: '${PACKAGE_LATEST_COMMIT}'"

cd "$QUIQQER_ROOT"

#./console composer config repositories.local path "${PACKAGE_FOLDER}"
echo "Adding package directory as an active update server to QUIQQER..."
{
  echo "";
  echo "[${PACKAGE_FOLDER}]";
  echo 'active="1"';
  echo 'type="path"';
} >> "${QUIQQER_ROOT}/etc/source.list.ini.php"

echo "Executing QUIQQER setup to add update server to composer.json..."
./console setup

echo "Requiring package into QUIQQER..."
COMPOSER_MIRROR_PATH_REPOS=1 ./console composer require --no-interaction --update-with-all-dependencies --minimal-changes "${PACKAGE_NAME}:dev-$CI_COMMIT_BRANCH#$PACKAGE_LATEST_COMMIT@dev as $PACKAGE_LATEST_VERSION"

echo "Replacing required version of package with files in ${PACKAGE_FOLDER}, because 'composer require' deletes files..."
PACKAGE_FOLDER_IN_QUIQQER=${QUIQQER_ROOT}/packages/${PACKAGE_NAME}
rm -rf ${PACKAGE_FOLDER_IN_QUIQQER}
cp -r ${PACKAGE_FOLDER} ${PACKAGE_FOLDER_IN_QUIQQER}

echo "Executing QUIQQER setup to finalize package installation..."
./console setup