//**
// This script define a handler that changes the url of second language links of WPML language selector in wpadmin bar
// by adding parameter lang=%required language code%
//*
(function() {

    // This function is looking for correct link to translated post of second language,
    // gets part of found link and language code, updates a link of language selector in wp admin bar
    // with language code and part of source link.
    function changeLinkOfLangSelector() {

        // after clicking Duplicates button we need to wait some time while language section will be refreshed
        // and a link to translatated post will appear.
        var timerId = setInterval( function() {
            // find link to translated post
            var sourceA = document.querySelector( '#icl_translations_table .js-wpml-translate-link' );
            var sourceHref = sourceA && sourceA.href;
            if ( sourceHref ) {
                //link is found. No need timer any more
                clearInterval(timerId);

                // get language code and second part of url (since last /) from the found link
                var matchResultSourceHref = sourceHref.match( /^.+\/(.+lang=(\w\w).*)$/ );
                var lang = matchResultSourceHref && matchResultSourceHref[2];
                var sourceSciptString = matchResultSourceHref && matchResultSourceHref[1];

                if ( !lang ) return;
                if ( !sourceSciptString ) return;

                // get a link of second language in wp admin bar
                var destinationA = document.querySelector( '#wpadminbar #wp-admin-bar-WPML_ALS_' + lang + ' .ab-item' );
                var destinationHref = destinationA && destinationA.href;
                if ( destinationHref ) {
                    // take first part of url from the beggining till last /
                    var matchResultDestinationHref = destinationHref.match( /^(.+\/)/ );
                    var newHref= matchResultDestinationHref && matchResultDestinationHref[1];
                    if ( !newHref ) return;
                        //create new correct link to translated post and update link of language selector in wp admin bar
                        newHref += sourceSciptString + '&admin_bar=1';
                        destinationA.href = newHref;
                }
            }
        }, 500 );
    }

    //This function is looking for button Duplicate in the pages and
    // assigns onClick handler for this button.
    function assignHandlerToMakeDuplicatesButton() {
        var button = document.querySelector('#icl_make_duplicates');
        if (button) {
            const handler = function () {
                //Handler can be removed after click
                button.removeEventListener( "click", handler );
                //Define function that will change link in language selector at wp admin bar
                changeLinkOfLangSelector();
            };
            button.addEventListener( "click", handler );
            return true;
        }
        return false;
    }

    // This function define handler that listens when div #icl_div is modify
    function assignHandlerToLangDiv() {
        var div = document.querySelector('#icl_div');
        if (div) {
            const handler2 = function () {
                //check if button Duplicates exist in language section
                if( assignHandlerToMakeDuplicatesButton() ) {
                    // Duplicates button exists and handler onClick of this button is defined
                    // We can remove handler for div #icl_div
                    div.removeEventListener( "DOMSubtreeModified", handler2 );
                }
            };
            div.addEventListener( "DOMSubtreeModified", handler2 );
        }
    }

    function isLanguageLinkCorrect() {
        var link = document.querySelector( '#wpadminbar #wp-admin-bar-WPML_ALS_de .ab-item' );
        if ( ! link) {
            link = document.querySelector( '#wpadminbar #wp-admin-bar-WPML_ALS_en .ab-item' );
        }
        var url = link && link.href;
        if ( url ) {
            if( url.search( /post=\d+/ ) !== -1 ) return true;
        }
        return false;
    }

    function readyWpmlext() {
        // check if language link is already correct. if so do nothing
        if( isLanguageLinkCorrect() ) return;

        //check if button Duplicates exist in language section
        if( !assignHandlerToMakeDuplicatesButton() ){
            //We need to define a handler for div #icl_div to listen when it will be modified,
            // for example, after pressing buttons Preview, Save draft, Publish.
            // Because button Duplicates could be added into div #icl_div.
            assignHandlerToLangDiv();
        }
    }

    document.addEventListener("DOMContentLoaded", readyWpmlext);

})();
