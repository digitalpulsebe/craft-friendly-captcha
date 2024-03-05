<?php

namespace digitalpulsebe\friendlycaptcha\assets;

use craft\web\AssetBundle as BaseAssetBundle;

class AssetBundle extends BaseAssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@digitalpulsebe/friendlycaptcha/assets';

        $this->js = [
            'js/friendlycaptcha.min.js',
            'js/friendlycaptcha.module.min.js',
        ];

        parent::init();
    }
}
