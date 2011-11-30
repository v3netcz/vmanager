#!/bin/sh
#
# Script for auto-update all GetText translations
#
# Author: Adam Staněk <adam.stanek@v3net.cz>

# saves the path to this script's directory
dir=` dirname $0 `

# absolutizes the path if necessary
if echo $dir | grep -v ^/ > /dev/null; then
	dir=` pwd `/$dir
fi

if [ $1 ]; then
	LANG="$1"
else
	LANG="cs"
fi

APP_DIR="$dir/../app"
PROJECT="vManager"
VERSION="1.0"
NOW=`date "+%Y-%m-%d% %H:%M%z"`
ARGS="-j --language=PHP --from-code=UTF-8 --keyword=__ --keyword=_n --keyword=_x --omit-header"
PLURAL="\"Plural-Forms: nplurals=3; plural=(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2;\\\\n\""

for MODULE in "$APP_DIR"/*; do
	if [ -d "$MODULE" ]; then
		if [ -d "$MODULE/Translations" ]; then

			if [ ! -f "$MODULE/Translations/$LANG.po" ]; then
				echo 'msgid ""' > "$MODULE/Translations/$LANG.po"
				echo 'msgstr ""' >> "$MODULE/Translations/$LANG.po"
				echo "\"POT-Creation-Date: $NOW\\\\n\"" >> "$MODULE/Translations/$LANG.po"
				echo "\"Language: $LANG\\\n\"" >> "$MODULE/Translations/$LANG.po"
				echo '"Content-Type: text/plain; charset=UTF-8\\n"' >> "$MODULE/Translations/$LANG.po"
				echo '"Plural-Forms: nplurals=2; plural=(n != 1);\\n"' >> "$MODULE/Translations/$LANG.po"
				echo >> "$MODULE/Translations/$LANG.po"
			fi

			# Jelikoz uchovaveme komentare, tak musime ty soucasne smazat, pred tim nez tam zapiseme nove
			sed 's/^#:.*//g' -i "$MODULE/Translations/$LANG.po"

			find "$MODULE" -name *.php -or -name *.latte | xargs xgettext $ARGS --output="$MODULE/Translations/$LANG.po"
		fi
	fi
done