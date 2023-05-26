/*global VuFind, finna*/
finna.organisationInfoPage = (function finnaOrganisationInfoPage() {
  var updateURL = false;
  var parent = null;
  var holder = null;
  var service = null;
  var infoWidget = null;
  var organisationList = {};
  var map = null;
  var mapHolder = null;
  var consortiumInfo = false;
  var consortium = false;

  function err() {
    holder.find('.no-information').removeClass('hidden');
    holder.find('.organisation-info-page').html('');
  }

  function updateWindowHash(hash) {
    if (hash === window.location.hash) {
      // Set hash first to empty value, so that onhashchange is triggered when
      // the same menu item is re-selected.
      window.location.hash = '';
    }
    window.location.hash = hash;
  }

  function updateConsortiumNotification(data) {
    if ('consortium' in data) {
      if (data.consortium.finna.notification) {
        holder.find('.consortium-notification')
          .html(data.consortium.finna.notification).removeClass('hide');
      }
    }
  }

  function updateDefaultServicePoint(data) {
    if ('consortium' in data) {
      if ('finna' in data.consortium
          && 'service_point' in data.consortium.finna
          && data.consortium.finna.service_point !== null
          && window.location.hash === ''
      ) {
        window.location.hash = data.consortium.finna.service_point;
      } else if (window.location.hash === '' && data.list.length > 1) {
        $('.office.map-ui.map').removeClass('hidden');
        $('.map-control-buttons .show-map').addClass('toggled');
        map.resize();
        map.reset();
      }
    }
  }

  function setOfficeInformationLoader(mode) {
    holder.find('.office-information-loader').toggle(mode);
  }

  function updateSelectedOrganisation(id, clearSearch) {
    setOfficeInformationLoader(true);
    holder.find('.error, .info-element').hide();
    holder.find('.more-less-btn-wrapper').remove();
    infoWidget.showDetails(id, '', true);
    if (clearSearch) {
      $('#office-search').val('');
    }

    var notification = holder.find('.office-search-notifications .notification');
    if (id in organisationList) {
      var data = organisationList[id];
      map.hideMarker();
      if ('address' in data && 'coordinates' in data.address) {
        map.selectMarker(id);
        notification.hide();
      } else {
        map.reset();
        notification.show().delay(2000).fadeOut(500);
        setOfficeInformationLoader(false);
      }
      return;
    } else {
      setOfficeInformationLoader(false);
    }
  }

  function initMap() {
    $.each(organisationList, function handleOrganisation(ind, obj) {
      // Map data (info bubble, icon)
      var bubble = holder.find('.map-bubble-template').clone();
      bubble.find('.name').text(obj.name);
      var openNow = null;
      if ('openNow' in obj) {
        openNow = obj.openNow;
      }

      if ('openTimes' in obj && obj.openTimes.schedules.length) {
        var scheduleTable = bubble.find('table');
        scheduleTable.find('tr').not('.template').remove();
        $.each(obj.openTimes.schedules, function handleOpenTimes(tind, scheduleObj) {
          var tr;
          if (!('closed' in scheduleObj)) { //scheduleObj['times'].length) {
            var timeObj = scheduleObj.times[0];
            tr = scheduleTable.find('tr:first-child').clone();
            tr.removeClass('template hide');
            if ('today' in scheduleObj) {
              tr.addClass(openNow ? 'open' : 'closed');
            }
            tr.find('.day').text(scheduleObj.day);
            tr.find('.opens').text(timeObj.opens);
            tr.find('.closes').text(timeObj.closes);
            scheduleTable.find('tbody').append(tr);
          } else {
            tr = scheduleTable.find('tr:first-child').clone();
            tr.removeClass('template hide');
            if ('today' in scheduleObj) {
              tr.addClass('today');
            }
            tr.find('.day').text(scheduleObj.day);
            tr.find('.time').hide();
            tr.find('.time.closed-today').show().removeClass('hide');
            scheduleTable.find('tbody').append(tr);
          }
        });
      }

      obj.map = {info: bubble.html()};
    });

    var defaultId = Object.keys(organisationList)[0];
    map.draw(organisationList, defaultId);

    // Expand map
    holder.find('.expand-map').on('click', function onClickExpandMap() {
      mapHolder.toggleClass('expand', true);
      map.resize();
      $(this).hide();
      holder.find('.contract-map').show();
    });
    holder.find('.contract-map').click (function onClickContractMap() {
      mapHolder.toggleClass('expand', false);
      map.resize();
      $(this).hide();
      holder.find('.expand-map').show();
    });
  }

  function initSearch(list) {
    var count = Object.keys(list).length;
    var translation = VuFind.translate('organisationInfoAutocomplete').replace('%%count%%', count);

    $(function initSelect() {
      var select = document.querySelector('#office-search');
      var placeholder = document.createElement('option');
      select.append(placeholder);
      $.each(list, function addToSelect(id, el) {
        var option = document.createElement('option');
        option.appendChild(document.createTextNode(el.name));
        if (el.address && el.address.city) {
          option.appendChild(document.createTextNode(', ' + el.address.city));
        }
        option.value = document.createTextNode(el.id).nodeValue;
        select.append(option);
      });
      $(select).select2({
        placeholder: translation,
        allowClear: true
      }).on('select2:select', function updateHash(e) {
        updateWindowHash(encodeURIComponent(e.params.data.id || 'undefined'));
      });
    });
  }

  /**
   * Gets the organisation list.
   *
   * @param {string} buildings List of buildings separated by a comma
   * @param {string} orgId     Organisation ID
   * @param {string} sector    Sector to look for data i.e 'mus', 'lib'...
   */
  function loadOrganisationList(buildings, orgId, sector) {
    const searchParams = {
      target: 'page',
      parent: parent,
      buildings: buildings,
      sector: sector
    };
    service.getOrganisations(searchParams, function onGetOrganisation(response) {
      if (response) {
        var id = orgId;
        holder.find('.loading').toggleClass('loading', false);

        var cnt = 0;
        $.each(response.list, function countItem(ind, obj) {
          organisationList[obj.id] = obj;
          cnt++;
        });

        infoWidget.organisationListLoaded(response);
        if (cnt > 0) {
          initMap();
          holder.find('.office-quick-information').show();

          // if theres only one service point, hide searchbox and ignore initSearch
          if (cnt === 1) {
            holder.find('.office-search .searchbox-office,.show-all').hide().parent('.flex-item').hide();
            id = Object.keys(organisationList)[0];
          } else {
            // IE opens Delay initing autocomplete menu to prevent IE from opening it automatically at
            initSearch(response.list);
          }

          if (typeof id != 'undefined' && id) {
            updateSelectedOrganisation(id, true);
          }
        } else {
          holder.find('.map-ui').hide();
        }

        updateConsortiumNotification(response);
        if (consortiumInfo) {
          if (cnt > 0) {
            finna.organisationInfoPageConsortium.enableConsortiumNaviItem('service');
          }
          finna.organisationInfoPageConsortium.updateConsortiumInfo(response, organisationList);
          finna.organisationInfoPageConsortium.initConsortiumNavi();
        }
        updateDefaultServicePoint(response);
        updateURL = true;
      } else {
        err();
      }
    });
  }

  function hideMapMarker() {
    holder.find('#marker-tooltip').hide();
  }

  function updateGeneralInfo(data, rssAvailable) {
    holder.find('.office-quick-information').toggleClass('hide', false);
    var contactHolder = holder.find('.contact-details-' + (rssAvailable ? 'rss' : 'no-rss'));
    contactHolder.show();

    holder.find('.office-quick-information .service-title').text(data.name);
    if ('address' in data && data.address) {
      holder.find('.office-links.address').html(data.address);
      if (!data.details.museum) {
        var address = holder.find('.address-contact');
        address.show().find('> p').html(data.address);
      }
    }
    if ('mailAddress' in data && !data.details.museum) {
      var mailAddress = holder.find('.mail-address-contact');
      mailAddress.show().find('> p').html(data.mailAddress);
    }
    if ('email' in data) {
      var email = data.email;
      holder.find('.email').attr('href', 'mailto:' + email).show();
      holder.find('.email span.email').text(email.replace('@', '(at)'));
      if (!data.details.museum) {
        holder.find('.email-contact').show();
      }
    }

    if ('emails' in data.details) {
      holder.find('.email-contact .emails').html(data.details.emails);
    }

    if ('homepage' in data) {
      holder.find('.office-website > a').attr('href', data.homepage);
      holder.find('.office-website').show();
    }

    if (data.details.museum) {
      holder.find('.contact-info-header').hide();
    }

    if ('routeUrl' in data) {
      holder.find('.office-links.route').attr('href', data.routeUrl).show();
    }

    if ('slogan' in data.details) {
      holder.find('.office-description.slogan').text(data.details.slogan).show();
    }

    var longDesc = holder.find('.office-description.description-long');
    if ('description' in data.details) {
      longDesc.html(data.details.description).show();
    } else {
      longDesc.html('');
    }
    longDesc.removeAttr('style');
    longDesc.removeClass('truncate-done');

    if ('links' in data.details) {
      var links = data.details.links;
      if (links.length) {
        $.each(links, function handleSocialButton(ind, obj) {
          if (obj.name.includes('Facebook')) {
            var btn = holder.find('.social-button');
            btn.find('> a').attr('href', obj.url);
            btn.show();
          }
        });
      }
    }

    var timeOpen = holder.find('.time-open');
    timeOpen.find('.times').remove();
    var staffTimesElem = timeOpen.find('.staff-times');
    staffTimesElem.find('.shift').remove();
    var closedTimesElem = timeOpen.find('.closed-times');
    closedTimesElem.find('.gap').remove();
    if ('schedules' in data.openTimes) {
      $.each(data.openTimes.schedules, function handleSchedule(ind, obj) {
        if ('today' in obj && 'times' in obj && obj.times.length) {
          var firstOpenDateTime = null;
          var firstOpenTime = null;
          var lastCloseDateTime = null;
          var lastCloseTime = null;
          var selfServiceTimes = [];
          var staffTimes = [];
          var gaps = [];

          $.each(obj.times, function checkOpenTimes(tind, time) {
            if (null === firstOpenDateTime || time.opens_datetime < firstOpenDateTime) {
              firstOpenDateTime = time.opens_datetime;
              firstOpenTime = time.opens;
            }
            if (null === lastCloseDateTime || time.closes_datetime > lastCloseDateTime) {
              lastCloseDateTime = time.closes_datetime;
              lastCloseTime = time.closes;
            }
            if (time.closed) {
              gaps.push(time);
            } else if (time.selfservice) {
              selfServiceTimes.push(time);
            } else {
              staffTimes.push(time);
            }
          });

          var item = timeOpen.find('.times-template').clone().addClass('times').removeClass('times-template hide');
          item.find('.opens').text(firstOpenTime);
          item.find('.closes').text(lastCloseTime);
          timeOpen.find('.times-template').before(item);
          timeOpen.show();

          if (selfServiceTimes.length > 0) {
            staffTimesElem.removeClass('hide');
            for (let i = 0; i < staffTimes.length; i++) {
              let schedule = staffTimes[i];
              let shift = staffTimesElem.find('.shift-template').clone().addClass('shift').removeClass('shift-template hide');
              shift.find('.opens').text(schedule.opens);
              shift.find('.closes').text(schedule.closes);
              if (i > 1) {
                shift.prepend(', ');
              }
              staffTimesElem.find('.shift-template').before(shift);
            }
          } else {
            staffTimesElem.addClass('hide');
          }

          if (gaps.length > 0) {
            closedTimesElem.removeClass('hide');
            for (let i = 0; i < gaps.length; i++) {
              let schedule = gaps[i];
              let gapElem = timeOpen.find('.gap-template').clone().addClass('gap').removeClass('gap-template hide');
              gapElem.find('.opens').text(schedule.opens);
              gapElem.find('.closes').text(schedule.closes);
              if (i > 1) {
                gapElem.prepend(', ');
              }
              closedTimesElem.find('.gap-template').before(gapElem);
            }
          } else {
            closedTimesElem.addClass('hide');
          }
        }
      });
    }

    var hasSchedules = 'openTimes' in data && 'schedules' in data.openTimes && data.openTimes.schedules.length > 0;
    var schedules = data.openTimes.schedules;
    var isClosedForWeek = schedules.every(schedule => schedule.closed === true);
    var hasScheduleDescriptions = 'scheduleDescriptions' in data.details
      ? data.details.scheduleDescriptions.every(description => description !== '')
      : false;

    if (isClosedForWeek && !hasScheduleDescriptions) {
      holder.find('.open-or-closed').hide();
    }
    else if (hasSchedules) {
      holder.find('.open-or-closed').toggleClass('hidden', null === data.openNow);
      holder.find('.open-or-closed > span.library-is-' + (data.openNow ? 'open' : 'closed')).show();
    }

    var img = holder.find('.building-image');
    if ('pictures' in data.details) {
      var src = data.details.pictures[0].url;
      img.show();
      if (img.attr('src') !== src) {
        img.attr('src', src);
        img.fadeTo(0, 0);
        img.on('load', function onLoadImage() {
          $(this).stop(true, true).fadeTo(300, 1);
        });
      } else {
        img.fadeTo(300, 1);
      }
      holder.find('.building-name').text(data.name).show();
    } else {
      img.hide();
    }

    if (data.details.museum) {
      holder.find('.extra-image').attr('src', data.details.pictures[1].url);
      holder.find('.extra-image-2').attr('src', data.details.pictures[2].url);
    } else {
      holder.find('.extra-image').hide();
      holder.find('.extra-image-2').hide();
    }

    if ('buildingYear' in data.details && data.details.buildingYear) {
      var year = holder.find('.building-year');
      year.find('> span').text(data.details.buildingYear);
      year.show();
    }

    if ('phone' in data.details && data.details.phone) {
      var phones = holder.find('.phone-numbers');
      phones.find('> p').html(data.details.phone);
      phones.show();
    }
    if ('contactInfo' in data.details && data.details.contactInfo) {
      var contactInfo = holder.find('.phone-numbers');
      contactInfo.find('> p').html(data.details.contactInfo);
      contactInfo.show();
    }
    if ('accessibilityInfo' in data.details && data.details.accessibilityInfo) {
      let template = document.getElementById('accessibility_info_template');
      let blocks = [];
      data.details.accessibilityInfo.forEach((info) => {
        let block = template.content.cloneNode(true);
        block.querySelector('.accessibility-group-heading').textContent = info.heading;
        let statements = block.querySelector('.accessibility-group-statements');
        info.statements.forEach((statement) => {
          let p = document.createElement('p');
          p.textContent = statement;
          statements.appendChild(p);
        });
        blocks.push(block);
      });
      if (blocks.length > 0) {
        let accessibilityDetails = holder.find('.accessibility-details');
        accessibilityDetails.find('.panel-body').append(blocks);
        accessibilityDetails.show();
      }
    }

    $('.office-quick-information').show();
    $('.office-information').show();
    VuFind.truncate.initTruncate(longDesc);
    setOfficeInformationLoader(false);
  }

  function updateServices(data) {
    if ('allServices' in data.details) {
      holder.find('.services').show();
      $('.service-header').addClass('hidden');
      $('.service-list').empty();
      var allServices = data.details.allServices;
      $.each(allServices, function handleService(ind, obj) {
        var serviceHolder = holder.find('.service-list.' + ind).empty();
        holder.find($('.service-header.' + ind)).removeClass('hidden');
        $.each(obj, function handleGrouping(group, services) {
          var div = $('<div/>');
          var serviceText = '';
          var serviceTitle = '<b>' + services[0] + '</b>';
          if (typeof services.desc !== 'undefined' || typeof services.shortDesc !== 'undefined') {
            serviceText = $('<button type="button" class="service-tooltip" data-toggle="tooltip" data-placement="bottom" data-html="true" />').html(serviceTitle);
            var serviceDesc = '';
            if (typeof services.desc !== 'undefined') {
              serviceDesc = services.desc;
            } else {
              serviceDesc = services.shortDesc;
            }
            var serviceToolTip = '<h4>' + services[0] + '</h4>' + serviceDesc;
            serviceText.attr('data-original-title', serviceToolTip);
          } else {
            serviceText = serviceTitle;
          }
          div.append(serviceText);
          div.appendTo(serviceHolder);
        });
      });
      finna.layout.initToolTips(holder);
    }
  }

  function rot13(string) {
    return string.replace(/[a-zA-Z]/g, function rot13replace(c) {
      // eslint-disable-next-line no-param-reassign
      return String.fromCharCode((c <= 'Z' ? 90 : 122) >= (c = c.charCodeAt(0) + 13) ? c : c - 26);
    });
  }

  function decryptEmailAddress(string) {
    let decrypted = rot13(string);
    decrypted = decrypted.replace(/\/dot\//g, '.');
    decrypted = decrypted.replace(/\/at\//g, '@');
    return decrypted;
  }

  function updatePersonnel(data) {
    if (data.details.personnel) {
      holder.find('.personnel').show();
      let personnelContainer = $('.personnel-container').empty();
      data.details.personnel.forEach(function handlePerson(person) {
        let tr = document.createElement('tr');
        let td = document.createElement('td');
        td.append(`${person.firstName || ''} ${person.lastName || ''}`);
        let span = document.createElement('span');
        span.append(document.createElement('br'));
        span.setAttribute('class', 'job-title');
        span.append(`${person.jobTitle || ''}`);
        td.append(span);
        tr.append(td);
        td = document.createElement('td');
        td.append(`${person.jobTitle || ''}`);
        tr.append(td);
        td = document.createElement('td');
        td.append(document.createComment('noindex'));
        td.append(document.createComment('googleoff: all'));
        if (person.email && person.email !== '') {
          let div = document.createElement('div');
          div.append(decryptEmailAddress(person.email));
          td.append(div);
        }
        if (person.phone && person.phone !== '') {
          let div = document.createElement('div');
          div.append(person.phone);
          td.append(div);
        }
        td.append(document.createComment('googleon: all'));
        td.append(document.createComment('/noindex'));
        tr.append(td);
        personnelContainer.append(tr);
      });
    }
  }

  function updateRSSFeeds(data) {
    var rssAvailable = false;
    if ('rss' in data.details) {
      $(data.details.rss).each(function handleRSSFeed(ind, obj) {
        if (obj.feedType !== 'news' && obj.feedType !== 'events') {
          return false;
        }
        const feedElement = document.createElement('finna-feed');
        feedElement.feedId = 'organisation-info|' + obj.parent + '|' + obj.id + '|' + obj.orgType + '|' + obj.feedType;
        feedElement.classList.add('feed-container', `${obj.feedType}-feed`);
        var feedHolder = holder.find('.feed-container.' + obj.feedType + '-feed');
        feedHolder
          .empty()
          .closest('.rss-container')
          .show()
          .find('.feed-container')
          .replaceWith(feedElement);
        // Use dataset to avoid jQuery caching issues:
        rssAvailable = true;
      });
    }
    return rssAvailable;
  }

  function getOrganisationFromURL() {
    if (window.location.hash !== '') {
      return parseInt(window.location.hash.replace('#', ''));
    }
    return false;
  }

  /**
   * Initialize organisation page
   *
   * @param options Array of options:
   *   library        int    Organisation id
   *   buildings      string Comma separated list of buildings to show on map
   *   consortiumInfo 0|1    Show consortium info?
   */
  function init(options) {
    holder = $('section[role="main"]');

    setOfficeInformationLoader(false);

    parent = options.id || null;
    consortiumInfo = (options.consortiumInfo || null) === 1;
    var buildings = options.buildings;
    var sector = options.sector;
    var mapTileUrl = 'https://map-api.finna.fi/v1/rendered/{z}/{x}/{y}.png';
    var attribution =
      '<i class="fa fa-map-marker marker open"></i><span class="map-marker-text">' + VuFind.translate('organisation_info_is_open') + '</span>' +
      '<i class="fa fa-map-marker marker closed"></i><span class="map-marker-text">' + VuFind.translate('organisation_info_is_closed') + '</span>' +
      '<i class="fa fa-map-marker marker no-schedule"></i><span class="map-marker-text">' + VuFind.translate('organisation_info_no_schedule') + '</span>' +
      '<span class="expand expand-map map-marker-text marker"><i class="fa fa-expand"></i>' + VuFind.translate('organisation_info_map_expand') + '</span>' +
      '<span class="collapse contract-map map-marker-text marker" style="display: none"> <i class="fa fa-condense"></i>' + VuFind.translate('organisation_info_map_collapse') + '</span>';

    if (typeof parent == 'undefined') {
      return;
    }

    mapHolder = holder.find('.map-widget');
    map = finna.organisationMap;
    map.init(mapHolder[0], mapTileUrl, attribution);

    $(map).on('marker-click', function onClickMarker(ev, id) {
      if (updateURL) {
        window.location.hash = id;
      }
      hideMapMarker();
    });

    $(map).on('marker-mouseout', function onMouseOutMarker(/*ev*/) {
      hideMapMarker();
    });

    $(map).on('marker-mouseover', function onMouseOverMarker(ev, data) {
      var tooltip = holder.find('#marker-tooltip');
      var name = organisationList[data.id].name;
      tooltip.removeClass('hide').html(name).css({
        'left': data.x,
        'top': data.y - 35
      });
      tooltip.css({'margin-left': -(tooltip.outerWidth()) / 2 + 20}).show();
    });

    holder.find('.map-control-buttons .show-map').on('click', function onClickShowMap() {
      mapHolder = $('.office.map-ui.map');
      if (mapHolder.hasClass('hidden')) {
        mapHolder.removeClass('hidden');
        holder.find('.map-controls').removeClass('hidden');
        $(this).addClass('toggled');
        map.resize();
        map.reset();
        var id = getOrganisationFromURL();
        if (id in organisationList) {
          var data = organisationList[id];
          if ('address' in data && 'coordinates' in data.address) {
            map.selectMarker(id);
          }
        }
      } else {
        mapHolder.addClass('hidden');
        holder.find('.map-controls').addClass('hidden');
        $(this).removeClass('toggled');
      }
      return false;
    });

    holder.find('.map-control-buttons .show-service-point').on('click', function onClickShowServicePoint() {
      var id = getOrganisationFromURL();
      if (id in organisationList) {
        var data = organisationList[id];
        if ('address' in data && 'coordinates' in data.address) {
          map.reset();
          map.selectMarker(id);
        }
      }
      return false;
    });

    holder.find('.map-control-buttons .show-all').on('click', function onClickShowAll() {
      mapHolder = $('.office.map-ui.map');
      if (mapHolder.hasClass('hidden')) {
        mapHolder.removeClass('hidden');
        $('.map-control-buttons .show-map').addClass('toggled');
      }
      map.resize();
      map.reset();
      return false;
    });

    service = finna.organisationInfo;
    infoWidget = finna.organisationInfoWidget;

    var widgetHolder = holder.find('.organisation-info');
    widgetHolder.on('detailsLoaded', function onDetailsLoaded(ev, id) {
      var info = service.getDetails(id);
      updateServices(info);
      updatePersonnel(info);
      var rssAvailable = updateRSSFeeds(info);
      updateGeneralInfo(info, rssAvailable);
    });

    infoWidget.init(widgetHolder, service);

    if (consortiumInfo) {
      consortium = finna.organisationInfoPageConsortium;
      consortium.init(parent, holder);
    }

    window.onhashchange = function onHashChange() {
      var id = getOrganisationFromURL();
      if (id) {
        updateSelectedOrganisation(id, false);
      }
    };

    var library = null;
    var hash = getOrganisationFromURL();
    if (hash) {
      library = hash;
    }
    loadOrganisationList(buildings, library, sector);
  }

  var my = {
    init: init
  };

  return my;

})();
