#!/usr/bin/env bash


if ! test -f doc_parameters.yml;then
    echo "'doc_parameters.yml' file not found, please create it" >&2
    exit 1
fi

mkdir -p build/tmp
cp -r private public web build/tmp

while read line;do
    if grep : <<<$line 2>&1 >/dev/null;then
        paramKey=$(sed "s/:.*$//g" <<<$line)
        paramValue=$(sed "s/^[^:]\+: //g" <<<$line)
        find build/tmp -type f -exec sed -i "s/{{[ ]*$paramKey[ ]*}}/$(sed 's/\//\\\//g' <<<$paramValue)/g" {} \;
    fi
done < doc/parameters.yml