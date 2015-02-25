<?php


namespace Statham;


class Report {

    private $parent;
    private $options = [];

    public $errors = [];
    private $path = [];

    public function __construct(Report $parent = null, array $options = []){

        if($parent instanceof Report){
            $this->parent = $parent;
            $this->options = $parent->options;
        }
        else {
            $this->options = $options;
        }

    }

    public function isValid(){
        return empty($this->errors);
    }

    public function getPath(){

        $path = [];
        if($this->parent instanceof Report){
            $path[] = $this->parent->getPath();
        }

        $path = array_merge($path, $this->path);

        if(!$this->options['reportPathsAsArray']){
            $path = '#/' . implode('/', array_map(function($segment){

                    if(Utils::isAbsoluteUri($segment)){
                        return "uri({$segment})";
                    }

                    return str_replace(["~", "/"], ["~0", "~1"], $segment);

                }, $path));
        }

        return $path;

    }

    public function pathPush($element){
        if(is_array($element)){
            $this->path = array_merge($this->path, $element);
        }
        else
            $this->path[] = $element;

    }

    public function pathPop($count = 1){
        array_splice($this->path, -$count);
    }

    public function addError($errorCode, array $params = [], $subReports = null, $schemaDescription = null){

        $errorMessage = vsprintf(Errors::getMessage($errorCode), $params);

        $error = [
            'code' => $errorCode,
            'params' => $params,
            'message' => $errorMessage,
            'path' => $this->getPath()
        ];

        if($schemaDescription !== null){
            if(is_object($schemaDescription)){
                if(isset($schemaDescription->description))
                    $error['description'] = $schemaDescription->description;
                else if(isset($schemaDescription->id))
                    $error['description'] = $schemaDescription->id;
            }
            else if(is_string($schemaDescription))
                $error['description'] = $schemaDescription;
        }

        if($subReports !== null){
            if(!is_array($subReports))
                $subReports = [$subReports];

            $error['inner'] = [];
            foreach($subReports as $subReport){
                foreach($subReport->errors as $subError){
                    $error['inner'][] = $subError;
                }
            }

            if(empty($error['inner']))
                unset($error['inner']);

        }

        $this->errors[] = $error;

    }

    /**
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }


}