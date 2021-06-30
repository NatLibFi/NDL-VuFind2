/* global finna, THREE, VuFind*/

// Use 1 dracoloader in all of the loaders, so we don't create multiple instances
var dracoLoader;

// Cache for holdings already loaded scenes, prevent multiple loads
var sceneCache = {};

/**
 * Check if fullscreen is supported
 */
function fullscreenSupported() {
  return (document.exitFullscreen || document.webkitExitFullscreen ||
    document.mozCancelFullScreen || document.webkitCancelFullScreen ||
    document.msExitFullscreen);
}

/**
 * Create a id for caching loaded scenes
 * 
 * @param {Object} loadInfo 
 */
function getCacheID(loadInfo) {
  return loadInfo.id + '-' + loadInfo.index + '-' + loadInfo.format;
}

/**
 * Get tangent
 * 
 * @param {Integer} deg 
 */
function getTanDeg(deg) {
  var rad = deg * Math.PI / 180;
  return Math.tan(rad);
}

/**
 * Constructor
 * 
 * @param {HtmlElement} trigger 
 * @param {Object} options 
 * @param {Object} scripts 
 */
function ModelViewer(trigger, options, scripts)
{
  var _ = this;
  _.trigger = $(trigger);
  _.texturePath = options.texturePath;
  if (typeof options.popup === 'undefined' || options.popup === false) {
    _.inlineId = 'inline-viewer';
  }
  _.debug = options.debug || false;
  _.ambientIntensity = +options.ambientIntensity || 1;
  _.hemisphereIntensity = +options.hemisphereIntensity || 0.3;
  _.viewerPaddingAngle = +options.viewerPaddingAngle || 35;

  _.loadInfo = options.modelload;
  _.loaded = false;
  _.isFileInput = _.trigger.is('input');
  _.createTrigger(options, scripts);
}

/**
 * Create a trigger for model viewer
 * 
 * @param {Object} options
 * @param {Object} scripts
 */
ModelViewer.prototype.createTrigger = function createTrigger(options, scripts) {
  var _ = this;
  var modal = $('#model-modal').find('.model-wrapper').first().clone();
  _.trigger.finnaPopup({
    id: 'modelViewer',
    cycle: false,
    parent: _.inlineId || undefined,
    overrideEvents: _.isFileInput ? 'change' : undefined,
    classes: 'model-viewer',
    translations: options.translations,
    modal: modal,
    beforeOpen: function onBeforeOpen() {
      var popup = this;
      $.fn.finnaPopup.closeOpen(popup.id);
      if (_.inlineId) {
        _.trigger.trigger('viewer-show');
      }
    },
    onPopupOpen: function onPopupOpen() {
      var popup = this;
      finna.layout.loadScripts(scripts, function onScriptsLoaded() {
        if (!_.root) {
          // Lets create required html elements
          _.canvasParent = popup.content.find('.model-canvas-wrapper');
          _.informations = {};
          _.root = popup.content.find('.model-viewer');
          _.controlsArea = _.root.find('.viewer-controls');
          _.optionsArea = _.root.find('.viewer-options');
          _.optionsArea.toggle(false);
          _.fullscreen = _.controlsArea.find('.model-fullscreen');
          _.viewerStateInfo = _.root.find('.viewer-state-wrapper');
          _.viewerStateInfo.hide();
          _.viewerStateInfo.html('0%');
          _.informationsArea = _.root.find('.statistics-table');
          _.root.find('.model-stats').attr('id', 'model-stats');
          _.root.find('.model-help').attr('id', 'model-help');
          _.informationsArea.toggle(false);
          _.trigger.addClass('open');
        }
        
        _.root.find('.model-help').html(
          VuFind.translate(finna.layout.isTouchDevice() ? 'model_help_mobile_html' : 'model_help_pc_html')
        );
        if (!_.isFileInput) {
          _.loadBackground();
        } else {
          _.modelPath = URL.createObjectURL(_.trigger[0].files[0]);
          _.loadBackground();
        }
      });
    },
    onPopupClose: function onPopupClose() {
      if (_.loop) {
        window.clearTimeout(_.loop);
        _.loop = null;
      }
      _.trigger.removeClass('open');
      _.root = null;
      _.renderer = null;
      _.canvasParent = null;
      _.informations = {};
      _.controlsArea = null;
      _.optionsArea = null;
      _.fullscreen = null;
      _.viewerStateInfo = null;
      _.informationsArea = null;
    }
  });
};

/**
 * Append information to table
 * 
 * @param {String} header
 * @param {String} info
 */
ModelViewer.prototype.setInformation = function setInformation(header, info)
{
  var _ = this;
  _.informationsArea.append('<tr><td class="model-header">' + header + '</td><td class="model-value">' + info + '</td></tr>');
};

/**
 * Set events for viewer
 */
ModelViewer.prototype.setEvents = function setEvents()
{
  var _ = this;
  var fullscreenEvents = 'fullscreenchange.finna mozfullscreenchange.finna webkitfullscreenchange.finna';
  $(window).off('resize').on('resize', function setNewScale() {
    if (typeof _.camera === 'undefined') {
      return;
    }
    _.updateScale();
  });

  $(document).off(fullscreenEvents).on(fullscreenEvents, function onScreenChange() {
    _.root.toggleClass('fullscreen', !_.root.hasClass('fullscreen'));
    _.updateScale();
  });

  _.fullscreen.off('click').on('click', function setFullscreen() {
    if (_.root.hasClass('fullscreen')) {
      if (document.exitFullscreen) {
        document.exitFullscreen();
      } else if (document.mozCancelFullScreen) { /* Firefox */
        document.mozCancelFullScreen();
      } else if (document.webkitExitFullscreen) { /* Chrome, Safari and Opera */
        document.webkitExitFullscreen();
      } else if (document.msExitFullscreen) { /* IE/Edge */
        document.msExitFullscreen();
      }
    } else {
      var elem = _.root[0];
      if (elem.requestFullscreen) {
        elem.requestFullscreen();
      } else if (elem.mozRequestFullScreen) { /* Firefox */
        elem.mozRequestFullScreen();
      } else if (elem.webkitRequestFullscreen) { /* Chrome, Safari and Opera */
        elem.webkitRequestFullscreen();
      } else if (elem.msRequestFullscreen) { /* IE/Edge */
        elem.msRequestFullscreen();
      } else if (elem.webkitEnterFullscreen) {
        elem.webkitEnterFullscreen(); // Iphone?!
      }
    }
  });
};

/**
 * Update renderer size to match parent size
 */
ModelViewer.prototype.updateScale = function updateScale()
{
  var _ = this;

  _.getParentSize();
  _.camera.aspect = _.size.x / _.size.y;
  _.camera.updateProjectionMatrix();
  _.renderer.setSize(_.size.x, _.size.y);
};

/**
 * Update internal value for parent size
 */
ModelViewer.prototype.getParentSize = function getParentSize()
{
  var _ = this;
  _.size = {
    x: _.root.width(),
    y: _.inlineId && !_.root.hasClass('fullscreen') ? _.root.width() : _.root.height()
  };
};

/**
 * Create a renderer for viewer
 */
ModelViewer.prototype.createRenderer = function createRenderer()
{
  var _ = this;
  _.getParentSize();
  _.renderer = new THREE.WebGLRenderer({
    antialias: true
  });
  // These are the settings to make glb files look good with threejs
  _.renderer.physicallyCorrectLights = true;
  _.renderer.gammaOutput = true;
  _.renderer.gammaInput = true;
  _.renderer.gammaFactor = 2.2;
  _.renderer.toneMapping = THREE.ReinhardToneMapping;
  _.encoding = THREE.sRGBEncoding;
  _.renderer.outputEncoding = _.encoding;
  
  _.renderer.shadowMap.enabled = true;
  _.renderer.setClearColor(0x000000);
  _.renderer.setPixelRatio(window.devicePixelRatio);
  _.renderer.setSize(_.size.x, _.size.y);
  _.canvasParent.append(_.renderer.domElement);

  if (!_.loaded) {
    // Create camera now.
    _.camera = new THREE.PerspectiveCamera(50, _.size.x / _.size.y, 0.1, 1000);
    _.cameraPosition = new THREE.Vector3(0, 0, 0);
    _.camera.position.set(_.cameraPosition.x, _.cameraPosition.y, _.cameraPosition.z);
  }
  _.animationLoop();
  if (!_.loaded) {
    _.viewerStateInfo.show();
  }
};

/**
 * Get model location for viewer
 */
ModelViewer.prototype.getModelPath = function getModelPath()
{
  var _ = this;
  _.viewerStateInfo.html(VuFind.translate('model_loading_file'));
  $.getJSON(
    VuFind.path + '/AJAX/JSON',
    {
      method: 'getModel',
      id: _.loadInfo.id,
      index: _.loadInfo.index,
      format: _.loadInfo.format,
      source: _.loadInfo.source
    }
  )
    .done(function onGetModelDone(response) {
      _.modelPath = response.data.url;
      _.loadGLTF();
      _.setEvents();
    })
    .fail(function onGetModelFailed(/*response*/) {
    });
};

/**
 * Load a gltf file for viewer
 */
ModelViewer.prototype.loadGLTF = function loadGLTF()
{
  var _ = this;

  if (!_.loaded) {
    var loader = new THREE.GLTFLoader();
    if (typeof dracoLoader === 'undefined') {
      dracoLoader = new THREE.DRACOLoader();
      dracoLoader.setDecoderPath(VuFind.path + '/themes/finna2/js/vendor/draco/');
    }
    loader.setDRACOLoader(dracoLoader);
    loader.load(
      _.modelPath,
      function onLoad (obj) {
        _.adjustScene(obj.scene);
        _.createControls();
        _.initMesh();
        _.viewerStateInfo.hide();
        _.optionsArea.toggle(true);
        if (!fullscreenSupported()) {
          _.optionsArea.find('.model-fullscreen').hide();
        }
        _.displayInformation();
      },
      function onLoading(xhr) {
        if (_.viewerStateInfo) {
          var loaded = '';
          if (xhr.total < 1) {
            loaded = (xhr.loaded / 1024 / 1024).toFixed(0) + 'MB';
          } else {
            loaded = (xhr.loaded / xhr.total * 100).toFixed(0) + '%';
          }
          _.viewerStateInfo.html(loaded);
        }
      },
      function onError(/*error*/) {
        if (_.viewerStateInfo) {
          _.viewerStateInfo.html('Error');
        }
      }
    );
  }
};

/**
 * Show needed information
 */
ModelViewer.prototype.displayInformation = function displayInformation() {
  var _ = this;
  _.informationsArea.toggle(true);
  _.informationsArea.append(
    '<tr><th class="text-center" colspan="2">' + VuFind.translate('Information of model') + '</th></tr>'
  );
  _.setInformation(VuFind.translate('Vertices'), _.vertices);
  _.setInformation(VuFind.translate('Triangles'), _.triangles);
};

/**
 * Adjust scene
 * 
 * @param {Object} scene
 */
ModelViewer.prototype.adjustScene = function adjustScene(scene)
{
  var _ = this;

  if (_.loaded) {
    return;
  }

  _.scene = scene;
  _.scene.background = _.background;
  if (_.debug) {
    var axesHelper = new THREE.AxesHelper( 5 );
    _.scene.add( axesHelper );
  }
  _.createLights();
};

/**
 * Create the animation loop for the viewer
 */
ModelViewer.prototype.animationLoop = function animationLoop()
{
  var _ = this;

  // Animation loop, required for constant updating
  _.loop = function animate() {
    if (_.renderer) {
      _.renderer.render(_.scene, _.camera);
      requestAnimationFrame(animate);
    }
  };

  window.setTimeout(_.loop, 1000 / 30);
};

/**
 * Create controls for the viewer
 */
ModelViewer.prototype.createControls = function createControls()
{
  var _ = this;
  // Basic controls for scene, imagine being a satellite at the sky
  _.controls = new THREE.OrbitControls(_.camera, _.renderer.domElement);

  // Should be THREE.Vector3(0,0,0)
  _.controls.target = new THREE.Vector3();
  _.controls.screenSpacePanning = true;
  _.controls.update();
};

/**
 * Adjust mesh so it looks better in the viewer
 */
ModelViewer.prototype.initMesh = function initMesh()
{
  var _ = this;
  var meshMaterial;
  var newBox = new THREE.Box3();
  if (!_.loaded) {
    _.vertices = 0;
    _.triangles = 0;
    _.meshes = 0;
    _.scene.traverse(function traverseMeshes(obj) {
      if (obj.type === 'Mesh') {
        _.meshes++;
        meshMaterial = obj.material;
  
        // Bumpscale and depthwrite settings
        meshMaterial.depthWrite = !meshMaterial.transparent;
        meshMaterial.bumpScale = 0;
  
        // Apply encodings so glb looks better and update it if needed
        if (meshMaterial.map) meshMaterial.map.encoding = _.encoding;
        if (meshMaterial.emissiveMap) meshMaterial.emissiveMap.encoding = _.encoding;
        if (meshMaterial.normalMap) meshMaterial.normalMap.encoding = _.encoding;
        if (meshMaterial.map || meshMaterial.emissiveMap || meshMaterial.normalMap) meshMaterial.needsUpdate = true;
  
        // Lets get available information about the model here so we can show them properly in information screen
        var geo = obj.geometry;
        if (typeof geo.isBufferGeometry !== 'undefined' && geo.isBufferGeometry) {
          _.vertices += +geo.attributes.position.count;
          _.triangles += +geo.index.count / 3;
        }
        newBox.expandByObject(obj);
  
        if (_.debug) {
          var box = new THREE.BoxHelper( obj, 0xffff00 );
          _.scene.add( box );
        }
      }
    });
    // Next part gets the center vector, so we can move it properly towards 0
    var newCenterVector = new THREE.Vector3();
    newBox.getCenter(newCenterVector);
    newCenterVector.negate();
    _.scene.traverse(function centerObjects(obj) {
      if (obj.type === 'Mesh') {
        obj.position.x += newCenterVector.x;
        obj.position.y += newCenterVector.y;
        obj.position.z += newCenterVector.z;
      }
    });

    // Set camera and position to center from the newly created object
    var objectHeight = (newBox.max.y - newBox.min.y) * 1.01;
    var objectWidth = (newBox.max.x - newBox.min.x) * 1.01;
    var result = 0;
    if (objectHeight >= objectWidth) {
      result = objectHeight / getTanDeg(_.viewerPaddingAngle);
    } else {
      result = objectWidth / getTanDeg(_.viewerPaddingAngle);
    }
    _.cameraPosition = result;
    _.camera.position.set(0, 0, _.cameraPosition);
    _.loaded = true;
  } else {
    _.camera.position.set(0, 0, _.cameraPosition);
  }
};

/**
 * Create lights for the viewer
 */
ModelViewer.prototype.createLights = function createLights()
{
  var _ = this;
  
  // Ambient light basically just is there all the time
  var ambientLight = new THREE.AmbientLight(0xFFFFFF, _.ambientIntensity); // soft white light
  _.scene.add(ambientLight);
  var light = new THREE.HemisphereLight(0xffffbb, 0x080820, _.hemisphereIntensity);
  _.scene.add( light );
};

/**
 * Load the background image for the viewer
 */
ModelViewer.prototype.loadBackground = function loadBackground()
{
  var _ = this;
  var tempLoader = new THREE.TextureLoader();
  if (_.loaded) {
    _.createRenderer();
    _.viewerStateInfo.hide();
    _.createControls();
    _.optionsArea.toggle(true);
    if (!fullscreenSupported()) {
      _.optionsArea.find('.model-fullscreen').hide();
    }
    _.displayInformation();
    _.setEvents();
    return;
  }
  tempLoader.load(
    _.texturePath,
    function onSuccess(texture) {
      _.background = texture;
      _.scene = new THREE.Scene();
      _.scene.background = _.background;
      _.createRenderer();
      _.getModelPath();
    },
    function onFailure(/*error*/) {
      // Leave empty for debugging purposes
    }
  );
};

(function modelModule($) {
  $.fn.finnaModel = function finnaModel(settings, scripts) {
    // Check if model viewer is already created
    var cacheId = getCacheID(settings.modelload);
    if (typeof sceneCache[cacheId] === 'undefined') {
      sceneCache[cacheId] = new ModelViewer($(this), settings, scripts);
    } else {
      sceneCache[cacheId].createTrigger(settings, scripts);
    }
  };
})(jQuery);
