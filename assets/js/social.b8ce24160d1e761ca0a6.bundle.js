/*! Modulix: derived from Elementor Pro 4.0.4, assets/js/social.b8ce24160d1e761ca0a6.bundle.js. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later. Modified by Modulix (2026-10-24): global names elementorProFrontend -> modulixFrontend, ElementorProFrontendConfig -> ModulixFrontendConfig. */
/*! elementor-pro - v4.0.0 - 28-04-2026 */
"use strict";
(self["webpackChunkelementor_pro"] = self["webpackChunkelementor_pro"] || []).push([["social"],{

/***/ "../modules/social/assets/js/frontend/handlers/facebook.js"
/*!*****************************************************************!*\
  !*** ../modules/social/assets/js/frontend/handlers/facebook.js ***!
  \*****************************************************************/
(__unused_webpack_module, exports) {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;
class FacebookHandler extends elementorModules.frontend.handlers.Base {
  getConfig() {
    return modulixFrontend.config.facebook_sdk;
  }
  setConfig(prop, value) {
    modulixFrontend.config.facebook_sdk[prop] = value;
  }
  parse() {
    // On FB SDK is loaded, parse current element
    FB.XFBML.parse(this.$element[0]);
  }
  loadSDK() {
    const config = this.getConfig();

    // Preventing from ajax request to be sent multiple times when loading multiple widgets
    if (config.isLoading || config.isLoaded) {
      return;
    }
    this.setConfig('isLoading', true);
    jQuery.ajax({
      url: 'https://connect.facebook.net/' + config.lang + '/sdk.js',
      dataType: 'script',
      cache: true,
      success: () => {
        FB.init({
          appId: config.app_id,
          version: 'v2.10',
          xfbml: false
        });
        this.setConfig('isLoaded', true);
        this.setConfig('isLoading', false);
        elementorFrontend.elements.$document.trigger('fb:sdk:loaded');
      }
    });
  }
  onInit(...args) {
    super.onInit(...args);
    this.loadSDK();
    const config = this.getConfig();
    if (config.isLoaded) {
      this.parse();
    } else {
      elementorFrontend.elements.$document.on('fb:sdk:loaded', () => this.parse());
    }
  }
}
exports["default"] = FacebookHandler;

/***/ }

}]);
//# sourceMappingURL=social.b8ce24160d1e761ca0a6.bundle.js.map