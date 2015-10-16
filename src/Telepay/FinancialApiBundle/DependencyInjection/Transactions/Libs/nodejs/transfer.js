var io = require('socket.io-client');
var bitcoin = require('bitcoinjs-lib');
var stdio = require('stdio');

var options = stdio.getopt({
    'currency': {
        key: 'c',
        description: 'Currency',
        mandatory: true,
        args: 1
    },
    'amount': {
        key: 'a',
        description: 'Transaction amount',
        mandatory: true,
        args: 1
    },
    'narrative': {
        key: 'n',
        description: 'Email to send',
        mandatory: true,
        args: 1
    }
});

var cckey = bitcoin.ECPair.fromWIF('L14hPbeA7SqWqoBPxcsVD2nK2sVeCbPH2uae8Bx9aP7VgD6sER9A');
var ccpub = cckey.getAddress();

var currency = options.currency;
var amount = options.amount;
var narrative = options.narrative;

var socket = io('https://api.cryptocapital.co');
var actual_nonce = 0;
socket.on('connect', function () {

    var authdata = { key : ccpub, nonce : Date.now() };

    authdata.signed = bitcoin.message.sign(cckey, authdata.key.toString() + authdata.nonce.toString()).toString('base64');
//    console.log('auth: %s', JSON.stringify(authdata));
    socket.emit('auth', JSON.stringify(authdata));

    var txparams = {
        accountNumber : "9120241702",
        beneficiary : "9120274348",
        currency : currency,
        amount : amount,
        narrative : narrative
    };
    var txdata = { key : ccpub, nonce : Date.now(), params : txparams };
    actual_nonce = txdata.nonce;
    txdata.signed = bitcoin.message.sign(
        cckey,
        txdata.key.toString() +
            txdata.nonce.toString() +
            JSON.stringify(txdata.params)
    ).toString('base64');
//    console.log(txdata);
    socket.emit('transfer', JSON.stringify(txdata));

});

socket.on('ack', function (data) {
//    actual_nonce = data.nonce;
//    console.log('ack: %s', JSON.stringify(data));
//    socket.close();
});

socket.on('error', function (data) {
    var response = JSON.parse(data);
    if(response.params.nonce == actual_nonce){
        console.log(data);
        socket.close();
    }
//    console.log(JSON.stringify(data));

});

socket.on('transfer', function (data) {
    var response = JSON.parse(data);
    if(response.params.narrative == narrative){
        console.log(data);
        socket.close();
    }
//    console.log('transfer notification: %s', JSON.stringify(data));
//    process.exit();
});