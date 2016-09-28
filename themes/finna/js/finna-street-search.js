finna.StreetSearch = (function() {
    var ssButton, terminateButton, spinner, spinnerContainer, getPositionSuccess, xhr;

    var reverseGeocodeService = 'https://api.digitransit.fi/geocoding/v1/reverse';
    var geolocationAccuracyTreshold = 20; // If accuracy >= treshold then give a warning for the user

    var doStreetSearch = function() {
        spinnerContainer.show();
        spinnerContainer.find('.fa-spinner').show();
        terminate = false;
        terminateButton.show();
        ssButton.hide(); 

        ssInfo(VuFind.translate('street_search_checking_for_geolocation'), 'continues');

        if (navigator.geolocation) {
            ssInfo(VuFind.translate('street_search_geolocation_available'), 'continues');
            navigator.geolocation.getCurrentPosition(reverseGeocode, geoLocationError, { timeout: 10000, maximumAge: 10000 });
        } else {
            geoLocationError();
        }
    }

    var terminateStreetSearch = function() {
        terminate = true;
        terminateButton.hide();
        spinnerContainer.hide();
        ssButton.show();
        if (typeof xhr !== 'undefined') {
            xhr.abort();
        }
    }
   
    var geoLocationError = function(error) {
        if (!getPositionSuccess) {
            errorString = 'street_search_other_error';
            if (error) {
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorString = 'street_search_geolocation_inactive';
                        break;
                    case error.TIMEOUT:
                        errorString = 'street_search_timeout';
                        break;
                    default:
                        // do nothing
                        break;
                }
            }
            ssInfo(VuFind.translate(errorString), 'stopped');
        }
    }

    var reverseGeocode = function(position) {
        getPositionSuccess = true;
	
        if (position.coords.accuracy >= geolocationAccuracyTreshold) {
            ssInfo(VuFind.translate('street_search_coordinates_found_accuracy_bad'), 'continues');
        } else {
            ssInfo(VuFind.translate('street_search_coordinates_found'), 'continues');
	}

	queryParameters = {
	    'point.lat': position.coords.latitude,
	    'point.lon': position.coords.longitude,
	    'size': '1'
        };
	
        url = reverseGeocodeService + '?' + $.param(queryParameters);
	
        xhr = $.ajax({
            method: "GET",
            dataType: "json",
            url: url
        })
	    .done(function(data) {
		if (data.features[0] && (street = data.features[0].properties.street) &&
		    (city = data.features[0].properties.locality)) {
		    buildSearch(street, city);
		} else {
		    ssInfo(VuFind.translate('street_search_no_streetname_found'), 'stopped');
		}
	    })
	    .fail(function() {
		ssInfo(VuFind.translate('street_search_reversegeocode_unavailable'), 'stopped');          
	    });
    }
 
    var buildSearch = function(street, city) {
        if (!terminate) {
            ssInfo(VuFind.translate('street_search_searching_for') + ' ' + street + ' ' + city, 'stopped');

            // resultsUrl = VuFind.path + '/Search/Results';
            resultsUrl = (VuFind.path.match(/vufind/)) ? "https://finna.fi/Search/Results" : VuFind.path + '/Search/Results';
            
            queryParameters = {
                'lookfor': street + ' ' + city,
                'type': 'AllFields',
                'limit': '100',
                'view': 'grid',
                'filter': [
                    '~format:"0/Image/"',
                    '~format:"0/Place/"',
                    'online_boolean:"1"'
                ]
            };
        
            url = resultsUrl + '?' + $.param(queryParameters);
            window.location.href = url;
        }
    }

    var ssInfo = function(message, type) {
        if (type) {
            if (type === 'stopped') {
                spinnerContainer.find('.fa-spinner').hide();
                terminateButton.hide();
            }
        }
        spinnerContainer.find('.info').text(message);        
    }

    var initPageElements = function () {
        ssButton = $("#street-search-button");
        terminateButton = $("#street-search-terminate");
        terminateButton.hide();
        spinnerContainer = $("#street-search-spinner");
        spinnerContainer.hide();

        terminate = false;

        ssButton.click(function() {
            doStreetSearch();
        });
        
        terminateButton.click(function() {
            terminateStreetSearch();
        });
    }

    var my = {
        init: function() {
            getPositionSuccess = false;
            initPageElements();
        }
    };
    return my;

})(finna);
