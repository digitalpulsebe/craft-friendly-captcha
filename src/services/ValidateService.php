<?php
/**
 * Friendly Captcha plugin for Craft CMS 4.x
 *
 * Integrate Friendly Captcha to fight spam in your Craft CMS forms
 *
 * @link      https://www.digitalpulse.be/
 * @copyright Copyright (c) 2022 Digital Pulse
 */

namespace digitalpulsebe\friendlycaptcha\services;

use craft\helpers\Html;
use craft\helpers\Template;
use digitalpulsebe\friendlycaptcha\FriendlyCaptcha;

use Craft;
use craft\base\Component;
use Exception;
use Twig\Markup;
use yii\base\InvalidConfigException;

class ValidateService extends Component
{
    protected array $endpoints = [
        'global' => 'https://api.friendlycaptcha.com/api/v1/',
        'eu' => 'https://eu-api.friendlycaptcha.eu/api/v1/',
    ];

    /**
     * FriendlyCaptcha::$plugin->validate->validateRequest()
     *
     * @return bool
     * @throws Exception
     */
    public function validateRequest(): bool
    {
        $solution = Craft::$app->getRequest()->getParam('frc-captcha-solution');
        $siteKey = $this->getSiteKey();
        $apiKey = $this->getApiKey();
        return $this->validateSolution($solution, $siteKey, $apiKey);
    }

    /**
     * FriendlyCaptcha::$plugin->validate->validateSolution($solution)
     *
     * @param string $solution
     * @param string $siteKey
     * @param string $apiKey
     * @param string $endpoint
     * @return bool
     * @throws Exception
     */
    public function validateSolution(string $solution, string $siteKey, string $apiKey, string $endpoint = 'global'): bool
    {
        $endpointUrl = $this->getEndpointUrl($endpoint, 'siteverify');

        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $endpointUrl);
        curl_setopt($curlHandle, CURLOPT_POST, true);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, http_build_query([
            'solution' => $solution,
            'siteKey' => $siteKey,
            'secret' => $apiKey
        ]));
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curlHandle);
        $curlError = curl_error($curlHandle);

        if (!curl_errno($curlHandle) && curl_getinfo($curlHandle, CURLINFO_HTTP_CODE) == 200) {
            $object = json_decode($response);
            curl_close($curlHandle);

            if (!$object->success) {
                Craft::warning('Friendly Captcha response did not validate: '.$response, __METHOD__);
            } else {
                Craft::info('Friendly Captcha response validated', __METHOD__);
            }

            return $object->success ?? false;
        }

        curl_close($curlHandle);

        /*
         * https://docs.friendlycaptcha.com/#/installation?id=verification-best-practices
         * If you receive a response code other than 200 in production,
         * you should probably accept the user's form despite not having been able to verify the CAPTCHA solution.
         * Maybe your server is misconfigured or the Friendly Captcha servers are down.
         * While we try to make sure that never happens, it is a good idea to assume one day disaster will strike.
         */

        // always log error

        $errorMessage = 'Error validating Friendly Captcha solution';

        if ($curlError) {
            $errorMessage .= ' CURL error: '.$curlError;
        } else {
            $errorMessage .= ' response: '.$response;
        }

        Craft::error($errorMessage, __METHOD__);

        if (Craft::$app->config->general->devMode) {
            // if dev mode is on: throw exception to show error
            throw new Exception($errorMessage);
        } elseif (CRAFT_ENVIRONMENT != 'production') {
            // dev mode is not on, but still not in production:
            return false;
        }

        return true;
    }

    protected function getApiKey(): string
    {
        return FriendlyCaptcha::$plugin->getSettings()->getApiKey();
    }

    protected function getSiteKey(): string
    {
        return FriendlyCaptcha::$plugin->getSettings()->getSiteKey();
    }

    public function getEndpointUrl(string $endpoint, string $service): string
    {
        if ($endpoint == 'custom') {
            $baseUrl = FriendlyCaptcha::$plugin->getSettings()->getCustomEndpoint();
            if (!str_ends_with($baseUrl, '/')) {
                // append trailing slash if needed
                $baseUrl = $baseUrl.'/';
            }
            return $baseUrl.$service;
        }
        if (!isset($this->endpoints[$endpoint])) {
            throw new Exception('Unsupported Friendly Captcha endpoint');
        }
        return $this->endpoints[$endpoint].$service;
    }

    /**
     * FriendlyCaptcha::$plugin->validate->renderWidget(['attribute' => 'value])
     *
     * to see supported attributes, see: https://docs.friendlycaptcha.com/#/widget_api?id=attribute-api-html-tags
     *
     * @param array $attributes html attributes to put on the widget
     * @return Markup
     * @throws \yii\base\Exception
     * @throws InvalidConfigException
     */
    public function renderWidget(array $attributes = []): Markup
    {
        $settings = FriendlyCaptcha::$plugin->getSettings();

        Craft::$app->view->registerJsFile(Craft::$app->assetManager->getPublishedUrl('@digitalpulsebe/friendlycaptcha/assets/js/friendlycaptcha.min.js', true), ['async' => true, 'defer' => true, 'nomodule' => true]);
        Craft::$app->view->registerJsFile(Craft::$app->assetManager->getPublishedUrl('@digitalpulsebe/friendlycaptcha/assets/js/friendlycaptcha.module.min.js', true), ['async' => true, 'defer' => true, 'type' => 'module']);

        $defaultAttributes = [
            'class' => 'frc-captcha',
            'data-sitekey' => $settings->getSiteKey(),
            'data-lang' => substr(Craft::$app->language ?? 'en', 0, 2),
            'data-start' => $settings->startEvent,
        ];

        if ($settings->endpoint != 'global') {
            $defaultAttributes['data-puzzle-endpoint'] = FriendlyCaptcha::$plugin->validate->getEndpointUrl($settings->endpoint, 'puzzle');
        }

        if ($settings->darkMode) {
            $defaultAttributes['class'] = 'frc-captcha dark';
        }

        $attributes = array_merge($defaultAttributes, $attributes);

        return Template::raw(
            Html::tag('div', '', $attributes)
        );
    }
}
