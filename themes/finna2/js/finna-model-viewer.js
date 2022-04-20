/* global THREE, ObjectHelper */

/**
 * Get tangent
 *
 * @param {Integer} deg 
 */
function getTanDeg(deg) {
  var rad = deg * Math.PI / 180;
  return Math.tan(rad);
}

var loader;
var dracoLoader;

class ModelViewerClass extends HTMLElement {

  static get observedAttributes() {
    return ['lazyload', 'proxy', 'scripts'];
  }

  get scripts() {
    return this.getAttribute('scripts');
  }

  set scripts(newValue) {
    this.setAttribute('scripts', newValue);
  }

  get src() {
    return this.getAttribute('src');
  }

  set src(newValue) {
    this.setAttribute('src', newValue);
  }

  get proxy() {
    return this.getAttribute('proxy');
  }

  set proxy(newValue) {
    this.setAttribute('proxy', newValue);
  }

  get texture() {
    return this.getAttribute('texture');
  }

  set texture(newValue) {
    this.setAttribute('texture', newValue);
  }

  set translations(newValue) {
    let cast = newValue;
    if (typeof cast !== 'object') {
      cast = JSON.parse(newValue);
    }
    this.translationsObj = cast;
  }

  get translations() {
    return this.translationsObj ? this.translationsObj : {};
  }

  get previewsrc() {
    return this.getAttribute('previewsrc');
  }

  set previewsrc(newValue) {
    this.setAttribute('previewsrc', newValue);
  }

  set debug(newValue) {
    this.setAttribute('debug', newValue);
  }

  get debug() {
    return this.getAttribute('debug') === 'true';
  }

  /**
   * Constructor for ModelViewerOld.
   */
  constructor()
  {
    super();
    this.dependenciesLoaded = false;
    this.lights = [];
    this.materials = [];
    this.meshes = [];
    this.cameras = [];
    this.renderers = [];
    this.scenes = [];
    this.loadScrips = {
      'js-threejs': 'three.min.js',
      'js-gltfloader': 'GLTFLoader.js',
      'js-gltfexporter': 'GLTFExporter.js',
      'js-orbitcontrols': 'OrbitControls.js',
      'js-dracoloader': 'DRACOLoader.js'
    };
    this.lightTypeMappings = [
      {name: 'SpotLight', value: 'SpotLight'},
      {name: 'DirectionalLight', value: 'DirectionalLight'},
    ];
    const thisClass = this;

    this.menuOptions = {
      allowedProperties: {
        advanced: [
          'name', 'type', 'position', 'color', 'groundColor',
          'intensity', 'roughness', 'clipIntersection', 'clipShadows',
          'depthWrite', 'dithering', 'emissive', 'emissiveIntensity',
          'flatShading', 'metalness', 'morphNormals', 'morphTargets',
          'opacity', 'premultipliedAlpha', 'roughness', 'side', 'toneMapped',
          'transparent', 'visible', 'wireframe', 'wireframeLinewidth', 'gammaFactor',
          'physicallyCorrectLights',
          'shininess', 'rotation', 'texts', 'renderOrder', 'scale', 'clearcoat',
          'clearcoatRoughness', 'normalScale', 'ior', 'sheen', 'sheenRoughness', 'sheenColor',
          'transmission', 'bumpScale', 'envMapIntensity'
        ],
        basic: [
          'name', 'intensity', 'roughness', 'metalness', 'envMapIntensity'
        ]
      },
      allowedSubProperties: [
        'x', 'y', 'z', 'r', 'g', 'b', '_x', '_y', '_z', '_w', 'en', 'fi', 'sv'
      ],
      defaultLightObject: {
        name: 'templatelight',
        type: 'type',
        color: 0xffffff
      },
      lightTypeMappings: [
        {name: 'SpotLight', value: 'SpotLight'},
        {name: 'DirectionalLight', value: 'DirectionalLight'},
      ],
      rangeTypes: [
        'intensity', 'roughness', 'envMapIntensity', 'metalness', 'rotation'
      ],
      colorKeys: [
        'color',
        'groundColor',
        'sheenColor'
      ],
      creatableObjects: [
        'DirectionalLight',
        'SpotLight'
      ],
      menuAreas: {
        advanced: [
          {done: false, name: 'File', prefix: 'file', holder: undefined, template: undefined, objects: [], created: [], canDelete: false, canExport: false},
          {done: false, name: 'Cameras', prefix: 'camera', holder: undefined, template: undefined, objects: thisClass.cameras, created: [], canDelete: false, canExport: true, updateFunction: () => { return thisClass.cameras; }, assignFunction: (e) => { thisClass.cameras = e; }},
          {done: false, name: 'Meshes', prefix: 'mesh', holder: undefined, template: undefined, objects: thisClass.meshes, created: [], canDelete: false, canExport: false, updateFunction: () => { return thisClass.meshes; }, assignFunction: (e) => { thisClass.meshes = e; }},
          {done: false, name: 'Materials', prefix: 'material', holder: undefined, template: undefined, objects: thisClass.materials, created: [], canDelete: false, canExport: false, updateFunction: () => { return thisClass.materials; }, assignFunction: (e) => { thisClass.materials = e; }},
          {done: false, name: 'Lights', prefix: 'light', holder: undefined, template: undefined, objects: thisClass.lights, created: [], canDelete: true, canExport: true, updateFunction: () => { return thisClass.lights; }, assignFunction: (e) => { thisClass.lights = e; }},
        ],
        basic: [
          {done: false, name: 'File', prefix: 'file', holder: undefined, template: undefined, objects: [], created: [], canDelete: false, canExport: false},
          {done: false, name: 'Materials', prefix: 'material', holder: undefined, template: undefined, objects: thisClass.materials, created: [], canDelete: false, canExport: true, updateFunction: () => { return thisClass.materials; }, assignFunction: (e) => { thisClass.materials = e; }},
          {done: false, name: 'Lights', prefix: 'light', holder: undefined, template: undefined, objects: thisClass.lights, created: [], canDelete: false, canExport: true, updateFunction: () => { return thisClass.lights; }, assignFunction: (e) => { thisClass.lights = e; }},
        ]
      },
      readOnly: [
        'name',
        'type'
      ],
      onAttributeChanged: () => {
        thisClass.scene.traverse((child) => {
          if (child.materal) {
            child.materal.needsUpdate = true;
          }
        });
      },
      properties: {
        rotation: (object, pointers, value) => {
          if (typeof pointers[2] !== 'undefined') {
            switch (pointers[2]) {
            case '_x':
              object.rotation.x = THREE.Math.degToRad(value);
              break;
            case '_y':
              object.rotation.y = THREE.Math.degToRad(value);
              break;
            case '_z':
              object.rotation.z = THREE.Math.degToRad(value);
              break;
            }
          }
        }
      },
      functions: {
        lightMenuCreated: function lightMenuCreated(menu) {
          if (this.menumode === 'basic') {
            return;
          }
          const addLight = this.createButton('add-light', 'add-light', 'Add light');
          menu.holder.append(addLight);
          addLight.addEventListener('click', () => {
            addLight.style.display = 'none';
            this.menu.removeEventListener('change', this.updateFunction);
            const templateClone = menu.template.cloneNode(true);
            const div = this.createDiv('setting-child');
            const span = document.createElement('span');
            span.textContent = 'name';
            div.append(span, this.createInput('text', 'light-name', ''));
            const selectDiv = this.createDiv('setting-child');
            const select = this.createSelect(this.options.lightTypeMappings, 'light-type', 'SpotLight');
            const selectSpan = document.createElement('span');
            selectSpan.textContent = this.translate('type');
            selectDiv.append(selectSpan, select);
            const saveLight = this.createButton('save-light', 'save-light', 'Save');
            const form = templateClone.querySelector('form');
            form.addEventListener('submit', (e) => e.preventDefault());
            form.prepend(saveLight, selectDiv, div);
            templateClone.classList.remove('template', 'hidden');
            menu.holder.prepend(templateClone);
            saveLight.addEventListener('click', e => {
              const saveForm = e.target.closest('form');
              if (saveForm) {
                this.addLight(saveForm);
                templateClone.parentNode.removeChild(templateClone);
                addLight.style.display = null;
                this.menu.addEventListener('change', this.updateFunction);
              }
            });
          });
        },
        fileMenuCreated: function fileMenuCreated(menu) {
          const exportButton = this.createButton('button export-model', 'export', this.translate('Export .glb'));
          exportButton.addEventListener('click', () => {
            this.menu.removeEventListener('change', this.updateFunction);
            thisClass.scene.children.forEach((child) => {
              child.userData.viewerSet = true;
              if (child.type === 'Mesh') {
                child.material.userData.envMapIntensity = child.material.envMapIntensity;
                child.material.userData.normalScale = child.material.normalScale;
              }
            });
            const exporter = new THREE.GLTFExporter();
            let cameraSave = thisClass.scene.children.find((obj) => {
              return obj.name === 'camera_stash';
            });
            if (!cameraSave) {
              cameraSave = new THREE.Object3D();
              cameraSave.name = 'camera_stash';
            }
            const pos = thisClass.camera.position;
            cameraSave.position.set(pos.x, pos.y, pos.z);
            thisClass.scene.add(cameraSave);
            exporter.parse(
              thisClass.scene,
              (gltf) => {
                const url = URL.createObjectURL(new Blob([gltf], { type: 'model/gltf-binary' }));
                const a = document.createElement('a');
                a.href = url;
                a.download = 'object.glb';
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url);
                this.menu.addEventListener('change', this.updateFunction);
              },
              (/*error*/) => {
                this.menu.addEventListener('change', this.updateFunction);
              },
              {
                binary: true,
                embedImages: true,
                maxTextureSize: 4096
              }
            );
          });
          menu.holder.append(exportButton);
          if (!thisClass.debug) {
            return;
          }
          const toggleMode = this.createButton('button toggle-mode', 'toggle-mode', this.translate('Toggle mode'));
          toggleMode.addEventListener('click', () => {
            this.menumode = this.menumode === 'basic' ? 'advanced' : 'basic';
            this.getMenus().forEach((area) => {
              area.done = false;
              area.created = [];
            });
            while (this.menu.firstChild) {
              this.menu.removeChild(this.menu.firstChild);
            }
            this.createMenu();
          });
          menu.holder.append(toggleMode);
        },
        addLight: function addLight(form) {
          const inputList = form.querySelectorAll('input, select');
          const name = form.querySelector('input[name="light-name"]');
          const duplicate = thisClass.lights.find((light) => {
            return light.name === name;
          });
          if (duplicate) {
            return;
          }
          const object = Object.assign({}, this.options.defaultLightObject);
          object.name = name.value;
          inputList.forEach((input) => {
            this.updateObject([object], input, name.value);
          });
          this.createObjectToScene(object);
          this.createMenu();
        },
        createObjectToScene: function createObject(object) {
          let newLight;
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
          thisClass.scene.add(newLight);
          thisClass.lights.push(newLight);
        },
        onDelete: function onDelete(object) {
          thisClass.scene.remove(object);
        }
      }
    };
  }

  restartViewer()
  {
    this.lights = [];
    this.materials = [];
    this.meshes = [];
    this.cameras = [];
    this.renderers = [];
    this.scenes = [];

    if (this.loaded) {
      this.scene.remove.apply(this.scene, this.scene.children);
      this.loadInfo.style.display = 'flex';
      this.loadGLTF();
    } else {
      this.createElement();
    }
  }

  connectedCallback()
  {
    this.menuOptions.translations = this.translations;
    this.getSize();
    const canvasWrapper = document.createElement('div');
    canvasWrapper.classList.add('wrapper');
    this.root = canvasWrapper;
    this.append(canvasWrapper);

    if (this.previewsrc) {
      this.preview = document.createElement('img');
      this.preview.src = this.previewsrc;
      this.preview.alt = '';
      this.root.append(this.preview);
    }

    this.loadInfo = document.createElement('button');
    this.loadInfo.classList.add('state', 'btn', 'btn-primary');
    this.loadInfo.textContent = this.translations['view model'] || 'View model';
    this.loadInfo.addEventListener('click', () => {
      if (!this.dependenciesLoaded) {
        return;
      }
      if (!this.src) {
        console.error('Missing src from model-viewer');
        return;
      }
      this.createElement();
    });
    this.root.append(this.loadInfo);
    const highlight = () => {
      this.root.classList.add('filedrop');
    };
    const unhighlight = () => {
      this.root.classList.remove('filedrop');
    };

    const dragStarts = ['dragenter', 'dragover'];
    dragStarts.forEach(eventName => {
      this.root.addEventListener(eventName, highlight, false);
    });
    const dragEnds = ['dragleave', 'drop'];
    dragEnds.forEach(eventName => {
      this.root.addEventListener(eventName, unhighlight, false);
    });
    window.addEventListener("dragover", (e) => {
      e.preventDefault();
    }, false);
    window.addEventListener("drop", (e) => {
      e.preventDefault();
    }, false);
    this.root.addEventListener('drop', (e) => {
      this.src = URL.createObjectURL(e.dataTransfer.files[0]);
      this.restartViewer();
    });
  }

  attributeChangedCallback(name, oldValue, newValue)
  {
    switch (name) {
    case 'proxy':
      if (!this.src) {
        fetch(newValue)
          .then(response => response.json())
          .then(data => {
            this.src = data.data.url;
          });
      }
      break;
    case 'scripts':
      this.load();
      break;
    }
  }

  load()
  {
    this.decoder = `${this.scripts}draco/`;
    const ref = this;
    const loaded = function onScriptLoad() {
      delete ref.loadScrips[this.reference];
      if (Object.keys(ref.loadScrips).length < 1) {
        ref.dependenciesLoaded = true;
      }
    };
    if (Object.keys(this.loadScrips).length < 1) {
      this.dependenciesLoaded = true;
      return;
    }
    const scripts = [];
    const found = [];
    for (const [key, value] of Object.entries(this.loadScrips)) {
      if (!document.getElementById(key)) {
        const scriptSrc = document.createElement('script');
        scriptSrc.reference = key;
        scriptSrc.onload = loaded;
        scriptSrc.async = '';
        scriptSrc.src = `${this.scripts}${value}`;
        scriptSrc.id = key;
        scripts.push(scriptSrc);
      } else {
        found.push(key);
      }
    }
    found.forEach((key) => {
      delete this.loadScrips[key];
    });
    
    if (scripts.length) {
      const head = document.querySelector('head');
      head.append(...scripts);
    } else {
      this.dependenciesLoaded = true;
    }
  }

  createElement()
  {
    this.loadInfo.remove();
    if (this.preview) {
      this.preview.remove();
      delete this.preview;
    }
    this.viewerPaddingAngle = 35;

    this.loaded = false;
    this.scene = new THREE.Scene();

    this.loadInfo = document.createElement('div');
    this.loadInfo.classList.add('state');
    this.root.append(this.loadInfo);

    const optionsArea = document.createElement('div');
    optionsArea.classList.add('options');
    this.root.append(optionsArea);

    const buttonsHolder = document.createElement('div');
    buttonsHolder.classList.add('buttons');
    optionsArea.append(buttonsHolder);

    const info = document.createElement('i');
    info.classList.add('fa');

    const srOnly = document.createElement('span');
    srOnly.classList.add('sr-only');

    const button = document.createElement('button');
    button.classList.add('collapsed', 'viewer-btn');
    button.type = 'button';
    button.dataset.toggle = 'collapse';
    button.append(info);
    button.append(srOnly);

    const buttons = [
      {class: 'model-fullscreen', translation: 'asd', info: 'fa-fullscreen'},
      {class: 'model-statistics', target: '#model-statistics-area', translation: 'asd', info: 'fa-info-circle-hollow'},
      {class: 'model-help', target: '#model-help-area', translation: 'asd', info: 'fa-question-circle-o'},
      {class: 'model-settings', target: '#object-helper-settings', translation: 'asd', info: 'fa-cog'},
    ];

    buttons.forEach((btn) => {
      const b = button.cloneNode(true);
      b.classList.add(btn.class);
      b.querySelector('span').textContent = btn.translation;
      b.querySelector('i').classList.add(btn.info);
      if (btn.target) {
        b.dataset.target = btn.target;
      }
      buttonsHolder.append(b);
      if (btn.class === 'model-fullscreen') {
        this.fullscreenBtn = b;
      }
    });

    const viewerStatistics = document.createElement('div');
    viewerStatistics.classList.add('statistics-area');
    optionsArea.append(viewerStatistics);

    const modelStats = document.createElement('div');
    modelStats.classList.add('model-stats', 'collapse');
    modelStats.id = 'model-statistics-area';

    const modelHelp = document.createElement('div');
    modelHelp.classList.add('model-help', 'collapse');
    modelHelp.id = 'model-help-area';
    modelHelp.innerHTML = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0)
      ? this.translations['mobile help'] || 'Mobile help'
      : this.translations['desktop help'] || 'Desktop help';
    viewerStatistics.append(modelStats, modelHelp);

    const statisticsTable = document.createElement('table');
    statisticsTable.classList.add('viewer-table');

    const tableBody = document.createElement('tbody');
    tableBody.classList.add('viewer-table-body');
    statisticsTable.append(tableBody);
    this.statistics = tableBody;

    modelStats.append(statisticsTable);

    this.createRenderer();
    this.loadBackground();
    this.createCamera();
    this.createControls();
    this.animationLoop();
    this.setEvents();
    this.loadGLTF();
  }

  loadBackground()
  {
    if (!this.texture) {
      return;
    }
    const cubeLoader = new THREE.CubeTextureLoader();
    cubeLoader.setPath(this.texture);
    cubeLoader.load(
      [
        'px.png',
        'nx.png',
        'py.png',
        'ny.png',
        'pz.png',
        'nz.png'
      ],
      (texture) => {
        this.background = texture;
        this.scene.background = this.background;
      }
    );
  }

  loadGLTF()
  {
    this.loadInfo.textContent = this.translations['loading file'] || 'Model loading.';
    if (!loader) {
      loader = new THREE.GLTFLoader();
      if (this.decoder) {
        dracoLoader = new THREE.DRACOLoader();
        dracoLoader.setDecoderPath(this.decoder);
        loader.setDRACOLoader(dracoLoader);
      }
    }
    loader.load(
      this.src,
      (obj) => {
        this.initMesh(obj);
        this.createLights();
        if (typeof this.objectHelper === 'undefined') {
          this.objectHelper = new ObjectHelper(this.root, this.menuOptions);
        } else {
          this.objectHelper.createMenu();
        }
        this.loadInfo.style.display = 'none';
        this.loaded = true;
      },
      (xhr) => {
        let loaded = '';
        if (xhr.total < 1) {
          loaded = `${(xhr.loaded / 1024 / 1024).toFixed(0)}MB`;
        } else {
          loaded = `${(xhr.loaded / xhr.total * 100).toFixed(0)}%`;
        }
        this.loadInfo.textContent = loaded;
      }
    );
  }

  initMesh(loadedObj)
  {
    this.vertices = 0;
    this.triangles = 0;
    this.meshCount = 0;
    const newBox = new THREE.Box3();
    let cameraPosition;
    while (loadedObj.scene.children.length > 0) {
      const child = loadedObj.scene.children[0];
      if (child.type === 'Object3D') {
        while (child.children.length > 0) {
          const subChild = child.children[0];
          this.scene.add(subChild);
        }
        if (child.name === 'camera_stash') {
          cameraPosition = child.position;
        }
        loadedObj.scene.remove(child);
      } else {
        this.scene.add(child);
        if (child.target) {
          this.scene.add(child.target);
        }
      }
    }
    this.scene.traverse((obj) => {
      if (obj.type === 'Mesh') {
        obj.material.envMap = this.background;
        if (typeof obj.material.userData.envMapIntensity !== 'undefined') {
          obj.material.envMapIntensity = obj.material.userData.envMapIntensity;
        } else {
          obj.material.envMapIntensity = 0.2;
        }
        if (obj.material.userData.normalScale) {
          obj.material.normalScale.x = obj.material.userData.normalScale.x;
          obj.material.normalScale.y = obj.material.userData.normalScale.y;
        }
        if (obj.material.emissiveMap) {
          obj.material.emissiveMap.encoding = this.encoding;
        }
        obj.material.needsUpdate = true;
        this.meshCount++;
        this.meshes.push(obj);

        if (obj.geometry.isBufferGeometry) {
          this.vertices += +obj.geometry.attributes.position.count;
          this.triangles += +obj.geometry.index.count / 3;
        }
        newBox.expandByObject(obj);
        this.materials.push(obj.material);
      } else if (this.lightTypeMappings.find((m) => { return m.value === obj.type; })) {
        this.lights.push(obj);
      }
    });
    let zero = new THREE.Vector3();
    newBox.getCenter(zero);
    zero.negate();
    this.scene.children.forEach((obj) => {
      if (obj.userData.viewerSet) {
        return;
      }
      if (obj.type === 'Mesh' || this.lightTypeMappings.find((m) => { return m.value === obj.type; })) {
        obj.position.x += zero.x;
        obj.position.y += zero.y;
        obj.position.z += zero.z;
      }
    });

    if (!cameraPosition) {
      // Set camera and position to center from the newly created object
      const objectHeight = (newBox.max.y - newBox.min.y);
      const objectWidth = (newBox.max.x - newBox.min.x);
      let result = 0;
      if (objectHeight >= objectWidth) {
        result = objectHeight / getTanDeg(this.viewerPaddingAngle);
      } else {
        result = objectWidth / getTanDeg(this.viewerPaddingAngle);
      }
      this.cameraPosition = result;
      this.camera.position.set(0, 0, this.cameraPosition);
    } else {
      this.cameraPosition = cameraPosition;
      this.camera.position.set(cameraPosition.x, cameraPosition.y, cameraPosition.z);
    }
    this.setInformation(this.translations.vertices || 'Vertices', this.vertices);
    this.setInformation(this.translations.triangles || 'Triangles', this.triangles);
  }

  createRenderer()
  {
    this.renderer = new THREE.WebGLRenderer({
      alpha: true,
      antialias: true
    });

    this.renderer.name = 'main_renderer';
    this.renderer.outputEncoding = this.encoding = THREE.sRGBEncoding;
    this.renderer.physicallyCorrectLights = true;
    this.renderer.shadowMap.enabled = true;
    this.renderer.toneMapping = THREE.ReinhardToneMapping;

    this.renderer.setClearColor(0xEEEEEE, 0);
    this.renderer.setPixelRatio(window.devicePixelRatio);
    this.renderer.setSize(this.size.x, this.size.y);

    this.root.append(this.renderer.domElement);
  }

  createCamera()
  {
    this.camera = new THREE.PerspectiveCamera(
      50,
      this.size.x / this.size.y,
      0.1,
      1000
    );

    this.camera.name = 'main_camera';
    this.camera.userData.viewerInitDone = true;

    this.cameraPosition = new THREE.Vector3(0, 0, 0);
    this.camera.position.set(
      this.cameraPosition.x,
      this.cameraPosition.y,
      this.cameraPosition.z
    );

    this.cameras.push(this.camera);
  }

  setInformation(h, i)
  {
    const tr = document.createElement('tr');

    const header = document.createElement('td');
    header.classList.add('header');
    header.textContent = document.createTextNode(h).nodeValue;

    const value = document.createElement('td');
    value.classList.add('value');
    value.textContent = document.createTextNode(i).nodeValue;

    tr.append(header, value);
    this.statistics.append(tr);
  }

  setEvents()
  {
    window.addEventListener('resize', () => {
      if (this.camera) {
        this.getSize();
        this.updateScale();
      }
    });
    
    const exitFullscreens = [
      'exitFullscreen',
      'mozCancelFullScreen',
      'webkitExitFullscreen'
    ];
    const enterFullscreens = [
      'requestFullscreen',
      'mozRequestFullScreen',
      'webkitRequestFullscreen',
      'webkitEneterFullscreen'
    ];

    this.fullscreenBtn.addEventListener('click', () => {
      if (this.classList.contains('fullscreen')) {
        exitFullscreens.some((func) => {
          if (document[func]) {
            this.classList.remove('fullscreen');
            document[func]();
            return true;
          }
        });
      } else {
        enterFullscreens.some((func) => {
          if (this[func]) {
            this.classList.add('fullscreen');
            this[func]();
            return true;
          }
        });
      }
    });
  }

  createControls()
  {
    this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
    this.controls.target = new THREE.Vector3();
    this.controls.screenSpacePanning = true;
    this.controls.update();
  }

  createLights()
  {
    if (this.lights.length > 0 || this.defaultSettings) {
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
  
    this.lights.push(lightBack);
    this.lights.push(lightFront);
    this.lights.push(lightLeft);
    this.lights.push(lightRight);
  
    this.scene.add(lightFront);
    this.scene.add(lightBack);
    this.scene.add(lightLeft);
    this.scene.add(lightRight);
  }

  getSize()
  {
    if (this.classList.contains('fullscreen')) {
      if (!this.oldSize) {
        this.oldSize = this.size;
      }
      this.size = {
        x: window.innerWidth,
        y: window.innerHeight
      };
    } else if (this.oldSize) {
      this.size = this.oldSize;
      delete this.oldSize;
    } else {
      const computed = getComputedStyle(this.parentElement);
      this.size = {
        x: this.parentElement.offsetWidth,
        y: this.parentElement.offsetHeight
      };
      this.size.x -= parseFloat(computed.paddingLeft) + parseFloat(computed.paddingRight);
      this.size.y -= parseFloat(computed.paddingTop) + parseFloat(computed.paddingBottom);
    }
  }

  updateScale()
  {
    this.camera.aspect = this.size.x / this.size.y;
    this.camera.updateProjectionMatrix();
    this.renderer.setSize(this.size.x, this.size.y);
  }

  animationLoop()
  {
    this.loop = () => {
      if (this.renderer) {
        if (this.controls) {
          this.controls.update();
        }
        requestAnimationFrame(this.loop);
        this.renderer.render(this.scene, this.camera);
      }
    };
  
    window.setTimeout(this.loop, 1000 / 60);
  }
}
customElements.define('finna-model-viewer', ModelViewerClass);
