<?php

namespace digitalpulsebe\friendlycaptcha\assets;

use craft\web\AssetBundle as BaseAssetBundle;

class AssetBundle extends BaseAssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@digitalpulsebe/friendlycaptcha/assets/js';

        $this->js = [
            'friendlycaptcha.min.js',
            'friendlycaptcha.module.min.js',
        ];

        parent::init();
    }
}
