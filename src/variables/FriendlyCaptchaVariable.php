<?php
/**
 * Friendly Captcha plugin for Craft CMS 4.x
 *
 * Integrate Friendly Captcha to fight spam in your Craft CMS forms
 *
 * @link      https://www.digitalpulse.be/
 * @copyright Copyright (c) 2022 Digital Pulse
 */

namespace digitalpulsebe\friendlycaptcha\variables;

use digitalpulsebe\friendlycaptcha\FriendlyCaptcha;

use Twig\Markup;
use yii\base\Exception;
use yii\base\InvalidConfigException;

class FriendlyCaptchaVariable
{

    /**
     * {{ craft.friendlyCaptcha.siteKey }}
     *
     * @return string
     */
    public function siteKey(): string
    {
        return FriendlyCaptcha::$plugin->getSettings()->getSiteKey();
    }

    /**
     * {{ craft.friendlyCaptcha.validateRequest }}
     *
     * @return bool
     * @throws \Exception
     */
    public function validateRequest(): bool
    {
        return FriendlyCaptcha::$plugin->validate->validateRequest();
    }

    /**
     * {{ craft.friendlyCaptcha.renderWidget() }}
     *
     * @param array $attributes
     * @return Markup
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function renderWidget(array $attributes = []): Markup
    {
        return FriendlyCaptcha::$plugin->validate->renderWidget($attributes);
    }
}
