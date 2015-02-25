<?php


namespace Statham\Schema;

use Statham\Report;
use Statham\Statham;

class Cache {

    /** @var Statham  */
    private $statham;
    /** @var array */
    private $cache = [];
    /** @var array */
    private $referenceCache = [];

    public function __construct(Statham $statham){
        $this->statham = $statham;
    }

    public function checkCacheForUri($uri){
        $uri = $this->getRemotePath($uri);
        return isset($this->cache[$uri]);
    }

    public function cacheSchemaByUri($id, $schema) {
        $uri = $this->getRemotePath($id);
        $this->cache[$uri] = $schema;
    }

    public function getSchemaByUri(Report $report, $uri, $root = null) {
        $remotePath = $this->getRemotePath($uri);
        $queryPath = $this->getQueryPath($uri);

        $result = null;
        if(empty($remotePath))
            $result = $root;
        else if(isset($this->cache[$remotePath]))
            $result = $this->cache[$remotePath];

        if(!empty($result) && !empty($remotePath) && $result !== $root){
            $report->pathPush($remotePath);

            $remoteReport = new Report($report);
            if($this->statham->compiler->compileSchema($remoteReport, $result, $this)){
                $this->statham->schemaValidator->validateSchema($remoteReport, $result, $this);
            }

            $remoteReportIsValid = $remoteReport->isValid();
            if(!$remoteReportIsValid){
                $report->addError('REMOTE_NOT_VALID', [$uri], $remoteReport);
            }

            $report->pathPop();

            if(!$remoteReportIsValid)
                return null;

        }

        if(!empty($result) && !empty($queryPath)){

            $parts = explode('/', $queryPath);
            for($i = 0, $lim = count($parts); $i < $lim; $i++){
                $key = $this->decodeJSONPointer($parts[$i]);
                if($i === 0){
                    $result = $this->findId($result, $key);
                }
                else if(isset($result->$key)) {
                    $result = $result->$key;
                }
            }

        }

        return $result;

    }

    public function getSchemaByReference($key){

        foreach($this->referenceCache as $i => $cached){
            if($cached[0] === $key){
                return $this->referenceCache[$i][1];
            }
        }

        $schema = json_decode(json_encode($key));
        $this->referenceCache[] = [$key, $schema];
        return $schema;

    }

    public function removeFromCacheByUri($uri) {
        $uri = $this->getRemotePath($uri);
        unset($this->cache[$uri]);
    }

    public function getRemotePath($uri){
        $pos = strpos($uri, '#');
        return $pos === false ? $uri : substr($uri, 0, $pos);
    }

    private function getQueryPath($uri) {
        $pos = strpos($uri, '#');
        return $pos === false ? null : substr($uri, $pos + 1);
    }

    private function decodeJSONPointer($str) {
        // http://tools.ietf.org/html/draft-ietf-appsawg-json-pointer-07#section-3
        return preg_replace_callback('/~[0-1]/', function($x){
            return $x === '~1' ? '/' : '~';
        }, rawurldecode($str));
    }

    private function findId($schema, $id){

        if(!$id)
            return $schema;

        if(isset($schema->id)){
            if($schema->id === $id || $schema->id[0] === '#' && substr($schema->id, 1) === $id){
                return $schema;
            }
        }

        $keys = get_object_vars($schema);
        for($i = count($keys); $i >= 0; $i--){
            $k = $keys[$i];
            if(strpos($k, '__$') !== false)
                continue;

            $result = $this->findId($schema->$k, $id);
            if($result) { return $result; }
        }

        return null;

    }



}