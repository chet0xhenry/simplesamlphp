<?php


/**
 * This file defines a flat file metadata source.
 * Instantiation of session handler objects should be done through
 * the class method getMetadataHandler().
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */
class SimpleSAML_Metadata_MetaDataStorageHandlerConfigFile extends SimpleSAML_Metadata_MetaDataStorageSource
{
    /**
     * This is the global config which also stores the metadata
     *
     * @var array
     */
    private $config;
    
    
    /**
     * This is an associative array which stores the different metadata sets we have loaded.
     *
     * @var array
     */
    private $cachedMetadata = array();

    
    /**
     * This constructor initializes the metadata storage handler with the
     * specified configuration. 
     *
     * @param array $config An associative array with the configuration for this handler.
     */
    protected function __construct($config)
    {
        assert('is_array($config)');

        // get the configuration
        $this->config = SimpleSAML_Configuration::getInstance();
    }


    /**
     * This function loads the given set of metadata from a file our metadata directory.
     * This function returns null if it is unable to locate the given set in the metadata directory.
     *
     * @param string $set The set of metadata we are loading.
     *
     * @return array An associative array with the metadata, or null if we are unable to load metadata from the given
     *     file.
     * @throws Exception If the metadata set cannot be loaded.
     */
    private function load($set)
    {
        $metadata = $this->config->getValue('metadata.array');
        $metadata = $metadata[$set];

        if (!is_array($metadata)) {
            throw new Exception('Could not load metadata set ['.$set.'] from global config');
        }

        return $metadata;
    }
    
    
    /**
     * This function retrieves the given set of metadata. It will return an empty array if it is
     * unable to locate it.
     *
     * @param string $set The set of metadata we are retrieving.
     *
     * @return array An associative array with the metadata. Each element in the array is an entity, and the
     *         key is the entity id.
     */
    public function getMetadataSet($set)
    {
        if (array_key_exists($set, $this->cachedMetadata)) {
            return $this->cachedMetadata[$set];
        }

        $metadataSet = $this->load($set);
        if ($metadataSet === null) {
            $metadataSet = array();
        }

        // add the entity id of an entry to each entry in the metadata
        foreach ($metadataSet as $entityId => &$entry) {
            if (preg_match('/__DYNAMIC(:[0-9]+)?__/', $entityId)) {
                $entry['entityid'] = $this->generateDynamicHostedEntityID($set);
            } else {
                $entry['entityid'] = $entityId;
            }
        }

        $this->cachedMetadata[$set] = $metadataSet;

        return $metadataSet;
    }


    private function generateDynamicHostedEntityID($set)
    {
        // get the configuration
        $baseurl = \SimpleSAML\Utils\HTTP::getBaseURL();

        if ($set === 'saml20-idp-hosted') {
            return $baseurl.'saml2/idp/metadata.php';
        } elseif ($set === 'shib13-idp-hosted') {
            return $baseurl.'shib13/idp/metadata.php';
        } elseif ($set === 'wsfed-sp-hosted') {
            return 'urn:federation:'.\SimpleSAML\Utils\HTTP::getSelfHost();
        } elseif ($set === 'adfs-idp-hosted') {
            return 'urn:federation:'.\SimpleSAML\Utils\HTTP::getSelfHost().':idp';
        } else {
            throw new Exception('Can not generate dynamic EntityID for metadata of this type: ['.$set.']');
        }
    }
}
