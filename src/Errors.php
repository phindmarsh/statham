<?php

namespace Statham;


class Errors {

    const INVALID_TYPE = "Expected type %1\$s but found type %2\$s";
    const INVALID_FORMAT = "Object didn't pass validation for format %1\$s: %2\$s";
    const ENUM_MISMATCH = "No enum match for: %1\$s";
    const ANY_OF_MISSING = "Data does not match any schemas from 'anyOf'";
    const ONE_OF_MISSING = "Data does not match any schemas from 'oneOf'";
    const ONE_OF_MULTIPLE = "Data is valid against more than one schema from 'oneOf'";
    const NOT_PASSED = "Data matches schema from 'not'";

    // Array errors
    const ARRAY_LENGTH_SHORT = "Array is too short (%1\$s), minimum %2\$s";
    const ARRAY_LENGTH_LONG = "Array is too long (%1\$s), maximum %2\$s";
    const ARRAY_UNIQUE = "Array items are not unique (duplicates %1\$s)";
    const ARRAY_ADDITIONAL_ITEMS = "Additional items not allowed";

    // Numeric errors
    const MULTIPLE_OF = "Value %1\$s is not a multiple of %2\$s";
    const MINIMUM = "Value %1\$s is less than minimum %2\$s";
    const MINIMUM_EXCLUSIVE = "Value %1\$s is equal or less than exclusive minimum %2\$s";
    const MAXIMUM = "Value %1\$s is greater than maximum %2\$s";
    const MAXIMUM_EXCLUSIVE = "Value %1\$s is equal or greater than exclusive maximum %2\$s";

    // Object errors
    const OBJECT_PROPERTIES_MINIMUM = "Too few properties defined (%1\$s), minimum %2\$s";
    const OBJECT_PROPERTIES_MAXIMUM = "Too many properties defined (%1\$s), maximum %2\$s";
    const OBJECT_MISSING_REQUIRED_PROPERTY = "Missing required property: %1\$s";
    const OBJECT_ADDITIONAL_PROPERTIES = "Additional properties not allowed: %1\$s";
    const OBJECT_DEPENDENCY_KEY = "Dependency failed - key must exist: %1\$s (due to key: %2\$s)";

    // String errors
    const MIN_LENGTH = "String is too short (%1\$s chars), minimum %2\$s";
    const MAX_LENGTH = "String is too long (%1\$s chars), maximum %2\$s";
    const PATTERN = "String does not match pattern %1\$s: %2\$s";

    // Schema validation errors
    const KEYWORD_TYPE_EXPECTED = "Keyword '%1\$s' is expected to be of type '%2\$s'";
    const KEYWORD_UNDEFINED_STRICT = "Keyword '%1\$s' must be defined in strict mode";
    const KEYWORD_UNEXPECTED = "Keyword '%1\$s' is not expected to appear in the schema";
    const KEYWORD_MUST_BE = "Keyword '%1\$s' must be %2\$s";
    const KEYWORD_DEPENDENCY = "Keyword '%1\$s' requires keyword '%2\$s'";
    const KEYWORD_PATTERN = "Keyword '%1\$s' is not a valid RegExp pattern: %2\$s";
    const KEYWORD_VALUE_TYPE = "Each element of keyword '%1\$s' array must be a '%2\$s'";
    const UNKNOWN_FORMAT = "There is no validation function for format '%1\$s'";
    const CUSTOM_MODE_FORCE_PROPERTIES = "%1\$s must define at least one property if present";

    // Remote errors
    const REF_UNRESOLVED = "Reference has not been resolved during compilation: %1\$s";
    const UNRESOLVABLE_REFERENCE = "Reference could not be resolved: %1\$s";
    const SCHEMA_NOT_REACHABLE = "Validator was not able to read schema with uri: %1\$s";
    const SCHEMA_TYPE_EXPECTED = "Schema is expected to be of type 'object'";
    const SCHEMA_NOT_AN_OBJECT = "Schema is not an object: %1\$s";
    const ASYNC_TIMEOUT = "%1\$s asynchronous task(s) have timed out after %2\$s ms";
    const PARENT_SCHEMA_VALIDATION_FAILED = "Schema failed to validate against its parent schema, see inner errors for details.";
    const REMOTE_NOT_VALID = "Remote reference didn't compile successfully: %1\$s";

    public static function defined($code){
        return constant("static::{$code}");
    }

    public static function getMessage($code){
        if(static::defined($code))
            return constant("static::$code");
        else
            throw new \InvalidArgumentException("Unknown error code {$code}");
    }

}