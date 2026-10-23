/*! Modulix: derived from Elementor Pro 4.0.4, modules/query-control/assets/js/editor/query-control.js (bundled in assets/js/editor.js). Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later. Modified by Modulix (2026-10-23): plain script instead of a webpack module; elementorPro.ajax.addRequest(action) -> elementorCommon.ajax.addRequest('pro_' + action), the prefix Elementor Pro's ajax helper adds; registered on elementor:init. */
(function ($) {
  'use strict';
  const __ = (window.wp && wp.i18n && wp.i18n.__) || ((text) => text);
  $(window).on('elementor:init', function () {
    const QueryControl = elementor.modules.controls.Select2.extend({
      cache: null,
      isTitlesReceived: false,
      getSelect2Placeholder() {
        return {
          id: '',
          text: __('All', 'modulix')
        };
      },
      getControlValueByName(controlName) {
        const name = this.model.get('group_prefix') + controlName;
        return this.elementSettingsModel.attributes[name];
      },
      getQueryDataDeprecated() {
        return {
          filter_type: this.model.get('filter_type'),
          object_type: this.model.get('object_type'),
          include_type: this.model.get('include_type'),
          query: this.model.get('query')
        };
      },
      getQueryData() {
        // Use a clone to keep model data unchanged:
        const autocomplete = elementorCommon.helpers.cloneObject(this.model.get('autocomplete'));
        if (_.isEmpty(autocomplete.query)) {
          autocomplete.query = {};
        }
        // Specific for Group_Control_Query
        if ('cpt_tax' === autocomplete.object) {
          autocomplete.object = 'tax';
          if (_.isEmpty(autocomplete.query) || _.isEmpty(autocomplete.query.post_type)) {
            autocomplete.query.post_type = this.getControlValueByName('post_type');
          }
        }
        return {
          autocomplete
        };
      },
      getSelect2DefaultOptions() {
        const self = this;
        return jQuery.extend(elementor.modules.controls.Select2.prototype.getSelect2DefaultOptions.apply(this, arguments), {
          ajax: {
            transport(params, success, failure) {
              const bcFormat = !_.isEmpty(self.model.get('filter_type'));
              let data = {},
                action = 'panel_posts_control_filter_autocomplete';
              if (bcFormat) {
                data = self.getQueryDataDeprecated();
                action = 'panel_posts_control_filter_autocomplete_deprecated';
              } else {
                data = self.getQueryData();
              }
              data.q = params.data.q;
              return elementorCommon.ajax.addRequest('pro_' + action, {
                data,
                success,
                error: failure
              });
            },
            data(params) {
              return {
                q: params.term,
                page: params.page
              };
            },
            cache: true
          },
          escapeMarkup(markup) {
            return markup;
          },
          minimumInputLength: 1
        });
      },
      getValueTitles() {
        const self = this,
          data = {},
          bcFormat = !_.isEmpty(this.model.get('filter_type'));
        let ids = this.getControlValue(),
          action = 'query_control_value_titles',
          filterTypeName = 'autocomplete',
          filterType = {};
        if (bcFormat) {
          filterTypeName = 'filter_type';
          filterType = this.model.get(filterTypeName).object;
          data.filter_type = filterType;
          data.object_type = self.model.get('object_type');
          data.include_type = self.model.get('include_type');
          data.unique_id = '' + self.cid + filterType;
          action = 'query_control_value_titles_deprecated';
        } else {
          filterType = this.model.get(filterTypeName).object;
          data.get_titles = self.getQueryData().autocomplete;
          data.unique_id = '' + self.cid + filterType;
        }
        if (!ids || !filterType) {
          return;
        }
        if (!_.isArray(ids)) {
          ids = [ids];
        }
        elementorCommon.ajax.loadObjects({
          action,
          ids,
          data,
          before() {
            self.addControlSpinner();
          },
          success(ajaxData) {
            self.isTitlesReceived = true;
            self.model.set('options', ajaxData);
            self.render();
          }
        });
      },
      addControlSpinner() {
        this.ui.select.prop('disabled', true);
        this.$el.find('.elementor-control-title').after('<span class="elementor-control-spinner">&nbsp;<i class="eicon-spinner eicon-animation-spin"></i>&nbsp;</span>');
      },
      onReady() {
        if (!this.isTitlesReceived) {
          this.getValueTitles();
        }
      }
    });
    elementor.addControlView('Query', QueryControl);
  });
})(jQuery);
