/* global finna, THREE, VuFind*/

// Use 1 dracoloader in all of the loaders, so we don't create multiple instances
var dracoLoader;

// Cache for holdings already loaded scenes, prevent multiple loads
var sceneCache = {};

var lightTypeMappings = [
  {name: 'SpotLight', value: 'SpotLight'},
  {name: 'DirectionalLight', value: 'DirectionalLight'},
  {name: 'AmbientLight', value: 'AmbientLight'},
  {name: 'PointLight', value: 'PointLight'},
  {name: 'HemisphereLight', value: 'HemisphereLight'}
];

// Boolean options
var booleanOptions = [
  {value: 'true', name: 'true'},
  {value: 'false', name: 'false'}
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
  _.poiTexturePath = options.poiTexturePath;
  if (typeof options.popup === 'undefined' || options.popup === false) {
    _.inlineId = 'inline-viewer';
  }
  _.disableDefaultLights = options.disableDefaultLights || false;
  _.debug = options.debug || false;
  _.ambientIntensity = +options.ambientIntensity || 1;
  _.hemisphereIntensity = +options.hemisphereIntensity || 0.3;
  _.viewerPaddingAngle = +options.viewerPaddingAngle || 35;
  _.lights = [];
  _.materials = [];
  _.meshes = [];
  _.cameras = [];
  _.renderers = [];
  _.scenes = [];
  _.annotations = [];
  _.loadInfo = options.modelload;
  _.loaded = false;
  _.isFileInput = _.trigger.is('input');
  _.defaultLightObject = {
    name: 'originalname',
    type: 'type',
    color: 0xffffff
  };
  _.defaultMaterialObject = {
    name: 'originalname',
    metalness: 0,
    roughness: 0,
  };
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
    parent: _.isFileInput ? 'debugViewerArea' : _.inlineId || undefined,
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
  _.renderer.gammaOutput = true;
  _.renderer.gammaInput = true;
  _.renderer.gammaFactor = 2.2;
  _.renderer.toneMapping = THREE.ReinhardToneMapping;
  _.encoding = THREE.LinearEncoding;
  _.renderer.outputEncoding = _.encoding;
  
  _.renderer.shadowMap.enabled = true;
  _.renderer.setClearColor(0x000000);
  _.renderer.setPixelRatio(window.devicePixelRatio);
  _.renderer.setSize(_.size.x, _.size.y);
  _.canvasParent.append(_.renderer.domElement);
  _.renderer.name = 'main_renderer';
  if (!_.loaded) {
    // Create camera now.
    _.camera = new THREE.PerspectiveCamera(50, _.size.x / _.size.y, 0.1, 1000);
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
        _.adjustScene(obj.scene);
        _.createControls();
        _.createLights();
        _.initMesh();
        if (!_.debugLights) {
          _.initMenu();
        }
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
  _.scenes.push(_.scene);
  _.scene.background = _.background;
  if (_.debug) {
    var axesHelper = new THREE.AxesHelper( 5 );
    _.scene.add( axesHelper );
  }
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
    _.meshCount = 0;
    _.scene.traverse(function traverseMeshes(obj) {
      if (obj.type === 'Mesh') {
        _.meshCount++;
        _.meshes.push(obj);
        meshMaterial = obj.material;
        // Bumpscale and depthwrite settings
        meshMaterial.depthWrite = !meshMaterial.transparent;
        meshMaterial.bumpScale = 0;

        // Apply encodings so glb looks better and update it if needed
        if (meshMaterial.map) meshMaterial.map.encoding = _.encoding;
        if (meshMaterial.emissiveMap) meshMaterial.emissiveMap.encoding = _.encoding;
        if (meshMaterial.normalMap) meshMaterial.normalMap.encoding = _.encoding;
        if (meshMaterial.map || meshMaterial.envMap || meshMaterial.emissiveMap || meshMaterial.normalMap) meshMaterial.needsUpdate = true;
  
        // Lets get available information about the model here so we can show them properly in information screen
        var geo = obj.geometry;
        if (typeof geo.isBufferGeometry !== 'undefined' && geo.isBufferGeometry) {
          _.vertices += +geo.attributes.position.count;
          _.triangles += +geo.index.count / 3;
        }
        meshMaterial.name = 'material_' + _.meshCount;
        newBox.expandByObject(obj);
        _.materials.push(meshMaterial);
        if (_.debug) {
          var box = new THREE.BoxHelper(obj, 0xffff00);
          _.scene.add( box );
        }
      } else {
        var isLight = false;
        lightTypeMappings.forEach(function getType(current) {
          if (isLight) {
            return;
          }
          if (current.value === obj.type) {
            _.lights.push(obj);
            isLight = true;
          }
        });
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
  if (_.disableDefaultLights) {
    return;
  }
  var hemiLight = new THREE.HemisphereLight(0xffffbb, 0x080820, _.hemisphereIntensity);
  hemiLight.name = 'hemisphere_finna';
  _.scene.add(hemiLight);
  // Ambient light basically just is there all the time
  var ambientLight = new THREE.AmbientLight(0xFFFFFF, _.ambientIntensity); // soft white light
  ambientLight.name = 'ambient_finna';
  _.scene.add(ambientLight);
  var light = new THREE.DirectionalLight(0xffffff, 0.3 * Math.PI);
  light.name = 'directional_finna';
  light.position.set(0.2, 0, 0.2); // ~60º
  _.scene.add(light);
};

var createInput = function createInput(inputType, name, value) {
  var input = document.createElement('input');
  input.type = inputType;
  input.name = name;
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

var defaultLights = [
  'directional_finna',
  'ambient_finna',
  'hemisphere_finna'
];

ModelViewer.prototype.initMenu = function initMenu() {
  var _ = this;
  _.raycaster = new THREE.Raycaster();
  _.raycaster.far = 1000;
  _.raycaster.near = 1;
  _.mouse = new THREE.Vector2();
  _.menuAreas = [
    {done: false, name: 'File', prefix: 'custom', holder: undefined, template: undefined, objects: [], created: []},
    {done: false, name: 'Renderers', prefix: 'renderer', holder: undefined, template: undefined, objects: _.renderers, created: []},
    {done: false, name: 'Scenes', prefix: 'scene', holder: undefined, template: undefined, objects: _.scenes, created: []},
    {done: false, name: 'Cameras', prefix: 'camera', holder: undefined, template: undefined, objects: _.cameras, created: []},
    {done: false, name: 'Meshes', prefix: 'mesh', holder: undefined, template: undefined, objects: _.meshes, created: []},
    {done: false, name: 'Materials', prefix: 'material', holder: undefined, template: undefined, objects: _.materials, created: []},
    {done: false, name: 'Annotations', prefix: 'annotation', holder: undefined, template: undefined, objects: _.annotations, created: []},
    {done: false, name: 'Lights', prefix: 'light', holder: undefined, template: undefined, objects: _.lights, created: []},
  ];
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
  _.annotationTemplate = {
    name: 'template',
    type: 'PlaneGeometry',
    customName: 'Annotation',
    position: {
      x: 0,
      y: 0,
      z: 0
    },
    texts: {
      fi: 'testi',
      en: 'test',
      sv: 'testen'
    }
  };

  window.addEventListener('mousemove', function checkForAnnotation(event) {
    if (event.target.tagName !== 'CANVAS') {
      return;
    }
    event.preventDefault();
    var rect = _.renderer.domElement.getBoundingClientRect();
    _.mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
    _.mouse.y = - ((event.clientY - rect.top) / rect.height) * 2 + 1;
    _.raycaster.setFromCamera(_.mouse, _.camera);

    var intersects = _.raycaster.intersectObjects(_.annotations, true);
    if (intersects.length > 0) {
      intersects[0].object.onMouseClicked();
    }
  });
  _.settingsMenu = createDiv('model-settings collapse');
  _.settingsMenu.id = 'model-settings';
  var viewer = document.querySelector('.model-viewer.modal-holder');
  viewer.prepend(_.settingsMenu);

  _.createMenuForSettings();
};

ModelViewer.prototype.createMenuForSettings = function createMenuForSettings() {
  var _ = this;
  _.menuAreas.forEach(function updateReferences(current) {
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
    _.menuAreas.forEach(function checkIfUpdate(current) {
      var exploded = form.className.split("-");
      var formPrefix = exploded[0];
      if (formPrefix === current.prefix) {
        var name = form.querySelector('input[name="' + formPrefix + '-name"]');
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

  _.menuAreas.forEach(function createAreaForMenu(menu) {    
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
        var exportButton = createButton('button export-settings', 'export', 'Export');
        var importButton = createButton('button import-settings', 'import', 'Import');
        var inputLights = document.querySelector('input[name="light-file-input"]');
        exportButton.addEventListener('click', function startExport() {
          _.saveSettingsAsJson(_.settingsMenu);
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
        menu.holder.append(exportButton, importButton);
        break;
      case 'annotation':
        var createAnnotation = createButton('button create-annotation', 'create-annotation', 'New annotation');
        createAnnotation.addEventListener('click', function onCreateAnnotation(/*e*/) {
          var self = this;
          _.settingsMenu.removeEventListener('change', updateFunction);
          var annotationClone = menu.template.cloneNode(true);
          createSettings(_.annotationTemplate, annotationClone, menu.prefix + '-');
          annotationClone.classList.remove('template', 'hidden');
          self.classList.add('hidden');
          menu.holder.prepend(annotationClone);
          menu.holder.querySelector('input[name="annotation-name"]').removeAttribute('readonly');
          var saveAnnotation = createButton('button annotation-save', 'save-annotation', 'Save');
          annotationClone.append(saveAnnotation);

          saveAnnotation.addEventListener('click', function onSaveAnnotation() {
            annotationClone.remove();
            self.classList.remove('hidden');
            var templateClone = Object.assign({}, _.annotationTemplate);
            _.createObjectToScene(templateClone);
            _.settingsMenu.addEventListener('change', updateFunction);
            _.createMenuForSettings();
          });

        });
        menu.holder.append(createAnnotation);
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
      switch (menu.prefix) {
      case 'light':
        if (!defaultLights.includes(current.name)) {
          var deleteButton = createButton('delete-light', 'delete-light', 'Delete');
          deleteButton.addEventListener('click', function removeLight(e) {
            var form = e.target.parentNode.querySelector('form');
            if (form) {
              _.deleteLight(form);
            }
          });
          templateClone.append(deleteButton);
        }
        break;
      }
    });
    menu.done = true;
  });
  _.settingsMenu.addEventListener('change', updateFunction);
};

var allowedProperties = [
  'name', 'type', 'position', 'color', 'groundColor',
  'intensity', 'roughness', 'clipIntersection', 'clipShadows',
  'depthWrite', 'dithering', 'emissive', 'emissiveIntensity',
  'flatShading', 'metalness', 'morphNormals', 'morphTargets',
  'opacity', 'premultipliedAlpha', 'roughness', 'side', 'toneMapped',
  'transparent', 'visible', 'wireframe', 'wireframeLinewidth', 'gammaFactor',
  'gammaInput', 'gammaOutput', 'physicallyCorrectLights', 'outputEncoding',
  'shininess', 'quaternion', 'texts', 'renderOrder', 'scale', 'clearcoat',
  'clearcoatRoughness', 'normalScale'
];

var allowedSubProperties = [
  'x', 'y', 'z', 'r', 'g', 'b', '_x', '_y', '_z', '_w', 'encoding', 'en', 'fi', 'sv'
];

var readOnly = [
  'name',
  'type'
];

var encodingTypes = [
  'encoding',
  'outputEncoding'
];

var colorKeys = [
  'color',
  'groundColor'
];

ModelViewer.prototype.createElement = function createElement(object, key, prefix) {
  if (!allowedProperties.includes(key) || object[key] === null) {
    return;
  }
  var _ = this;
  var type = typeof object[key];
  var objValue = object[key];
  var div = createDiv('setting-child');
  var span = document.createElement('span');
  span.append(document.createTextNode(key));
  div.append(span);
  switch (type) {
  case 'boolean':
    div.append(createSelect(booleanOptions, prefix + key, '' + objValue));
    break;
  case 'number':
    if (encodingTypes.includes(key)) {
      div.append(createSelect(_.encodingMappings, prefix + key, objValue));
    } else {
      div.append(createInput('number', prefix + key, objValue));
    }
    break;
  case 'string':
    var input = createInput('text', prefix + key, objValue);
    if (readOnly.includes(key)) {
      input.setAttribute('readonly', 'readonly');
    }
    div.append(input);
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

      subSpan.append(document.createTextNode(subKey));
      div.append(subSpan);
      switch (subKeyType) {
      case 'number':
        div.append(createInput('number', subPrefix, subValue));
        break;
      case 'string':
        div.append(createInput('text', subPrefix, subValue));
        break;
      case 'boolean':
        div.append(createSelect(booleanOptions, subPrefix, '' + subValue));
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
      if (current.name === 'Meshes') {
        return;
      }
      var found = targets.find(function findMaterial(element) {
        return element.name === current.name;
      });
      var isLight = lightTypeMappings.find(function findLightPreset(element) {
        return element.type === current.value;
      });
      if (!found && isLight) {
        _.createObjectToScene(current);
        found = targets.find(function findMaterial(element) {
          return element.name === current.name;
        });
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
    var menu = _.menuAreas.find(function menuObject(obj) {
      return obj.name === section;
    });
    if (menu) {
      importFunction(menu.objects, settings[section]);
    }
  });
  _.createMenuForSettings(true);
};

/**
 * Update a light in the scene
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
    newLight = new THREE.DirectionalLight(object.color, 1);
    newLight.position.set(0, 0, 0);
    newLight.name = object.name;
    break;
  case 'AmbientLight':
    newLight = new THREE.AmbientLight(object.color);
    newLight.name = object.name;
    break;
  case 'HemisphereLight':
    if (typeof object.groundColor === 'undefined') {
      object.groundColor = 0x080820;
    }
    newLight = new THREE.HemisphereLight(object.color, object.groundColor, 1);
    newLight.name = object.name;
    break;
  case 'PointLight':
    newLight = new THREE.PointLight( 0xff0000, 1, 100 );
    newLight.position.set(0, 0, 0);
    newLight.name = object.name;
    break;
  case 'PlaneGeometry':
    var map = new THREE.TextureLoader().load(_.poiTexturePath);
    var material = new THREE.SpriteMaterial({map: map, depthTest: false});
    var sprite = new THREE.Sprite( material );
    sprite.onMouseClicked = function onAnnotationClicked(/*e*/) {
      console.log(this.texts);
    };
    sprite.position.set(0, 0, 0);
    sprite.renderOrder = 5;
    _.scene.add(sprite);
    sprite.name = object.name;
    sprite.texts = object.texts;
    sprite.customName = object.customName;
    _.annotations.push(sprite);
    break;
  default:
    break;
  }
  if (!newLight) {
    return;
  }
  _.scene.add(newLight);
  _.lights.push(newLight);
};

/**
 * Delete a light from the scene
 */
ModelViewer.prototype.deleteLight = function deleteLight(form) {
  var _ = this;
  var name = form.querySelector('input[name="light-name"]');
  if (name) {
    var light = _.scene.getObjectByName(name.value);
    if (light) {
      _.scene.remove(light);
      _.lights = _.lights.filter(function findLight(element) {
        return element.name !== name.value;
      });
      _.createMenuForSettings(true);
    }
  }
};

ModelViewer.prototype.saveSettingsAsJson = function saveSettingsAsJson() {
  var _ = this;
  var json = '';
  var object = {};

  var assignFunction = function assignObjects(objects, holder) {
    objects.forEach(function getInputsAsObject(el) {
      var keys = Object.keys(el);
      var tempObject = {};
      var current = Object.assign({}, tempObject);
      for (var i = 0; i < keys.length; i++) {
        var key = keys[i];
        if (!allowedProperties.includes(key)) {
          continue;
        }
        current[key] = el[key];
      }
      object[holder].push(current);
    });
  };
  _.menuAreas.forEach(function saveSection(section) {
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
  _.createMenuForSettings(true);
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
