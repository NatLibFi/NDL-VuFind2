import { Viewer, type ViewerConfigOptions } from '@samvera/clover-iiif';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';

interface CloverConfig {
  iiifManifestURL: string,
  loadingText: string,
  options: ViewerConfigOptions,
}

/** Retrieve manifest URL and overriding options from PHP backend by way of
  * the window object.
  *
  * @see /themes/finna2/templates/_ui/components/finna-iiif-viewer.phtml
  */
const config: CloverConfig = (window as any).cloverConfig;
// Set some defaults
const defaultOptions = {
  openSeadragon: {
    gestureSettingsMouse: {
      scrollToZoom: true
    }
  },
  showIIIFBadge: false,
  customLoadingComponent: () =>
    <div className="iiif-viewer-loading">{config.loadingText}</div>
};
const options = { ...defaultOptions, ...config.options };

createRoot(document.getElementById('iiif-viewer')!).render(
  <StrictMode>
    <Viewer iiifContent={config.iiifManifestURL} options={options}
            />
  </StrictMode>
);
