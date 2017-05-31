<?php
namespace craft\commerce\web\twig;

use Craft;
use craft\commerce\Plugin;

/**
 * Class CommerceTwigExtension
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2015, Pixel & Tonic, Inc.
 * @license   https://craftcommerce.com/license Craft Commerce License Agreement
 * @see       https://craftcommerce.com
 * @package   Commerce\Extensions
 * @since     1.0
 */
class Extension extends \Twig_Extension
{

    /**
     * @return string
     */
    public function getName()
    {
        return 'Craft Commerce Twig Extension';
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new \Twig_SimpleFilter('json_encode_filtered', [$this, 'jsonEncodeFiltered']),
            new \Twig_SimpleFilter('commerceCurrency', [$this, 'commerceCurrency']),

        ];
    }


    /**
     * Formats and optionally converts a currency amount into the supplied valid payment currency as per the rate setup in payment currencies.
     *
     * @param      $amount
     * @param      $currency
     * @param bool $convert
     * @param bool $format
     * @param bool $stripZeros
     *
     * @return float
     */
    public function commerceCurrency($amount, $currency, $convert = false, $format = true, $stripZeros = false)
    {
        $this->_validatePaymentCurrency($currency);

        // return input if no currency passed, and both convert and format are false.
        if (!$convert && !$format) {
            return $amount;
        }

        if ($convert) {
            $amount = Plugin::getInstance()->getPaymentCurrencies()->convert($amount, $currency);
        }

        if ($format) {
            $amount = craft()->numberFormatter->formatCurrency($amount, $currency, $stripZeros);
        }

        return $amount;
    }

    private function _validatePaymentCurrency($currency)
    {
        $currency = Plugin::getInstance()->getPaymentCurrencies()->getPaymentCurrencyByIso($currency);

        if (!$currency) {
            throw new \Twig_Error(Craft::t('commerce', 'commerce', 'Not a valid currency code'));
        }
    }

    public function jsonEncodeFiltered($input)
    {
        $array = $this->recursiveSanitizeArray($input);

        return json_encode($array);
    }

    private function recursiveSanitizeArray($array)
    {
        $finalArray = [];

        foreach ($array as $key => $value) {
            $newKey = self::sanitize($key);

            if (is_array($value)) {
                $finalArray[$newKey] = $this->recursiveSanitizeArray($value);
            } else {
                $finalArray[$newKey] = self::sanitize($value);
            }
        }

        return $finalArray;
    }

    public static function sanitize($input)
    {
        $sanitized = $input;

        if (!is_int($sanitized)) {
            $sanitized = filter_var($sanitized, FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $newValue = filter_var($sanitized, FILTER_SANITIZE_SPECIAL_CHARS);

            if (is_numeric($newValue)) {
                $sanitized = intval($newValue);
            } else {
                $sanitized = $newValue;
            }
        }

        return $sanitized;
    }
}