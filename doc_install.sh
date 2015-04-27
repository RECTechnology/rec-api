#!/usr/bin/env bash


if ! test -f doc/parameters.yml;then
    echo "'doc/parameters.yml' file not found, please create it" >&2
    exit 1
fi

TMP_DIR=build/doc/tmp
mkdir -p $TMP_DIR 2>/dev/null
rm -rf $TMP_DIR/*
cp -r doc/private doc/public doc/web $TMP_DIR

while read line;do
    if grep : <<<$line 2>&1 >/dev/null;then
        paramKey=$(sed "s/:.*$//g" <<<$line)
        paramValue=$(sed "s/^[^:]\+: //g" <<<$line)
        find $TMP_DIR -type f -exec sed -i "s/{{[ ]*$paramKey[ ]*}}/$(sed 's/\//\\\//g' <<<$paramValue)/g" {} \;
    fi
done < doc/parameters.yml