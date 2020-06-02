<?php
/**
 * Modified BeSimple SoapClient for Zend HTTP Client
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2020.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
namespace Finna\ILS\Driver;

use BeSimple\SoapCommon\Helper;
use VuFindHttp\HttpServiceInterface;

/**
 * Modified SoapClient that uses a cURL style proxy wrapper that in turn uses Zend
 * HTTP Client for all underlying HTTP requests in order to use proper authentication
 * for all requests. This also adds NTLM support. A custom WSDL downloader resolves
 * remote xsd:includes and allows caching of all remote referenced items.
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Andreas Schamberger <mail@andreass.net>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
class ProxySoapClient extends \BeSimple\SoapClient\SoapClient
{
    /**
     * Constructor.
     *
     * @param HttpServiceInterface $httpService HTTP Service
     * @param string               $wsdl        WSDL file
     * @param array(string=>mixed) $options     Options array
     *
     * @throws \SoapFault
     */
    public function __construct(HttpServiceInterface $httpService, $wsdl,
        array $options = []
    ) {
        // tracing enabled: store last request/response header and body
        if (isset($options['trace']) && true === $options['trace']) {
            $this->tracingEnabled = true;
        }
        // store SOAP version
        if (isset($options['soap_version'])) {
            $this->soapVersion = $options['soap_version'];
        }

        $this->curl = new ProxyCurl($httpService, $options);

        if (isset($options['extra_options'])) {
            unset($options['extra_options']);
        }

        $wsdlFile = $this->loadWsdl($wsdl, $options);
        // TODO $wsdlHandler = new WsdlHandler($wsdlFile, $this->soapVersion);
        $this->soapKernel = new \BeSimple\SoapClient\SoapKernel();
        // set up type converter and mime filter
        $this->configureMime($options);
        // we want the exceptions option to be set
        $options['exceptions'] = true;
        // disable obsolete trace option for native SoapClient as we need to do our
        // own tracing anyways
        $options['trace'] = false;
        // disable WSDL caching as we handle WSDL caching for remote URLs ourself
        $options['cache_wsdl'] = WSDL_CACHE_NONE;

        try {
            // Kludge to call grandparent's constructor
            call_user_func(
                [get_parent_class(get_parent_class($this)), '__construct'],
                $wsdlFile, $options
            );
        } catch (\SoapFault $soapFault) {
            // Discard cached WSDL file if there's a problem with it
            if ('WSDL' === $soapFault->faultcode) {
                unlink($wsdlFile);
            }

            throw $soapFault;
        }
    }

    /**
     * Downloads WSDL files with cURL. Uses all SoapClient options for
     * authentication. Uses the WSDL_CACHE_* constants and the 'soap.wsdl_*'
     * ini settings. Does only file caching as SoapClient only supports a file
     * name parameter.
     *
     * @param string               $wsdl    WSDL file
     * @param array(string=>mixed) $options Options array
     *
     * @return string
     */
    protected function loadWsdl($wsdl, array $options)
    {
        // option to resolve wsdl/xsd includes
        $resolveRemoteIncludes = true;
        if (isset($options['resolve_wsdl_remote_includes'])) {
            $resolveRemoteIncludes = $options['resolve_wsdl_remote_includes'];
        }
        // option to enable cache
        $wsdlCache = WSDL_CACHE_DISK;
        if (isset($options['cache_wsdl'])) {
            $wsdlCache = $options['cache_wsdl'];
        }
        $wsdlDownloader
            = new WsdlDownloader($this->curl, $resolveRemoteIncludes, $wsdlCache);
        try {
            $cacheFileName = $wsdlDownloader->download($wsdl);
        } catch (\RuntimeException $e) {
            throw new \SoapFault(
                'WSDL',
                "SOAP-ERROR: Parsing WSDL: Couldn't load from '" . $wsdl
                . "' : failed to load external entity \"" . $wsdl . '"'
            );
        }

        return $cacheFileName;
    }
}

/**
 * WsdlDownloader that fixes storing the modified WSDL.
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Andreas Schamberger <mail@andreass.net>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 * @todo     Remove when https://github.com/smartboxgroup/BeSimpleSoap/pull/19 is
 * merged and update is available via composer
 */
class WsdlDownloader extends \BeSimple\SoapClient\WsdlDownloader
{
    /**
     * Resolves remote WSDL/XSD includes within the WSDL files.
     *
     * @param string  $xml            XML file
     * @param string  $cacheFilePath  Cache file name
     * @param boolean $parentFilePath Parent file name
     *
     * @return void
     */
    protected function resolveRemoteIncludes($xml, $cacheFilePath,
        $parentFilePath = null
    ) {
        $doc = new \DOMDocument();
        $parsedOk = $doc->loadXML($xml);
        if (!$parsedOk) {
            throw new \RuntimeException("SOAP-ERROR: Couldn't parse xml: $xml");
        }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace(Helper::PFX_XML_SCHEMA, Helper::NS_XML_SCHEMA);
        $xpath->registerNamespace(Helper::PFX_WSDL, Helper::NS_WSDL);

        // WSDL include/import
        $query = './/' . Helper::PFX_WSDL . ':include | .//'
            . Helper::PFX_WSDL . ':import';
        $nodes = $xpath->query($query);
        if ($nodes->length > 0) {
            foreach ($nodes as $node) {
                $location = $node->getAttribute('location');
                if ($this->isRemoteFile($location)) {
                    $location = $this->download($location);
                    $node->setAttribute('location', $location);
                } elseif (null !== $parentFilePath) {
                    $location = $this
                        ->resolveRelativePathInUrl($parentFilePath, $location);
                    $location = $this->download($location);
                    $node->setAttribute('location', $location);
                }
            }
        }

        // XML schema include/import
        $query = './/' . Helper::PFX_XML_SCHEMA . ':include | .//'
            . Helper::PFX_XML_SCHEMA . ':import';
        $nodes = $xpath->query($query);
        if ($nodes->length > 0) {
            foreach ($nodes as $node) {
                if ($node->hasAttribute('schemaLocation')) {
                    $schemaLocation = $node->getAttribute('schemaLocation');
                    if ($this->isRemoteFile($schemaLocation)) {
                        $schemaLocation = $this->download($schemaLocation);
                        $node->setAttribute('schemaLocation', $schemaLocation);
                    } elseif (null !== $parentFilePath) {
                        $schemaLocation = $this->resolveRelativePathInUrl(
                            $parentFilePath, $schemaLocation
                        );
                        $schemaLocation = $this->download($schemaLocation);
                        $node->setAttribute('schemaLocation', $schemaLocation);
                    }
                }
            }
        }

        $xmlResolved = $doc->saveXML();

        if (empty($xmlResolved) || strlen($xmlResolved) < self::XML_MIN_LENGTH) {
            throw new \RuntimeException(
                "SOAP-ERROR: Detected empty wsdl in: $cacheFilePath for xml: $xml"
            );
        }

        file_put_contents($cacheFilePath, $xmlResolved);
    }
}
