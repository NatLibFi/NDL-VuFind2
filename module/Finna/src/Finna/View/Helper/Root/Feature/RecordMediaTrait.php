<?php

namespace Finna\View\Helper\Root\Feature;

trait RecordMediaTrait
{
  public function getMediaURLs($openUrlActive = false): array
  {
    $cacheKey = __FUNCTION__ . "|" . $openUrlActive ? 'true' : 'false';
    if (isset($this->cache[$cacheKey])) {
      return $this->cache[$cacheKey];
    }
    $onlineURLs = $this->driver->tryMethod('getOnlineURLs', [], []);
    $mergedDataURLs = $this->driver->tryMethod('getMergedRecordData')['urls'] ?? [];
    $urls = $this->getLinkDetails($openUrlActive);
    $combinedURLs = array_merge($urls, $mergedDataURLs, $onlineURLs);

    $audios = $this->getAudios($combinedURLs);
    $videos = $this->getVideos($combinedURLs);
    $models = $this->driver->tryMethod('getModels', [], []);
    $medias = compact('audios', 'videos', 'models');
    $hasDigitalObjects = $audios || $videos || $models;
    return $this->cache[$cacheKey] = compact('medias', 'hasDigitalObjects');
  }
  public function getAudios(&$urls): array
  {
    $results = [];
    foreach ($urls as $i => $url) {
      if ($url['embed'] ?? false === 'audio') {
        $results[$i] = $url;
      }
    }
    $urls = array_diff_key($urls, $results);
    return $results;
  }
  public function getVideos(&$urls): array
  {
    $results = [];
    $recordLinker = $this->getView()->plugin('recordLinker');
    foreach ($urls as $i => $url) {
      if (
        ($url['embed'] ?? false) === 'video'
        || $recordLinker()->getEmbeddedVideo($url['url']) == 'data-embed-iframe'
      ) {
        $results[$i] = $url;
      }
    }
    $urls = array_diff_key($urls, $results);
    return $results;
  }
}
