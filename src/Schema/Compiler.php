<?php


namespace Statham\Schema;


use Statham\Report;
use Statham\Statham;
use Statham\Utils;

class Compiler {

    protected $statham;

    public function __construct(Statham $statham){
        $this->statham = $statham;
    }

    public function compileSchema(Report $report, $schema){

        $compiled = '__$compiled';
        $missingReferences = '__$missingReferences';

        if(is_string($schema)){
            $loadedSchema = $this->statham->cache->getSchemaByUri($report, $schema);
            if(!$loadedSchema){
                $report->addError('SCHEMA_NOT_REACHABLE', [$schema]);
                return false;
            }
            $schema = $loadedSchema;
        }

        if(is_array($schema)){
            return $this->compileArrayOfSchemas($report, $schema);
        }

        if(isset($schema->$compiled, $schema->id) && !$this->statham->cache->checkCacheForUri($schema->id)){
            unset($schema->$compiled);
        }

        if(isset($schema->$compiled)){
            return true;
        }

        if(isset($schema->id)){
            $this->statham->cache->cacheSchemaByUri($schema->id, $schema);
        }

        $isValidExceptReferences = $report->isValid();
        unset($schema->$missingReferences);

        $refs = $this->collectReferences($schema);
        foreach($refs as $ref){
            $response = $this->statham->cache->getSchemaByUri($report, $ref['ref'], $schema);
            if(!$response){

                $isAbsolute = Utils::isAbsoluteUri($ref['ref']);
                $isDownloaded = false;
                $ignoreUnresolvableRemotes = $this->statham->options['ignoreUnresolvableReferences'];

                if($isAbsolute){
                    $isDownloaded = $this->statham->cache->checkCacheForUri($ref['ref']);
                }

                if(!$isAbsolute || !$isDownloaded && !$ignoreUnresolvableRemotes){
                    $report->pathPush($ref['path']);
                    $report->addError('UNRESOLVABLE_REFERENCE', [$ref['ref']]);
                    $report->pathPop(count($ref['path']));

                    if($isValidExceptReferences){
                        if(!isset($schema->$missingReferences))
                            $schema->$missingReferences = [];

                        $schema->{$missingReferences}[] = $ref;
                    }
                }

            }
            $ref['obj']->{'__' . $ref['key'] . 'Resolved'} = $response;
        }

        $isValid = $report->isValid();
        if($isValid){
            $schema->$compiled = true;
        }
        else if(isset($schema->id)){
            $this->statham->cache->removeFromCacheByUri($schema->id);
        }

        return $isValid;

    }

    private function compileArrayOfSchemas(Report $report, array $schemas){

        $compiled = 0;
        $lastLoopCompiled = null;

        do {

            foreach($report->errors as $i => $error){
                if($error['code'] === 'UNRESOLVABLE_REFERENCE'){
                    array_splice($report->errors, $i, 1);
                }
            }

            $lastLoopCompiled = $compiled;
            $compiled = $this->compileArrayOfSchemasLoop($report, $schemas);

            foreach($schemas as $i => $schema){
                if(isset($schema->{'__$missingReferences'}) && !empty($schema->{'__$missingReferences'})){
                    foreach($schema->{'__$missingReferences'} as $j => $missing){
                        $response = $this->findId($schemas, $missing['ref']);
                        if($response){
                            $missing['obj']->{'__' . $missing['key'] . 'Resolved'} = $response;
                            array_splice($schema->{'__$missingReferences'}, $j, 1);
                        }
                    }
                    if(empty($schema->{'__$missingReferences'})){
                        unset($schema->{'__$missingReferences'});
                    }
                }
            }
        } while($compiled !== count($schemas) && $compiled !== $lastLoopCompiled);

        return $report->isValid();

    }

    private function compileArrayOfSchemasLoop(Report $mainReport, array $schemas){
        $compiledCount = 0;
        foreach($schemas as $schema){
            $report = new Report($mainReport);
            $isValid = $this->compileSchema($report, $schema);
            if($isValid){
                $compiledCount++;
            }

            $mainReport->errors = array_merge($mainReport->errors, $report->errors);
        }

        return $compiledCount;
    }

    private function findId(array $array, $id){
        foreach($array as $item){
            if($item->id === $id){
                return $item;
            }
        }
        return null;
    }

    private function collectReferences($object, &$results = [], $scope = [], $path = []){

        $ref = '$ref';
        $refResolved = '__$refResolved';
        $schema = '$schema';
        $schemaResolved = '__$schemaResolved';

        if(!is_object($object) && !is_array($object))
            return $results;

        if(is_object($object)){
            if(isset($object->id) && is_string($object->id))
                $scope[] = $object->id;

            if(isset($object->$ref) && is_string($object->$ref) && !isset($object->$refResolved)){
                $results[] = [
                    'ref' => $this->mergeReference($scope, $object->$ref),
                    'key' => $ref,
                    'obj' => $object,
                    'path' => $path
                ];
            }

            if(isset($object->$schema) && is_string($object->$schema) && !isset($object->$schemaResolved)){
                $results[] = [
                    'ref' => $this->mergeReference($scope, $object->$schema),
                    'key' => $schema,
                    'obj' => $object,
                    'path' => $path
                ];
            }
        }


        foreach($object as $key => $value){
            if(strpos($key, '__$') !== false) continue;
            $path[] = $key;
            $this->collectReferences(is_object($object) ? $object->$key : $object[$key], $results, $scope, $path);
            array_pop($path);
        }


        if(is_object($object) && isset($object->id) && is_string($object->id))
            array_pop($scope);

        return $results;

    }

    private function mergeReference($scope, $ref){
        if(Utils::isAbsoluteUri($ref)){
            return $ref;
        }

        $joinedScope = implode('', $scope);
        $isScopeAbsolute = Utils::isAbsoluteUri($joinedScope);
        $isScopeRelative = Utils::isRelativeUri($joinedScope);
        $isRefRelative = Utils::isRelativeUri($ref);

        if($isScopeAbsolute && $isRefRelative){
            $toRemove = preg_match('/\/[^\/]*$/', $joinedScope, $matches, PREG_OFFSET_CAPTURE);
            if($toRemove === 1){
                list(, $index) = $matches[0];
                $joinedScope = substr($joinedScope, 0, $index + 1);
            }
        }
        else if($isScopeRelative && $isRefRelative){
            $joinedScope = "";
        }
        else {
            $toRemove = preg_match('/[^#\/]+$/', $joinedScope, $matches, PREG_OFFSET_CAPTURE);
            if($toRemove === 1){
                list(, $index) = $matches[0];
                $joinedScope = substr($joinedScope, 0, $index + 1);
            }
        }

        return preg_replace('/##/', '#', $joinedScope . $ref);
    }

}