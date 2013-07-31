#
# Extract doc from a shell script
# If generating html, build a TOC
#
# Syntax : mk_sh_doc.sh <source> <target> <format (html|md)> [options]
#
#============================================================================

[ -z "$PHP" ] && PHP=php

PPC_DIR=`dirname $0`
SOURCE=$1
shift
TARGET=$1
shift
FORMAT=$1
shift
OPTIONS=$*
PPC="$PHP $PPC_DIR/ppc.php"
XTRACT="$PHP $PPC_DIR/extract_shell_doc.php -f $FORMAT"

export PHP PPC_DIR SOURCE TARGET OPTIONS PPC XTRACT FORMAT

#-------------

(
[ "$FORMAT" = html ] && echo '<p>{toc}</p><p>&nbsp;</p><hr/>'
[ -f src/$TARGET.start.$FORMAT ] && cat src/$TARGET.start.$FORMAT
$XTRACT $OPTIONS $SOURCE
[ -f src/$TARGET.end.$FORMAT ] && cat src/$TARGET.end.$FORMAT
) >$TARGET

if [ "$FORMAT" = html ] ; then
	mv $TARGET $TARGET.tmp
	PPC_DIR=. $PPC $TARGET.tmp
	mv _$TARGET.tmp $TARGET
	/bin/rm -f $TARGET.tmp
fi
