#!/bin/sh
#
# Script for auto-compile of all PO files
#
# Author: Adam Staněk <adam.stanek@v3net.cz>

# saves the path to this script's directory
dir=` dirname $0 `

# absolutizes the path if necessary
if echo $dir | grep -v ^/ > /dev/null; then
	dir=` pwd `/$dir
fi

APP_DIR="$dir/../app"

for MODULE in "$APP_DIR"/*; do
	if [ -d "$MODULE" ]; then
		if [ -d "$MODULE/Translations" ]; then
			for LANG in "$MODULE/Translations"/*.po; do
				msgfmt --output-file ${LANG%.*}.mo --verbose $LANG
			done
		fi
	fi
done