/* global VuFind */
class FinnaCookieConsentElement extends HTMLElement {

  get consentCategories() {
    return this.getAttribute('consent-categories');
  }

  set consentCategories(newValue) {
    this.setAttribute('consent-categories', newValue);
  }

  get serviceBaseUrl() {
    return this.getAttribute('service-base-url');
  }

  set serviceBaseUrl(newValue) {
    this.setAttribute('service-base-url', newValue);
  }

  get serviceUrl() {
    return this.getAttribute('service-url');
  }

  set serviceUrl(newValue) {
    this.setAttribute('service-url', newValue);
  }
  
  constructor() {
    super();
  }

  connectedCallback() {
    // Create the element
    const divInfo = document.createElement('div');
    divInfo.classList.add('embedded-content-cookie-info');

    const divHeading = document.createElement('div');
    divHeading.classList.add('embedded-content-heading');
    divHeading.append(VuFind.translate('embedded_content_heading'));
    divInfo.append(divHeading);

    const divDescription = document.createElement('div');
    divDescription.classList.add('embedded-content-description');
    const replacements = {
      '%%serviceBaseUrl%%': this.serviceBaseUrl,
      '%%consentCategories%%': this.consentCategories
    };
    divDescription.append(VuFind.translate('embedded_content_description', replacements));
    divInfo.append(divDescription);

    const divActions = document.createElement('div');
    divActions.classList.add('embedded-content-actions');

    const aOuterLink = document.createElement('a');
    aOuterLink.classList.add('btn', 'btn-primary');
    aOuterLink.href = this.serviceUrl || '';
    aOuterLink.target = '_blank';
    aOuterLink.append(VuFind.translate('embedded_content_external_link'));
  
    const linkIcon = document.createElement('i');
    linkIcon.classList.add('fa', 'fa-new-window');
    linkIcon.setAttribute('aria-hidden', true);
    aOuterLink.append(linkIcon);

    const linkSpan = document.createElement('span');
    linkSpan.classList.add('sr-only');
    linkSpan.append(VuFind.translate('Open in a new window'));
    aOuterLink.append(linkSpan);
    divActions.append(aOuterLink);

    const aShowModal = document.createElement('a');
    aShowModal.classList.add('btn', 'btn-default');
    aShowModal.href = '#';
    aShowModal.setAttribute('aria-haspopup', 'dialog');
    aShowModal.append(VuFind.translate('Cookie Settings'));
    aShowModal.addEventListener('click', (e) => {
      // Proxy a click to first found element with proper data-cc attribute
      e.preventDefault();
      e.stopPropagation();
      $.fn.finnaPopup.closeOpen();
      const found = document.querySelector('span[data-cc]');
      if (found) {
        found.click();
      }
    })
    divActions.append(aShowModal);
    divDescription.append(divActions);
    this.append(divInfo);
  }
}

customElements.define('finna-consent', FinnaCookieConsentElement);
