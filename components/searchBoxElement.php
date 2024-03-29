<?php

/*
 * Search box element addon for Bear CMS
 * https://github.com/bearcms/search-box-element-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

use BearFramework\App;

$app = App::get();

$outputType = (string) $component->getAttribute('output-type');
$outputType = isset($outputType[0]) ? $outputType : 'full-html';
$isFullHtmlOutputType = $outputType === 'full-html';

echo '<html>';
echo '<body>';

if ($isFullHtmlOutputType) {
    echo '<div class="bearcms-search-box-element" style="position:relative;">';
    $buttonOnClick = 'var q=this.nextSibling.value;if(q.length>0){window.location.href="' . $app->urls->get('/s/') . '"+encodeURIComponent(q)+"/";}else{this.nextSibling.focus();}';
    echo '<a title="' . htmlentities(__('bearcms/search-box-element-addon/ButtonTitle')) . '" onclick="' . htmlentities($buttonOnClick) . '" class="bearcms-search-box-element-button"></a>';
    echo '<input onkeyup="if(event.keyCode==13){this.previousSibling.click();}" class="bearcms-search-box-element-input" autocomplete="off" placeholder="' . htmlentities(__('bearcms/search-box-element-addon/ButtonTitle')) . '"/>';
    echo '</div>';
} else {
    echo '[' . __('bearcms/search-box-element-addon/ButtonTitle') . ']';
}

echo '</body>';
echo '</html>';
