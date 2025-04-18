<?php

namespace Laminas\I18n\Validator;

use IntlException;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Exception;
use Locale;
use NumberFormatter;
use Traversable;

use function array_key_exists;
use function intl_is_failure;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function strtr;

/** @final */
class IsInt extends AbstractValidator
{
    public const INVALID        = 'intInvalid';
    public const NOT_INT        = 'notInt';
    public const NOT_INT_STRICT = 'notIntStrict';

    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected $messageTemplates = [
        self::INVALID        => 'Invalid type given. String or integer expected',
        self::NOT_INT        => 'The input does not appear to be an integer',
        self::NOT_INT_STRICT => 'The input is not strictly an integer',
    ];

    /**
     * Optional locale
     *
     * @var string|null
     */
    protected $locale;

    /**
     * Data type is not enforced by default, so the string '123' is considered an integer.
     * Setting strict to true will enforce the integer data type.
     *
     * @var bool
     */
    protected $strict = false;

    /**
     * Constructor for the integer validator
     *
     * @param iterable<string, mixed> $options
     */
    public function __construct($options = [])
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (isset($options['locale'])) {
            $this->setLocale($options['locale']);
        }

        if (array_key_exists('strict', $options)) {
            $this->setStrict($options['strict']);
        }

        parent::__construct($options);
    }

    /**
     * Returns the set locale
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0
     *
     * @return string|null
     */
    public function getLocale()
    {
        if (null === $this->locale) {
            $this->locale = Locale::getDefault();
        }
        return $this->locale;
    }

    /**
     * Sets the locale to use
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0. Provide options to the constructor instead.
     *
     * @param string|null $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Returns the strict option
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0
     *
     * @return bool
     */
    public function getStrict()
    {
        return $this->strict;
    }

    /**
     * Sets the strict option mode
     *
     * @deprecated Since 2.28.0 - This method will be removed in 3.0. Provide options to the constructor instead.
     *
     * @param bool $strict
     * @return $this
     * @throws Exception\InvalidArgumentException
     */
    public function setStrict($strict)
    {
        if (! is_bool($strict)) {
            throw new Exception\InvalidArgumentException('Strict option must be a boolean');
        }

        $this->strict = $strict;
        return $this;
    }

    /**
     * Returns true if and only if $value is a valid integer
     *
     * @param mixed $value
     * @return bool
     * @throws Exception\InvalidArgumentException
     */
    public function isValid($value)
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            $this->error(self::INVALID);
            return false;
        }

        if (is_int($value)) {
            return true;
        }

        if ($this->strict) {
            $this->error(self::NOT_INT_STRICT);
            return false;
        }

        $this->setValue($value);

        $locale = $this->getLocale();
        try {
            $format = new NumberFormatter($locale, NumberFormatter::DECIMAL);
            if (intl_is_failure($format->getErrorCode())) {
                throw new Exception\InvalidArgumentException('Invalid locale string given');
            }
        } catch (IntlException $intlException) {
            throw new Exception\InvalidArgumentException('Invalid locale string given', 0, $intlException);
        }

        try {
            $parsedInt = $format->parse((string) $value, NumberFormatter::TYPE_INT64);
            if (intl_is_failure($format->getErrorCode())) {
                $this->error(self::NOT_INT);
                return false;
            }
        } catch (IntlException) {
            $this->error(self::NOT_INT);
            return false;
        }

        $decimalSep  = $format->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
        $groupingSep = $format->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL);

        $valueFiltered = strtr((string) $value, [
            $groupingSep => '',
            $decimalSep  => '.',
        ]);

        if ((string) $parsedInt !== $valueFiltered) {
            $this->error(self::NOT_INT);
            return false;
        }

        return true;
    }
}
