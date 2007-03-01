#!/bin/sh

PROJECT_SVN='svn://nescafe/project/trunk'



SELF=`basename "$0"`
_BASEDIR=`basename "$SELF"`

if [ -z "$1" ]
then
    echo Usage: $SELF '<-c|-i>'
    echo	-c  clean
    echo	-i  import to repositary and init
    exit
fi


if [ "$1" == '-c' ]
then
    (
    cd $_BASEDIR
    find -type d -name '.svn' -exec rm -rf {} \;
    )
    exit
fi


if [ "$1" == '-i' ]
then
    (
    cd $_BASEDIR
    svn import -m'Initial import' "$PROJECT_SVN" .
    svn propset 'svn:externals' '\
    core.osb svn://nescafe.corp.jetstyle.ru/core.redarmy/trunk/core/core.osb
    core.nop svn://nescafe.corp.jetstyle.ru/core.redarmy/trunk/core/core.nop
    ' libs
    svn propset 'svn:ignore' '*' files
    svn propset 'svn:ignore' '*' files/zcache
    svn propset 'svn:ignore' '*' files/zcache/cms
    svn propset 'svn:ignore' '*.php' config
    chmod 777 files
    chmod 777 files/zcache
    chmod 777 files/zcache/cms
    svn ci -m 'Initialized'
    for i in config/*.sample
    do
	name=`echo $i | sed -n 's/\.sample//p'`
	if [ ! -f "${name}.php" ]
	then
	    cp "$i" "${name}.php"
	fi
    done
    )
    exit
fi
