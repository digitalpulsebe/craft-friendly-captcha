<?php

namespace digitalpulsebe\friendlycaptcha\models;

use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\helpers\App;

class Settings extends Model
{
    /**
     * siteKey from generated for the Friendly Captcha account
     *
     * @var string
     */
    public string $siteKey = '';

    /**
     * secret from generated for the Friendly Captcha account
     *
     * @var string
     */
    public string $apiKey = '';

    /**
     * Validate ContactForm
     *
     * @var bool
     */
    public bool $validateContactForm = false;

    /**
     * Validate UsersRegistration
     *
     * @var bool
     */
    public bool $validateUsersRegistration = false;

    /**
     * start puzzle challenge on event
     * https://docs.friendlycaptcha.com/#/widget_api?id=data-start-attribute
     *
     * @var string
     */
    public string $startEvent = 'focus';

    /**
     * endpoints: global|eu
     * EU endpoint is for Friendly Captcha Advanced or Enterprise plan.
     * https://docs.friendlycaptcha.com/#/eu_endpoint
     *
     * @var string
     */
    public string $endpoint = 'global';

    /**
     * @var bool
     */
    public bool $darkMode = false;

    /**
     * @return string
     */
    public function getSiteKey(): string
    {
        return App::parseEnv($this->siteKey);
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return App::parseEnv($this->apiKey);
    }

    public function behaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => ['siteKey', 'apiKey'],
            ],
        ];
    }

    public function rules()
    {
        return [
            ['siteKey', 'string'],
            ['apiKey', 'string'],
            ['darkMode', 'boolean'],
            ['startEvent', 'in', 'range' => ['auto', 'focus', 'none']],
            ['endpoint', 'in', 'range' => ['global', 'eu']],
            [['siteKey', 'apiKey', 'startEvent', 'endpoint'], 'required'],
            ['validateContactForm', 'boolean'],
            ['validateUsersRegistration', 'boolean'],
        ];
    }
}
