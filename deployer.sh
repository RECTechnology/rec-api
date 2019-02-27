#!/usr/bin/env bash

if [[ "$WEBHOOK" == "" ]];then
    printf "[WARN] No webhooks to deploy, exiting...\n"
    exit 0
fi

tr ',' '\n' <<<$WEBHOOK | while read url;do
    for tries in `seq 1 10`;do
        if curl -f -X POST "$url";then
            break
        else
            printf "[WARN] webhook failed, retrying in 30 seconds...\n"
            sleep 30
        fi
    done
done
