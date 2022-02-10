/* global finna, THREE, VuFind*/

// Use 1 dracoloader in all of the loaders, so we don't create multiple instances
var dracoLoader;

// Cache for holdings already loaded scenes, prevent multiple loads
var sceneCache = {};

var lightTypeMappings = [
  {name: 'SpotLight', value: 'SpotLight'},
  {name: 'DirectionalLight', value: 'DirectionalLight'},
];

// Boolean options
var booleanOptions = [
  {value: 'true', name: 'true'},
  {value: 'false', name: 'false'}
];

// Allowed properties to display in viewer menu
var allowedProperties = {
  advanced: [
    'name', 'type', 'position', 'color', 'groundColor',
    'intensity', 'roughness', 'clipIntersection', 'clipShadows',
    'depthWrite', 'dithering', 'emissive', 'emissiveIntensity',
    'flatShading', 'metalness', 'morphNormals', 'morphTargets',
    'opacity', 'premultipliedAlpha', 'roughness', 'side', 'toneMapped',
    'transparent', 'visible', 'wireframe', 'wireframeLinewidth', 'gammaFactor',
    'physicallyCorrectLights', 'outputEncoding',
    'shininess', 'quaternion', 'texts', 'renderOrder', 'scale', 'clearcoat',
    'clearcoatRoughness', 'normalScale', 'ior', 'sheen', 'sheenRoughness', 'sheenColor',
    'transmission', 'bumpScale', 'envMapIntensity'
  ],
  basic: [
    'name', 'intensity', 'roughness', 'metalness', 'envMapIntensity'
  ]
};

// Allowed subproperties to display in viewer menu
var allowedSubProperties = [
  'x', 'y', 'z', 'r', 'g', 'b', '_x', '_y', '_z', '_w', 'encoding', 'en', 'fi', 'sv'
];

// Inputs which are readonly
var readOnly = [
  'name',
  'type'
];

// Encoding types
var encodingTypes = [
  'encoding',
  'outputEncoding'
];

var rangeTypes = [
  'intensity', 'roughness', 'envMapIntensity', 'metalness'
];

// Keys for colors, requires creating three color
var colorKeys = [
  'color',
  'groundColor',
  'sheenColor'
];

// Creatable object types
var creatableObjects = [
  'DirectionalLight',
  'SpotLight',
  'Sprite'
];

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
  _.inlineId = 'inline-viewer';
  if (options.settings.length > 0) {
    _.defaultSettings = JSON.parse(options.settings);
  }
  _.viewerPaddingAngle = 35;
  _.lights = [];
  _.materials = [];
  _.meshes = [];
  _.cameras = [];
  _.renderers = [];
  _.scenes = [];
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
    id: options.idOverride || 'modelViewer',
    cycle: false,
    parent: _.isFileInput ? 'debugViewerArea' : _.inlineId,
    overrideEvents: _.isFileInput ? 'change' : undefined,
    classes: 'model-viewer',
    translations: options.translations,
    modal: modal,
    beforeOpen: function onBeforeOpen() {
      var popup = this;
      $.fn.finnaPopup.closeOpen(popup.id);
      _.trigger.trigger('viewer-show');
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
          _.root.find('.model-settings').attr('id', 'model-settings');
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
  if (options.fileInput && !_.inputViewerDone) {
    _.inputViewerDone = true;
    var selectFileDiv = document.createElement('div');
    selectFileDiv.classList.add('debug-model-viewer');
    var viewerHolderDiv = document.createElement('div');
    viewerHolderDiv.id = 'debugViewerArea';
    var fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.classList.add('model-viewer-input');
    selectFileDiv.append(viewerHolderDiv, fileInput);
    _.trigger.closest('.recordcover-holder').append(selectFileDiv);
    var copySettings = Object.assign({}, options);
    copySettings.idOverride = 'debugViewer';
    delete copySettings.fileInput;
    new ModelViewer(fileInput, copySettings, scripts);
  }
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
  _.renderers.push(_.renderer);
  // These are the settings to make glb files look good with threejs
  _.renderer.physicallyCorrectLights = true;
  _.renderer.toneMapping = THREE.ReinhardToneMapping;
  _.encoding = THREE.sRGBEncoding;
  _.renderer.outputEncoding = _.encoding;

  _.renderer.shadowMap.enabled = true;
  _.renderer.setClearColor(0xEEEEEE, 0);
  _.renderer.setPixelRatio(window.devicePixelRatio);
  _.renderer.setSize(_.size.x, _.size.y);
  _.canvasParent.append(_.renderer.domElement);
  _.renderer.domElement.setAttribute('draggable', 'false');
  _.renderer.name = 'main_renderer';
  if (!_.loaded) {
    // Create camera now.
    _.camera = new THREE.PerspectiveCamera(50, _.size.x / _.size.y, 0.1, 1000);
    _.camera.userData.viewerInitDone = true;
    _.cameras.push(_.camera);
    _.camera.name = 'main_camera';
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
  if (_.isFileInput && _.modelPath) {
    _.loadGLTF();
    _.setEvents();
    return;
  }
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
        _.initMesh(obj);
        _.createControls();
        _.createLights();
        _.initMenu();
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
      function onError(error) {
        if (_.viewerStateInfo) {
          _.viewerStateInfo.html(error);
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
 * Create the animation loop for the viewer
 */
ModelViewer.prototype.animationLoop = function animationLoop()
{
  var _ = this;

  // Animation loop, required for constant updating
  _.loop = function animate() {
    if (_.renderer) {
      if (_.controls) {
        _.controls.update();
      }
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
  _.camera.position.set(0, 0, 40);
  _.controls.screenSpacePanning = true;
  _.controls.update();
};

/**
 * Adjust mesh so it looks better in the viewer
 */
ModelViewer.prototype.initMesh = function initMesh(loadedObj)
{
  var _ = this;
  var meshMaterial;
  if (!_.loaded) {
    _.vertices = 0;
    _.triangles = 0;
    _.meshCount = 0;
    var newBox = new THREE.Box3();
    while (loadedObj.scene.children.length > 0) {
      var cur = loadedObj.scene.children[0];
      if (cur.type === 'Object3D') {
        _.scene.add(cur.children);
        loadedObj.scene.remove(cur);
      } else {
        _.scene.add(cur);
        if (cur.target) {
          _.scene.add(cur.target);
        }
      }
    }
    _.scene.traverse(function initObj(obj) {
      if (obj.type === 'Mesh') {
        meshMaterial = obj.material;
        meshMaterial.envMap = _.background;
        if (meshMaterial.userData.envMapIntensity) {
          meshMaterial.envMapIntensity = meshMaterial.userData.envMapIntensity;
        } else {
          meshMaterial.envMapIntensity = 0.2;
        }
        if (meshMaterial.userData.normalScale) {
          meshMaterial.normalScale.x = meshMaterial.userData.normalScale.x;
          meshMaterial.normalScale.y = meshMaterial.userData.normalScale.y;
        }
        if (!obj.userData.viewerSet) {
          meshMaterial.name = 'material_' + _.meshCount;
          // Apply encodings so glb looks better and update it if needed
          if (meshMaterial.map) meshMaterial.map.encoding = _.encoding;
          if (meshMaterial.emissiveMap) meshMaterial.emissiveMap.encoding = _.encoding;
          if (meshMaterial.normalMap) meshMaterial.normalMap.encoding = _.encoding;
        }
        if (meshMaterial.map || meshMaterial.envMap || meshMaterial.emissiveMap || meshMaterial.normalMap) meshMaterial.needsUpdate = true;
        _.meshCount++;
        _.meshes.push(obj);
        // Lets get available information about the model here so we can show them properly in information screen
        var geo = obj.geometry;
        if (geo.isBufferGeometry) {
          _.vertices += +geo.attributes.position.count;
          _.triangles += +geo.index.count / 3;
        }

        newBox.expandByObject(obj);
        _.materials.push(meshMaterial);
      } else {
        var isLight = lightTypeMappings.find(function checkLight(mapping) {
          return mapping.value === obj.type;
        });
        if (isLight) {
          var newPosVector = new THREE.Vector3(obj.position.x, obj.position.y, obj.position.z);
          obj.position = newPosVector;
          _.lights.push(obj);
        }
      }
    });
    var newCenterVector = new THREE.Vector3();
    newBox.getCenter(newCenterVector);
    newCenterVector.negate();
    _.scene.children.forEach(function tryToCenter(obj) {
      if (obj.userData.viewerSet) {
        return;
      }
      var isLight = lightTypeMappings.find(function checkLight(mapping) {
        return mapping.value === obj.type;
      });
      if (isLight || obj.type === 'Mesh') {
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
  }
};

/**
 * Create lights for the viewer
 */
ModelViewer.prototype.createLights = function createLights()
{
  var _ = this;
  if (_.lights.length > 0 || _.defaultSettings) {
    return;
  }
  var lightFront = new THREE.DirectionalLight(0xffffff, 1);
  lightFront.name = 'directional_finna_front';
  lightFront.userData.name = 'directional_finna_front';
  lightFront.position.set(0, 25, 25);
  lightFront.userData.viewerSet = true;
  var lightLeft = new THREE.DirectionalLight(0xffffff, 1);
  lightLeft.name = 'directional_finna_left';
  lightLeft.userData.name = 'directional_finna_left';
  lightLeft.position.set(-25, 25, 0);
  lightLeft.userData.viewerSet = true;
  var lightRight = new THREE.DirectionalLight(0xffffff, 1);
  lightRight.name = 'directional_finna_right';
  lightRight.userData.name = 'directional_finna_right';
  lightRight.position.set(25, 25, 0);
  lightRight.userData.viewerSet = true;
  var lightBack = new THREE.DirectionalLight(0xffffff, 1);
  lightBack.name = 'directional_finna_back';
  lightBack.userData.name = 'directional_finna_back';
  lightBack.position.set(0, 25, -25);
  lightBack.userData.viewerSet = true;

  _.lights.push(lightBack);
  _.lights.push(lightFront);
  _.lights.push(lightLeft);
  _.lights.push(lightRight);

  _.scene.add(lightFront);
  _.scene.add(lightBack);
  _.scene.add(lightLeft);
  _.scene.add(lightRight);
};

var createInput = function createInput(inputType, name, value) {
  var input = document.createElement('input');
  input.type = inputType;
  input.name = name;
  if ('range' === inputType) {
    input.min = 0;
    input.max = 1;
    input.step = 0.01;
  }
  input.value = value;
  return input;
};

var createSelect = function createSelect(options, name, selected) {
  var select = document.createElement('select');
  select.name = name; 
  for (var i = 0; i < options.length; i++) {
    var current = options[i];
    var option = document.createElement('option');
    option.value = current.value;
    option.innerHTML = current.name || current.value;
    select.append(option);
  }
  select.value = selected;
  return select;
};

var createDiv = function createDiv(className) {
  var div = document.createElement('div');
  div.className = className;
  return div;
};

var createForm = function createForm(formClass) {
  var form = document.createElement('form');
  form.className = formClass;
  return form;
};

var createButton = function createButton(className, value, text) {
  var button = document.createElement('button');
  button.className = className;
  button.type = 'button';
  button.value = 'value';
  button.innerHTML = text;
  return button;
};

/**
 * Initialize the menu. Assign variables.
 */
ModelViewer.prototype.initMenu = function initMenu() {
  var _ = this;
  _.menuMode = 'basic';
  _.menuAreas = {
    advanced: [
      {done: false, name: 'File', prefix: 'custom', holder: undefined, template: undefined, objects: [], created: [], canDelete: false, canExport: false},
      {done: false, name: 'Renderers', prefix: 'renderer', holder: undefined, template: undefined, objects: _.renderers, created: [], canDelete: false, canExport: true},
      {done: false, name: 'Scenes', prefix: 'scene', holder: undefined, template: undefined, objects: _.scenes, created: [], canDelete: false, canExport: true},
      {done: false, name: 'Cameras', prefix: 'camera', holder: undefined, template: undefined, objects: _.cameras, created: [], canDelete: false, canExport: true},
      {done: false, name: 'Meshes', prefix: 'mesh', holder: undefined, template: undefined, objects: _.meshes, created: [], canDelete: false, canExport: false},
      {done: false, name: 'Materials', prefix: 'material', holder: undefined, template: undefined, objects: _.materials, created: [], canDelete: false, canExport: true},
      {done: false, name: 'Lights', prefix: 'light', holder: undefined, template: undefined, objects: _.lights, created: [], canDelete: true, canExport: true},
    ],
    basic: [
      {done: false, name: 'File', prefix: 'custom', holder: undefined, template: undefined, objects: [], created: [], canDelete: false, canExport: false},
      {done: false, name: 'Materials', prefix: 'material', holder: undefined, template: undefined, objects: _.materials, created: [], canDelete: false, canExport: true},
      {done: false, name: 'Lights', prefix: 'light', holder: undefined, template: undefined, objects: _.lights, created: [], canDelete: false, canExport: true},
    ]
  };
  _.encodingMappings = [
    {name: 'LinearEncoding', value: THREE.LinearEncoding},
    {name: 'sRGBEncoding', value: THREE.sRGBEncoding},
    {name: 'GammaEncoding', value: THREE.GammaEncoding},
    {name: 'RGBEEncoding', value: THREE.RGBEEncoding},
    {name: 'RGBM7Encoding', value: THREE.RGBM7Encoding},
    {name: 'RGBM16Encoding', value: THREE.RGBM16Encoding},
    {name: 'RGBDEncoding', value: THREE.RGBDEncoding},
    {name: 'BasicDepthPacking', value: THREE.BasicDepthPacking},
    {name: 'RGBADepthPacking', value: THREE.RGBADepthPacking}
  ];
  _.defaultLightObject = {
    name: 'templatelight',
    type: 'type',
    color: 0xffffff
  };
  _.settingsMenu = createDiv('model-settings collapse');
  _.settingsMenu.id = 'model-settings';
  var viewer = document.querySelector('.model-viewer.modal-holder');
  viewer.prepend(_.settingsMenu);
  if (_.defaultSettings) {
    _.getSettingsFromJson(_.defaultSettings);
  }
  _.createMenuForSettings();
};

/**
 * Create UI for the settings.
 */
ModelViewer.prototype.createMenuForSettings = function createMenuForSettings() {
  var _ = this;
  _.renderer.renderLists.dispose();
  _.menuAreas[_.menuMode].forEach(function updateReferences(current) {
    if (current.name === 'File') {
      return;
    }
    current.objects = _[current.name.toLowerCase()];
  });

  var createSettings = function createSettings(object, template, prefix) {
    var form = template.querySelector('form');
    var keys = Object.keys(object);
    for (var i = 0; i < keys.length; i++) {
      var key = keys[i];
      var div = _.createElement(object, key, prefix);
      if (div) {
        form.append(div);
      }
    }
  };

  var updateFunction = function onValueUpdated(e) {
    var form = e.target.closest('form');
    if (form.classList.length === 0) {
      return;
    }
    _.menuAreas[_.menuMode].forEach(function checkIfUpdate(current) {
      var exploded = form.className.split("-");
      var formPrefix = exploded[0];
      if (formPrefix === current.prefix) {
        var name = form.querySelector('input[name="' + formPrefix + '-name"]');
        var pairInput = ['range', 'number'];
        var input = e.target;
        if ((pairInput).includes(input.type)) {
          // Now find another value to update
          var second = form.querySelector('input[name="' + input.name + '"]:not([type="' + input.type + '"])');
          second.value = input.value;
        }
        _.updateObject(current.objects, e.target, name.value);
      }
    });
    /* Update materials just in case */
    _.scene.traverse(function setUpdate(child) {
      if (child.material) {
        child.material.needsUpdate = true;
      }
    });
  };

  _.menuAreas[_.menuMode].forEach(function createAreaForMenu(menu) {    
    if (!menu.done) {
      var holderRoot = createDiv(menu.prefix + '-root');
      var titleSpan = document.createElement('span');
      titleSpan.addEventListener('click', function toggleSettings(/*e*/) {
        var holder = this.parentNode.querySelector('.toggle');
        if (holder) {
          if (holder.classList.contains('hidden')) {
            holder.classList.remove('hidden');
          } else {
            holder.classList.add('hidden');
          }
        }
      });
      titleSpan.className = 'holder-title';
      titleSpan.innerHTML = menu.name;
      holderRoot.append(titleSpan);
      menu.holder = createDiv(menu.prefix + '-holder toggle hidden');
      menu.template = createDiv('model-setting ' + menu.prefix + ' template hidden');
      menu.template.append(createForm(menu.prefix + '-form'));
      holderRoot.append(menu.holder);
      _.settingsMenu.append(holderRoot);
    } else {
      menu.created.forEach(function removeCreated(child) {
        child.remove();
      });
      menu.created = [];
    }
    
    if (!menu.done) {
      switch (menu.prefix) {
      case 'light':
        if ('basic' === _.menuMode) {
          break;
        }
        var addLightButton = createButton('add-light', 'add-light', 'Add light');
        menu.holder.append(addLightButton);
        addLightButton.addEventListener('click', function createNewLightTemplate(/*e*/) {
          var selfBtn = this;
          selfBtn.classList.add('hidden');
          _.settingsMenu.removeEventListener('change', updateFunction);
          var templateClone = menu.template.cloneNode(true);
          var div = createDiv('setting-child');
          var span = document.createElement('span');
          span.innerHTML = 'name';
          div.append(span, createInput('text', 'light-name', ''));
          var selectDiv = document.createElement('div');
          selectDiv.classList.add('setting-child');
          var select = createSelect(lightTypeMappings, 'light-type', 'SpotLight');
          var selectSpan = document.createElement('span');
          selectSpan.innerHTML = 'type';
          selectDiv.append(selectSpan, select);
          var saveLight = createButton('save-light', 'save-light', 'Save');
          var form = templateClone.querySelector('form');
          form.addEventListener('submit', function onSubmit(e) {
            e.preventDefault();
          });
          form.prepend(saveLight, selectDiv, div);
          templateClone.classList.remove('template', 'hidden');
          menu.holder.prepend(templateClone);
          saveLight.addEventListener('click', function saveFromTemplate(ev) {
            form = ev.target.closest('form');
            if (form) {
              _.addLight(form);
              templateClone.remove();
              selfBtn.classList.remove('hidden');
              _.settingsMenu.addEventListener('change', updateFunction);
            }
          });
        });
        break;
      case 'custom':
        var exportButton = createButton('button export-settings', 'export', 'Export .json');
        var exportGLBButton = createButton('button export-settings', 'export', 'Export .glb');
        var importButton = createButton('button import-settings', 'import', 'Import .json');
        var toggleMode = createButton('button toggle-mode', 'toggle-mode', 'Toggle mode');
        var inputLights = document.querySelector('input[name="light-file-input"]');
        if (!inputLights) {
          var newInput = document.createElement('input');
          newInput.classList.add('template', 'hidden');
          newInput.type = 'file';
          newInput.name = 'light-file-input';
          newInput.setAttribute('accept', '.json');
          document.body.appendChild(newInput);
          inputLights = newInput;
        }
        exportButton.addEventListener('click', function startExport() {
          _.saveSettingsAsJson(_.settingsMenu);
        });
        toggleMode.addEventListener('click', function changeViewerMode() {
          _.menuMode = _.menuMode === 'basic' ? 'advanced' : 'basic';
          _.menuAreas[_.menuMode].forEach(function resetArea(current) {
            current.done = false;
            while (_.settingsMenu.firstChild) {
              _.settingsMenu.removeChild(_.settingsMenu.firstChild);
            }
            current.created = [];
          });
          _.createMenuForSettings(); 
        });
        exportGLBButton.addEventListener('click', function startExport() {
          _.settingsMenu.removeEventListener('change', updateFunction);
          _.scene.children.forEach(function markAsExported(cur) {
            cur.userData.viewerSet = true;
            if (cur.type === 'Mesh') {
              cur.material.userData.envMapIntensity = cur.material.envMapIntensity;
              cur.material.userData.normalScale = cur.material.normalScale;
            }
          }); 
          var exporter = new THREE.GLTFExporter();
          // Parse the input and generate the glTF output
          exporter.parse(
            _.scene,
            // called when the gltf has been generated
            function onParseDone(gltf) {
              var url = URL.createObjectURL(new Blob([gltf], {type: 'application/octet-stream'}));
              var downloadAnchorNode = document.createElement('a');
              downloadAnchorNode.setAttribute("href", url);
              downloadAnchorNode.setAttribute("download", "object.glb");
              document.body.appendChild(downloadAnchorNode); // required for firefox
              downloadAnchorNode.click();
              downloadAnchorNode.remove();
              URL.revokeObjectURL(url);
              _.settingsMenu.addEventListener('change', updateFunction);
            },
            // called when there is an error in the generation
            function onParseError(/*error*/) {

            },
            {
              binary: true,
              embedImages: true,
              maxTextureSize: 4096
            }
          );
        });
        importButton.addEventListener('click', function openFileDialog() {
          inputLights.click();
        });
        inputLights.addEventListener('change', function startImport() {
          var file = inputLights.files[0];
          if (file) {
            var reader = new FileReader();
            reader.onload = function onFileLoaded(event) {
              var fileString = event.target.result;
              if (fileString) {
                _.getSettingsFromJson(JSON.parse(fileString));
              }
            };
            reader.readAsText(file);
          }
        });
        menu.holder.append(exportButton, exportGLBButton, importButton, toggleMode);
        break;
      }
    }

    menu.objects.forEach(function createSetting(current) {
      // Create settings
      var templateClone = menu.template.cloneNode(true);
      templateClone.classList.remove('hidden', 'template');
      createSettings(current, templateClone, menu.prefix + '-');
      menu.holder.append(templateClone);
      menu.created.push(templateClone);
      if (menu.canDelete) {
        var value = 'delete-' + current.prefix;
        var deleteButton = createButton(value, value, 'Delete');
        deleteButton.addEventListener('click', function removeLight(e) {
          var form = e.target.parentNode.querySelector('form');
          if (form) {
            _.deleteObject(form, menu.prefix, menu.name);
          }
        });
        templateClone.append(deleteButton);
      }
    });
    menu.done = true;
  });
  _.settingsMenu.addEventListener('change', updateFunction);
};

ModelViewer.prototype.setMenuMode = function setMenuMode(mode) {
  var _ = this;
  if (mode) {
    _.menuMode = mode;
  } else {
    _.menuMode = _.menuMode === 'basic' ? 'advanced' : 'basic';
  }
  _.menuAreas[_.menuMode].forEach(function resetArea(current) {
    current.done = false;
    while (_.settingsMenu.firstChild) {
      _.settingsMenu.removeChild(_.settingsMenu.firstChild);
    }
    current.created = [];
  });
  _.createMenuForSettings();
};

/**
 * Creates a menuelement for given object
 * 
 * @param {object} object To create menu for
 * @param {string} key    Key for the input name
 * @param {string} prefix Prefix for the input name
 * 
 * @return {HTMLDivElement}
 */
ModelViewer.prototype.createElement = function createElement(object, key, prefix) {
  var _ = this;
  if (!allowedProperties[_.menuMode].includes(key) || object[key] === null) {
    return;
  }
  var type = typeof object[key];
  var objValue = object[key];
  var div = createDiv('setting-child');
  var groupDiv = createDiv('setting-group');
  div.append(groupDiv);
  var span = document.createElement('span');
  span.append(document.createTextNode(key));
  groupDiv.append(span);
  switch (type) {
  case 'boolean':
    groupDiv.append(createSelect(booleanOptions, prefix + key, '' + objValue));
    break;
  case 'number':
    if (encodingTypes.includes(key)) {
      groupDiv.append(createSelect(_.encodingMappings, prefix + key, objValue));
    } else {
      groupDiv.append(createInput('number', prefix + key, objValue));
      if ('basic' === _.menuMode && rangeTypes.includes(key)) {
        div.append(createInput('range', prefix + key, objValue));
      }
    }
    break;
  case 'string':
    var input = createInput('text', prefix + key, objValue);
    if (readOnly.includes(key)) {
      input.setAttribute('readonly', 'readonly');
    }
    groupDiv.append(input);
    break;
  case 'object':
    var subKeys = Object.keys(objValue);
    for (var j = 0; j < subKeys.length; j++) {
      var subKey = subKeys[j];
      if (!allowedSubProperties.includes(subKey)) {
        continue;
      }
      var subKeyType = typeof objValue[subKey];
      var subValue = objValue[subKey];
      var subSpan = document.createElement('span');
      var subPrefix = prefix + key + '-' + subKey;
      var subGroupDiv = createDiv('setting-group child');
      subSpan.append(document.createTextNode(subKey));
      subGroupDiv.append(subSpan);
      div.append(subGroupDiv);
      switch (subKeyType) {
      case 'number':
        subGroupDiv.append(createInput('number', subPrefix, subValue));
        break;
      case 'string':
        subGroupDiv.append(createInput('text', subPrefix, subValue));
        break;
      case 'boolean':
        subGroupDiv.append(createSelect(booleanOptions, subPrefix, '' + subValue));
        break;
      }
    }
    break;
  }
  return div;
};

/**
 * Get settings from a JSON object
 * 
 * @param {object} $settings JSON settings
 */
ModelViewer.prototype.getSettingsFromJson = function getSettingsFromJson(settings) {
  var _ = this;
  var importFunction = function importFunction(targets, imported) {
    imported.forEach(function checkMatch(current) {
      var found = targets.find(function findMaterial(element) {
        return element.name === current.name;
      });

      if (!found) {
        var create = creatableObjects.find(function findLightPreset(type) {
          return type === current.type;
        });
        if (create) {
          _.createObjectToScene(current);
          found = targets.find(function findMaterial(element) {
            return element.name === current.name;
          });
        }
      }
      if (found) {
        var keys = Object.keys(current);
        for (var i = 0; i < keys.length; i++) {
          var key = keys[i];
          if (readOnly.includes(key)) {
            continue;
          }
          if (typeof found[key] !== 'undefined') {
            if (colorKeys.includes(key)) {
              found[key] = new THREE.Color(current[key]);
            } else if (typeof found[key] === 'object') {
              var subKeys = Object.keys(current[key]);
              for (var j = 0; j < subKeys.length; j++) {
                var subKey = subKeys[j];
                found[key][subKey] = current[key][subKey];
              }
            } else {
              found[key] = current[key];
            }
          }
        }
      }
    });
  };
  var keys = Object.keys(settings);
  keys.forEach(function saveSection(section) {
    if (['Meshes', 'File'].includes(section)) {
      return;
    }
    var menu = _.menuAreas[_.menuMode].find(function menuObject(obj) {
      return obj.name === section;
    });
    if (menu) {
      importFunction(menu.objects, settings[section]);
    }
  });
  _.createMenuForSettings(true);
};

/**
 * Update an object in the scene
 * 
 * @param {array} objects array of objects
 * @param {HTMLInputElement} input element containing data
 * @param {string} name name of the wanted object 
 */
ModelViewer.prototype.updateObject = function updateObject(objects, input, name) {
  var _ = this;
  var pointers = input.name.split('-');
  var object = objects.find(function find(element) {
    return element.name === name;
  });
  if (!object) {
    return;
  }
  var value = input.value;
  if (['true', 'false'].includes(value)) {
    value = (value === 'true');
  }
  if (pointers.length === 3) {
    value = _.castValueTo(object[pointers[1]][pointers[2]], value);
    object[pointers[1]][pointers[2]] = value;
  } else if (pointers.length === 2) {
    value = _.castValueTo(object[pointers[1]], value);
    object[pointers[1]] = value;
  }

};

/**
 * If value is not a string, then cast it to the proper format
 * 
 * @param {*} to   To value to check type and cast to
 * @param {*} from From what value to cast to
 * 
 */
ModelViewer.prototype.castValueTo = function castValueTo(to, from) {
  var typeTo = typeof to;
  var typeFrom = typeof from;
  if (typeTo === typeFrom) {
    return from;
  }
  var casted = from;
  switch (typeTo) {
  case 'number':
    casted = Number(from);
    break;
  }
  return casted;
};

/**
 * Add a light to the scene
 */
ModelViewer.prototype.addLight = function addLight(form) {
  var _ = this;
  var inputList = form.querySelectorAll('input, select');
  var name = form.querySelector('input[name="light-name"]');
  var found = _.lights.find(function findLight(element) {
    return element.name === name.value;
  });
  if (found) {
    return false;
  }
  var object = Object.assign({}, _.defaultLightObject);
  object.name = name.value;
  inputList.forEach(function checkInput(input) {
    _.updateObject([object], input, name.value);
  });
  _.createObjectToScene(object);
  _.createMenuForSettings(true);
};

/**
 * Creates a visual object to the scene from a js object
 * 
 * @param {object} object To create visual object from
 */
ModelViewer.prototype.createObjectToScene = function createObjectToScene(object) {
  var _ = this;
  var newLight;
  switch (object.type) {
  case 'SpotLight':
    newLight = new THREE.SpotLight(object.color);
    newLight.position.set(0, 0, 0);
    newLight.name = object.name;
    newLight.lookAt(new THREE.Vector3());
    break;
  case 'DirectionalLight':
    newLight = new THREE.DirectionalLight(object.color, object.intensity || 1);
    newLight.position.set(0, 0, 0);
    newLight.name = object.name;
    break;
  default:
    break;
  }
  if (!newLight) {
    return;
  }
  newLight.userData.viewerSet = true;
  _.scene.add(newLight);
  _.lights.push(newLight);
};

/**
 * Delete an object from the scene
 * 
 * @param {HTMLFormElement} form Containing the data for the object.
 * @param {string} prefix        Prefix for the menu area.
 * @param {string} menuName      Name of the menu. 
 */
ModelViewer.prototype.deleteObject = function deleteObject(form, prefix, menuName) {
  var _ = this;
  var name = form.querySelector('input[name="' + prefix + '-name"]');
  if (name) {
    var found = _.scene.getObjectByName(name.value);
    if (found) {
      _.scene.remove(found);
      _.menuAreas[_.menuMode].forEach(function filterProperly(current) {
        if (current.prefix !== prefix) {
          return;
        }
        current.objects = current.objects.filter(function getElements(element) {
          return element.name !== name.value;
        });
        _[menuName.toLowerCase()] = current.objects;
      });
      _.createMenuForSettings();
    }
  }
};

/**
 * Save current scene as a json file. Useful for creating base for viewer.
 */
ModelViewer.prototype.saveSettingsAsJson = function saveSettingsAsJson() {
  var _ = this;
  var json = '';
  var object = {};
  var oldMode = _.menuMode;
  _.setMenuMode('advanced');
  var assignFunction = function assignObjects(objects, holder) {
    objects.forEach(function getInputsAsObject(el) {
      var keys = Object.keys(el);
      var tempObject = {};
      var current = Object.assign({}, tempObject);
      for (var i = 0; i < keys.length; i++) {
        var key = keys[i];
        if (!allowedProperties[_.menuMode].includes(key)) {
          continue;
        }
        current[key] = el[key];
      }
      object[holder].push(current);
    });
  };
  _.menuAreas[_.menuMode].forEach(function saveSection(section) {
    if (!section.canExport) {
      return;
    }
    if (!object[section.name]) {
      object[section.name] = [];
    }
    assignFunction(section.objects, section.name);
  });

  json = JSON.stringify(object);
  var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(json);
  var downloadAnchorNode = document.createElement('a');
  downloadAnchorNode.setAttribute("href", dataStr);
  downloadAnchorNode.setAttribute("download", "scenesettings.json");
  document.body.appendChild(downloadAnchorNode); // required for firefox
  downloadAnchorNode.click();
  downloadAnchorNode.remove();
  _.setMenuMode(oldMode);
};

/**
 * Load the background image for the viewer
 */
ModelViewer.prototype.loadBackground = function loadBackground()
{
  var _ = this;
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
    _.initMenu();
    return;
  }
  var cubeLoader = new THREE.CubeTextureLoader();
  cubeLoader.setPath(VuFind.path + '/themes/finna2/images/')
    .load([
      'px.png',
      'nx.png',
      'py.png',
      'ny.png',
      'pz.png',
      'nz.png'
    ],
    function onSuccess(texture) {
      _.background = texture;
      if (!_.scene) {
        _.scene = new THREE.Scene();
      }
      _.scene.background = _.background;
      if (!_.renderer) {
        _.createRenderer();
        _.getModelPath();
      }
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
