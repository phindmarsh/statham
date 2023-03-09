<?php


namespace Statham\Schema;

use Statham\Report;
use Statham\Statham;
use Statham\Utils;

class Validator {

    protected $validations = [

        '$ref' => ['string'],
        '$schema' => ['string'],
        'multipleOf' => ['number', 'greaterThanZero'],
        'maximum' => ['number'],
        'exclusiveMaximum' => ['boolean', 'depends:maximum'],
        'minimum' => ['number'],
        'exclusiveMinimum' => ['boolean', 'depends:minimum'],
        'maxLength' => ['number', 'greaterThanOrEqualToZero'],
        'minLength' => ['number', 'greaterThanOrEqualToZero'],
        'pattern' => ['string', 'validRegex'],
        'additionalItems' => ['additionalPropertiesOrItems'],
        'items' => ['items'],
        'maxItems' => ['number', 'greaterThanOrEqualToZero'],
        'minItems' => ['number', 'greaterThanOrEqualToZero'],
        'uniqueItems' => ['boolean'],
        'properties' => ['properties'],
        'additionalProperties' => ['additionalPropertiesOrItems'],
        'maxProperties' => ['integer', 'greaterThanOrEqualToZero'],
        'minProperties' => ['integer', 'greaterThanOrEqualToZero'],
        'patternProperties' => ['patternProperties'],
        'required' => ['required'],
        'dependencies' => ['dependencies'],
        'enum' => ['enum'],
        'type' => ['type'],
        'allOf' => ['allAnyOneOf'],
        'not' => ['not'],
        'definitions' => ['definitions'],
        'format' => ['string', 'format'],
        'id' => ['string'],
        'title' => ['string'],
        'description' => ['string'],
        'default' => []
    ];

    public function __construct(Statham $statham){
        $this->statham = $statham;
    }

    public function isString(Report $report, $schema, $key){
        if(!is_string($schema->$key)){
            $report->addError("KEYWORD_TYPE_EXPECTED", [$key, 'string']);
        }
    }

    public function isNumber(Report $report, $schema, $key){
        if(!is_numeric($schema->$key)){
            $report->addError('KEYWORD_TYPE_EXPECTED', [$key, 'number']);
        }
    }

    public function isInteger(Report $report, $schema, $key){
        if(!is_integer($schema->$key)){
            $report->addError('KEYWORD_TYPE_EXPECTED', [$key, 'integer']);
        }
    }

    public function isGreaterThanZero(Report $report, $schema, $key){
        if($schema->$key <= 0){
            $report->addError('KEYWORD_MUST_BE', [$key, 'strictly greater than 0']);
        }
    }

    public function isBoolean(Report $report, $schema, $key){
        if(!is_bool($schema->$key)){
            $report->addError('KEYWORD_TYPE_EXPECTED', [$key, 'boolean']);
        }
    }

    public function isDepends(Report $report, $schema, $key, $args){
        if(!isset($schema->{$args})){
            $report->addError('KEYWORD_DEPENDENCY', [$key, $args]);
        }
    }

    public function isGreaterThanOrEqualToZero(Report $report, $schema, $key){
        if($schema->$key < 0){
            $report->addError('KEYWORD_MUST_BE', [$key, 'greater than, or equal to 0']);
        }
    }

    public function isValidRegex(Report $report, $schema, $key){
        if(preg_match(Utils::regex($schema->$key), "") === false){
            $report->addError('KEYWORD_PATTERN', [$key, Utils::regex($schema->$key)]);
        }
    }

    public function isAdditionalPropertiesOrItems(Report $report, $schema, $key){
        if(!is_bool($schema->$key) && !is_object($schema->$key)){
            $report->addError('KEYWORD_TYPE_EXPECTED', [$key, 'boolean or object']);
        }
        else if(is_object($schema->$key)){
            $report->pathPush($key);
            $this->validateSchema($report, $schema->$key);
            $report->pathPop();
        }
    }

    public function isItems(Report $report, $schema, $key){

        if(is_object($schema)){
            $report->pathPush($key);
            $this->validateSchema($report, $schema->$key);
            $report->pathPop();
        }
        else if(is_array($schema->$key)){
            $report->pathPush($key);
            foreach($schema->$key as $i => $item){
                $report->pathPush($i);
                $this->validateSchema($report, $schema->{$key}[$i]);
                $report->pathPop();
            }
            $report->pathPop();
        }
        else {
            $report->addError('KEYWORD_TYPE_EXPECTED', [$key, ['array or object']]);
        }

    }

    public function isProperties(Report $report, $schema, $key){
        if(!is_object($schema->$key)){
            $report->addError('KEYWORD_TYPE_EXPECTED', [$key, 'object']);
            return;
        }

        $report->pathPush($key);
        foreach($schema->$key as $i => $value){
            $report->pathPush($i);
            $this->validateSchema($report, $schema->{$key}->$i);
            $report->pathPop();
        }
        $report->pathPop();
    }

    public function isPatternProperties(Report $report, $schema, $key){
        if(!is_object($schema->$key)){
            $report->addError('KEYWORD_TYPE_EXPECTED', [$key, 'object']);
            return;
        }

        $report->pathPush($key);
        foreach($schema->$key as $i => $value){

            if(preg_match(Utils::regex($i), "") === false){
                $report->addError('KEYWORD_PATTERN', [$key, Utils::regex($i)]);
            }

            $report->pathPush($i);
            $this->validateSchema($report, $schema->$key->$i);
            $report->pathPop();
        }
        $report->pathPop();

    }

    public function isRequired(Report $report, $schema, $key){
        if(!is_array($schema->$key)){
            $report->addError('KEYWORD_TYPE_EXPECTED', [$key, 'array']);
        }
        else if(empty($schema->$key)){
            $report->addError('KEYWORD_MUST_BE', [$key, 'an array with at least one element']);
        }
        else {
            foreach($schema->$key as $i => $value){
                if(!is_string($value)){
                    $report->addError('KEYWORD_VALUE_TYPE', [$key, 'string']);
                }
            }
            if(0 !== count(array_diff($schema->$key, array_unique($schema->$key)))){
                $report->addError('KEYWORD_MUST_BE', [$key, 'an array with unique items']);
            }
        }
    }

    public function isDependencies(Report $report, $schema, $key){
        if(!is_object($schema)){
            $report->addError('KEYWORD_TYPE_EXPECTED', [$key, 'object']);
        }
        else {
            foreach($schema->$key as $schemaKey => $schemaDependency){

                if(is_object($schemaDependency)){
                    $report->pathPush($key);
                    $report->pathPush($schemaKey);
                    $this->validateSchema($report, $schemaDependency);
                    $report->pathPop();
                    $report->pathPop();
                }
                else if(is_array($schemaDependency)){
                    if(empty($schemaDependency)){
                        $report->addError('KEYWORD_MUST_BE', [$key, 'not empty array']);
                    }
                    foreach($schemaDependency as $i => $value){
                        if(!is_string($value)){
                            $report->addError('KEYWORD_VALUE_TYPE', [$key, 'string']);
                        }
                    }
                    if(0 !== count(array_diff($schemaDependency, array_unique($schemaDependency)))){
                        $report->addError('KEYWORD_MUST_BE', [$key, 'an array with unique items']);
                    }
                }
                else {
                    $report->addError('KEYWORD_VALUE_TYPE', [$key, 'object or array']);
                }
            }
        }
    }

    public function isEnum(Report $report, $schema, $key){
        if(!is_array($schema->$key)){
            $report->addError('KEYWORD_TYPE_EXPECTED', [$key, 'array']);
        }
        else if(empty($schema->$key)){
            $report->addError('KEYWORD_MUST_BE', [$key, 'an array with at least one element']);
        }
        else if(0 !== count(array_diff($schema->$key, array_unique($schema->$key)))){
            $report->addError('KEYWORD_MUST_BE', [$key, 'an array with unique items']);
        }
    }

    public function isType(Report $report, $schema, $key){
        $primitives = ["array", "boolean", "integer", "number", "null", "object", "string"];

        if(is_array($schema->$key)){
            foreach ($schema->$key as $type) {
                if(!in_array($type, $primitives)){
                    $report->addError('KEYWORD_TYPE_EXPECTED', [$key, implode(',', $primitives)]);
                }
            }
            if(0 !== count(array_diff($schema->$key, array_unique($schema->$key)))){
                $report->addError('KEYWORD_MUST_BE', [$key, 'an array with unique items']);
            }
        }
        else if(is_string($schema->$key)){
            if(!in_array($schema->$key, $primitives)){
                $report->addError('KEYWORD_TYPE_EXPECTED', [$key, implode(',', $primitives)]);
            }
        }
        else {
            $report->addError('KEYWORD_TYPE_EXPECTED', [$key, 'string or array']);
        }
    }

    public function isAllAnyOneOf(Report $report, $schema, $key){
        if(!is_array($schema->$key)){
            $report->addError('KEYWORD_TYPE_EXPECTED', [$key, 'array']);
        }
        else if(empty($schema->$key)){
            $report->addError('KEYWORD_MUST_BE', [$key, 'an array with at least one element']);
        }
        else {
            $report->pathPush($key);
            foreach($schema->$key as $i => $value){
                $report->pathPush($i);
                $this->validateSchema($report, $schema->{$key}[$i]);
                $report->pathPop();
            }
            $report->pathPop();
        }
    }

    public function isNot(Report $report, $schema, $key){
        if(!is_object($schema->$key)){
            $report->addError('KEYWORD_TYPE_EXPECTED', [$key, 'object']);
        }
        else {
            $report->pathPush($key);
            $this->validateSchema($report, $schema->$key);
            $report->pathPop();
        }
    }

    public function isDefinitions(Report $report, $schema, $key){
        if(!is_object($schema->$key)){
            $report->addError('KEYWORD_TYPE_EXPECTED', [$key, 'object']);
        }
        else {
            $report->pathPush($key);
            foreach($schema->$key as $i => $value){
                $report->pathPush($i);
                $this->validateSchema($report, $schema->{$key}->$i);
                $report->pathPop();
            }
            $report->pathPop();
        }
    }

    public function isFormat(Report $report, $schema, $key){
        if(!is_string($schema->$key)){
            $report->addError('KEYWORD_TYPE_EXPECTED', [$key, 'string']);
        }
        else {
            //@todo implement format validators
        }
    }

    public function validateSchema(Report $report, $schema){

        $validated = '__$validated';
        $schemaResolved = '__$schemaResolved';

        if(is_array($schema))
            return $this->validateArrayOfSchemas($report, $schema);


        if(isset($schema->$validated))
            return true;

        $hasParentSchema = isset($schema->{'$schema'}) && $schema->id !== $schema->{'$schema'};
        if($hasParentSchema){
            if(isset($schema->$schemaResolved) && $schema->$schemaResolved !== $schema){
                $subReport = new Report($report);
                $valid = $this->statham->jsonValidator->validate($subReport, $schema->$schemaResolved, $schema);
                if(!$valid){
                    $report->addError("PARENT_SCHEMA_VALIDATION_FAILED", [], $subReport);
                }
            }
            else {
                if(!$this->statham->options['ignoreUnresolvableReferences']){
                    $report->addError("REF_UNRESOLVED", [$schema->{'$schema'}]);
                }
            }
        }

        //@todo implement this:
        //if($statham->options['noTypeless']){
        //}

        if(!is_array($schema) && !is_object($schema)){
            var_dump($schema);
        }

        foreach($schema as $key => $value){
            if(strpos($key, '__$') !== false) continue;

            if(isset($this->validations[$key])){
                foreach($this->validations[$key] as $validation){
                    list($func, $args) = explode(':', $validation) + [null, []];
                    $this->{'is'.ucfirst($func)}($report, $schema, $key, $args);
                }
            }
        }

        $isValid = $report->isValid();
        if($isValid){
            $schema->$validated = true;
        }
        return $isValid;
    }

    private function validateArrayOfSchemas(Report $report, $array){
        foreach($array as $schema){
            $this->validateSchema($report, $schema);
        }
        return $report->isValid();
    }

}