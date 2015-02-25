<?php

namespace Statham;

use Statham\Json\Validator as JsonValidator;
use Statham\Schema\Cache;
use Statham\Schema\Compiler;
use Statham\Schema\Validator as SchemaValidator;

class Statham {

    public $options = array(
        // report error paths as an array of path segments to get to the offending node
        'reportPathsAsArray' => false,
        'ignoreUnresolvableReferences' => false
    );

    /** @var Cache */
    public $cache;
    /** @var Report */
    public $report;

    public function __construct(array $options = array()){
        $this->options = array_merge($this->options, $options);
        $this->cache = new Cache($this);
        $this->compiler = new Compiler($this);
        $this->schemaValidator = new SchemaValidator($this);
        $this->jsonValidator = new JsonValidator($this);
    }

    public function compileSchema($schema){

        $this->report = new Report(null, $this->options);
        $this->compiler->compileSchema($this->report, $schema, $this);

        return $this->report->isValid();

    }

    public function validateSchema($schema){

        $this->report = new Report(null, $this->options);

        $compiled = $this->compiler->compileSchema($this->report, $schema);
        if($compiled){
            $this->schemaValidator->validateSchema($this->report, $schema, $this);
        }

        return $this->report->isValid();

    }

    public function validate($json, $schema){

        $this->report = new Report(null, $this->options);

        if(is_object($schema)){
            $schema = $this->cache->getSchemaByReference($schema);
        }

        if(is_string($schema)){
            $schema = $this->cache->getSchemaByUri($this->report, $schema);
        }

        $compiled = $this->compiler->compileSchema($this->report, $schema);

        if(!$compiled)
            return false;

        $validated = $this->schemaValidator->validateSchema($this->report, $schema);

        if(!$validated)
            return false;

        $this->jsonValidator->validate($this->report, $schema, $json);

        return $this->report->isValid();
    }

    public function getErrors(){
        if(isset($this->report))
            return $this->report->getErrors();
        else
            return [];
    }

    public function getMissingRemoteReferences(){
        $missingReferences = $this->getMissingReferences();
        $missingRemoteReferences = [];
        foreach($missingReferences as $missing){
            $remoteReference = $this->cache->getRemotePath($missing);
            if($remoteReference){
                $missingRemoteReferences[$remoteReference] = true;
            }
        }
        return array_keys($missingRemoteReferences);
    }

    public function getMissingReferences(){
        $missing = [];
        foreach($this->getErrors() as $error){
            if($error['code'] === 'REF_UNRESOLVED'){
                $reference = $error['params'][0];
                $missing[$reference] = true;
            }
        }
        return array_keys($missing);
    }

    public function setRemoteReference($uri, $schema){
        $this->cache->cacheSchemaByUri($uri, $schema);
    }

}