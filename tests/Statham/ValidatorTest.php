<?php

declare(strict_types=1);

namespace Statham\Tests;

use PHPUnit\Framework\TestCase;
use Statham\Statham;

final class ValidatorTest extends TestCase
{
    public function testMultipleOfAcceptsFloatingPointValues(): void
    {
        $validator = new Statham();
        $schema = json_decode('{"type": "number", "multipleOf": 0.05}');

        self::assertTrue($validator->validate(1.15, $schema));
        self::assertSame([], $validator->getErrors());
    }

    public function testMultipleOfRejectsNonMultiple(): void
    {
        $validator = new Statham();
        $schema = json_decode('{"type": "number", "multipleOf": 0.05}');

        self::assertFalse($validator->validate(1.14, $schema));
        $errorCodes = array_column($validator->getErrors(), 'code');
        self::assertContains('MULTIPLE_OF', $errorCodes);
    }

    public function testDependenciesKeywordWithInvalidDefinitionReportsError(): void
    {
        $validator = new Statham();
        $schema = json_decode('{"type": "object", "dependencies": "not-an-object"}');

        self::assertFalse($validator->validateSchema($schema));
        $errorCodes = array_column($validator->getErrors(), 'code');
        self::assertContains('KEYWORD_TYPE_EXPECTED', $errorCodes);
    }

    public function testLocalRefResolvesAgainstDefinitions(): void
    {
        $validator = new Statham();
        $schema = json_decode(
            <<<JSON
{
    "type": "object",
    "definitions": {
        "stringValue": {
            "type": "string"
        }
    },
    "properties": {
        "name": {
            "\$ref": "#/definitions/stringValue"
        }
    }
}
JSON
        );

        $data = json_decode('{"name": "Statham"}');

        self::assertTrue($validator->validate($data, $schema));
        self::assertSame([], $validator->getErrors());
    }
}
