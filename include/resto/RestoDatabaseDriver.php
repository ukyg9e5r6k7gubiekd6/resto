<?php

/*
 * RESTo
 * 
 * RESTo - REstful Semantic search Tool for geOspatial 
 * 
 * Copyright 2013 Jérôme Gasperi <https://github.com/jjrom>
 * 
 * jerome[dot]gasperi[at]gmail[dot]com
 * 
 * 
 * This software is governed by the CeCILL-B license under French law and
 * abiding by the rules of distribution of free software.  You can  use,
 * modify and/ or redistribute the software under the terms of the CeCILL-B
 * license as circulated by CEA, CNRS and INRIA at the following URL
 * "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and  rights to copy,
 * modify and redistribute granted by the license, users are provided only
 * with a limited warranty  and the software's author,  the holder of the
 * economic rights,  and the successive licensors  have only  limited
 * liability.
 *
 * In this respect, the user's attention is drawn to the risks associated
 * with loading,  using,  modifying and/or developing or reproducing the
 * software by the user in light of its specific status of free software,
 * that may mean  that it is complicated to manipulate,  and  that  also
 * therefore means  that it is reserved for developers  and  experienced
 * professionals having in-depth computer knowledge. Users are therefore
 * encouraged to load and test the software's suitability as regards their
 * requirements in conditions enabling the security of their systems and/or
 * data to be ensured and,  more generally, to use and operate it in the
 * same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL-B license and that you accept its terms.
 * 
 */

/**
 * RESTo Database
 */
abstract class RestoDatabaseDriver {
    
    /*
     * Debug mode
     */
    public $debug = false;
    
    /*
     * Results per page
     */
    public $resultsPerPage = 50;

    /*
     * Valid facet type with parent hierarchy
     */
    public $validFacetTypes = array(
        'collection' => null,
        'year' => null,
        'month' => 'year',
        'day' => 'month',
        'continent' => null,
        'country' => 'continent',
        'region' => 'country',
        'state' => 'region',
        'landuse' => null,
        'platform' => null,
        'instrument' => 'platform',
        'sensorMode' => 'instrument',
        'productType' => null,
        'processingLevel' => null
    );

    /**
     * Return normalized $sentence i.e. in lowercase and without accents
     * This function is superseed in RestoDabaseDriver_PostgreSQL and use
     * the inner function lower(unaccent($sentence)) defined in installDB.sh
     * 
     * @param string $sentence
     */
    public function normalize($sentence) {
        return $sentence;
    }

    /**
     * Return database handler
     * 
     * @return database handler
     */
    abstract public function getHandler();

    /**
     * List all collections
     * 
     * @return array
     * @throws Exception
     */
    abstract public function listCollections();
    
    /**
     * List all groups
     * 
     * @return array
     * @throws Exception
     */
    abstract public function listGroups();
    
    /**
     * Check if collection $name exists within resto database
     * 
     * @param string $name - collection name
     * @return boolean
     * @throws Exception
     */
    abstract public function collectionExists($name);

    /**
     * Check if feature identified by $identifier exists within {schemaName}.features table
     * 
     * @param string $identifier - feature unique identifier 
     * @param string $schema - schema name
     * @return boolean
     * @throws Exception
     */
    abstract public function featureExists($identifier, $schema = null);

    /**
     * Check if user identified by $identifier exists within database
     * 
     * @param string $identifier - user email
     * 
     * @return boolean
     * @throws Exception
     */
    abstract public function userExists($identifier);
    
    /**
     * Insert feature within collection
     * 
     * @param string $collectionName
     * @param array $elements
     * @param RestoModel $model
     * @throws Exception
     */
    abstract public function storeFeature($collectionName, $elements, $model);

    /**
     * Remove feature from database
     * 
     * @param RestoFeature $feature
     */
    abstract public function removeFeature($feature);

    /**
     * Return true if resource is shared (checked with proof)
     * 
     * @param string $resourceUrl
     * @param string $token
     * @return boolean
     */
    abstract public function isValidSharedLink($resourceUrl, $token);
    
    /**
     * Return true if resource is within cart
     * 
     * @param string $identifier
     * @param string $resourceUrl
     * @return boolean
     * @throws exception
     */
    abstract public function isInCart($identifier, $resourceUrl);
    
    /**
     * Return cart for user
     * 
     * @param string $identifier
     * @return array
     * @throws exception
     */
    abstract public function getCartItems($identifier);
    
    /**
     * Add resource url to cart
     * 
     * @param string $identifier
     * @param string $itemId
     * @param string $resourceUrl
     * @param array $resourceInfo
     * @return boolean
     * @throws exception
     */
    abstract public function addToCart($identifier, $itemId, $resourceUrl, $resourceInfo);
    
    /**
     * Remove resource from cart
     * 
     * @param string $identifier
     * @param string $itemId
     * @return boolean
     * @throws exception
     */
    abstract public function removeFromCart($identifier, $itemId);
    
    /**
     * Get user profile
     * 
     * @param string $identifier : can be email (or string) or integer (i.e. uid)
     * @param string $password : if set then profile is returned only if password is valid
     * @return array : this function should return array('userid' => -1, 'groupname' => 'unregistered')
     *                 if user is not found in database
     * @throws exception
     */
    abstract public function getUserProfile($identifier, $password = null);

    /**
     * Get users profile
     * 
     * @param type $keyword
     * @param type $min
     * @param type $number
     * @return array
     * @throws Exception
     */
    abstract public function getUsersProfiles($keyword = null, $min = 0, $number = 50);

    /**
     * Save user profile to database i.e. create new entry if user does not exist
     * 
     * @param array $profile
     * @return integer (userid)
     * @throws exception
     */
    abstract public function storeUserProfile($profile);

    /**
     * Update user profile to database
     * 
     * @param array $profile
     * @return integer (userid)
     * @throws exception
     */
    abstract public function updateUserProfile($profile);
    
    /**
     * Activate user
     * 
     * @param string $userid
     * @throws Exception
     */
    abstract public function activateUser($userid);
    
     /**
     * Deactivate user
     * 
     * @param string $userid
     * @throws Exception
     */
    abstract public function deactivateUser($userid);
    
    /**
     * Return rights from user $identifier
     * 
     * @param string $identifier
     * @param string $collectionName
     * @param string $featureIdentifier
     * @return array
     * @throws Exception
     */
    abstract public function getRights($identifier, $collectionName, $featureIdentifier = null);

    /**
     * Get complete rights list for $identifier
     * 
     * @param string $identifier
     * @return array
     * @throws Exception
     */
    abstract public function getRightsList($identifier);
    
    /**
     * Store a right to database
     *     
     *     array(
     *          'search' => // true or false
     *          'visualize' => // true or false
     *          'download' => // true or false
     *          'canpost' => // true or false
     *          'canput' => // true or false
     *          'candelete' => //true or false
     *          'filters' => array(...)
     * 
     * @param array $rights
     * @param string $identifier
     * @param string $collectionName
     * @param string $featureIdentifier
     * @throws Exception
     */
    abstract function storeRights($rights, $identifier, $collectionName, $featureIdentifier = null);
    
    /**
     * Update rights to database
     *     
     *     array(
     *          'search' => // true or false
     *          'visualize' => // true or false
     *          'download' => // true or false
     *          'canpost' => // true or false
     *          'canput' => // true or false
     *          'candelete' => //true or false
     *          'filters' => array(...)
     *     )
     * 
     * @param array $rights
     * @param string $identifier
     * @param string $collectionName
     * @param string $featureIdentifier
     * 
     * @throws Exception
     */
    abstract public function updateRights($rights, $identifier, $collectionName, $featureIdentifier = null);
    
    /**
     * Delete rights from database
     * 
     * @param string $identifier
     * @param string $collectionName
     * @param string $featureIdentifier
     * 
     * @throws Exception
     */
    abstract public function deleteRights($identifier, $collectionName = null, $featureIdentifier = null);
    
    /**
     * Check if user signed collection license
     * 
     * @param string $identifier
     * @param string $collectionName
     * @return boolean
     * @throws Exception
     */
    abstract public function licenseSigned($identifier, $collectionName);

    /**
     * Get signed licenses for user
     * 
     * @param string $identifier
     * @return array
     * @throws Exception
     */
    abstract public function getSignedLicenses($identifier);

    /**
     * Get collection description
     * 
     * @param string $collectionName
     * @return array
     * @throws Exception
     */
    abstract public function getCollectionDescription($collectionName);

    /**
     * Get description of all collections
     * 
     * @return array
     * @throws Exception
     */
    abstract public function getCollectionsDescriptions();

    /**
     * Remove collection from RESTo database
     * 
     * @param RestoCollection $collection
     * @return array
     * @throws Exception
     */
    abstract public function removeCollection($collection);

    /**
     * Save collection to database
     * 
     * @param RestoCollection $collection
     * @throws Exception
     */
    abstract public function storeCollection($collection);

    /**
     * Save query to database
     * 
     * @param string $userid : User id
     * @param array $query
     * @throws Exception
     */
    abstract public function storeQuery($userid, $query);

    /**
     * 
     * Get array of features descriptions
     *
     * @param array $params
     * @param RestoModel $model
     * @param string $collectionName
     * @param integer $limit
     * @param integer $offset
     * @param boolean $count : true to return the total number of results without pagination
     * 
     * @return array
     * @throws Exception
     */
    abstract public function getFeaturesDescriptions($params, $model, $collectionName, $limit, $offset, $count = false);

    /**
     * 
     * Get feature description
     *
     * @param integer $identifier
     * @param RestoModel $model
     * @param RestoCollection $collection
     * @param array $filters
     * 
     * @return array
     * @throws Exception
     */
    abstract public function getFeatureDescription($identifier, $model, $collection = null, $filters = array());

    /**
     * 
     * Return keywords from database
     *
     * @param string $language : ISO A2 language code
     * 
     * @return array
     * @throws Exception
     */
    abstract public function getKeywords($language);

    /**
     * Store facet within database (i.e. add 1 to the counter of facet if exist)
     * 
     * Input facet structure :
     *      array(
     *          array(
     *              'type' => 'platform',
     *              'value' => 'PHR'
     *          ),
     *          array(
     *              'type' => 'year',
     *              'value' => '2011'
     *          ),
     *          ...
     *      )
     * 
     * @param array $facets 
     * @param type $collectionName
     */
    abstract public function storeFacets($facets, $collectionName);

    /**
     * Return facets elements from a type for a given collection
     * Returned array structure
     * 
     *      array(
     *          'type#' => array(
     *              'value1' => count1,
     *              'value2' => count2,
     *              'parent' => array(
     *                  'value3' => count3,
     *                  ...
     *              )
     *              ...
     *          ),
     *          'type2' => array(
     *              ...
     *          ),
     *          ...
     *      )
     *  
     * @param string $collectionName
     * @param array $type
     * @return array
     */
    abstract public function getFacets($collectionName = null, $type = array());

    /**
     * Remove facet for collection i.e. decrease by one counter
     * 
     * @param array $facet
     * @param string $collectionName
     */
    abstract public function removeFacet($facet, $collectionName);

    /**
     * Return resource description from database i.e. fields
     *  - resource
     *  - resourceMimeType
     *  - resourceSize
     *  - resourceChecksum
     * 
     * @param string $identifier
     * @param string $collectionName
     * @return array  ('url', 'mimeType', 'size', 'checksum)
     * 
     * @throws Exception
     */
    abstract public function getResourceFields($identifier, $collectionName = null);

    /**
     * Get user history
     * 
     * @param integer $userid
     * @param array $options
     *          
     *      array(
     *         'orderBy' => // order field (default querytime),
     *         'ascOrDesc' => // ASC or DESC (default DESC)
     *         'collectionName' => // collection name
     *         'service' => // 'search', 'download' or 'visualize' (default null),
     *         'startIndex' => // (default 0),
     *         'numberOfResults' => // (default 50)
     *     )
     *          
     * @return array
     * @throws Exception
     */
    abstract public function getHistory($userid = null, $options = array());
}
