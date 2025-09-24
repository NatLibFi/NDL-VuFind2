<?php

// Define path to application directory
defined('APPLICATION_PATH')
    || define(
        'APPLICATION_PATH',
        (getenv('VUFIND_APPLICATION_PATH') ? getenv('VUFIND_APPLICATION_PATH')
            : dirname(__DIR__))
    );

// Define application environment
defined('APPLICATION_ENV')
    || define(
        'APPLICATION_ENV',
        (getenv('VUFIND_ENV') ? getenv('VUFIND_ENV') : 'production')
    );

// Define default search backend identifier
defined('DEFAULT_SEARCH_BACKEND') || define('DEFAULT_SEARCH_BACKEND', 'Solr');

// Define path to local override directory
defined('LOCAL_OVERRIDE_DIR')
    || define(
        'LOCAL_OVERRIDE_DIR',
        (getenv('VUFIND_LOCAL_DIR') ? getenv('VUFIND_LOCAL_DIR') : '')
    );

// Define path to cache directory
defined('LOCAL_CACHE_DIR')
    || define(
        'LOCAL_CACHE_DIR',
        (getenv('VUFIND_CACHE_DIR')
            ? getenv('VUFIND_CACHE_DIR')
            : (strlen(LOCAL_OVERRIDE_DIR) > 0 ? LOCAL_OVERRIDE_DIR . '/cache' : ''))
    );

// Define database datetime format
defined('VUFIND_DATABASE_DATETIME_FORMAT') || define('VUFIND_DATABASE_DATETIME_FORMAT', 'Y-m-d H:i:s');

// Define default earliest year for date ranges
defined('VUFIND_DEFAULT_EARLIEST_YEAR') || define('VUFIND_DEFAULT_EARLIEST_YEAR', 1400);

// Define default latest year offset from current year for date ranges
defined('VUFIND_DEFAULT_LATEST_YEAR_OFFSET') || define('VUFIND_DEFAULT_LATEST_YEAR_OFFSET', 1);

// Define default API key header field name
defined('VUFIND_API_KEY_DEFAULT_HEADER_FIELD') || define('VUFIND_API_KEY_DEFAULT_HEADER_FIELD', 'X-API-KEY');

// Define IIIF Presentation API manifest content type regex pattern
defined('IIIF_MANIFEST_CONTENT_TYPE_V3_REGEX') || define(
    'IIIF_MANIFEST_CONTENT_TYPE_V3_REGEX',
    '/application\/ld(\+json)?;profile="http:\/\/iiif.io\/api\/presentation\/3\/context.json"/'
);
