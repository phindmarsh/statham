# statham
A JSON Schema validator written in PHP

This is a (pretty much straight up) port of the Javascript [ZSchema](https://github.com/zaggino/z-schema) library for validating JSON against JSON schemas, and validating JSON Schemas themselves.

## Why

Motivation for writing this was to validate [swagger.io](https://swagger.io) files in PHP, and none of the existing validators worked with recursive schemas, or resolved nested references properly. The Javascript one worked as I needed it to, so I ported it.

So Statham will:
- Not recurse infinitely when validating a schema that references itself
- Properly deal with $refs that are located in different schemas

## Usage

```php

// schemas used during validation
$schemas = [
  "http://json-schema.org/draft-04/schema#"
  "http://swagger.io/v2/schema.json#"
];

$statham = new \Statham\Statham();

// download each schema and add to internal cache
foreach($schemas as $schema_url){
    $schema = json_decode(file_get_contents($schema_url));
    $statham->setRemoteReference($schema_url, $schema);
}

// just validate the schema (no data)
$statham->validateSchema($schemas[1]);

// validate $json_to_validate against a given schema
$statham->validate($json_to_validate, $schemas[1]);

```

Statham doesn't automatically download externally referenced schemas, (it totally could, but reasons), so use `$statham->setRemoteReference($url, $schema_object)` for any schemas that will be used during validation.

Schemas can either be passed as an object (doesn't support schemas as arrays, but again, totally _could_), or if you've used `$statham->setRemoteReference()` you can pass it as a string, being the URL of the schema.

JSON data can only be passed as objects, so just use `json_decode($json)` (no second `true` argument) and you'll be fine.

## Todo

It doesn't check formats properly (so emails, dates, etc aren't validated they are anything beyond a string). This is easy enough to do, I just haven't yet.

