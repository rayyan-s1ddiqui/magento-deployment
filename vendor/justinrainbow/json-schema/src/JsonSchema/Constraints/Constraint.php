<?php

declare(strict_types=1);

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JsonSchema\Constraints;

use JsonSchema\Entity\JsonPointer;

/**
 * The Base Constraints, all Validators should extend this class
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 */
abstract class Constraint extends BaseConstraint implements ConstraintInterface
{
    protected $inlineSchemaProperty = '$schema';

    public const CHECK_MODE_NONE =             0x00000000;
    public const CHECK_MODE_NORMAL =           0x00000001;
    public const CHECK_MODE_TYPE_CAST =        0x00000002;
    public const CHECK_MODE_COERCE_TYPES =     0x00000004;
    public const CHECK_MODE_APPLY_DEFAULTS =   0x00000008;
    public const CHECK_MODE_EXCEPTIONS =       0x00000010;
    public const CHECK_MODE_DISABLE_FORMAT =   0x00000020;
    public const CHECK_MODE_EARLY_COERCE =     0x00000040;
    public const CHECK_MODE_ONLY_REQUIRED_DEFAULTS   = 0x00000080;
    public const CHECK_MODE_VALIDATE_SCHEMA =  0x00000100;

    /**
     * Bubble down the path
     *
     * @param JsonPointer|null $path Current path
     * @param mixed            $i    What to append to the path
     */
    protected function incrementPath(?JsonPointer $path, $i): JsonPointer
    {
        $path = $path ?? new JsonPointer('');

        if ($i === null || $i === '') {
            return $path;
        }

        $path = $path->withPropertyPaths(
            array_merge(
                $path->getPropertyPaths(),
                [$i]
            )
        );

        return $path;
    }

    /**
     * Validates an array
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     */
    protected function checkArray(&$value, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        $validator = $this->factory->createInstanceFor('collection');
        $validator->check($value, $schema, $path, $i);

        $this->addErrors($validator->getErrors());
    }

    /**
     * Validates an object
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $properties
     * @param mixed $additionalProperties
     * @param mixed $patternProperties
     */
    protected function checkObject(&$value, $schema = null, ?JsonPointer $path = null, $properties = null,
        $additionalProperties = null, $patternProperties = null, $appliedDefaults = []): void
    {
        /** @var ObjectConstraint $validator */
        $validator = $this->factory->createInstanceFor('object');
        $validator->check($value, $schema, $path, $properties, $additionalProperties, $patternProperties, $appliedDefaults);

        $this->addErrors($validator->getErrors());
    }

    /**
     * Validates the type of a property
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     */
    protected function checkType(&$value, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        $validator = $this->factory->createInstanceFor('type');
        $validator->check($value, $schema, $path, $i);

        $this->addErrors($validator->getErrors());
    }

    /**
     * Checks a undefined element
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     */
    protected function checkUndefined(&$value, $schema = null, ?JsonPointer $path = null, $i = null, $fromDefault = false): void
    {
        /** @var UndefinedConstraint $validator */
        $validator = $this->factory->createInstanceFor('undefined');

        $validator->check($value, $this->factory->getSchemaStorage()->resolveRefSchema($schema), $path, $i, $fromDefault);

        $this->addErrors($validator->getErrors());
    }

    /**
     * Checks a string element
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     */
    protected function checkString($value, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        $validator = $this->factory->createInstanceFor('string');
        $validator->check($value, $schema, $path, $i);

        $this->addErrors($validator->getErrors());
    }

    /**
     * Checks a number element
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     */
    protected function checkNumber($value, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        $validator = $this->factory->createInstanceFor('number');
        $validator->check($value, $schema, $path, $i);

        $this->addErrors($validator->getErrors());
    }

    /**
     * Checks a enum element
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     */
    protected function checkEnum($value, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        $validator = $this->factory->createInstanceFor('enum');
        $validator->check($value, $schema, $path, $i);

        $this->addErrors($validator->getErrors());
    }

    /**
     * Checks a const element
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     */
    protected function checkConst($value, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        $validator = $this->factory->createInstanceFor('const');
        $validator->check($value, $schema, $path, $i);

        $this->addErrors($validator->getErrors());
    }

    /**
     * Checks format of an element
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     */
    protected function checkFormat($value, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        $validator = $this->factory->createInstanceFor('format');
        $validator->check($value, $schema, $path, $i);

        $this->addErrors($validator->getErrors());
    }

    /**
     * Get the type check based on the set check mode.
     */
    protected function getTypeCheck(): TypeCheck\TypeCheckInterface
    {
        return $this->factory->getTypeCheck();
    }
}
