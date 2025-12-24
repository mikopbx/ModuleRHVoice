"use strict";

/*
 * Copyright (C) MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Nikolay Beketov, 11 2018
 *
 */

/* global globalRootUrl, globalTranslate, Form, Config */
var ModuleRHVoice = {
  $formObj: $('#modulerh-voice-form'),
  $checkBoxes: $('#modulerh-voice-form .ui.checkbox'),
  $dropDowns: $('#modulerh-voice-form .ui.dropdown'),
  $disabilityFields: $('#modulerh-voice-form  .disability'),
  $statusToggle: $('#module-status-toggle'),
  $moduleStatus: $('#status'),

  /**
   * Field validation rules
   * https://semantic-ui.com/behaviors/form.html
   */
  validateRules: {
    textField: {
      identifier: 'text_field',
      rules: [{
        type: 'empty',
        prompt: globalTranslate.mod_tplValidateValueIsEmpty
      }]
    },
    areaField: {
      identifier: 'text_area_field',
      rules: [{
        type: 'empty',
        prompt: globalTranslate.mod_tplValidateValueIsEmpty
      }]
    },
    passwordField: {
      identifier: 'password_field',
      rules: [{
        type: 'empty',
        prompt: globalTranslate.mod_tplValidateValueIsEmpty
      }]
    }
  },

  /**
   * On page load we init some Semantic UI library
   */
  initialize: function initialize() {
    // инициализируем чекбоксы и выподающие менюшки
    ModuleRHVoice.$checkBoxes.checkbox();
    ModuleRHVoice.$dropDowns.dropdown();
    ModuleRHVoice.checkStatusToggle();
    window.addEventListener('ModuleStatusChanged', ModuleRHVoice.checkStatusToggle);
    ModuleRHVoice.initializeForm();
    $('#download-button').on('click', function () {
      var text = ModuleRHVoice.$formObj.form('get value', 'text');

      if (!text || text.trim() === '') {
        return;
      }

      var voice = ModuleRHVoice.$formObj.form('get value', 'voice');
      var baseUrl = '/pbxcore/api/rhvoice/say';
      var queryParams = new URLSearchParams({
        text: text,
        voice: voice
      }).toString();
      var url = "".concat(baseUrl, "?").concat(queryParams);
      console.log(url);

      if (url) {
        window.open(url, '_blank');
      } else {
        console.error('URL не указан');
      }
    });
  },

  /**
   * Change some form elements classes depends of module status
   */
  checkStatusToggle: function checkStatusToggle() {
    if (ModuleRHVoice.$statusToggle.checkbox('is checked')) {
      ModuleRHVoice.$disabilityFields.removeClass('disabled');
      ModuleRHVoice.$moduleStatus.show();
    } else {
      ModuleRHVoice.$disabilityFields.addClass('disabled');
      ModuleRHVoice.$moduleStatus.hide();
    }
  },

  /**
   * Send command to restart module workers after data changes,
   * Also we can do it on TemplateConf->modelsEventChangeData method
   */
  applyConfigurationChanges: function applyConfigurationChanges() {
    ModuleRHVoice.changeStatus('Updating');
    $.api({
      url: "".concat(Config.pbxUrl, "/pbxcore/api/modules/ModuleRHVoice/reload"),
      on: 'now',
      successTest: function successTest(response) {
        // test whether a JSON response is valid
        return Object.keys(response).length > 0 && response.result === true;
      },
      onSuccess: function onSuccess() {
        ModuleRHVoice.changeStatus('Connected');
      },
      onFailure: function onFailure() {
        ModuleRHVoice.changeStatus('Disconnected');
      }
    });
  },

  /**
   * We can modify some data before form send
   * @param settings
   * @returns {*}
   */
  cbBeforeSendForm: function cbBeforeSendForm(settings) {
    var result = settings;
    result.data = ModuleRHVoice.$formObj.form('get values');
    return result;
  },

  /**
   * Some actions after forms send
   */
  cbAfterSendForm: function cbAfterSendForm() {
    ModuleRHVoice.applyConfigurationChanges();
  },

  /**
   * Initialize form parameters
   */
  initializeForm: function initializeForm() {
    Form.$formObj = ModuleRHVoice.$formObj;
    Form.url = "".concat(globalRootUrl, "module-r-h-voice/save");
    Form.validateRules = ModuleRHVoice.validateRules;
    Form.cbBeforeSendForm = ModuleRHVoice.cbBeforeSendForm;
    Form.cbAfterSendForm = ModuleRHVoice.cbAfterSendForm;
    Form.initialize();
  },

  /**
   * Update the module state on form label
   * @param status
   */
  changeStatus: function changeStatus(status) {
    switch (status) {
      case 'Connected':
        ModuleRHVoice.$moduleStatus.removeClass('grey').removeClass('red').addClass('green');
        ModuleRHVoice.$moduleStatus.html(globalTranslate.modulerh_voiceConnected);
        break;

      case 'Disconnected':
        ModuleRHVoice.$moduleStatus.removeClass('green').removeClass('red').addClass('grey');
        ModuleRHVoice.$moduleStatus.html(globalTranslate.modulerh_voiceDisconnected);
        break;

      case 'Updating':
        ModuleRHVoice.$moduleStatus.removeClass('green').removeClass('red').addClass('grey');
        ModuleRHVoice.$moduleStatus.html("<i class=\"spinner loading icon\"></i>".concat(globalTranslate.modulerh_voiceUpdateStatus));
        break;

      default:
        ModuleRHVoice.$moduleStatus.removeClass('green').removeClass('red').addClass('grey');
        ModuleRHVoice.$moduleStatus.html(globalTranslate.modulerh_voiceDisconnected);
        break;
    }
  }
};
$(document).ready(function () {
  ModuleRHVoice.initialize();
});
//# sourceMappingURL=modulerh-voice-index.js.map