<?php

/*
 * Search box element addon for Bear CMS
 * https://github.com/bearcms/search-box-element-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS\SearchBoxElement\Internal;

use BearFramework\App;
use DOMDocument;
use IvoPetkov\HTML5DOMDocument;

/**
 *
 */
class Utilities
{

    /**
     * 
     * @return void
     */
    static function updateIndex(): void
    {
        $app = App::get();

        $urls = null;
        $robotsURL = $app->urls->get('/robots.txt');
        $result = self::makeRequest($robotsURL);
        if ($result['status'] === 200) {
            $robotsLines = explode("\n", $result['content']);
            $sitemapURL = '';
            foreach ($robotsLines as $robotsLine) {
                $robotsLine = strtolower(trim($robotsLine));
                if (strpos($robotsLine, 'sitemap:') === 0) {
                    $sitemapURL = trim(substr($robotsLine, 8));
                    break;
                }
            }
            if (strlen($sitemapURL) === 0) {
                $sitemapURL = $app->urls->get('/sitemap.xml');
            }
            $result = self::makeRequest($sitemapURL);
            if ($result['status'] === 200) {
                $dom = new DOMDocument();
                try {
                    $dom->loadXML($result['content']);
                    $elements = $dom->getElementsByTagName('url');
                    if ($elements->length > 0) {
                        $urls = [];
                        foreach ($elements as $element) {
                            $lastModDate = '';
                            $lastmodElements = $element->getElementsByTagName('lastmod');
                            if ($lastmodElements->length === 1) {
                                $lastModDate = $lastmodElements->item(0)->nodeValue;
                            }
                            $locationElements = $element->getElementsByTagName('loc');
                            if ($locationElements->length === 1) {
                                $urls[$locationElements->item(0)->nodeValue] = $lastModDate;
                            }
                        }
                    }
                } catch (\Exception $e) {
                }
            }
        }
        if ($urls === null) {
            return;
        }
        // Find the base URL. Dont get if from $app->request->base because the site may be served by multiple domains
        $shortestURL = null;
        foreach ($urls as $url => $lastModDate) {
            if ($shortestURL === null || strlen($url) < strlen($shortestURL)) {
                $shortestURL = $url;
            }
        }
        $baseURL = rtrim($shortestURL, '\/');
        $paths = [];
        foreach ($urls as $url => $lastModDate) {
            $paths[str_replace($baseURL, '', $url)] = $lastModDate;
        }

        $data = self::getData();
        $hasChange = false;
        $pathsToRemove = array_diff($app->dataIndex->getKeys('bearcms-search'), array_keys($paths));
        if (!empty($pathsToRemove)) {
            $hasChange = true;
            $app->dataIndex->deleteMultiple('bearcms-search', $pathsToRemove);
        }
        $tasksData = [];
        foreach ($paths as $path => $lastModDate) {
            if (!isset($data[$path]) || $data[$path] !== $lastModDate) {
                $taskID = 'bearcms-search-update-page-index-' . md5($path);
                $tasksData[] = [
                    'definitionID' => 'bearcms-search-update-page-index',
                    'data' => $path,
                    'options' => ['id' => $taskID, 'ignoreIfExists' => true]
                ];
            }
        }
        if (!empty($tasksData)) {
            $hasChange = true;
            $app->tasks->addMultiple($tasksData);
        }
        if ($hasChange) {
            self::setData($paths);
        }
    }

    /**
     * 
     * @param string $path
     * @return void
     */
    static function updatePageIndex(string $path): void
    {
        $data = self::getData();
        if (!isset($data[$path])) {
            return;
        }
        $app = App::get();
        $url = $app->request->base . $path; // Dont use $app->urls->get() because the path may be encoded already
        $result = self::makeRequest($url);
        if ($result['status'] === 200) {
            $dom = new HTML5DOMDocument();
            $dom->loadHTML($result['content'], HTML5DOMDocument::ALLOW_DUPLICATE_IDS);
            $titleElement = $dom->querySelector('title');
            $title = $titleElement !== null ? html_entity_decode($titleElement->innerHTML) : '';
            $bodyElement = $dom->querySelector('body');
            $content = '';
            if ($bodyElement !== null) {
                $scriptElements = $bodyElement->querySelectorAll('script');
                foreach ($scriptElements as $scriptElement) {
                    $scriptElement->parentNode->removeChild($scriptElement);
                }
                $content = $bodyElement->innerHTML;
                $content = str_replace('</', ' </', $content); // add space so when tags removed there is space between words
                $content = strip_tags($content);
                $content = preg_replace("/\s+/", " ", $content);
                $content = html_entity_decode($content);
            }
            $data = [
                'path' => $path,
                'title' => trim($title),
                'content' => trim($content)
            ];
            $app->dataIndex->set('bearcms-search', $path, $data);
        }
    }

    /**
     * 
     * @param string $query
     * @param integer $limit
     * @param integer $page
     * @return array|null NULL - the index is not ready
     */
    static function search(string $query, int $limit = 20, int $page = 1): ?array
    {
        if (!self::dataExists()) {
            self::addIndexUpdateTask();
            return null;
        }
        $query = trim($query);
        if (strlen($query) === 0) {
            return [];
        }
        $app = App::get();
        $items = $app->dataIndex->getList('bearcms-search');
        if (count($items) === 0) {
            return null;
        }

        $crop = function ($text, $length) {
            if (mb_strlen($text) <= $length) {
                return $text;
            } else {
                $text = mb_substr($text, 0, $length);
                $position = mb_strrpos($text, " ");
                if ($position > 0) {
                    $text = mb_substr($text, 0, $position);
                }
                $text .= " ...";
                return $text;
            }
        };

        $contentMaxLength = 200;
        $loweredQuery = mb_strtolower($query);
        $results = [];
        $index = 0;
        $resultsOrder = [];
        foreach ($items as $item) {
            $url = isset($item->path) ? $app->request->base . $item->path : (isset($item->url) ? $item->url : $app->request->base . '/'); // compatibility with an old version
            $title = $item->title;
            $content = $item->content;
            $loweredTitle = mb_strtolower($title);
            $loweredContent = mb_strtolower($content);
            $termPosition = mb_strpos($loweredContent, $loweredQuery);
            if ($termPosition !== false) {
                if ($termPosition - 50 > 0) {
                    $lastSpaceIndex = mb_strrpos(mb_substr($content, 0, $termPosition - 50), ' ');
                    $content = '... ' . trim($crop(mb_substr($content, $lastSpaceIndex), $contentMaxLength));
                } else {
                    $content = $crop($content, $contentMaxLength);
                }
                $results[$index] = ['url' => $url, 'title' => $title, 'content' => $content];
                $value = sizeof(explode($loweredQuery, $loweredContent));
                $value--;
                $positionValue = sizeof(explode($loweredQuery, $loweredTitle, 2));
                $positionValue--;
                $resultsOrder[$index] = $value + 5 * $positionValue;
                $index++;
            }
        }
        arsort($resultsOrder);
        $orderKeys = array_keys($resultsOrder);
        $chunks = array_chunk($orderKeys, $limit);
        $selectedChunk = isset($chunks[$page - 1]) ? $chunks[$page - 1] : [];
        $temp = [];
        foreach ($selectedChunk as $index) {
            $temp[] = $results[$index];
        }
        return $temp;
    }

    /**
     * 
     * @return array
     */
    static function getData(): array
    {
        $app = App::get();
        $value = $app->data->getValue(self::getDataKey());
        if ($value !== null) {
            return json_decode($value, true);
        }
        return [];
    }

    /**
     * 
     * @param array $data
     * @return void
     */
    static function setData(array $data): void
    {
        $app = App::get();
        $app->data->setValue(self::getDataKey(), json_encode($data));
    }

    /**
     * 
     * @return void
     */
    static function deleteData(): void
    {
        $app = App::get();
        $app->data->delete(self::getDataKey());
    }

    /**
     * 
     * @return boolean
     */
    static function dataExists(): bool
    {
        $app = App::get();
        return $app->data->exists(self::getDataKey());
    }

    /**
     * 
     * @return string
     */
    static function getDataKey(): string
    {
        return 'bearcms-search-box-element/index.json';
    }

    /**
     * 
     * @param string $url
     * @return array
     */
    static function makeRequest(string $url): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_USERAGENT, 'bearcms-audits-bot');
        //    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        //    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $content = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['status' => (int) $httpCode, 'content' => (string) $content];
    }

    static function addIndexUpdateTask(int $delay = 0): void
    {
        $app = App::get();
        $app->tasks->add('bearcms-search-update-index', null, [
            'id' => 'bearcms-search-update-index',
            'startTime' => (time() + $delay),
            'ignoreIfExists' => true
        ]);
    }
}
