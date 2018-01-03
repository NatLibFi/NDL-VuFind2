/*global VuFind, finna */
finna.MapFacet = (function finnaStreetMap() {

  function initMap(mapCanvas, _options){
    if (mapCanvas.length === 0) {
      return;
    }

    L.drawLocal.draw.handlers.circle.tooltip.start = '';
    L.drawLocal.draw.handlers.simpleshape.tooltip.end = '';
    L.drawLocal.draw.handlers.circle.radius = VuFind.translate('radiusPrefix');

    var defaults = {
      tileLayer: L.tileLayer('//map-api.finna.fi/v1/rendered/{z}/{x}/{y}.png', {
        zoom: 10,
        tileSize: 256,
        attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>'
      }),
      center: new L.LatLng(64.8, 26),
      zoom: 8,
      items: []
    };
    var options = $.extend(defaults, _options);

    var drawnItems = new L.FeatureGroup();
    $.each(options.items, function drawItem(idx, item) {
      var matches = item.match(/pt=([\d.]+),([\d.]+) d=([\d.]+)/);
      if (matches) {
        var circle = new L.Circle([matches[1], matches[2]], matches[3] * 1000);
        drawnItems.addLayer(circle);
      }
    });

    var map = new L.Map(mapCanvas.get(0), {
      attributionControl: false,
      layers: [options.tileLayer, drawnItems],
      center: options.center,
      zoom: options.zoom,
      zoomControl: false
    });

    finna.layout.initMap(map);

    if (options.items.length > 0) {
      var onLoad = function tileLayerOnLoad() {
        var bounds = drawnItems.getBounds();
        fitZoom = map.getBoundsZoom(bounds);
        map.fitBounds(bounds, fitZoom);
        options.tileLayer.off('load', onLoad);
      };
      options.tileLayer.on('load', onLoad);
    }
    return map;
  }

  function initFacetMap(options){
    initMap($(".map"), options);
  }

  function initModalMap(options) {
    function closeModalCallback(modal) {
      modal.removeClass('location-service location-service-qrcode');
      modal.find('.modal-dialog').removeClass('modal-lg');
    }
    var modal = $('#modal');
    modal.one('hidden.bs.modal', function onHiddenModal() {
      closeModalCallback($(this));
    });
    modal.find('.modal-dialog').addClass('modal-lg');
    initMap($(".modal-map"), options)
  }

  var my = {
    initFacetMap: initFacetMap,
    initModalMap: initModalMap
  };

  return my;
})();
