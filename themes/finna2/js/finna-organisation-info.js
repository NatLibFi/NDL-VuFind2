/*global VuFind, finna */
finna.organisationInfo = (function finnaOrganisationInfo() {
  var organisationList = {};

  function query(queryParams, callback) {
    var url = VuFind.path + '/AJAX/JSON';
    var org = {'id': queryParams.parent, 'sector': queryParams.sector || ''};
    delete queryParams.sector;
    delete queryParams.parent;

    var params = {method: 'getOrganisationInfo', parent: org, params: queryParams};
    $.getJSON(url, params)
      .done(function onGetOrganisationInfoDone(response) {
        if (response.data) {
          callback(true, response.data);
          return;
        }
        callback(false, 'Error reading organisation info');
      })
      .fail(function onGetOrganisationInfoFall(response/*, textStatus, err*/) {
        var error = false;
        if (typeof response.responseJSON !== 'undefined') {
          error = response.responseJSON.data;
        }
        callback(false, error);
      });
  }

  function getCachedDetails(id) {
    if (typeof organisationList[id].details !== 'undefined') {
      return organisationList[id].details;
    }
    return null;
  }

  function getField(obj, field, organisationId) {
    var res = finna.common.getField(obj, field);
    if (res !== null) {
      return res;
    }
    if (organisationId) {
      var cache = getCachedDetails(organisationId);
      if (cache) {
        if (typeof cache[field] != 'undefined') {
          return cache[field];
        }
      }
    }
    return null;
  }

  function cacheSchedules(id, data) {
    var schedules = finna.common.getField(data, 'openTimes');
    if (schedules) {
      organisationList[id].openTimes = schedules;
      organisationList[id].details.openTimes = schedules;
    }
    var scheduleDesc = finna.common.getField(data, 'scheduleDescriptions');
    if (scheduleDesc) {
      organisationList[id].details.scheduleDescriptions = scheduleDesc;
      organisationList[id].scheduleDescriptions = scheduleDesc;
    }
  }

  /**
   * Fetch organisations.
   *
   * @param {object}   searchParams   Parameters to form the search query
   *                                  - target: page
   *                                  - buildings: list of buildings separated by a comma
   * @param {function} callback       Callback function, function (response|false, callbackParams)
   * @returns void
   */
  function getOrganisations(searchParams, callback) {
    const parent = searchParams.parent;
    if (typeof parent === 'undefined') {
      return;
    }
    if (parent in organisationList) {
      callback(organisationList[parent]);
    }
    searchParams.action = 'consortium';
    query(searchParams, function onQueryDone(success, response) {
      if (!success) {
        callback(false);
        return;
      }
      var list = getField(response, 'list');
      $.each(list, function handleList(ind, obj) {
        organisationList[obj.id] = obj;
        organisationList[obj.id].details = {};
        cacheSchedules(obj.id, obj);
      });
      callback(response);
    });
  }

  function getInfo(id) {
    if (!(id in organisationList)) {
      return false;
    }
    return organisationList[id];
  }

  function getDetails(id) {
    if (!(id in organisationList)) {
      return false;
    }

    var data = organisationList[id];
    var details = {};
    var openNow = getField(data, 'openNow');
    if (openNow !== null) {
      details.openNow = openNow;
    }

    $(['name', 'email', 'homepage', 'routeUrl', 'mapUrl', 'openToday', 'buildingYear', 'openTimes', 'schedule-descriptions'])
      .each(function handleField(ind, field) {
        var val = getField(data, field);
        if (val) {
          details[field] = val;
        }
      });

    var address = '';
    var mailAddress = '';
    var street = getField(data.address, 'street');
    if (street) {
      address += street;
    }
    var zipcode = getField(data.address, 'zipcode');
    if (zipcode) {
      address += ', ' + zipcode;
    }
    var city = getField(data.address, 'city');
    if (city) {
      address += ' ' + city;
    }

    details.address = address;

    var mail = getField(data, 'mailAddress');
    if (mail) {
      var mailStreet = getField(mail, 'street');
      if (mailStreet) {
        mailAddress += mailStreet + '<br>';
      }
      var mailBoxnumber = getField(mail, 'boxNumber');
      if (mailBoxnumber) {
        mailAddress += VuFind.translate('organisation_info_box_number') + ' ' + mailBoxnumber + '<br>';
      }
      var mailZipcode = getField(mail, 'zipcode');
      if (mailZipcode) {
        mailAddress += mailZipcode;
      }
      var mailArea = getField(mail, 'area');
      if (mailArea) {
        mailAddress += ' ' + mailArea;
      }
      details.mailAddress = mailAddress;
    }

    var cached = getCachedDetails(id);
    if (cached) {
      details = $.extend(details, {details: cached});
    }
    return details;
  }

  function cacheDetails(id, details) {
    if (!('openTimes' in details) && 'openTimes' in organisationList[id]) {
      details.openTimes = organisationList[id].openTimes;
    }
    organisationList[id].details = details;
  }

  /**
   * Get building schedules.
   * @param {object} queryParams Query parameters used:
   *                             - Parent: Organisation parent id.
   *                             - Target: Target,
   *                             - id: Organisation id
   *                             - periodStart: Start date of period
   *                             - dir: period paging direction -1 | 1
   *                             - fullDetails: 0|1
   *                             - allServices: 0|1
   * @param {function} callback  Callback function (false | string, object)
   *
   * @returns void
   */
  function getSchedules(queryParams, callback) {
    const fullDetails = queryParams.fullDetails || 0;
    const id = queryParams.id || '';
    query(queryParams, function onQueryDone(success, obj) {
      if (!success) {
        callback(false);
        return;
      }
      if (fullDetails) {
        cacheDetails(id, obj);
      }
      cacheSchedules(id, obj);

      var result = {};
      $(['openTimes', 'scheduleDescriptions', 'periodStart', 'weekNum', 'currentWeek', 'phone', 'emails', 'links', 'facility-image', 'services', 'pictures', 'rss'])
        .each(function handleField(ind, field) {
          var val = getField(obj, field, id);
          if (val) {
            result[field] = val;
          }
        });

      callback(result);
    });
  }

  var my = {
    getOrganisations: getOrganisations,
    getInfo: getInfo,
    getDetails: getDetails,
    getSchedules: getSchedules
  };

  return my;
})();
