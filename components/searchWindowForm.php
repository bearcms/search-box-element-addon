<?php
/*
 * Search box element addon for Bear CMS
 * https://github.com/bearcms/search-box-element-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

use BearFramework\App;

$app = App::get();

$form->constraints
    ->setRequired('bearcms-search-input');

$form->onSubmit = function ($values) use ($app) {
    $searchTerm = $values['bearcms-search-input'];
    return $app->urls->get('/s/' . $searchTerm . '/');
};

echo '<html>';
echo '<body>';

echo '<form onsubmitsuccess="clientPackages.get(\'modalWindows\').then(function(m){m.closeAll();m.showLoading();});window.location.href=event.result;">';
echo '<form-element-textbox name="bearcms-search-input" placeholder="' . htmlentities(__('bearcms/search-box-element-addon/ModalWindow/SearchHint')) . '"/>';
echo '<form-element-submit-button text="' . htmlentities(__('bearcms/search-box-element-addon/ModalWindow/SearchButton')) . '"/>';
echo '</form>';

echo '</body>';
echo '</html>';
