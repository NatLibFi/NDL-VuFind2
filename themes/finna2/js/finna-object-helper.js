/* exported ObjectHelper */

class ObjectHelper {
  constructor(menuHolder, options = {})
  {
    this.menuHolder = menuHolder;
    this.options = options;
    this.translations = this.options.translations || {};
    this.menumode = 'basic';
    if (this.options.functions) {
      for (const [name, f] of Object.entries(this.options.functions)) {
        const copy = f;
        copy.bind(this);
        this[name] = copy;
      }
    }
    this.booleanOptions = [
      {value: 'true', name: 'true'},
      {value: 'false', name: 'false'}
    ];

    this.menu = this.createDiv('object-helper collapse');
    this.menu.id = 'object-helper-settings';
    this.menuHolder.prepend(this.menu);

    this.createMenu();
  }

  createInput(inputType, name, value) {
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
  }
  
  createSelect(options, name, selected) {
    var select = document.createElement('select');
    select.name = name; 
    for (var i = 0; i < options.length; i++) {
      var current = options[i];
      var option = document.createElement('option');
      option.value = current.value;
      option.textContent = this.transEsc(current.name);
      select.append(option);
    }
    select.value = selected;
    return select;
  }
  
  createDiv(className) {
    var div = document.createElement('div');
    div.className = className;
    return div;
  }
  
  createForm(formClass) {
    var form = document.createElement('form');
    form.className = formClass;
    return form;
  }
  
  createButton(className, value, text) {
    var button = document.createElement('button');
    button.className = className;
    button.type = 'button';
    button.value = 'value';
    button.textContent = text;
    return button;
  }

  getMenus() {
    return this.options.menuAreas[this.menumode];
  }

  escape(s) {
    return document.createTextNode(s).nodeValue;
  }

  translate(s) {
    return this.menumode === 'basic'
      ? this.translations[s.toLowerCase()] || s
      : s;
  }

  transEsc(s) {
    return this.translate(this.escape(s));
  }

  createMenu() {
    this.getMenus().forEach((area) => {
      if (typeof area.updateFunction === 'function') {
        area.objects = area.updateFunction();
      }
    });
    const createSettings = (object, template, prefix) => {
      const form = template.querySelector('form');
      for (const key in object) {
        if (object.hasOwnProperty(key)) {
          const div = this.createElement(object, key, prefix);
          if (div) {
            form.append(div);
          }
        }
      }
    };

    this.updateFunction = (e) => {
      const form = e.target.closest('form');
      if (form.classList.length === 0) {
        return;
      }
      const exploded = form.className.split('-');
      const formPrefix = exploded[0];
      const updateArea = this.getMenus().find((area) => {
        return formPrefix === area.prefix;
      });
      if (updateArea) {
        const name = form.querySelector(`input[name="${formPrefix}-name"]`);
        const input = e.target;
        if (['range', 'number'].includes(input.type) && 'basic' === this.menumode) {
          const second = form.querySelector(`input[name="${input.name}"]:not([type="${input.type}"])`);
          second.value = input.value;
        }
        this.updateObject(updateArea.objects, input, name.value);
      }
    };

    this.getMenus().forEach((menu) => {
      if (menu.done) {
        menu.created.forEach((child) => child.remove());
        menu.created = [];
        return;
      }
      const root = this.createDiv(`${menu.prefix}-root`);
      const title = document.createElement('span');
      menu.holder = this.createDiv(`${menu.prefix}-holder toggle hidden`);
      menu.template = this.createDiv(`model-setting ${menu.prefix} template hidden`);
      menu.template.append(this.createForm(`${menu.prefix}-form`));
      title.addEventListener('click', () => {
        if (menu.holder) {
          if (menu.holder.classList.contains('hidden')) {
            menu.holder.classList.remove('hidden');
          } else {
            menu.holder.classList.add('hidden');
          }
        }
      });
      title.className = 'holder-title';
      title.textContent = this.translate(menu.name);
      root.append(title);
      root.append(menu.holder);
      this.menu.append(root);

      if (typeof menu.onCreateCustom !== 'undefined') {
        menu.onCreateCustom(this, menu);
      }
    });

    this.getMenus().forEach((menu) => {
      menu.objects.forEach((current) => {
        const templateClone = menu.template.cloneNode(true);
        templateClone.classList.remove('hidden', 'template');
        createSettings(current, templateClone, `${menu.prefix}-`);
        menu.holder.append(templateClone);
        menu.created.push(templateClone);
        if (menu.canDelete) {
          const value = `delete-${menu.prefix}`;
          const button = this.createButton(value, value, this.translate('Delete'));
          button.addEventListener('click', (e) => {
            const form = e.target.parentNode.querySelector('form');
            if (form) {
              this.deleteObject(form, menu.prefix, menu.name);
            }
          });
          templateClone.append(button);
        }
      });
      if (!menu.done && typeof this[`${menu.prefix}MenuCreated`] === 'function') {
        this[`${menu.prefix}MenuCreated`](menu);
      }
      menu.done = true;
    });
    this.menu.addEventListener('change', this.updateFunction);
  }

  setMenuMode(mode)
  {
    this.menuMode = mode;
    this.getMenus().forEach((area) => {
      area.done = false;
      while (this.menu.firstChild) {
        this.menu.removeChild(this.menu.firstChild);
      }
      area.created = [];
    });
    this.createMenu();
  }

  createElement(object, key, prefix)
  {
    if (!this.options.allowedProperties[this.menumode].includes(key) || object[key] === null) {
      return;
    }
    const type = typeof object[key];
    const value = object[key];
    const div = this.createDiv('setting-child');
    const groupDiv = this.createDiv('setting-group');
    div.append(groupDiv);
    const span = document.createElement('span');
    span.textContent = this.transEsc(key);
    groupDiv.append(span);
    const params = [`${prefix}${this.escape(key)}`, `${this.escape(value)}`];
    switch (type) {
    case 'boolean':
      groupDiv.append(this.createSelect(this.booleanOptions, ...params));
      break;
    case 'number':
      groupDiv.append(this.createInput('number', ...params));
      if ('basic' === this.menumode && this.options.rangeTypes.includes(key)) {
        div.append(this.createInput('range', ...params));
      }
      break;
    case 'string':
      var input = this.createInput('text', ...params);
      if (this.options.readOnly.includes(key)) {
        input.setAttribute('readonly', 'readonly');
      }
      groupDiv.append(input);
      break;
    case 'object':
      for (const subKey in value) {
        if (!this.options.allowedSubProperties.includes(subKey)) {
          continue;
        }
        const subKeyType = typeof value[subKey];
        const subSpan = document.createElement('span');
        const subGroupDiv = this.createDiv('setting-group child');
        const subParams = [`${prefix}${this.escape(key)}-${this.escape(subKey)}`, this.escape(value[subKey])];
        subSpan.textContent = this.transEsc(subKey);
        subGroupDiv.append(subSpan);
        div.append(subGroupDiv);
        switch (subKeyType) {
        case 'number':
          subGroupDiv.append(this.createInput('number', ...subParams));
          break;
        case 'string':
          subGroupDiv.append(this.createInput('text', ...subParams));
          break;
        case 'boolean':
          subGroupDiv.append(this.createSelect(this.booleanOptions, ...subParams));
          break;
        }
      }
      break;
    }
    return div;
  }

  updateObject(objects, input, name)
  {
    const object = objects.find((current) => {
      return current.name === name;
    });
    if (!object) {
      return;
    }
    let value = input.value;
    if (['true', 'false'].includes(value)) {
      value = (value === 'true');
    }
    const pointers = input.name.split('-');
    if (typeof this.options.properties[pointers[1]] === 'function') {
      this.options.properties[pointers[1]](object, pointers, value);
      return;
    }
    if (pointers.length === 3) {
      object[pointers[1]][pointers[2]] = this.castValueTo(object[pointers[1]][pointers[2]], value);
    } else if (pointers.length === 2) {
      object[pointers[1]] = this.castValueTo(object[pointers[1]], value);
    }
  }

  castValueTo(to, from)
  {
    const typeTo = typeof to;
    const typeFrom = typeof from;
    if (typeTo === typeFrom) {
      return from;
    }
    let casted = from;
    switch (typeTo) {
    case 'number':
      casted = Number(from);
      break;
    }
    return casted;
  }

  deleteObject(form, prefix)
  {
    const name = form.querySelector('input[name="' + prefix + '-name"]');
    if (name) {
      const menu = this.getMenus().find(m => m.prefix === prefix);
      if (menu) {
        var found = menu.objects.find(o => o.name === name.value);
        if (found) {
          if (typeof this.onDelete === 'function') {
            this.onDelete(found);
          }
          menu.objects = menu.objects.filter((element) => {
            return element.name !== name.value;
          });
          if (typeof menu.assignFunction === 'function') {
            menu.assignFunction(menu.objects);
          }
          this.createMenu();
        }
      }
    }
  }
}