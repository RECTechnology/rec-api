var MagicAccessToken = MagicAccessToken || {};

MagicAccessToken.init = function(){
    var urlInterval = window.setInterval(function(){readUrl(urlInterval);}, 1000);
    var tokenInterval = window.setInterval(function(){writeToken();}, 1000);
};

function readUrl(urlInterval){
    var url = location.search;
    if (url.length > 0) {
        var urlParams = url.substring(1).split('&');
        var accessToken = urlParams[0];
        var elements = document.getElementsByClassName('sample-request-header input-xxlarge');
        if( elements.length > 0){
            for(i = 0; i<elements.length; i++){
                elements[i].value = "Bearer " + accessToken;
            }
            clearInterval(urlInterval);
        }
    }
    else{
        clearInterval(urlInterval);
    }
}


function writeToken(){
    var data = document.getElementsByClassName('sample-request-response-json');
    if( data.length > 0){
        var text = data[0].innerHTML;
        var index = text.indexOf('access_token');
        if(index > -1){
            var ini = text.indexOf(":") + 3;
            var end = text.indexOf(",") + -1;
            var accessToken = text.substring(ini, end);
            var elements = document.getElementsByClassName('sample-request-header input-xxlarge');
            for(i = 0; i<elements.length; i++){
                elements[i].value = "Bearer " + accessToken;
            }
        }
    }
}

window.onload = function() {
    MagicAccessToken.init();
};

