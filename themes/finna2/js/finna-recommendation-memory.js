/* global finna */
finna.recommendationMemory = (function finnaRecommendationMemory() {
  /**
   * Encode string into 64 byte unicode string
   * @param {string} str String to unicode
   * @returns {string} Encoded byte string
   */
  function b64EncodeUnicode(str) {
    return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, function replacer(match, p1) {
      return String.fromCharCode(parseInt(p1, 16));
    }));
  }

  /**
   * Get data as byte string
   * @param {string} srcMod Source modifier
   * @param {string} rec Rec
   * @param {string} orig Orig
   * @param {string} recType RecType
   * @returns {string} Encoded byte string
   */
  function getDataString(srcMod, rec, orig, recType) {
    var data = {
      'srcMod': srcMod,
      'rec': rec,
      'orig': orig,
      'recType': recType
    };
    return b64EncodeUnicode(JSON.stringify(data));
  }

  var my = {
    PARAMETER_NAME: 'rmKey',
    getDataString: getDataString
  };

  return my;
})();
