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
    $largeImages = $this->hasLargeImage() ? $this->driver->tryMethod('getAllImages') : [];
    $audios = $this->getAudios($combinedURLs);
    $videos = $this->getVideos($combinedURLs);
    $models = $this->driver->tryMethod('getModels', [], []);
    $medias = [$largeImages, $audios, $videos, $models];

    // Determine if any media objects (images, audios, videos, models) are present
    $hasMedia = !empty($largeImages) || !empty($audios) || !empty($videos) || !empty($models);

    // Check if there are multiple distinct types of media present
    $hasMultipleMediaTypes = count(array_filter($medias)) > 1;

    // Return cached result
    return $this->cache[$cacheKey] = compact('largeImages', 'audios', 'videos', 'models', 'hasMedia', 'hasMultipleMediaTypes');
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
  /**
   * Check if record has large image
   *
   * @return bool
   */
  public function hasLargeImage(): bool
  {
      $language = $this->getView()->layout()->userLang;
      $imageTypes = ['small', 'medium', 'large', 'master'];
      $images = $this->getAllImages($language, false, false);
      $hasValidImages = false;
      foreach ($images as $image) {
          if (array_intersect(array_keys($image['urls'] ?? []), $imageTypes)) {
              $hasValidImages = true;
              break;
          }
      }
      if (!$hasValidImages) {
          return false;
      }

      // Check for record formats:
      $largeImageRecordFormats
          = isset($this->config->Record->large_image_record_formats)
        ? $this->config->Record->large_image_record_formats->toArray()
          : ['lido', 'forward', 'forwardAuthority', 'ead3'];
      $recordFormat = $this->driver->tryMethod('getRecordFormat');
      if (in_array($recordFormat, $largeImageRecordFormats)) {
          return true;
      }

      // Check for formats that use large image layout:
      $largeImageFormats
          = isset($this->config->Record->large_image_formats)
          ? $this->config->Record->large_image_formats->toArray()
          : [
              '0/Image/',
              '0/PhysicalObject/',
              '0/WorkOfArt/',
              '0/Video/',
          ];
      $formats = $this->driver->tryMethod('getFormats');
      if (array_intersect($formats, $largeImageFormats)) {
          return true;
      }

      return false;
  }
}
