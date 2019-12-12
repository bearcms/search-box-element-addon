<?php

/*
 * Search box element addon for Bear CMS
 * https://github.com/bearcms/search-box-element-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

use BearCMS\SearchBoxElement\Internal\Utilities;
use BearFramework\App;

$app = App::get();

$app->bearCMS->addons
    ->register('bearcms/search-box-element-addon', function (\BearCMS\Addons\Addon $addon) use ($app) {
        $addon->initialize = function (array $options = []) use ($app) {
            $context = $app->contexts->get(__FILE__);

            $enableService = isset($options['enableService']) ? $options['enableService'] : true;

            $context->assets
                ->addDir('assets');

            $context->classes
                ->add('BearCMS\SearchBoxElement\Internal\Utilities', 'classes/Utilities.php');

            $app->localization
                ->addDictionary('en', function () use ($context) {
                    return include $context->dir . '/locales/en.php';
                })
                ->addDictionary('bg', function () use ($context) {
                    return include $context->dir . '/locales/bg.php';
                });

            \BearCMS\Internal\ElementsTypes::add('searchBox', [
                'componentSrc' => 'bearcms-search-box-element',
                'componentFilename' => $context->dir . '/components/searchBoxElement.php'
            ]);

            \BearCMS\Internal\Themes::$elementsOptions['searchBox'] = function ($context, $idPrefix, $parentSelector) {
                $group = $context->addGroup(__('bearcms/search-box-element-addon/Search box'));

                $groupInput = $group->addGroup(__('bearcms/search-box-element-addon/Input'));
                $groupInput->addOption($idPrefix . "SearchBoxInputCSS", "css", '', [
                    "cssTypes" => ["cssSize", "cssText", "cssTextShadow", "cssPadding", "cssMargin", "cssBackground", "cssBorder", "cssRadius", "cssShadow"],
                    "cssOutput" => [
                        ["rule", $parentSelector . " .bearcms-search-box-element-input", "width:100%;display:inline-block;box-sizing:border-box;border:0;margin:0;padding:0;"],
                        ["selector", $parentSelector . " .bearcms-search-box-element-input"],
                    ],
                    "value" => '{"height":"42px","font-family":"Arial","color":"#000000","font-size":"14px","line-height":"42px","padding-right":"15px","padding-left":"15px","width":"100%","background-color":"#ffffff","border-top":"1px solid #cccccc","border-right":"1px solid #cccccc","border-bottom":"1px solid #cccccc","border-left":"1px solid #cccccc","border-top-left-radius":"2px","border-top-right-radius":"2px","border-bottom-left-radius":"2px","border-bottom-right-radius":"2px"}'
                ]);

                $groupButton = $group->addGroup(__('bearcms/search-box-element-addon/Button'));
                $groupButton->addOption($idPrefix . "SearchBoxButtonCSS", "css", '', [
                    "cssTypes" => ["cssSize", "cssBackground", "cssBorder", "cssRadius", "cssShadow"],
                    "cssOutput" => [
                        ["rule", $parentSelector . " .bearcms-search-box-element-button", "box-sizing:border-box;display:block;text-decoration:none;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;position:absolute;right:0;cursor:pointer;"],
                        ["selector", $parentSelector . " .bearcms-search-box-element-button"]
                    ],
                    "value" => '{"height":"42px","width":"42px","border-top-right-radius":"2px","border-bottom-right-radius":"2px","background-color":"#555","background-image":"url(addon:bearcms\/search-box-element-addon:assets\/icon.png)","background-position":"center center","background-repeat":"no-repeat","background-attachment":"scroll","background-size":"cover"}'
                ]);
            };

            $app->routes
                ->add(['/s', '/s/'], [
                    [$app->bearCMS, 'disabledCheck'],
                    function () use ($app) {
                        $response = new App\Response\PermanentRedirect($app->urls->get());
                        $response->headers->set($response->headers->make('X-Robots-Tag', 'noindex, nofollow'));
                        return $response;
                    }
                ])
                ->add('/s/*', [
                    [$app->bearCMS, 'disabledCheck'],
                    function () use ($app, $enableService) {
                        if (!$enableService) {
                            return;
                        }
                        $query = trim($app->request->path->getSegment(1));
                        if (strlen($query) === 0) {
                            $response = new App\Response\PermanentRedirect($app->urls->get());
                            $response->headers->set($response->headers->make('X-Robots-Tag', 'noindex, nofollow'));
                            return $response;
                        }
                        $results = Utilities::search($query);
                        $content = '<html>';
                        $content .= '<head>';
                        $title = sprintf(__('bearcms/search-box-element-addon/page-title'), $query);
                        $content .= '<title>' . htmlspecialchars($title) . '</title>';
                        $content .= '<meta name="description" content="' . htmlentities($title) . '">';
                        $content .= '</head>';
                        $content .= '<body>';
                        $content .= '<bearcms-heading-element text="' . htmlentities($title) . '" size="large"/>';
                        if ($results === null) {
                            $textContent = __('bearcms/search-box-element-addon/building-search-index');
                        } elseif (!empty($results)) {
                            $resultsHTML = [];
                            foreach ($results as $result) {
                                $resultsHTML[] = '<a href="' . htmlentities($result['url']) . '" >' . htmlspecialchars($result['title']) . '</a><br>' . htmlspecialchars($result['content']);
                            }
                            $textContent = implode('<br><br>', $resultsHTML);
                        } else {
                            $textContent = sprintf(__('bearcms/search-box-element-addon/no-results-found'), $query);
                        }
                        $content .= '<bearcms-text-element text="' . htmlentities('<br>' . $textContent) . '"/>';
                        $content .= '</body>';
                        $content .= '</html>';
                        $response = new App\Response\HTML($content);
                        $app->bearCMS->apply($response);
                        $response->headers->set($response->headers->make('X-Robots-Tag', 'noindex, nofollow'));
                        return $response;
                    }
                ]);

            $app->tasks
                ->define('bearcms-search-update-index', function () use ($enableService) {
                    if (!$enableService) {
                        return;
                    }
                    Utilities::updateIndex();
                })
                ->define('bearcms-search-update-page-index', function (string $url) use ($enableService) {
                    if (!$enableService) {
                        return;
                    }
                    Utilities::updatePageIndex($url);
                });;

            if ($enableService) {
                $app->bearCMS
                    ->addEventListener('internalSitemapChange', function () {
                        Utilities::addIndexUpdateTask();
                    });
            }
        };
    });
