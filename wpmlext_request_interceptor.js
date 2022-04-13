//**
// This js sciript defines interceptor of window.fetch/jQuery.ajax request in order to add parameter lang=%required language%
// to request's url
//*
(function() {

    function isString(val) {
        return (typeof val === "string" || val instanceof String);
    }

    // This function gets current language of the post from language selector
    // in right sidebar in language section.
    function getLanguageFromSelect(){
        var languageSelect = document.querySelector( '#icl_post_language' );
        var currentLang = languageSelect && languageSelect.value;
        return currentLang;
    }

    // This is an interceptor of window.fetch method that is used to send requests to WP API.
    // This method is used in Gutenberg editor.
    function interceptorGetenberg() {
        var originalFetch = window.fetch;

        var handlerWindowFetch = function(){
            //console.log('here');
            var url = arguments && arguments[0];
            // We are interested in tags and categories path of WP api.
            if( url && isString(url)
                && (url.indexOf('wp-json/wp/v2/tags') !== -1
                || url.indexOf('wp-json/wp/v2/categories') !== -1 )
            ) {
                // method of request must be not POST
                // if method POST we cannot add parameter lang to request url
                var method = arguments[1] && arguments[1].method;
                if( method === undefined || method !== 'POST' ) {
                    var currentLang = getLanguageFromSelect();
                    if ( currentLang ) {
                        // add parameter lang with current post lanugae code to url
                        url = url + '&lang=' + currentLang;
                        arguments[0] = url;
                    }
                }
            }
            // return control to original fetch method to process request
            return originalFetch.apply(this, arguments);
        }
        window.fetch = handlerWindowFetch;
    }

    //This is an interceptor of jQuery ajax request that is used to send request to old WP API.
    // This method is used in classic editor.
    function interceptorClassic() {
        // ajaxSetup is a method that allows to change request parameters befor sending ajax request
        jQuery.ajaxSetup({
            beforeSend: function (xhr,settings) {
                var url = settings && settings.url;
                // we need ajax-tag-search API path only
                if (url && isString(url) && url.indexOf('ajax-tag-search') !== -1 ) {
                    var currentLang = getLanguageFromSelect();
                    if ( currentLang ) {
                        // add parameter lang with current post lanugae code to url
                        url = url + '&lang=' + currentLang;
                        settings.url = url;
                    }
                }
            }
        });
    }

    function readyWpmlext() {
        interceptorGetenberg();
        interceptorClassic();
    }

    document.addEventListener("DOMContentLoaded", readyWpmlext);

})();
