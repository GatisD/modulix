/*! Modulix: derived from Elementor Pro 4.0.4, assets/js/popup.467433314d83de8c86ec.bundle.js. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later. Modified by Modulix (2026-10-23): global names elementorProFrontend -> modulixFrontend, ElementorProFrontendConfig -> ModulixFrontendConfig. */
/*! elementor-pro - v4.0.0 - 28-04-2026 */
"use strict";
(self["webpackChunkelementor_pro"] = self["webpackChunkelementor_pro"] || []).push([["popup"],{

/***/ "../modules/popup/assets/js/frontend/handlers/forms-action.js"
/*!********************************************************************!*\
  !*** ../modules/popup/assets/js/frontend/handlers/forms-action.js ***!
  \********************************************************************/
(__unused_webpack_module, exports) {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;
var _default = exports["default"] = elementorModules.frontend.handlers.Base.extend({
  getDefaultSettings() {
    return {
      selectors: {
        form: '.elementor-form'
      }
    };
  },
  getDefaultElements() {
    var selectors = this.getSettings('selectors'),
      elements = {};
    elements.$form = this.$element.find(selectors.form);
    return elements;
  },
  bindEvents() {
    this.elements.$form.on('submit_success', this.handleFormAction);
  },
  handleFormAction(event, response) {
    if ('undefined' === typeof response.data.popup) {
      return;
    }
    const popupSettings = response.data.popup;
    if ('open' === popupSettings.action) {
      return modulixFrontend.modules.popup.showPopup(popupSettings);
    }
    setTimeout(() => {
      return modulixFrontend.modules.popup.closePopup(popupSettings, event);
    }, 1000);
  }
});

/***/ }

}]);
//# sourceMappingURL=popup.467433314d83de8c86ec.bundle.js.map