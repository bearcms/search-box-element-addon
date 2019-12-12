<?php

/*
 * Search box element addon for Bear CMS
 * https://github.com/bearcms/search-box-element-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

BearFramework\Addons::register('bearcms/search-box-element-addon', __DIR__, [
    'require' => [
        'bearcms/bearframework-addon',
        'bearframework/localization-addon',
        'bearframework/tasks-addon',
        'ivopetkov/data-index-bearframework-addon'
    ]
]);
