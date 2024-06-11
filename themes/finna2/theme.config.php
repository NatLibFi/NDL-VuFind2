<?php
$config = [
    'extends' => 'bootstrap3',
    'helpers' => [
        'factories' => [
            'Finna\View\Helper\Root\AdjustHeadingLevel' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Finna\View\Helper\Root\Aipa' => 'Finna\View\Helper\Root\AipaFactory',
            'Finna\View\Helper\Root\ApiRecordFormatter' => 'Finna\View\Helper\Root\ApiRecordFormatterFactory',
            'Finna\View\Helper\Root\Auth' => 'Finna\View\Helper\Root\AuthFactory',
            'Finna\View\Helper\Root\AuthorizationNotification' => 'Finna\View\Helper\Root\AuthorizationNotificationFactory',
            'Finna\View\Helper\Root\Authority' => 'Finna\View\Helper\Root\AuthorityFactory',
            'Finna\View\Helper\Root\Autocomplete' => 'Finna\View\Helper\Root\AutocompleteFactory',
            'Finna\View\Helper\Root\Barcode' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Finna\View\Helper\Root\BazaarSession' => 'Finna\View\Helper\Root\BazaarSessionFactory',
            'Finna\View\Helper\Root\Browse' => 'Finna\View\Helper\Root\BrowseFactory',
            'Finna\View\Helper\Root\Callnumber' => 'Finna\View\Helper\Root\CallNumberFactory',
            'Finna\View\Helper\Root\Citation' => 'Finna\View\Helper\Root\CitationFactory',
            'Finna\View\Helper\Root\CleanHtml' => 'Finna\View\Helper\Root\CleanHtmlFactory',
            'Finna\View\Helper\Root\Combined' => 'Finna\View\Helper\Root\CombinedFactory',
            'Finna\View\Helper\Root\Component' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Finna\View\Helper\Root\Config' => 'VuFind\View\Helper\Root\ConfigFactory',
            'Finna\View\Helper\Root\Content' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Finna\View\Helper\Root\Cookie' => 'Finna\View\Helper\Root\CookieFactory',
            'Finna\View\Helper\Root\CookieConsent' => 'VuFind\View\Helper\Root\CookieConsentFactory',
            'Finna\View\Helper\Root\CustomElement' => 'Finna\View\Helper\Root\CustomElementFactory',
            'Finna\View\Helper\Root\EDS' => 'Finna\View\Helper\Root\EDSFactory',
            'Finna\View\Helper\Root\Feed' => 'Finna\View\Helper\Root\FeedFactory',
            'Finna\View\Helper\Root\FeedTabs' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Finna\View\Helper\Root\FileSize' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Finna\View\Helper\Root\FileSrc' => 'Finna\View\Helper\Root\HelperWithThemeInfoFactory',
            'Finna\View\Helper\Root\FinnaSurvey' => 'Finna\View\Helper\Root\HelperWithMainConfigFactory',
            'Finna\View\Helper\Root\Followup' => 'Finna\View\Helper\Root\FollowupFactory',
            'Finna\View\Helper\Root\HtmlElement' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Finna\View\Helper\Root\Holdings' => 'VuFind\View\Helper\Root\HoldingsFactory',
            'Finna\View\Helper\Root\Iframe' => 'Finna\View\Helper\Root\IframeFactory',
            'Finna\View\Helper\Root\ImageSrc' => 'Finna\View\Helper\Root\HelperWithThemeInfoFactory',
            'Finna\View\Helper\Root\LayoutClass' => 'VuFind\View\Helper\Bootstrap3\LayoutClassFactory',
            'Finna\View\Helper\Root\LinkedEventsTabs' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Finna\View\Helper\Root\Markdown' => 'VuFind\View\Helper\Root\MarkdownFactory',
            'Finna\View\Helper\Root\Matomo' => 'Finna\View\Helper\Root\MatomoFactory',
            'Finna\View\Helper\Root\MatomoTracking' => 'Finna\View\Helper\Root\MatomoTrackingFactory',
            'Finna\View\Helper\Root\MetaLib' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Finna\View\Helper\Root\Navibar' => 'Finna\View\Helper\Root\NavibarFactory',
            'Finna\View\Helper\Root\R2' => 'Finna\View\Helper\Root\R2Factory',
            'Finna\View\Helper\Root\OnlinePayment' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Finna\View\Helper\Root\OpenUrl' => 'VuFind\View\Helper\Root\OpenUrlFactory',
            'Finna\View\Helper\Root\OrganisationDisplayName' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Finna\View\Helper\Root\OrganisationInfo' => 'Finna\View\Helper\Root\OrganisationInfoFactory',
            'Finna\View\Helper\Root\OrganisationsList' => 'Finna\View\Helper\Root\OrganisationsListFactory',
            'Finna\View\Helper\Root\PersonaAuth' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Finna\View\Helper\Root\Piwik' => 'VuFind\View\Helper\Root\PiwikFactory',
            'Finna\View\Helper\Root\Primo' => 'Finna\View\Helper\Root\PrimoFactory',
            'Finna\View\Helper\Root\ProxyUrl' => 'Finna\View\Helper\Root\ProxyUrlFactory',
            'Finna\View\Helper\Root\Record' => 'Finna\View\Helper\Root\RecordFactory',
            'Finna\View\Helper\Root\RecordDataFormatter' => 'Finna\View\Helper\Root\RecordDataFormatterFactory',
            'Finna\View\Helper\Root\RecordFieldMarkdown' => 'Finna\View\Helper\Root\RecordFieldMarkdownFactory',
            'Finna\View\Helper\Root\RecordImage' => 'Finna\View\Helper\Root\RecordImageFactory',
            'Finna\View\Helper\Root\RecordLink' => 'Finna\View\Helper\Root\RecordLinkFactory',
            'Finna\View\Helper\Root\RecordLinker' => 'Finna\View\Helper\Root\RecordLinkerFactory',
            'Finna\View\Helper\Root\ResultFeed' => 'VuFind\View\Helper\Root\ResultFeedFactory',
            'Finna\View\Helper\Root\ScriptSrc' => 'Finna\View\Helper\Root\HelperWithThemeInfoFactory',
            'Finna\View\Helper\Root\Search' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Finna\View\Helper\Root\SearchBox' => 'VuFind\View\Helper\Root\SearchBoxFactory',
            'Finna\View\Helper\Root\SearchMemory' => 'VuFind\View\Helper\Root\SearchMemoryFactory',
            'Finna\View\Helper\Root\SearchTabs' => 'Finna\View\Helper\Root\SearchTabsFactory',
            'Finna\View\Helper\Root\SearchTabsRecommendations' => 'Finna\View\Helper\Root\SearchTabsRecommendationsFactory',
            'Finna\View\Helper\Root\StreetSearch' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Finna\View\Helper\Root\StripTags' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Finna\View\Helper\Root\Summon' => 'Finna\View\Helper\Root\SummonFactory',
            'Finna\View\Helper\Root\SystemMessages' => 'Finna\View\Helper\Root\SystemMessagesFactory',
            'Finna\View\Helper\Root\TotalIndexed' => 'Finna\View\Helper\Root\TotalIndexedFactory',
            'Finna\View\Helper\Root\Translation' => 'Finna\View\Helper\Root\TranslationFactory',
            'Finna\View\Helper\Root\TruncateUrl' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Finna\View\Helper\Root\UserAgent' => 'Finna\View\Helper\Root\UserAgentFactory',
            'Finna\View\Helper\Root\UserListEmbed' => 'Finna\View\Helper\Root\UserListEmbedFactory',
            'Finna\View\Helper\Root\UserPublicName' => 'Laminas\ServiceManager\Factory\InvokableFactory',

            'VuFind\View\Helper\Root\Linkify' => 'Finna\View\Helper\Root\LinkifyFactory',
        ],
        'aliases' => [
            'adjustHeadingLevel' => 'Finna\View\Helper\Root\AdjustHeadingLevel',
            'aipa' => 'Finna\View\Helper\Root\Aipa',
            'apiRecordFormatter' => 'Finna\View\Helper\Root\ApiRecordFormatter',
            'auth' => 'Finna\View\Helper\Root\Auth',
            'authority' => 'Finna\View\Helper\Root\Authority',
            'authorizationNote' => 'Finna\View\Helper\Root\AuthorizationNotification',
            'autocomplete' => 'Finna\View\Helper\Root\Autocomplete',
            'barcode' => 'Finna\View\Helper\Root\Barcode',
            'bazaarSession' => 'Finna\View\Helper\Root\BazaarSession',
            'callnumber' => 'Finna\View\Helper\Root\Callnumber',
            'cleanHtml' => 'Finna\View\Helper\Root\CleanHtml',
            'combined' => 'Finna\View\Helper\Root\Combined',
            'component' => 'Finna\View\Helper\Root\Component',
            'content' => 'Finna\View\Helper\Root\Content',
            'cookie' => 'Finna\View\Helper\Root\Cookie',
            'cookieConsent' => 'Finna\View\Helper\Root\CookieConsent',
            'customElement' => 'Finna\View\Helper\Root\CustomElement',
            'eds' => 'Finna\View\Helper\Root\EDS',
            'feed' => 'Finna\View\Helper\Root\Feed',
            'feedTabs' => 'Finna\View\Helper\Root\FeedTabs',
            'fileSize' => 'Finna\View\Helper\Root\FileSize',
            'fileSrc' => 'Finna\View\Helper\Root\FileSrc',
            'finnaSurvey' => 'Finna\View\Helper\Root\FinnaSurvey',
            'followup' => 'Finna\View\Helper\Root\Followup',
            // For back-compatibility
            'holdingsSettings' => 'Finna\View\Helper\Root\Holdings',
            'htmlElement' => 'Finna\View\Helper\Root\HtmlElement',
            //use root highlight so search results use span instead of mark
            'highlight' => 'VuFind\View\Helper\Root\Highlight',
            'iframe' => 'Finna\View\Helper\Root\Iframe',
            'imageSrc' => 'Finna\View\Helper\Root\ImageSrc',
            'indexedTotal' => 'Finna\View\Helper\Root\TotalIndexed',
            'linkedEventsTabs' => 'Finna\View\Helper\Root\LinkedEventsTabs',
            'markdown' => 'Finna\View\Helper\Root\Markdown',
            'matomoTracking' => 'Finna\View\Helper\Root\MatomoTracking',
            'metaLib' => 'Finna\View\Helper\Root\MetaLib',
            'navibar' => 'Finna\View\Helper\Root\Navibar',
            'R2' => 'Finna\View\Helper\Root\R2',
            'onlinePayment' => 'Finna\View\Helper\Root\OnlinePayment',
            'organisationInfo' => 'Finna\View\Helper\Root\OrganisationInfo',
            'organisationDisplayName' => 'Finna\View\Helper\Root\OrganisationDisplayName',
            'organisationsList' => 'Finna\View\Helper\Root\OrganisationsList',
            'personaAuth' => 'Finna\View\Helper\Root\PersonaAuth',
            'primo' => 'Finna\View\Helper\Root\Primo',
            'recordFieldMarkdown' => 'Finna\View\Helper\Root\RecordFieldMarkdown',
            'recordImage' => 'Finna\View\Helper\Root\RecordImage',
            'recordLink' => 'Finna\View\Helper\Root\RecordLink',
            'scriptSrc' => 'Finna\View\Helper\Root\ScriptSrc',
            'stripTags' => 'Finna\View\Helper\Root\StripTags',
            'search' => 'Finna\View\Helper\Root\Search',
            'searchbox' => 'Finna\View\Helper\Root\SearchBox',
            'searchMemory' => 'Finna\View\Helper\Root\SearchMemory',
            'searchTabsRecommendations' => 'Finna\View\Helper\Root\SearchTabsRecommendations',
            'streetSearch' => 'Finna\View\Helper\Root\StreetSearch',
            'systemMessages' => 'Finna\View\Helper\Root\SystemMessages',
            'translation' => 'Finna\View\Helper\Root\Translation',
            'truncateUrl' => 'Finna\View\Helper\Root\TruncateUrl',
            'userAgent' => 'Finna\View\Helper\Root\UserAgent',
            'userlistEmbed' => 'Finna\View\Helper\Root\UserListEmbed',
            'userPublicName' => 'Finna\View\Helper\Root\UserPublicName',

            // Overrides
            'VuFind\View\Helper\Root\Browse' => 'Finna\View\Helper\Root\Browse',
            'VuFind\View\Helper\Root\Citation' => 'Finna\View\Helper\Root\Citation',
            'VuFind\View\Helper\Root\Config' => 'Finna\View\Helper\Root\Config',
            'VuFind\View\Helper\Root\Holdings' => 'Finna\View\Helper\Root\Holdings',
            'VuFind\View\Helper\Root\Matomo' => 'Finna\View\Helper\Root\Matomo',
            'VuFind\View\Helper\Root\OpenUrl' => 'Finna\View\Helper\Root\OpenUrl',
            'VuFind\View\Helper\Root\Piwik' => 'Finna\View\Helper\Root\Piwik',
            'VuFind\View\Helper\Root\ProxyUrl' => 'Finna\View\Helper\Root\ProxyUrl',
            'VuFind\View\Helper\Root\Record' => 'Finna\View\Helper\Root\Record',
            'VuFind\View\Helper\Root\RecordDataFormatter' => 'Finna\View\Helper\Root\RecordDataFormatter',
            'VuFind\View\Helper\Root\RecordLinker' => 'Finna\View\Helper\Root\RecordLinker',
            'VuFind\View\Helper\Root\ResultFeed' => 'Finna\View\Helper\Root\ResultFeed',
            'VuFind\View\Helper\Root\SearchTabs' => 'Finna\View\Helper\Root\SearchTabs',
            'VuFind\View\Helper\Root\Summon' => 'Finna\View\Helper\Root\Summon',
            'VuFind\View\Helper\Bootstrap3\LayoutClass' => 'Finna\View\Helper\Root\LayoutClass',

            // Aliases for non-standard cases
            'Combined' => 'combined',
            'KeepAlive' => 'keepAlive',
            'MetaLib' => 'metaLib',
            'metalib' => 'metaLib',
            'Primo' => 'primo',
            'proxyurl' => 'proxyUrl',
            'searchtabs' => 'searchTabs',
            'transesc' => 'transEsc',
            'inlinescript' => 'inlineScript',
        ],
    ],
    'css' => [
        'vendor/bootstrap-datepicker3.min.css',
        'vendor/bootstrap-slider.min.css',
        'vendor/dataTables.bootstrap.min.css',
        'vendor/L.Control.Locate.min.css',
        'vendor/leaflet.css',
        'vendor/leaflet.draw.css',
        'vendor/easymde.min.css',
        'vendor/splide-core.min.css',
        'vendor/video-js.min.css',
        'finna.css',
        'finnaicons.css',
        'vendor/priority-nav-core.css',
    ],
    'js' => [
        ['file' => 'vendor/bootstrap-accessibility.min.js', 'disabled' => true],
        'finna-object-editor.js',
        'account_ajax.js',
        'advanced_search.js',
        'cart.js',
        'check_item_statuses.js',
        'check_save_statuses.js',
        'checkouts.js',
        'collection_record.js',
        'combined-search.js',
        'cookie.js',
        'covers.js',
        'doi.js',
        'embedded_record.js',
        'facets.js',
        // 'hierarchyTree.js', hierarchyTree only works inline
        'hold.js',
        'ill.js',
        'keep_alive.js',
        'record.js',
        'record_versions.js',
        'requests.js',
        'resultcount.js',
        'lib/autocomplete.js',
        'finna.js',
        'finna-script-loader.js',
        'finna-popup.js',
        'finna-autocomplete.js',
        'finna-authority.js',
        'finna-image-paginator.js',
        'finna-menu-movement.js',
        'finna-comments.js',
        'finna-common.js',
        'finna-content-feed.js',
        'finna-fines.js',
        'finna-item-status.js',
        'finna-adv-search.js',
        'finna-daterange-vis.js',
        'finna-layout.js',
        'finna-linked-events.js',
        'finna-openurl.js',
        'finna-map.js',
        'finna-map-facet.js',
        'finna-menu.js',
        'finna-mylist.js',
        'finna-online-payment.js',
        'finna-organisation-info.js',
        'finna-organisation-map-leaflet.js',
        'finna-primo-adv-search.js',
        'finna-R2.js',
        'finna-recommendation-memory.js',
        'finna-record.js',
        'finna-search-tabs-recommendations.js',
        'finna-street-search.js',
        'vendor/bootstrap-datepicker.min.js',
        'vendor/bootstrap-datepicker.en-GB.min.js',
        'vendor/bootstrap-datepicker.fi.min.js',
        'vendor/bootstrap-datepicker.sv.min.js',
        'vendor/bootstrap-slider.min.js',
        'vendor/jquery.colorhelpers.min.js',
        'vendor/jquery.dataTables.min.js',
        'vendor/dataTables.bootstrap.min.js',
        'vendor/jquery.editable.min.js',
        'vendor/jquery.flot.min.js',
        'vendor/jquery.flot.selection.min.js',
        'vendor/sortable.min.js',
        'vendor/easymde.min.js',
        'vendor/splide.min.js',
        'vendor/gauge.min.js',
        'vendor/priority-nav.min.js',
        'vendor/leaflet.min.js',
        'vendor/leaflet.draw.min.js',
        'vendor/L.Control.Locate.min.js',
        'vendor/js.cookie.js',
        'vendor/select-a11y.iife.js',
        'vendor/popper.min.js',
        'vendor/cally.iife.js',
        'finna-multiselect.js',
        'finna-model-viewer.js',
        'finna-video-element.js',
        'finna-feed-element.js',
        'finna-carousel-manager.js',
        'finna-select-a11y.js',
        'finna-a11y.js',
        'finna-datepicker.js',
    ],
    'less' => [
        'active' => false,
    ],
    'favicon' => 'favicon.ico',
    'icons' => [
        'sets' => [
            'FinnaIcons' => [
                'template' => 'font',
                'prefix' => 'fi fi-',
                'src' => 'finnaicons.css',
            ],
        ],
        'aliases' => [
            'accordion-collapse' => 'Alias:collapse-close',
            'accordion-expand' => 'Alias:collapse-open',
            'adv-search-group-add' => 'FontAwesome:plus-circle',
            'adv-search-group-remove' => 'FinnaIcons:remove',
            'audio-play' => 'FontAwesome:play-circle',
            'authority-corporatename' => 'FinnaIcons:authority-communityname',
            'authority-corporatename-alt' => 'FinnaIcons:authority-corporatename',
            'authority-familyname' => 'FinnaIcons:authority-familyname',
            'authority-personalname' => 'FinnaIcons:authority-personalname',
            'authority-unknownname' => 'FinnaIcons:authority-personalname',
            'back' => 'FontAwesome:chevron-left',
            'back-to-login' => 'FontAwesome:arrow-left',
            'back-to-up' => 'FontAwesome:arrow-up',
            'browse-selected' => 'FontAwesome:arrow-down',
            'browse-unselected' => 'FontAwesome:arrow-right',
            'cart' => 'FontAwesome:suitcase',
            'cart-add' => 'Alias:cart',
            'cart-email' => 'Alias:email',
            'cart-empty' => 'FontAwesome:remove',
            'cart-export' => 'Aias:export',
            'cart-print' => 'Alias:print',
            'cart-remove' => 'FontAwesome:close',
            'cart-save' => 'FontAwesome:save',
            'carousel-follow-link' => 'FinnaIcons:arrow-right',
            'carousel-close' => 'FontAwesome:times',
            'carousel-show' => 'FontAwesome:ellipsis-h',
            'cite' => 'FinnaIcons:asterisk',
            'condensed-collapse' => 'FinnaIcons:up',
            'condensed-expand' => 'FinnaIcons:down',
            'contact-email' => 'Alias:email',
            'database-info' => 'FinnaIcons:info-database',
            'database-browse' => 'FinnaIcons:browse-database',
            'daterange-expand' => 'FinnaIcons:expand',
            'daterange-next' => 'FinnaIcons:right',
            'daterange-prev' => 'FinnaIcons:left',
            'daterange-zoom-in' => 'FinnaIcons:zoom-in',
            'daterange-zoom-out' => 'FinnaIcons:zoom-out',
            'dropdown-close' => 'FontAwesome:caret-up',
            'dropdown-open' => 'FontAwesome:caret-down',
            'download' => 'FontAwesome:download',
            'email' => 'FinnaIcons:envelope',
            'export' => 'FinnaIcons:export',
            'external-link' => 'FontAwesome:chevron-circle-right',
            'facebook' => 'FinnaIcons:facebook',
            'facet-collapse' => 'FinnaIcons:down',
            'facet-exclude' => 'FontAwesome:times',
            'facet-expand' => 'FinnaIcons:right',
            'favorite' => 'FinnaIcons:pin',
            'feed-calendar' => 'FontAwesome:calendar-o',
            'feed-xcal-date' => 'FontAwesome:calendar',
            'feed-xcal-location' => 'FontAwesome:map-marker',
            'feed-xcal-time' => 'FontAwesome:clock-o',
            'feed-pause' => 'FontAwesome:pause-circle',
            'feed-play' => 'FontAwesome:play-circle',
            'filter-collapse' => 'FinnaIcons:up',
            'filter-expand' => 'FinnaIcons:down',
            'finna-suggestions-link' => 'FinnaIcons:arrow-right',
            'full-results-link' => 'FinnaIcons:right',
            'google-plus' => 'FinnaIcons:google+',
            'help' => 'FinnaIcons:help-circle',
            'homepage-link' => 'FinnaIcons:home',
            'hierarchy-tree' => 'FinnaIcons:sitemap',
            'holdings-collapse' => 'FontAwesome:arrow-down',
            'holdings-expand' => 'FontAwesome:arrow-right',
            'holdings-locations-collapse' => 'FinnaIcons:up',
            'holdings-locations-expand' => 'FinnaIcons:down',
            'image-gallery-view' => 'FinnaIcons:image-gallery',
            'image-information-hide' => 'FinnaIcons:up',
            'image-information-show' => 'FinnaIcons:down',
            'image-less' => 'FinnaIcons:up',
            'image-next' => 'FontAwesome:chevron-right',
            'image-more' => 'FinnaIcons:down',
            'image-previous' => 'FontAwesome:chevron-left',
            'image-zoom-in' => 'FinnaIcons:zoom-in',
            'image-zoom-out' => 'FinnaIcons:zoom-out',
            'image-zoom-reset' => 'FinnaIcons:zoom-all',
            'info' => 'FinnaIcons:info-circle',
            'information-pics-view' => 'FinnaIcons:information-pics',
            'instagram' => 'FontAwesome:instagram',
            'language' => 'FinnaIcons:globe',
            'library-card-connect' => 'FontAwesome:link',
            'library-card-disconnect' => 'FontAwesome:unlink',
            'library-card-edit' => 'FontAwesome:pen',
            'library-card-password' => 'FontAwesome:lock',
            'library-card-selected' => 'FontAwesome:check',
            'linkedin' => 'FinnaIcons:linkedin',
            'linked-event-address' => 'FinnaIcons:map',
            'linked-event-audience' => 'FontAwesome:users',
            'linked-event-date' => 'FontAwesome:calendar',
            'linked-event-email' => 'FontAwesome:envelope',
            'linked-event-home' => 'FontAwesome:home',
            'linked-event-location' => 'FontAwesome:map-marker',
            'linked-event-phone' => 'FontAwesome:phone-square',
            'linked-event-time' => 'FontAwesome:clock-o',
            'list-add' => 'FinnaIcons:plus-small',
            'list-edit' => 'FontAwesome:pen',
            'list-note' => 'FontAwesome:file-text-o',
            'list-public' => 'FinnaIcons:public',
            'list-remove' => 'FontAwesome:close',
            'list-save' => 'FontAwesome:files-o',
            'list-tag-edit' => 'FontAwesome:tag',
            'list-tag-delete' => 'FontAwesome:times',
            'login' => 'Alias:user',
            'map' => 'FontAwesome:map-o',
            'map-collapse' => 'FontAwesome:compress',
            'map-expand' => 'FontAwesome:expand',
            'map-narrow' => 'FontAwesome:crosshairs',
            'map-marker' => 'FontAwesome:map-marker',
            'map-remove' => 'FontAwesome:times',
            'menu-open' => 'FontAwesome:angle-down',
            'mobilemenu-bars' => 'FinnaIcons:bars',
            'mobilemenu-close' => 'FinnaIcons:remove',
            'model-3d' => 'FinnaIcons:3d',
            'model-viewer-fullscreen' => 'FontAwesome:fullscreen',
            'my-account' => 'Alias:user',
            'myresearch' => 'FinnaIcons:research',
            'new-window' => 'FinnaIcons:new-window',
            'organisation-info-closed' => 'FontAwesome:exclamation-circle',
            'pager-first' => 'Alias:page-first',
            'pager-last' => 'Alias:page-last',
            'pager-next' => 'Alias:page-next',
            'pager-prev' => 'Alias:page-prev',
            'page-first' => 'FinnaIcons:chevrons-right-circle-filled',
            'page-last' => 'FinnaIcons:chevrons-left-circle-filled',
            'page-next' => 'FinnaIcons:arrow-right-circle-filled',
            'page-prev' => 'FinnaIcons:arrow-left-circle-filled',
            'page-simple-next' => 'FinnaIcons:arrow-right',
            'page-simple-prev' => 'FinnaIcons:arrow-left',
            'phone' => 'FontAwesome:phone-square',
            'popover-collapse' => 'FontAwesome:caret-up',
            'popover-expand' => 'FontAwesome:caret-down',
            'pinterest' => 'FinnaIcons:pinterest',
            'print' => 'FinnaIcons:print',
            'profile' => 'Alias:user',
            'profile-delete' => 'FinnaIcons:remove',
            'profile-export' => 'FontAwesome:download',
            'profile-import' => 'FontAwesome:upload',
            'public' => 'FinnaIcons:public',
            'qrcode' => 'FinnaIcons:qr-code',
            'record-back' => 'FinnaIcons:arrow-left',
            'record-next' => 'FinnaIcons:arrow-right',
            'record-prev' => 'FinnaIcons:arrow-left',
            'remove' => 'FinnaIcons:remove',
            'remove-filter' => 'FinnaIcons:remove',
            'reserve-computer' => 'FontAwesome:desktop',
            'resolver-more-options' => 'FinnaIcons:new-window',
            'route' => 'FontAwesome:bus',
            'rss-feed' => 'FinnaIcons:bell',
            'r2-registered' => 'FinnaIcons:research-open',
            'r2-restricted' => 'FinnaIcons:research',
            'search' => 'FinnaIcons:basic-search',
            'search-add' => 'FinnaIcons:plus-small',
            'search-advanced' => 'FinnaIcons:adv-search',
            'search-authority' => 'Alias:search',
            'search-basic' => 'Alias:search',
            'search-delete' => 'FontAwesome:remove',
            'search-filter-remove' => 'FontAwesome:window-close',
            'search-eds' => 'Alias:search',
            'search-edit' => 'Alias:edit',
            'search-street-location' => 'FontAwesome:crosshairs',
            'search-primo' => 'Alias:search',
            'search-remove' => 'Alias:search-delete',
            'search-save' => 'FontAwesome:plus',
            'search-summon' => 'Alias:search',
            'service-available' => 'FontAwesome:ok',
            'service-unavailable' => 'FontAwesome:remove',
            'share' => 'FinnaIcons:bookmark',
            'show-less' => 'FontAwesome:arrow-up',
            'show-more' => 'FontAwesome:arrow-down',
            'sidefacets-close' => 'FontAwesome:times',
            'sidebar-collapse' => 'FinnaIcons:down',
            'sidebar-expand' => 'FinnaIcons:up',
            'sign-in' => 'Alias:user',
            'sign-out' => 'FinnaIcons:sign-out',
            'spell-expand' => 'FontAwesome:plus-circle',
            'staff-link' => 'FontAwesome:user',
            'staff-view' => 'FontAwesome:code',
            'status-available' => 'FinnaIcons:circle-filled',
            'status-unavailable' => 'FinnaIcons:remove',
            'status-unknown' => 'FontAwesome:circle-o',
            'survey-close' => 'FontAwesome:close',
            'popup-close' => 'FontAwesome:close',
            'time-open' => 'FontAwesome:clock-o',
            'type-dropdown-open' => 'FontAwesome:angle-down',
            'twitter' => 'FinnaIcons:x-twitter',
            'ui-close' => 'FontAwesome:times',
            'ui-edit' => 'FontAwesome:pen',
            'ui-reset-search' => 'FinnaIcons:reset',
            'user' => 'FinnaIcons:user',
            'view-condensed' => 'FinnaIcons:list-compressed',
            'view-grid' => 'FinnaIcons:image-gallery',
            'view-list' => 'FinnaIcons:information-pics',
            'video-play' => 'FontAwesome:play-circle',
            'week-next' => 'FontAwesome:arrow-right',
            'week-prev' => 'FontAwesome:arrow-left',
            'whatsapp' => 'FinnaIcons:phone',
        ],
    ],
];
include 'components.config.php';
return $config;
