#!/bin/bash

get_token(){
  echo -n "Getting the access token... " >&2
  curl -X POST https://api.rec.barcelona/oauth/v2/token \
    -d client_id=1_23zebs1ciqsk088s4wckckgwkcogo8ws8os48osc40s0s8ss0k \
    -d client_secret=2zspa4vc6ask0wk0kcso0sowg0w088k00k00gkcs8ggs0g400g \
    -d grant_type=password \
    -d username=20490192k \
    -d password=cacapolla999 -s | jq -r .access_token
  echo "Done" >&2
}

get_payment_url(){
  echo -n "Getting the payment url... " >&2
  curl -X POST https://api.rec.barcelona/delegated/lemonway \
    -H "Authorization: Bearer $1" \
    -d dni=$2 \
    -d cif=$3 \
    -d amount=$4 -s | tee -a debug.log | jq -r .pay_in_info.payment_url
  echo "Done" >&2
}

token=`get_token`

while read line;do
  nif=`awk -F, '{print $1}' <<<$line`
  cif=`awk -F, '{print $2}' <<<$line`
  amount=`awk -F, '{print $3}' <<<$line`
  cardholder='Name'
  pan=`awk -F, '{print $4}' <<<$line`
  expiryyear=`awk -F, '{print $5}' <<<$line`
  expirymonth=`awk -F, '{print $6}' <<<$line`
  cvv2=`awk -F, '{print $7}' <<<$line`

  payment_url=`get_payment_url $token $nif $cif $amount`
  if [[ $payment_url == "null" ]];then
    echo "Error getting the payment url, continuing with the next" >&2
    continue
  fi

  echo -n "Executing bot to pay... " >&2
  if ./pay-cli.py "$payment_url" "$cardholder" $pan $expirymonth $expiryyear $cvv2;then
    echo "Success" >&2
  else 
    echo "Error" >&2
  fi
done
