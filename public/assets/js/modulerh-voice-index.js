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
  installedVoices: [],

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

    // Докачка голосов по запросу.
    ModuleRHVoice.installedVoices = ModuleRHVoice.readInstalledVoices();
    $('#modulerh-voice-form .library-type-select').dropdown({
      onChange: function onChange() {
        ModuleRHVoice.updateVoiceState();
      }
    });
    ModuleRHVoice.updateVoiceState();
    ModuleRHVoice.decorateDropdown();
    $('#download-voice-button').on('click', ModuleRHVoice.downloadSelectedVoice);

    // Прослушать сгенерированный файл прямо на странице.
    $('#play-button').on('click', function () {
      var url = ModuleRHVoice.buildSayUrl();
      if (!url) {
        return;
      }
      var audio = document.getElementById('tts-audio');
      $('#tts-audio').show();
      audio.src = url;
      var p = audio.play();
      if (p && typeof p.catch === 'function') {
        p.catch(function () {});
      }
    });

    // Скачать сгенерированный файл.
    $('#download-button').on('click', function () {
      var url = ModuleRHVoice.buildSayUrl();
      if (url) {
        window.open("".concat(url, "&dl=1"), '_blank');
      }
    });
  },

  /**
   * Builds the /say URL for the entered text and selected voice, or '' if the text is empty.
   */
  buildSayUrl: function buildSayUrl() {
    var text = ModuleRHVoice.$formObj.form('get value', 'text');
    if (!text || text.trim() === '') {
      return '';
    }
    var voice = ModuleRHVoice.$formObj.form('get value', 'voice');
    var queryParams = new URLSearchParams({
      text: text,
      voice: voice
    }).toString();
    return "/pbxcore/api/rhvoice/say?".concat(queryParams);
  },

  /**
   * Reads the list of already installed voices passed from the controller.
   */
  readInstalledVoices: function readInstalledVoices() {
    try {
      return JSON.parse($('#rhvoice-installed-voices').val() || '[]');
    } catch (e) {
      return [];
    }
  },

  /**
   * Marks installed voices with a check icon directly in the dropdown items.
   */
  decorateDropdown: function decorateDropdown() {
    $('#modulerh-voice-form .library-type-select .menu .item').each(function () {
      var $item = $(this);
      var value = $item.attr('data-value');
      var installed = ModuleRHVoice.installedVoices.indexOf(value) !== -1;
      var hasMark = $item.find('i.rhv-installed-mark').length > 0;
      if (installed && !hasMark) {
        $item.prepend('<i class="check green icon rhv-installed-mark"></i>');
      } else if (!installed && hasMark) {
        $item.find('i.rhv-installed-mark').remove();
      }
    });
  },

  /**
   * Updates the download button / status depending on whether the selected voice is installed.
   */
  updateVoiceState: function updateVoiceState() {
    var voice = ModuleRHVoice.$formObj.form('get value', 'voice');
    var $btn = $('#download-voice-button');
    var $status = $('#download-voice-status');

    if (!voice) {
      $btn.addClass('disabled');
      $status.html('');
      return;
    }

    if (ModuleRHVoice.installedVoices.indexOf(voice) !== -1) {
      $btn.addClass('disabled').removeClass('loading');
      $status.html("<i class=\"check green icon\"></i>".concat(globalTranslate.modulerh_voiceInstalled));
    } else {
      $btn.removeClass('disabled loading');
      $status.html("<span style=\"color:#767676;\">".concat(globalTranslate.modulerh_voiceNotInstalled, "</span>"));
    }
  },

  /**
   * Human-readable label for a download state.
   */
  stateLabel: function stateLabel(state) {
    switch (state) {
      case 'queued':
      case 'downloading':
        return globalTranslate.modulerh_voiceDownloading;
      case 'extracting':
        return globalTranslate.modulerh_voiceExtracting;
      case 'installing':
        return globalTranslate.modulerh_voiceInstalling;
      default:
        return '';
    }
  },

  /**
   * Updates the progress bar state.
   */
  setProgress: function setProgress(state, percent, detail) {
    var label = ModuleRHVoice.stateLabel(state);
    if (detail) {
      label += " — ".concat(detail);
    }
    // Ставим ширину бара напрямую, без зависимости от Semantic UI progress-компонента.
    $('#download-voice-progress .bar').css('width', "".concat(percent, "%"));
    $('#download-voice-progress-label').text(label);
  },

  /**
   * Reflects a failed download in the UI.
   */
  downloadFailed: function downloadFailed() {
    $('#download-voice-button').removeClass('loading disabled');
    $('#download-voice-progress').hide();
    $('#download-voice-status').html("<i class=\"times red icon\"></i>".concat(globalTranslate.modulerh_voiceDownloadFailed));
  },

  /**
   * Starts an asynchronous voice download and begins polling its progress.
   */
  downloadSelectedVoice: function downloadSelectedVoice() {
    var voice = ModuleRHVoice.$formObj.form('get value', 'voice');
    if (!voice || ModuleRHVoice.installedVoices.indexOf(voice) !== -1) {
      return;
    }
    $('#download-voice-button').addClass('loading disabled');
    $('#download-voice-status').html('');
    $('#download-voice-progress').show();
    ModuleRHVoice.setProgress('queued', 1, '');

    $.api({
      url: '/pbxcore/api/modules/ModuleRHVoice/download-voice',
      on: 'now',
      method: 'GET',
      data: {
        voice: voice
      },
      successTest: function successTest(response) {
        return Object.keys(response).length > 0 && response.result === true;
      },
      onSuccess: function onSuccess() {
        ModuleRHVoice.pollProgress(voice);
      },
      onFailure: function onFailure() {
        ModuleRHVoice.downloadFailed();
      },
      onError: function onError() {
        ModuleRHVoice.downloadFailed();
      }
    });
  },

  /**
   * Polls the download progress until the voice is installed or an error occurs.
   */
  pollProgress: function pollProgress(voice) {
    $.api({
      url: '/pbxcore/api/modules/ModuleRHVoice/voice-progress',
      on: 'now',
      method: 'GET',
      data: {
        voice: voice
      },
      successTest: function successTest(response) {
        return Object.keys(response).length > 0 && response.result === true;
      },
      onSuccess: function onSuccess(response) {
        var st = response.data || {};
        var state = st.state || 'idle';
        var percent = st.percent || 0;
        if (state === 'done') {
          if (ModuleRHVoice.installedVoices.indexOf(voice) === -1) {
            ModuleRHVoice.installedVoices.push(voice);
          }
          ModuleRHVoice.setProgress('installing', 100, '');
          $('#download-voice-button').removeClass('loading');
          ModuleRHVoice.decorateDropdown();
          ModuleRHVoice.updateVoiceState();
          $('#download-voice-status').html("<i class=\"check green icon\"></i>".concat(globalTranslate.modulerh_voiceDownloadOk));
          setTimeout(function () {
            $('#download-voice-progress').hide();
          }, 1500);
          return;
        }
        if (state === 'error') {
          ModuleRHVoice.downloadFailed();
          return;
        }
        ModuleRHVoice.setProgress(state, percent, st.message || '');
        setTimeout(function () {
          ModuleRHVoice.pollProgress(voice);
        }, 1000);
      },
      onFailure: function onFailure() {
        ModuleRHVoice.downloadFailed();
      },
      onError: function onError() {
        ModuleRHVoice.downloadFailed();
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