/*! Modulix: derived from Elementor Pro 4.0.4, assets/js/code-highlight.872070dcb83100cdc6a7.bundle.js. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later. Modified by Modulix (2026-10-23): global names elementorProFrontend -> modulixFrontend, ElementorProFrontendConfig -> ModulixFrontendConfig. */
/*! elementor-pro - v4.0.0 - 28-04-2026 */
"use strict";
(self["webpackChunkelementor_pro"] = self["webpackChunkelementor_pro"] || []).push([["code-highlight"],{

/***/ "../modules/code-highlight/assets/js/frontend/handler.js"
/*!***************************************************************!*\
  !*** ../modules/code-highlight/assets/js/frontend/handler.js ***!
  \***************************************************************/
(__unused_webpack_module, exports) {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports["default"] = void 0;
class codeHighlightHandler extends elementorModules.frontend.handlers.Base {
  onInit(...args) {
    super.onInit(...args);
    Prism.highlightAllUnder(this.$element[0], false);
  }
  onElementChange() {
    // Handle the changes for "Word Wrap" feature
    Prism.highlightAllUnder(this.$element[0], false);
  }
}
exports["default"] = codeHighlightHandler;

/***/ }

}]);
//# sourceMappingURL=code-highlight.872070dcb83100cdc6a7.bundle.js.map