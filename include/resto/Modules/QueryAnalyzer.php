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
 * QueryAnalyzer module
 * 
 * Extract OpenSearch EO search parameters from
 * an input string (i.e. searchTerms)
 * A typical searchTerms query can be anything :
 * 
 *      searchTerms = "spot5 images with forest in france between march 2012 and may 2012"
 * 
 * The query analyzer converts this string into comprehensive request.
 * 
 * For instance the previous string will be transformed as :
 *  
 *      eo:platform = SPOT5
 *      time:start = 2012-01-03T00:00:00Z
 *      time:end = 2012-31-05T00:00:00Z
 *      geo:box = POLYGON(( ...coordinates of France country...))
 *      searchTerms = landuse:forest
 * 
 * IMPORTANT : if a word is prefixed by 'xxx=' then QueryAnalyzer considered the string as a key=value pair
 * 
 * Some notes :
 *
 * # Dates
 * 
 * Detected dates format are :
 *      
 *      ISO8601 : see isISO8601($str) in lib/functions.php (e.g 2010-10-23)
 *      <month> <year> (e.g. may 2010)
 *      <year> <month> (e.g. 2010 may)
 *      <day> <month> <year> (e.g. 10 may 2010)
 *      <year> <month> <day> (e.g. 2010 may 10)
 * 
 * # Detected patterns
 * 
 * ## When ?
 * 
 *      <today>
 *      <tomorrow>
 *      <yesterday>
 * 
 *      <after> "date"
 *      <before> "date"
 *      
 *      <between> "date" <and> "date"
 *      <between> "month" <and> "month" (year)
 *      <between> "day" <and> "day" (month) (year)
 *      
 *      <in> "date"
 * 
 *      <last> "(year|month|day)"
 *      <last> "numeric" "(year|month|day)"
 *      "numeric" <last> "(year|month|day)"
 *      "(year|month|day)" <last>
 * 
 *      <next> "(year|month|day)"
 *      <next> "numeric" "(year|month|day)"
 *      "numeric" <next> "(year|month|day)"
 *      "(year|month|day)" <next>
 * 
 *      <since> "numeric" "(year|month|day)"
 *      <since> "month" "year"
 *      <since> "date"
 *      <since> "numeric" <last> "(year|month|day)"
 *      <since> <last> "numeric" "(year|month|day)"
 *      <since> <last> "(year|month|day)"
 *      <since> "(year|month|day)" <last>
 * 
 *      "numeric" "(year|month|day)" <ago>
 * 
 * 
 * A 'modifier' is a term which modify the way following term(s) are handled.
 * Known <modifier> and expected "terms" are :
 * 
 *      <with> "keyword"
 *      <with> "quantity"   // equivalent to "quantity" <greater> (than) 0 "unit"
 * 
 *      <without> "keyword"
 *  
 *      <without> "quantity"   // equivalent to "quantity" <equal> 0 "unit"
 * 
 *      "quantity" <lesser> (than) "numeric" "unit"
 *      "quantity" <greater> (than) "numeric" "unit"
 *      "quantity" <equal> (to) "numeric" "unit"
 *      <lesser> (than) "numeric" "unit" (of) "quantity" 
 *      <greater> (than) "numeric" "unit" (of) "quantity"
 *      <equal> (to) "numeric" "unit" (of) "quantity"
 * 
 *      
 *     
 *      <month>
 *      <season>
 * 
 * @param array $params
 */
require 'QueryAnalyzer/QueryManager.php';
require 'QueryAnalyzer/WhatProcessor.php';
require 'QueryAnalyzer/WhenProcessor.php';
require 'QueryAnalyzer/WhereProcessor.php';
class QueryAnalyzer extends RestoModule {

    /*
     * Error messages
     */
    const INVALID_UNIT = 'INVALID_UNIT';
    const LOCATION_NOT_FOUND = 'LOCATION_NOT_FOUND';
    const MISSING_ARGUMENT = 'MISSING_ARGUMENT';
    const MISSING_UNIT = 'MISSING_UNIT';
    const NOT_UNDERSTOOD = 'NOT_UNDERSTOOD';
    
    /*
     * Query manager
     */
    public $queryManager = null;
    
    /*
     * Processors
     */
    public $whenProcessor = null;
    public $whereProcessor = null;
    public $whatProcessor = null;
    
    /**
     * Constructor
     * 
     * @param RestoContext $context
     * @param RestoUser $user
     * @param RestoModel $model
     */
    public function __construct($context, $user, $model = null) {
        parent::__construct($context, $user);
        
        /*
         * Patterns processor (i.e. When, What and Where)
         * Note : Where processor needs gazetteer
         */
        $this->queryManager = new QueryManager($this->context->dictionary, $model);
        $this->whenProcessor = new WhenProcessor($this->queryManager, $this->context, $this->user);
        $this->whatProcessor = new WhatProcessor($this->queryManager);
        if (isset($context->modules['Gazetteer'])) {
            $this->whereProcessor = new WhereProcessor($this->queryManager, new Gazetteer($context, $user, $context->modules['Gazetteer']));
        }
        
    }

    /**
     * Run module - this function should be called by Resto.php
     * 
     * @param array $elements : route element
     * @param array $data : POST or PUT parameters
     * 
     * @return string : result from run process in the $context->outputFormat
     */
    public function run($elements) {
        
        /*
         * Only GET method on 'search' route with json outputformat is accepted
         */
        if ($this->context->method !== 'GET' || count($elements) !== 0) {
            RestoLogUtil::httpError(404);
        }
        $query = isset($this->context->query['searchTerms']) ? $this->context->query['searchTerms'] : (isset($this->context->query['q']) ? $this->context->query['q'] : null);
        
        return $this->analyze($query);
        
    }
    
    /**
     * Query analyzer process searchTerms and modify query parameters accordingly
     * 
     * @param string $query
     * @return type
     */
    public function analyze($query) {

        $startTime = microtime(true);
        
        /*
         * QueryAnalyzer only apply on searchTerms filter
         */
        if (!isset($query)) {
            RestoLogUtil::httpError(400, 'Missing mandatory searchTerms');
        }
       
        return array(
            'query' => $query,
            'language' => $this->context->dictionary->language,
            'analyze' => $this->process($query),
            'processingTime' => microtime(true) - $startTime
        );
        
    }
    
    /**
     * Return array of search terms from input query
     * 
     * @param string $query
     * @return array
     */
    private function process($query) {
        
        /*
         * Initialize QueryManager
         */
        $this->queryManager->setWords($this->queryToWords($query));
        
        /*
         * Extract (in this order !) "what", "when" and "where" elements from query
         * Suppose that query is structured (i.e. is a sentence) 
         */
        $this->processWhat(true);
        $this->processWhen(true);
        $this->processWhere(true);
        
        /*
         * Remaining words are unstructured (i.e. not a sentence)
         */
        $this->processWhat(false);
        $this->processWhen(false);
        $this->processWhere(false);
        
        /*
         * Return processing results
         */
        return array(
            'Processed' => $this->queryManager->words,
            'What' => $this->whatProcessor->result,
            'When' => $this->whenProcessor->result,
            'Where' => isset($this->whereProcessor) ? $this->whereProcessor->result : array(),
            'Errors' => $this->queryManager->errors
        );
        
    }
    
    /**
     * Extract time patterns from query
     * 
     * @param boolean $fromSentence
     */
    private function processWhen($fromSentence) {
        $fromSentence ? $this->processSentence('when') : $this->processWords('when');
    }
    
    /**
     * Extract location patterns from query
     * Note: needs Gazetteer module up and running
     * 
     * @param boolean $fromSentence
     */
    private function processWhere($fromSentence) {
        if (isset($this->whereProcessor)) {
            $fromSentence ? $this->processSentence('where') : $this->processWords('where');
        }
    }
    
    /**
     * Extract what patterns from query
     * 
     * @param boolean $fromSentence
     */
    private function processWhat($fromSentence) {
        $fromSentence ? $this->processSentence('what') : $this->processWords('what');
    }
    
    /*
     * Extract What, When and Where patterns from unstructured words
     * 
     * @param string $type
     */
    private function processWords($type) {
        for ($i = 0; $i < $this->queryManager->length; $i++) {
            if ($this->queryManager->isValidPosition($i)) {
                switch ($type) {
                    case 'what':
                        $this->whatProcessor->processWith($i, 0);
                        break;
                    case 'when':
                        $this->whenProcessor->processIn($i, 0);
                        break;
                    case 'where':
                        $this->whereProcessor->processIn($i, 0);
                        break;
                }
            }
        }
    }
    
    /*
     * Extract What, When and Where patterns from sentence
     * 
     * @param string $type
     */
    private function processSentence($type) {
        for ($i = 0; $i < $this->queryManager->length; $i++) {
            if ($this->queryManager->isValidPosition($i)) {
                switch ($type) {
                    case 'what':
                        $this->processModifier($this->context->dictionary->get(RestoDictionary::QUANTITY_MODIFIER, $this->queryManager->words[$i]['word']), $this->whatProcessor, $i);
                        break;
                    case 'when':
                        $this->processModifier($this->context->dictionary->get(RestoDictionary::TIME_MODIFIER, $this->queryManager->words[$i]['word']), $this->whenProcessor, $i);
                        break;
                    case 'where':
                        $this->processModifier($this->context->dictionary->get(RestoDictionary::LOCATION_MODIFIER, $this->queryManager->words[$i]['word']), $this->whereProcessor, $i);
                        break;
                }
            }
        }
    }
    
    /**
     * Process Modifier
     * 
     * @param string $modifier
     * @param string $processorClass
     * @param integer $position
     * @return array
     */
    private function processModifier($modifier, $processorClass, $position) {
        if (isset($modifier)) {
            $functionName = 'process' . ucfirst($modifier);
            if (method_exists($processorClass, $functionName)) {
                call_user_func_array(array($processorClass, $functionName), array($position));
            }
        }
    }
    
    /**
     * 
     * Explode query into normalized array of words
     * 
     * In order :
     *   - replace in query ' , and ; characters by space
     *   - transliterate query string afterward (i.e. all words in lowercase without accent)
     *   - split remaining query - split each terms with (" " character)
     *   - add a space between numeric value and '%' character
     * 
     * @param string $query
     * @return array
     */
    private function queryToWords($query) {
        $words = array();
        $rawWords = RestoUtil::splitString($this->context->dbDriver->normalize(str_replace(array('\'', ',', ';'), ' ', $query)));
        for ($i = 0, $ii = count($rawWords); $i < $ii; $i++) {
            $term = trim($rawWords[$i]);
            if ($term === '') {
                continue;
            }
            $splitted = explode('%', $term);
            if (count($splitted) === 2 && is_numeric($splitted[0])) {
                $words[] = $splitted[0];
                $words[] = '%';
            }
            else {
                $words[] = $rawWords[$i];
            }
        }
        return $words;
    }
    
}