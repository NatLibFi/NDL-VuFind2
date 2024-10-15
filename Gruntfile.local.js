module.exports = function(grunt) {
  const fs = require("fs");
  const path = require("path");
  const os = require("node:os");

  grunt.registerTask("finna:scss", function finnaScssFunc() {
    const config = getFinnaSassConfig({
        outputStyle: 'compressed',
        quietDeps: true
      }, false);
    grunt.config.set('dart-sass', config);
    grunt.task.run('dart-sass');
  });

  grunt.registerTask('finna:check:scss', function sassCheck() {
    const config = getFinnaSassConfig({
        quietDeps: true
      }, true);
    grunt.config.set('dart-sass', config);
    grunt.task.run('dart-sass');
  });

  grunt.registerTask("finna:lessToSass", function finnaLessToSassFunc() {
    const exclude = grunt.option('exclude');
    const excludedFiles = exclude ? exclude.split(',') : [];
    const include = grunt.option('include');
    const includedFiles = include ? include.split(',') : [];

    const sharedFileOpts = {
        expand: true,
        src: ['*.less', '**/*.less'],
        filter: function(filepath) {
          if (includedFiles.length > 0) {
            for (let included of includedFiles) {
              if (filepath.endsWith(included)) {
                return true;
              }
            }
            return false;
          }
          for (let excluded of excludedFiles) {
            if (filepath.endsWith(excluded)) {
              return false;
            }
          }
          return true;
        },
        ext: '.scss'
    };

    let themeDir = grunt.option('theme-dir');
    if (themeDir) {
      themeDir = path.resolve(themeDir);
    }
    const files = themeDir
      ? [
        {
          ...sharedFileOpts,
          cwd: themeDir + '/less',
          dest: themeDir + '/scss'
        }
      ]
      : [
        {
          ...sharedFileOpts,
          cwd: 'themes/finna2/less',
          dest: 'themes/finna2/scss'
        },
        {
          ...sharedFileOpts,
          cwd: 'themes/custom/less',
          dest: 'themes/custom/scss'
        },
      ];

    const replacements = [
      // Activate SCSS
      {
        pattern: /\/\* #SCSS>/gi,
        replacement: "/* #SCSS> */",
        order: -1 // Do before anything else
      },
      {
        pattern: /<#SCSS \*\//gi,
        replacement: "/* <#SCSS */",
        order: -1
      },
      // Deactivate LESS
      {
        pattern: /\/\* #LESS> \*\/.*?\/\* <#LESS \*\//gis,
        replacement: "",
        order: -1
      },
      { // Change separator in @include statements
        pattern: /@include ([^\(]+)\(([^\)]+)\);/gi,
        replacement: function mixinCommas(match, $1, $2) {
          return '@include ' + $1 + '(' + $2.replace(/;/g, ',') + ');';
        },
        order: 4 // after defaults included in less-to-sass
      },
      { // Remove unquote
        pattern: /unquote\("([^"]+)"\)/gi,
        replacement: function ununquote(match, $1) {
          return $1;
        },
        order: 4
      },
      { // Fix tilde literals
        pattern: /~'(.*?)'/gi,
        replacement: '$1',
        order: 4
      },
      { // Inline &:extends converted
        pattern: /&:extend\(([^\)]+?)( all)?\)/gi,
        replacement: '@extend $1',
        order: 4
      },
      { // Wrap variables in calcs with #{}
        pattern: /calc\([^;]+/gi,
        replacement: function calcVariables(match) {
          return match.replace(/(\$[\w\-]+)/gi, '#{$1}');
        },
        order: 4
      },
      { // Wrap variables set to css variables with #{}
        pattern: /(--[\w-:]+:\s*)((\$|darken\(|lighten\()[^;]+)/gi,
        replacement: '$1#{$2}',
        order: 5
      },
      { // Remove !default from extends (icons.scss)
        pattern: /@extend ([^;}]+) !default;/gi,
        replacement: '@extend $1;',
        order: 6
      },
      { // Revert invalid @ => $ changes for css rules:
        pattern: /\$(supports|container) \(/gi,
        replacement: '@$1 (',
        order: 7
      },
      { // Revert @if => $if change:
        pattern: /\$if \(/gi,
        replacement: '@if (',
        order: 7
      },
      { // Revert @use => $use change:
        pattern: /\$use '/gi,
        replacement: "@use '",
        order: 7
      },
      { // Fix comparison:
        pattern: / ==< /gi,
        replacement: ' <= ',
        order: 7
      },
      { // Remove !important from variables:
        pattern: /(?<!\(.*)(\$.+):(.+)\s*!important\s*;/g,
        replacement: '$1:$2;',
        order: 8
      },
      { // fadein => fade-in:
        pattern: /fadein\((\S+),\s*(\S+)\)/g,
        replacement: function (match, color, adjustment) {
          return 'fade-in(' + color + ', ' + (adjustment.replace('%', '') / 100) + ')';
        },
        order: 8
      },
      { // fadeout => fade-out:
        pattern: /fadeout\((\S+),\s*(\S+)\)/g,
        replacement: function (match, color, adjustment) {
          return 'fade-out(' + color + ', ' + (adjustment.replace('%', '') / 100) + ')';
        },
        order: 8
      },
      { // replace invalid characters in variable names:
        pattern: /\$([^: };/]+)/g,
        replacement: function (match, variable) {
          return '$' + variable.replace('.', '__');
        },
        order: 9
      },
    ];
    if (!grunt.option('no-default')) {
      replacements.push(
        { // Add !default (but avoid messing with function params):
          pattern: /(?<!\(.*)(\$.+):(.+);/g,
          replacement: '$1:$2 !default;',
          order: 19
        }
      );
    }
    if (grunt.option('replace-vars')) {
      const vars = {
        'action-link-color': '#007c90',
        'gray-lighter': '#d1d1d1',
        'gray-ultralight': '#f7f7f7',
        'gray-light': '#595959',
        'gray-darker': '#000',
        'gray-dark': '#121212',
        'gray': '#2b2b2b',
        'body-bg': '#fff',
        'screen-xs': '480px',
        'screen-xs-min': '480px',
        'screen-xs-max': '767px',
        'screen-sm': '768px',
        'screen-sm-min': '768px',
        'screen-md': '992px',
        'screen-md-min': '992px',
        'navbar-default-link-color': '#fff',
        'content-font-size-base': '16px',
        'content-headings-font-size-h1': '28px',
        'content-headings-font-size-h2': '24px',
        'content-headings-font-size-h3': '21px',
        'content-headings-font-size-h4': '18px',
      };
      let order = 20;
      // Change variables where used (but not where declared!):
      Object.entries(vars).forEach(([src, dst]) => {
        replacements.push(
          {
            pattern: new RegExp("(.+)\\$(" + src + ")\\b", "g"),
            replacement: '$1' + dst + ' /* $2 */',
            order: order
          }
        );
        ++order;
      });
    }

    console.log(themeDir ? "Converting theme " + themeDir : "Converting Finna default themes");
    grunt.config.set('lessToSass', {
     convert: {
        files: files,
        options: {
          replacements: replacements
        }
      }
    });
    grunt.task.run('lessToSass');
  });

  function getLoadPaths(file) {
    var config;
    var parts = file.split('/');
    parts.pop(); // eliminate filename

    // initialize search path with directory containing LESS file
    var retVal = [];
    retVal.push(parts.join('/'));

    // Iterate through theme.config.php files collecting parent themes in search path:
    while (config = fs.readFileSync("themes/" + parts[1] + "/theme.config.php", "UTF-8")) {
      // First identify mixins:
      var mixinMatches = config.match(/["']mixins["']\s*=>\s*\[([^\]]+)\]/);
      if (mixinMatches !== null) {
        var mixinParts = mixinMatches[1].split(',');
        for (var i = 0; i < mixinParts.length; i++) {
          parts[1] = mixinParts[i].trim().replace(/['"]/g, '');
          retVal.push(parts.join('/') + '/');
        }
      }

      // Now move up to parent theme:
      var matches = config.match(/["']extends["']\s*=>\s*['"](\w+)['"]/);

      // "extends" set to "false" or missing entirely? We've hit the end of the line:
      if (matches === null || matches[1] === 'false') {
        break;
      }

      parts[1] = matches[1];
      retVal.push(parts.join('/') + '/');
    }
    return retVal;
  }

  function getFinnaSassConfig(additionalOptions, checkOnly) {
    var sassConfig = {},
      path = require('path'),
      themeList = fs.readdirSync(path.resolve('themes')).filter(function (theme) {
        return fs.existsSync(path.resolve('themes/' + theme + '/scss/finna.scss'));
      });

    for (var i in themeList) {
      if (Object.prototype.hasOwnProperty.call(themeList, i)) {
        var config = {
          options: {},
          files: [{
            expand: true,
            cwd: path.join('themes', themeList[i], 'scss'),
            src: ['finna.scss'],
            dest: path.join(checkOnly ? os.tmpdir() : 'themes', themeList[i], 'css'),
            ext: '.css'
          }]
        };
        for (var key in additionalOptions) {
          if (Object.prototype.hasOwnProperty.call(additionalOptions, key)) {
            config.options[key] = additionalOptions[key];
          }
        }
        config.options.includePaths = getLoadPaths('themes/' + themeList[i] + '/scss/finna.scss');
        config.options.includePaths.push('vendor/');
        config.options.includePaths.push(path.resolve('themes/bootstrap3/scss/vendor'));

        sassConfig[themeList[i]] = config;
      }
    }
    return sassConfig;
  }
};
