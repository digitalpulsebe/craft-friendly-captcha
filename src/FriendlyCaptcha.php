<?php
/**
 * Friendly Captcha plugin for Craft CMS 3.x
 *
 * Integrate Friendly Captcha to fight spam in your Craft CMS forms
 *
 * @link      https://www.digitalpulse.be/
 * @copyright Copyright (c) 2022 Digital Pulse
 */

namespace digitalpulsebe\friendlycaptcha;

use craft\contactform\models\Submission;
use digitalpulsebe\friendlycaptcha\services\ValidateService as ValidateService;
use digitalpulsebe\friendlycaptcha\variables\FriendlyCaptchaVariable;
use digitalpulsebe\friendlycaptcha\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\elements\User;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;
use yii\base\ModelEvent;

/**
 *
 * @author    Digital Pulse
 * @package   FriendlyCaptcha
 * @since     1.0.0
 *
 * @property  ValidateService $validate
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class FriendlyCaptcha extends Plugin
{
    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * FriendlyCaptcha::$plugin
     *
     * @var FriendlyCaptcha
     */
    public static $plugin;

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public $hasCpSettings = true;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public $hasCpSection = false;

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('friendlyCaptcha', FriendlyCaptchaVariable::class);
            }
        );

        // Handle form submissions of the craftcms/contact-form plugin
        if (class_exists(Submission::class) && $this->settings->validateContactForm) {
            Event::on(Submission::class, Submission::EVENT_BEFORE_VALIDATE, function (ModelEvent $event) {
                $submission = $event->sender;
                if (!$this->validate->validateRequest()) {
                    $submission->addError('friendlyCaptcha', Craft::t('friendly-captcha', 'Please verify you are human.'));
                    $event->isValid = false;
                }
            });
        }

        // Handle user registration forms
        if ($this->settings->validateUsersRegistration && Craft::$app->getRequest()->getIsSiteRequest()) {
            Event::on(User::class, User::EVENT_BEFORE_VALIDATE, function (ModelEvent $event) {
                /** @var User $user */
                $user = $event->sender;

                // Only new users
                if ($user->id === null && $user->uid === null && $user->contentId === null) {
                    if (!$this->validate->validateRequest()) {
                        $user->addError('friendlyCaptcha', Craft::t('friendly-captcha', 'Please verify you are human.'));
                        $event->isValid = false;
                    }
                }
            });
        }

        Craft::info(
            Craft::t(
                'friendly-captcha',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'friendly-captcha/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}
