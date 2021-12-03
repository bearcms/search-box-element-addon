<?php

/*
 * Search box element addon for Bear CMS
 * https://github.com/bearcms/search-box-element-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

/**
 * @runTestsInSeparateProcesses
 */
class SearchBoxElementTest extends BearCMS\AddonTests\PHPUnitTestCase
{
    /**
     * 
     */
    public function testOutput()
    {
        $app = $this->getApp();

        $html = '<bearcms-search-box-element/>';
        $result = $app->components->process($html);

        $this->assertTrue(strpos($result, '<div class="bearcms-search-box-element"') !== false);
        $this->assertTrue(strpos($result, 'class="bearcms-search-box-element-input"') !== false);
    }
}
