#!/bin/sh

# Finds PHP binary
PHPBIN=`whereis php-cgi`
if [ "$PHPBIN" == "" ]; then
	PHPBIN=`whereis php`

	if [ "$PHPBIN" == "" ]; then
		echo "Error. PHP binary not found"
		exit 1
	fi
fi

# saves the path to this script's directory
dir=` dirname $0 `

# absolutizes the path if necessary
if echo $dir | grep -v ^/ > /dev/null; then
	dir=` pwd `/$dir
fi

# run tests
$PHPBIN "$dir/scripts/runtests.php" -p "$PHPBIN" $*