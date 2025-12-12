<?php

namespace Finna\View\Helper\Root\Feature;

trait RecordMediaTrait
{
public function getMediaURLs($openUrlActive = false): array
{
    $cacheKey = __FUNCTION__ . "|" . ($openUrlActive ? 'true' : 'false');

    if (isset($this->cache[$cacheKey])) {
        return $this->cache[$cacheKey];
    }

    $onlineURLs = $this->driver->tryMethod('getOnlineURLs', [], []);
    $mergedDataURLs = $this->driver->tryMethod('getMergedRecordData')['urls'] ?? [];
    $urls = $this->getLinkDetails($openUrlActive);

    // Combine URLs
    $combinedURLs = array_merge($urls, $mergedDataURLs, $onlineURLs);

    // Get media types
    $audios = $this->getAudios($combinedURLs);
    $videos = $this->getVideos($combinedURLs);
    $models = $this->driver->tryMethod('getModels', [], []);
    $medias = [$audios, $videos, $models];

    // Determine if any media objects (audios, videos, models) are present
    $hasMedia = !empty($audios) || !empty($videos) || !empty($models);

    // Check if there are multiple distinct types of media present
    $hasMultipleMediaTypes = count(array_filter($medias)) > 1;
    // Return cached result
    return $this->cache[$cacheKey] = compact('audios', 'videos', 'models', 'hasMedia', 'hasMultipleMediaTypes');
  }
  public function getAudios(&$urls): array
  {
    $results = [];
    foreach ($urls as $i => $url) {
        // Check if 'embed' key exists and its value is 'audio'
        if (($url['embed'] ?? false) === 'audio') {
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
      // Check if 'embed' key exists and its value is 'video' or if it meets the second condition
          if (
              ($url['embed'] ?? false) === 'video' ||
              $recordLinker()->getEmbeddedVideo($url['url']) == 'data-embed-iframe'
          ) {
              $results[$i] = $url;
          }
      }
      $urls = array_diff_key($urls, $results);
      return $results;
  }
}
