#
# Extract doc from a shell script, then generate the TOC and save to an HTML file
#
#============================================================================

[ -z "$PHP" ] && PHP=php

PPC_DIR=`dirname $0`
SOURCE=$1
shift
TARGET=$1
shift
OPTIONS=$*
PPC="$PHP $PPC_DIR/ppc.php"
XTRACT="$PHP $PPC_DIR/extract_shell_doc.php"

export PHP PPC_DIR SOURCE TARGET OPTIONS PPC XTRACT

#-------------

(
echo '<p>{toc}</p><p>&nbsp;</p><hr/>'
[ -f src/$TARGET.start.htm ] && cat src/$TARGET.start.htm
$XTRACT $OPTIONS $SOURCE
[ -f src/$TARGET.end.htm ] && cat src/$TARGET.end.htm
) >$TARGET.tmp

PPC_DIR=. $PPC $TARGET.tmp
mv _$TARGET.tmp $TARGET
/bin/rm -f $TARGET.tmp


