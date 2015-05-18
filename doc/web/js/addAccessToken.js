
window.onload = function(){
    var urlParts = window.location.href.split('?');
    if(urlParts.length > 1) {
        var accessToken = urlParts[urlParts.length - 1];
        $('.api-link').each(function () {
            var hrefParts = $(this).attr('href').split('#');
            var docSection = hrefParts[hrefParts.length - 1];
            var linkSection = hrefParts.slice(0, hrefParts.length - 1).join();
            $(this).attr('href', linkSection + '?' + accessToken + '#' + docSection);
        });
    }
};
