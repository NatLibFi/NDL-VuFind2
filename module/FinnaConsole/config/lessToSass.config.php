<?php

// The first few rules up to 'literal' are based on the rules in the grunt-less-to-sass library
return [
    [ // functions
        'pattern' => '/(?!@debug|@import|@media|@keyframes|@font-face|@include|@extend|@mixin|@supports|@container |@if |@use |@page |@-\w)@/i',
        'replacement' => '$',
    ],
    [ // when => if
        'pattern' => '/\.([\w_-]*)\s*\((.*)\)\s*when\s*\((.*)=(.*)\)\s*\{(\s*)([^}]+)}[;]?/i',
        'replacement' => '@if $3==$4 {$5@mixin $1($2){$5$6}}',
    ],
    [ // .class => @extend .class
        'pattern' => '/\.([[a-zA-Z-_]*)\s*;/i',
        'replacement' => '@extend .$1;',
    ],
    [ // Remove .less extension from imports
        'pattern' => "/\@import\s*[\"'](.*).less[\"']/i",
        'replacement' => function ($matches) {
            return '@import \'' . str_replace('/less/', '/scss/', $matches[1]) . '\'';
        },
    ],
    [ // Nested include
        'pattern' => '/(\s*)\#([\w\-]*)\s*>\s*\@include\s+(.*);/i',
        'replacement' => '$1@include $2-$3;',
    ],
    [ // Include mixin
        'pattern' => '/(\s+)\.([\w\-]*)\s*\((.*)\);/i',
        'replacement' => '$1@include $2($3);',
    ],
    [ // Mixin declaration
        'pattern' => '/\.([\w\-]*)\s*\((.*)\)\s*\{/i',
        'replacement' => '@mixin $1($2){',
    ],
    [
        'pattern' => '/spin\((.+),(.+)\)/i',
        'replacement' => 'adjust-hue($1,$2)',
    ],
    [ // shade/tint
        'pattern' => '/(shade|tint)\(([^,]+),\s?([\d%]+)\)/i',
        'replacement' => function ($matches) {
            [, $method, $color2, $weight] = $matches;
            $color1 = $method === 'shade' ? '#000000' : '#ffffff';
            return "mix($color1, $color2, $weight)";
        },
    ],
    [ // fade
        'pattern' => '/fade\((.*),\s?([\d]+)\%\)/mi',
        'replacement' => 'rgba($1, ($2/100))',
    ],
    [ // literal
        'pattern' => '/~"(.*)"/i',
        'replacement' => 'unquote("$1")',
    ],

    [ // Fix comparison:
        'pattern' => '/ ==< /i',
        'replacement' => ' <= ',
    ],
    [ // Remove !important from variables:
        'pattern' => '/^[^(]*(\$.+?):(.+?)\s*!important\s*;/m',
        'replacement' => '$1:$2;',
    ],
/*    [ // Remove !important from functions:
        'pattern' => '/^[^(]*(\$.+?):(.+?)\s*!important\s*\)/m',
        'replacement' => '$1:$2;',
    ],*/
    [ // fadein => fade-in:
        'pattern' => '/fadein\((\S+),\s*(\S+)\)/',
        'replacement' => function ($matches) {
            return 'fade-in(' . $matches[1] . ', ' . (str_replace('%', '', $matches[2]) / 100) . ')';
        },
    ],
    [ // fadeout => fade-out:
        'pattern' => '/fadeout\((\S+),\s*(\S+)\)/',
        'replacement' => function ($matches) {
            return 'fade-out(' . $matches[1] . ', ' . (str_replace('%', '', $matches[2]) / 100) . ')';
        },
    ],
    [ // replace invalid characters in variable names:
        'pattern' => '/\$([^: };\/]+)/',
        'replacement' => function ($matches) {
            return '$' . str_replace('.', '__', $matches[1]);
        },
    ],
    [ // remove invalid &:
        'pattern' => '/([a-zA-Z])&:/',
        'replacement' => '$1:',
    ],
    [ // remove (reference) from import):
        'pattern' => '/@import\s+\(reference\)\s*/',
        'replacement' => '@import /*(reference)*/ ',
    ],
    [ // fix missing semicolon from background-image rule:
        'pattern' => '/(\$background-image:([^;]+?))\n/',
        'replacement' => "\$1;\n",
    ],
    [ // remove broken (and useless) rule:
        'pattern' => '/\.feed-container \.list-feed \@include feed-header\(\);/',
        'replacement' => '',
    ],
    [ // interpolate variables in media queries:
        'pattern' => '/\@media (\$[^ ]+)/',
        'replacement' => '@media #{$1}',
    ],
    [ // missing semicolon:
        'pattern' => '/(.+:.*auto)\n/',
        'replacement' => "\$1;\n",
    ],
    [ // lost space in mixin declarations:
        'pattern' => '/(\@mixin.+){/',
        'replacement' => '$1 {',
    ],
    [ // special cases: media query variables
        'pattern' => '/(\$(mobile-portrait|mobile|tablet|desktop):\s*)(.*?);/s',
        'replacement' => '$1"$2";',
    ],
    [ // special cases: mobile mixin
        'pattern' => '/\.mobile\(\{(.*?)\}\);/s',
        'replacement' => '@media #{$mobile} { & { $1 } }',
    ],
    [ // special cases: mobile mixin 2
        'pattern' => '@mixin mobile($rules){',
        'replacement' => '@mixin mobile {',
    ],
    [ // special cases: mobile mixin 3
        'pattern' => '$rules();',
        'replacement' => '@content;',
    ],
    [ // invalid mixin name
        'pattern' => 'text(uppercase)',
        'replacement' => 'text-uppercase',
    ],
    [ // when isnumber
        'pattern' => '& when (isnumber($z-index))',
        'replacement' => '@if $z-index != null',
    ],
    [ // blocks extending container
        'pattern' => '@include container();',
        'replacement' => '@extend .container;',
    ],
    [ // blocks extending more-link
        'pattern' => '@include more-link();',
        'replacement' => '@extend .more-link;',
    ],
    [ // fix math operations
        'pattern' => '/(\s+)(\(.+\/.+\))/',
        'replacement' => '$1calc$2',
    ],
    [ // typo
        'pattern' => '$carousel-header-color none;',
        'replacement' => '$carousel-header-color: none;',
    ],
    [ // typo
        'pattern' => '$brand-primary // $link-color;',
        'replacement' => '$brand-primary; // $link-color',
    ],
    [ // typo
        'pattern' => '- aukioloaikojen otsikko',
        'replacement' => '{ /* aukioloaikojen otsikko */ }',
    ],
    [ // typo
        'pattern' => '$link-hover-color: $tut-a-hover,',
        'replacement' => '$link-hover-color: $tut-a-hover;',
    ],
    [ // typo
        'pattern' => 'rgba(43,65,98,0,9)',
        'replacement' => 'rgba(43,65,98,0.9)',
    ],
    [ // typo $input-bg: ##ff8d0f;
        'pattern' => '/:\s*##+/',
        'replacement' => ': #',
    ],
    [ // typo
        'pattern' => '!importanti',
        'replacement' => '!important',
    ],
    [ // typo
        'pattern' => '$brand-secondary: #;',
        'replacement' => '',
    ],
    [ // typo
        'pattern' => '$brand-secondary: ###;',
        'replacement' => '',
    ],
    [ // typo
        'pattern' => '#00000;',
        'replacement' => '#000000;',
    ],
    [ // typo
        'pattern' => 'background-color: ;',
        'replacement' => '',
    ],
    [ // typo
        'pattern' => '$header-background-color #fff;',
        'replacement' => '$header-background-color: #fff;',
    ],
    [ // typo
        'pattern' => '$action-link-color #FFF;',
        'replacement' => '$action-link-color: #FFF;',
    ],
    [ // typo
        'pattern' => '$finna-browsebar-background (selaa palkin taustaväri)',
        'replacement' => '//$finna-browsebar-background (selaa palkin taustaväri)',
    ],
    [ // typo
        'pattern' => '$finna-browsebar-link-color(selaa palkin linkin)',
        'replacement' => '//$finna-browsebar-link-color(selaa palkin linkin)',
    ],
    [ // typo
        'pattern' => '$finna-browsebar-highlight-background (selaa palkin korotuksen taustaväri)',
        'replacement' => '//$finna-browsebar-highlight-background (selaa palkin korotuksen taustaväri)',
    ],
    [ // typo
        'pattern' => '$home-2_fi  {',
        'replacement' => '.home-2_fi  {',
    ],
    [ // disable unsupported extend
        'pattern' => '@extend .finna-panel-default .panel-heading;',
        'replacement' => '// Not supported in SCSS: @extend .finna-panel-default .panel-heading;',
    ],

    [ // gradient mixin call
        'pattern' => '#gradient.vertical($background-start-color; $background-end-color; $background-start-percent; $background-end-percent);',
        'replacement' => 'gradient-vertical($background-start-color, $background-end-color, $background-start-percent, $background-end-percent);',
    ],
    [ // common typo in home column styles
        'pattern' => '/(\.home-1, \.home-3 \{[^}]+)}(\s*\n\s*\& \.left-column-content.*?\& .right-column-content \{.*?\}.*?\})/s',
        'replacement' => "\$1\$2\n}",
    ],
    [ // another typo in home column styles
        'pattern' => '/(\n\s+\.left-column-content.*?\n\s+)& (.right-column-content)/s',
        'replacement' => "\$1\$2",
    ],
    [ // missing semicolon: display: none
        'pattern' => '/display: none\n/',
        'replacement' => 'display: none;',
    ],
    [ // missing semicolon in variable definitions
        'pattern' => '/(\n\s*\$' . static::VARIABLE_CHARS . '+\s*:\s*?[^;\s]+)((\n|\s*\/\/))/',
        'replacement' => "\$1;\$2",
    ],
    [ // missing semicolon: $header-text-color: #000000
        'pattern' => '/$header-text-color: #000000\n/',
        'replacement' => '$header-text-color: #000000;',
    ],
    [ // missing semicolon: clip: rect(0px,1200px,1000px,0px)
        'pattern' => '/clip: rect\(0px,1200px,1000px,0px\)\n/',
        'replacement' => "clip: rect(0px,1200px,1000px,0px);\n",
    ],
    [ // missing semicolon: $finna-feedback-background: darken(#d80073, 10%) //
        'pattern' => '/\$finna-feedback-background: darken\(#d80073, 10%\)\s*?(\n|\s*\/\/)/',
        'replacement' => "\$finna-feedback-background: darken(#d80073, 10%);\$1",
    ],
    [ // invalid (and obsolete) rule
        'pattern' => '/(\@supports\s*\(-ms-ime-align:\s*auto\)\s*\{\s*\n\s*clip-path.*?\})/s',
        'replacement' => "// Invalid rule commented out by SCSS conversion\n/*\n\$1\n*/",
    ],

    [ // literal fix
        'pattern' => "~ ')'",
        'replacement' => ')',
    ],
    [ // literal fix
        'pattern' => 'calc(100vh - "#{$navbar-height}~")',
        'replacement' => 'calc(100vh - #{$navbar-height})',
    ],

    [ // math without calc
        'pattern' => '/(.*\s)(\S+ \/ (\$|\d)[^\s;]*)/',
        'replacement' => function ($matches) {
            [$full, $pre, $math] = $matches;
            if (str_contains($matches[1], '(')) {
                return $full;
            }
            return $pre . "calc($math)";
        },
    ],
];
