finna.StreetSearch = (function() {
    var ssbutton, terminateButton, buttonArea, spinner, spinnerContainer,
        getPositionSuccess;
    
    var doStreetSearch = function() {
        ssInfo(VuFind.translate('street_search_checking_for_geolocation'), 'continues');

        if (navigator.geolocation) {
            ssInfo(VuFind.translate('street_search_geolocation_available'), 'continues');
            navigator.geolocation.getCurrentPosition(reverseGeocode, geoLocationError, { timeout: 10000, maximumAge: 10000 });
        } else {
            geoLocationError();
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

        ssInfo(VuFind.translate('street_search_coordinates_found'), 'continues');

        reverseGeocodeService = 'https://api.digitransit.fi/geocoding/v1/reverse';

        queryParameters = {
            'point.lat' : position.coords.latitude,
            'point.lon' : position.coords.longitude,
            'size' : '1'
        };
        
        url = reverseGeocodeService + '?' + $.param(queryParameters);

        $.ajax({
            method: "GET",
            dataType: "json",
            url: url
        })
        .done(function(data) {
            if(data.features[0] && (street = data.features[0].properties.street) &&
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

            resultsUrl = VuFind.path + '/Search/Results';
            
            queryParameters = {
                'lookfor' : street + ' ' + city,
                'type' : 'AllFields',
                'limit' : '100',
                'view' : 'grid',
                'filter' : [
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


    var my = {
        init: function() {
            getPositionSuccess = false;
            
            ssbutton = $("#street-search-button");
            buttonArea = $("#street-search-button-area");
            terminate = false;
            terminateButton = $('<button/>').attr('id', 'street-search-terminate').text(VuFind.translate('Abort'));
            infoText = $('<div/>').addClass('info');
            infoArea = $('<div/>').append(infoText).
                      append(terminateButton);
            infoBox = $('<div/>').addClass('infoBox').append(infoArea);
            spinnerContainer = $('<div/>').attr('id', 'street-search-spinner').hide().
                               addClass('alert alert-info').
                               append($('<div/>').addClass('fa fa-spinner fa-spin')).
                               append(infoBox);
            buttonArea.append(spinnerContainer);

            ssbutton.click(function() {
                spinnerContainer.show();
                spinnerContainer.find('.fa-spinner').show();
                terminate = false;
                terminateButton.show();
                doStreetSearch();
            });

            terminateButton.click(function() {
                terminate = true;
                terminateButton.hide();
                spinnerContainer.hide();
            });
        }
    };

    return my;
})(finna);

$(document).ready(function() {
    finna['StreetSearch'].init();
});