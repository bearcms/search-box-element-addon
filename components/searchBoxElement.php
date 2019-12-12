<?php

use BearFramework\App;

$app = App::get();

echo '<html>';
echo '<body>';

echo '<div style="position:relative;">';
$buttonOnClick = 'window.location.href="' . $app->urls->get('/s/') . '"+encodeURIComponent(this.nextSibling.value)+"/";';
echo '<a title="' . htmlentities(__('bearcms/search-box-element-addon/ButtonTitle')) . '" onclick="' . htmlentities($buttonOnClick) . '" class="bearcms-search-box-element-button"></a>';
echo '<input onkeyup="if(event.keyCode==13){this.previousSibling.click();}" class="bearcms-search-box-element-input" autocomplete="off" placeholder="' . htmlentities(__('bearcms/search-box-element-addon/ButtonTitle')) . '"/>';
echo '</div>';

echo '</body>';
echo '</html>';
