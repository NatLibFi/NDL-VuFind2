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
    $mergedData = $this->driver->tryMethod('getMergedRecordData')['urls'] ?? [];
    $urls = $this->getLinkDetails($openUrlActive);
    $combinedURLs = array_merge($urls, $mergedData, $onlineURLs);

    $audios = $this->getAudios($combinedURLs);
    $videos = $this->getVideos($combinedURLs);
    $models = $this->driver->tryMethod('getModels', [], []);
    $medias = compact('audios', 'videos', 'models');
    $hasDigitalObjects = $audios || $videos || $models;
    return $this->cache[$cacheKey] = compact('medias', 'hasDigitalObjects');
  }
  public function getAudios($urls): array
  {
    return array_filter(
      $urls,
      fn ($url) => ($url['embed'] ?? false) === 'audio'
    );
  }
  public function getVideos(): array
  {
    $recordLinker = $this->getView()->plugin('recordLinker');
    return array_filter(
      $this->getAllURLs(),
      fn ($url) => ($url['embed'] ?? false) === 'video' || $recordLinker()->getEmbeddedVideo($url['url']) === 'data-embed-iframe'
    );
  }
}