<?php


namespace Statham\Json;


use Statham\Report;
use Statham\Statham;
use Statham\Utils;

class Validator {

    private $rootSchema;
    private $statham;

    public function __construct(Statham $statham) {
        $this->statham = $statham;
    }

    public function isMultipleOf(Report $report, $schema, $json){
        if(!is_numeric($json))
            return;

        if($json % $schema->multipleOf !== 0){
            $report->addError('MULTIPLE_OF', [$json, $schema->multipleOf], null, $schema);
        }
    }

    public function isMaximum(Report $report, $schema, $json){
        if(!is_numeric($json))
            return;

        $exclusive = isset($schema->exclusiveMaximum) && $schema->exclusiveMaximum;
        if($exclusive && $json >= $schema->maximum){
            $report->addError('MAXIMUM_EXCLUSIVE', [$json, $schema->maximum], null, $schema);
        }
        else if(!$exclusive && $json > $schema->maximum){
            $report->addError('MAXIMUM', [$json, $schema->maximum], null, $schema);
        }
    }

    // looked after by isMaximum
    public function isExclusiveMaximum(){}

    public function isMinimum(Report $report, $schema, $json){
        if(!is_numeric($json))
            return;

        $exclusive = isset($schema->exclusiveMinimum) && $schema->exclusiveMinimum;
        if($exclusive && $json <= $schema->minimum){
            $report->addError('MINIMUM_EXCLUSIVE', [$json, $schema->minimum], null, $schema);
        }
        else if(!$exclusive && $json < $schema->minimum){
            $report->addError('MINIMUM', [$json, $schema->minimum], null, $schema);
        }
    }

    // looked after by isMinimum
    public function isExclusiveMinimum(){}

    public function isMaxLength(Report $report, $schema, $json){
        if(!is_string($json))
            return;

        $length = mb_strlen($json);
        if(mb_strlen($json) > $schema->maxLength){
            $report->addError('MAX_LENGTH', [$length, $schema->maxLength], null, $schema);
        }
    }

    public function isMinLength(Report $report, $schema, $json){
        if(!is_string($json))
            return;

        $length = mb_strlen($json);
        if(mb_strlen($json) < $schema->minLength){
            $report->addError('MIN_LENGTH', [$length, $schema->minLength], null, $schema);
        }
    }

    public function isPattern(Report $report, $schema, $json){
        if(!is_string($json))
            return;

        if(preg_match(Utils::regex($schema->pattern), $json) !== 1){
            $report->addError('PATTERN', [$schema->pattern, $json], null, $schema);
        }
    }

    public function isAdditionalItems(Report $report, $schema, $json){
        if(!is_array($json))
            return;

        if($schema->additionalItems === false && (isset($schema->items) && is_array($schema->items))){
            if(count($json) > count($schema->items)){
                $report->addError('ARRAY_ADDITIONAL_ITEMS', null, null, $schema);
            }
        }
    }

    // handled in isAdditionalItems
    public function isItems(){}

    public function isMaxItems(Report $report, $schema, $json){
        if(!is_array($json))
            return;

        $count = count($json);
        if($count > $schema->maxItems){
            $report->addError('ARRAY_LENGTH_LONG', [$count, $schema->maxItems], null, $schema);
        }
    }

    public function isMinItems(Report $report, $schema, $json){
        if(!is_array($json))
            return;

        $count = count($json);
        if($count < $schema->minItems){
            $report->addError('ARRAY_LENGTH_SHORT', [$count, $schema->minItems], null, $schema);
        }
    }

    public function isUniqueItems(Report $report, $schema, $json){
        if(!is_array($json))
            return;

        if($schema->uniqueItems){
            $diff = Utils::arrayIsUnique($json);
            if(!$diff){
                $report->addError('ARRAY_UNIQUE', [json_encode($json)], null, $schema);
            }
        }
    }

    public function isMaxProperties(Report $report, $schema, $json){
        if(!is_object($json))
            return;

        $keysCount = count(get_object_vars($json));
        if($keysCount > $schema->maxProperties){
            $report->addError('OBJECT_PROPERTIES_MAXIMUM', [$keysCount, $schema->maxProperties], null, $schema);
        }

    }

    public function isMinProperties(Report $report, $schema, $json){
        if(!is_object($json))
            return;

        $keysCount = count(get_object_vars($json));
        if($keysCount < $schema->minProperties){
            $report->addError('OBJECT_PROPERTIES_MINIMUM', [$keysCount, $schema->minProperties], null, $schema);
        }

    }

    public function isRequired(Report $report, $schema, $json){
        if(!is_object($json))
            return;

        foreach($schema->required as $required){
            if(!property_exists($json, $required)){
                $report->addError('OBJECT_MISSING_REQUIRED_PROPERTY', [$required], null, $schema);
            }
        }
    }

    public function isAdditionalProperties(Report $report, $schema, $json){
        if(!isset($schema->properties) && !isset($schema->patternProperties)){
            $this->isProperties($report, $schema, $json);
        }
    }

    public function isPatternProperties(Report $report, $schema, $json){
        if(!isset($schema->properties)){
            $this->isProperties($report, $schema, $json);
        }
    }

    public function isProperties(Report $report, $schema, $json){
        if(!is_object($json))
            return;

        $properties = isset($schema->properties) ? $schema->properties : new \stdClass();
        $patternProperties = isset($schema->patternProperties) ? $schema->patternProperties : new \stdClass();

        if(isset($schema->additionalProperties) && $schema->additionalProperties === false){

            $json_keys = array_keys(get_object_vars($json));
            $property_keys = array_keys(get_object_vars($properties));
            $pattern_keys = array_keys(get_object_vars($patternProperties));

            $json_keys = array_values(array_diff($json_keys, $property_keys));

            foreach($pattern_keys as $pattern){
                $pattern = Utils::regex($pattern);
                $i = count($json_keys);
                while($i--){
                    if(preg_match($pattern, $json_keys[$i]) === 1){
                        array_splice($json_keys, $i, 1);
                    }
                }
            }

            if(!empty($json_keys)){
                $report->addError('OBJECT_ADDITIONAL_PROPERTIES', [implode(',', $json_keys)], null, $schema);
            }

        }
    }

    public function isDependencies(Report $report, $schema, $json){
        if(!is_object($json))
            return;

        foreach($schema->dependencies as $depencencyName => $depencencyDefinition){
            if(isset($json->$depencencyName)) {
                if(is_object($depencencyDefinition)){
                    $this->validate($report, $depencencyDefinition, $json);
                }
                else {
                    foreach($depencencyDefinition as $requiredPropertyName){
                        if(!isset($json->$requiredPropertyName)){
                            $report->addError('OBJECT_DEPENCENCY_KEY', [$requiredPropertyName, $depencencyName], null, $schema);
                        }
                    }
                }
            }
        }
    }

    public function isEnum(Report $report, $schema, $json){
        if(!in_array($json, $schema->enum, true)){
            $report->addError('ENUM_MISMATCH', [$json], null, $schema);
        }
    }

    // handled directly in validate()
    public function isType(){}

    public function isAllOf(Report $report, $schema, $json){
        foreach($schema->allOf as $all){
            if(!$this->validate($report, $all, $json)){
                break;
            }
        }
    }

    public function isAnyOf(Report $report, $schema, $json) {

        $subReports = [];
        $passed = false;

        foreach($schema->anyOf as $any){
            $subReport = new Report($report);
            $subReports[] = $subReport;
            $passed = $this->validate($subReport, $any, $json);
            if($passed)
                break;
        }

        if(!$passed){
            $report->addError('ANY_OF_MISSING', [], $subReports, $schema);
        }
    }

    public function isOneOf(Report $report, $schema, $json){
        $passes = 0;
        $subReports = [];

        foreach($schema->oneOf as $one){
            $subReport = new Report($report);
            $subReports[] = $subReport;
            if($this->validate($subReport, $one, $json))
                $passes++;
        }

        if($passes === 0){
            $report->addError('ONE_OF_MISSING', [], $subReports, $schema);
        }
        else if($passes > 1){
            $report->addError('ONE_OF_MULTIPLE', [], null, $schema);
        }
    }

    public function isNot(Report $report, $schema, $json){

        $subReport = new Report($report);
        if($this->validate($subReport, $schema->not, $json)){
            $report->addError('NOT_PASSED', null, null, $schema);
        }

    }

    // not used
    public function isDefinitions(){}

    public function isFormat(Report $report, $schema, $json){

        //@todo implement this

    }

    public function validate(Report $report, $schema, $json){

        $ref = '$ref';
        $refResolved = '__$refResolved';

        if(!is_object($schema)){
            $report->addError('SCHEMA_NOT_AN_OBJECT', [gettype($schema)], null, $schema);
        }

        // anything is valid against an empty schema
        if(count(get_object_vars($schema)) === 0){
            return true;
        }

        $isRoot = false;
        if(!isset($this->rootSchema)){
            $this->rootSchema = $schema;
            $isRoot = true;
        }

        if(isset($schema->$ref)){
            $maxRefs = 99;
            while(isset($schema->$ref) && $maxRefs > 0){
                if(!isset($schema->$refResolved)){
                    $report->addError('REF_UNRESOLVED', [$schema->$ref], null, $schema);
                    break;
                }
                else if($schema->$refResolved === $schema){
                    break;
                }
                else {
                    $schema = $schema->$refResolved;
                }
                $maxRefs--;
            }
            if($maxRefs === 0){
                throw new \RuntimeException("Circular dependency by $ref references");
            }
        }

        $jsonType = Utils::whatIs($json);
        if(isset($schema->type)){
            if(is_string($schema->type)){
                if($jsonType !== $schema->type && ($jsonType !== "integer" || $schema->type !== "number")){
                    $report->addError('INVALID_TYPE', [$schema->type, $jsonType], null, $schema);
                }
                else if(strpos($schema->type, $jsonType) === false && ($jsonType !== "integer" || strpos($schema->type, "number") === false)){
                    $report->addError('INVALID_TYPE', [$schema->type, $jsonType], null, $schema);
                }
            }
        }

        foreach($schema as $key => $value){
            $func = 'is' . ucfirst($key);
            if(method_exists($this, $func)){
                $this->$func($report, $schema, $json);
            }
        }

        if(is_object($json)){
            $this->recurseObject($report, $schema, $json);
        }
        else if(is_array($json)){
            $this->recurseArray($report, $schema, $json);
        }

        if($isRoot){
            unset($this->rootSchema);
        }

        return $report->isValid();
    }

    private function recurseArray(Report $report, $schema, $json){

        $idx = count($json);

        if(isset($schema->items)){
            if(is_array($schema->items)){

                $items_count = count($schema->items);
                while($idx--){
                    if($idx < $items_count){
                        $report->pathPush($idx);
                        $this->validate($report, $schema->items[$idx], $json[$idx]);
                        $report->pathPop();
                    }
                    else {
                        if(isset($schema->additionalItems) && is_object($schema->additionalItems)){
                            $report->pathPush($idx);
                            $this->validate($report, $schema->additionalItems, $json[$idx]);
                            $report->pathPop();
                        }
                    }
                }
            }
            else if(is_object($schema->items)){
                while($idx--){
                    $report->pathPush($idx);
                    $this->validate($report, $schema->items, $json[$idx]);
                    $report->pathPop();
                }
            }
        }
    }

    private function recurseObject(Report $report, $schema, $json){

        $additionalProperties = isset($schema->additionalProperties) ? $schema->additionalProperties : true;
        if($additionalProperties === true)
            $additionalProperties = new \stdClass();

        $properties = isset($schema->properties) ? $schema->properties : new \stdClass();
        $patternProperties = isset($schema->patternProperties) ? $schema->patternProperties : new \stdClass();

        foreach($json as $m => $propertyValue){

            $schemas = [];
            if(isset($properties->$m)){
                $schemas[] = $properties->$m;
            }

            foreach($patternProperties as $regex => $property){
                if(preg_match(Utils::regex($regex), $m) === 1){
                    $schemas[] = $patternProperties->$regex;
                }
            }

            if(empty($schemas) && $additionalProperties !== false){
                $schemas[] = $additionalProperties;
            }

            foreach($schemas as $test_schema){
                $report->pathPush($m);
                $this->validate($report, $test_schema, $propertyValue);
                $report->pathPop();
            }

        }
    }

}