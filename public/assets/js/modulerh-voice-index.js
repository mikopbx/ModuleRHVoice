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
  initialize: function () {
    function initialize() {
      // инициализируем чекбоксы и выподающие менюшки
      ModuleRHVoice.$checkBoxes.checkbox();
      ModuleRHVoice.$dropDowns.dropdown();
      ModuleRHVoice.checkStatusToggle();
      window.addEventListener('ModuleStatusChanged', ModuleRHVoice.checkStatusToggle);
      ModuleRHVoice.initializeForm();
    }

    return initialize;
  }(),

  /**
   * Change some form elements classes depends of module status
   */
  checkStatusToggle: function () {
    function checkStatusToggle() {
      if (ModuleRHVoice.$statusToggle.checkbox('is checked')) {
        ModuleRHVoice.$disabilityFields.removeClass('disabled');
        ModuleRHVoice.$moduleStatus.show();
      } else {
        ModuleRHVoice.$disabilityFields.addClass('disabled');
        ModuleRHVoice.$moduleStatus.hide();
      }
    }

    return checkStatusToggle;
  }(),

  /**
   * Send command to restart module workers after data changes,
   * Also we can do it on TemplateConf->modelsEventChangeData method
   */
  applyConfigurationChanges: function () {
    function applyConfigurationChanges() {
      ModuleRHVoice.changeStatus('Updating');
      $.api({
        url: "".concat(Config.pbxUrl, "/pbxcore/api/modules/ModuleRHVoice/reload"),
        on: 'now',
        successTest: function () {
          function successTest(response) {
            // test whether a JSON response is valid
            return Object.keys(response).length > 0 && response.result === true;
          }

          return successTest;
        }(),
        onSuccess: function () {
          function onSuccess() {
            ModuleRHVoice.changeStatus('Connected');
          }

          return onSuccess;
        }(),
        onFailure: function () {
          function onFailure() {
            ModuleRHVoice.changeStatus('Disconnected');
          }

          return onFailure;
        }()
      });
    }

    return applyConfigurationChanges;
  }(),

  /**
   * We can modify some data before form send
   * @param settings
   * @returns {*}
   */
  cbBeforeSendForm: function () {
    function cbBeforeSendForm(settings) {
      var result = settings;
      result.data = ModuleRHVoice.$formObj.form('get values');
      return result;
    }

    return cbBeforeSendForm;
  }(),

  /**
   * Some actions after forms send
   */
  cbAfterSendForm: function () {
    function cbAfterSendForm() {
      ModuleRHVoice.applyConfigurationChanges();
    }

    return cbAfterSendForm;
  }(),

  /**
   * Initialize form parameters
   */
  initializeForm: function () {
    function initializeForm() {
      Form.$formObj = ModuleRHVoice.$formObj;
      Form.url = "".concat(globalRootUrl, "module-r-h-voice/save");
      Form.validateRules = ModuleRHVoice.validateRules;
      Form.cbBeforeSendForm = ModuleRHVoice.cbBeforeSendForm;
      Form.cbAfterSendForm = ModuleRHVoice.cbAfterSendForm;
      Form.initialize();
    }

    return initializeForm;
  }(),

  /**
   * Update the module state on form label
   * @param status
   */
  changeStatus: function () {
    function changeStatus(status) {
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

    return changeStatus;
  }()
};
$(document).ready(function () {
  ModuleRHVoice.initialize();
});
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInNyYy9tb2R1bGVyaC12b2ljZS1pbmRleC5qcyJdLCJuYW1lcyI6WyJNb2R1bGVSSFZvaWNlIiwiJGZvcm1PYmoiLCIkIiwiJGNoZWNrQm94ZXMiLCIkZHJvcERvd25zIiwiJGRpc2FiaWxpdHlGaWVsZHMiLCIkc3RhdHVzVG9nZ2xlIiwiJG1vZHVsZVN0YXR1cyIsInZhbGlkYXRlUnVsZXMiLCJ0ZXh0RmllbGQiLCJpZGVudGlmaWVyIiwicnVsZXMiLCJ0eXBlIiwicHJvbXB0IiwiZ2xvYmFsVHJhbnNsYXRlIiwibW9kX3RwbFZhbGlkYXRlVmFsdWVJc0VtcHR5IiwiYXJlYUZpZWxkIiwicGFzc3dvcmRGaWVsZCIsImluaXRpYWxpemUiLCJjaGVja2JveCIsImRyb3Bkb3duIiwiY2hlY2tTdGF0dXNUb2dnbGUiLCJ3aW5kb3ciLCJhZGRFdmVudExpc3RlbmVyIiwiaW5pdGlhbGl6ZUZvcm0iLCJyZW1vdmVDbGFzcyIsInNob3ciLCJhZGRDbGFzcyIsImhpZGUiLCJhcHBseUNvbmZpZ3VyYXRpb25DaGFuZ2VzIiwiY2hhbmdlU3RhdHVzIiwiYXBpIiwidXJsIiwiQ29uZmlnIiwicGJ4VXJsIiwib24iLCJzdWNjZXNzVGVzdCIsInJlc3BvbnNlIiwiT2JqZWN0Iiwia2V5cyIsImxlbmd0aCIsInJlc3VsdCIsIm9uU3VjY2VzcyIsIm9uRmFpbHVyZSIsImNiQmVmb3JlU2VuZEZvcm0iLCJzZXR0aW5ncyIsImRhdGEiLCJmb3JtIiwiY2JBZnRlclNlbmRGb3JtIiwiRm9ybSIsImdsb2JhbFJvb3RVcmwiLCJzdGF0dXMiLCJodG1sIiwibW9kdWxlcmhfdm9pY2VDb25uZWN0ZWQiLCJtb2R1bGVyaF92b2ljZURpc2Nvbm5lY3RlZCIsIm1vZHVsZXJoX3ZvaWNlVXBkYXRlU3RhdHVzIiwiZG9jdW1lbnQiLCJyZWFkeSJdLCJtYXBwaW5ncyI6Ijs7QUFBQTs7Ozs7Ozs7QUFRQTtBQUVBLElBQU1BLGFBQWEsR0FBRztBQUNyQkMsRUFBQUEsUUFBUSxFQUFFQyxDQUFD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LFdBREYsQ0FDYyxNQURkLEVBRUVBLFdBRkYsQ0FFYyxLQUZkLEVBR0VFLFFBSEYsQ0FHVyxPQUhYO0FBSUEzQixVQUFBQSxhQUFhLENBQUNPLGFBQWQsQ0FBNEI2QyxJQUE1QixDQUFpQ3RDLGVBQWUsQ0FBQ3VDLHVCQUFqRDtBQUNBOztBQUNELGFBQUssY0FBTDtBQUNDckQsVUFBQUEsYUFBYSxDQUFDTyxhQUFkLENBQ0VrQixXQURGLENBQ2MsT0FEZCxFQUVFQSxXQUZGLENBRWMsS0FGZCxFQUdFRSxRQUhGLENBR1csTUFIWDtBQUlBM0IsVUFBQUEsYUFBYSxDQUFDTyxhQUFkLENBQTRCNkMsSUFBNUIsQ0FBaUN0QyxlQUFlLENBQUN3QywwQkFBakQ7QUFDQTs7QUFDRCxhQUFLLFVBQUw7QUFDQ3RELFVBQUFBLGFBQWEsQ0FBQ08sYUFBZCxDQUNFa0IsV0FERixDQUNjLE9BRGQsRUFFRUEsV0FGRixDQUVjLEtBRmQsRUFHRUUsUUFIRixDQUdXLE1BSFg7QUFJQTNCLFVBQUFBLGFBQWEsQ0FBQ08sYUFBZCxDQUE0QjZDLElBQTVCLGlEQUF3RXRDLGVBQWUsQ0FBQ3lDLDBCQUF4RjtBQUNBOztBQUNEO0FBQ0N2RCxVQUFBQSxhQUFhLENBQUNPLGFBQWQsQ0FDRWtCLFdBREYsQ0FDYyxPQURkLEVBRUVBLFdBRkYsQ0FFYyxLQUZkLEVBR0VFLFFBSEYsQ0FHVyxNQUhYO0FBSUEzQixVQUFBQSxhQUFhLENBQUNPLGFBQWQsQ0FBNEI2QyxJQUE1QixDQUFpQ3RDLGVBQWUsQ0FBQ3dDLDBCQUFqRDtBQUNBO0FBNUJGO0FBOEJBOztBQWxKb0I7QUFBQTtBQUFBLENBQXRCO0FBcUpBcEQsQ0FBQyxDQUFDc0QsUUFBRCxDQUFELENBQVlDLEtBQVosQ0FBa0IsWUFBTTtBQUN2QnpELEVBQUFBLGFBQWEsQ0FBQ2tCLFVBQWQ7QUFDQSxDQUZEIiwic291cmNlc0NvbnRlbnQiOlsiLypcbiAqIENvcHlyaWdodCAoQykgTUlLTyBMTEMgLSBBbGwgUmlnaHRzIFJlc2VydmVkXG4gKiBVbmF1dGhvcml6ZWQgY29weWluZyBvZiB0aGlzIGZpbGUsIHZpYSBhbnkgbWVkaXVtIGlzIHN0cmljdGx5IHByb2hpYml0ZWRcbiAqIFByb3ByaWV0YXJ5IGFuZCBjb25maWRlbnRpYWxcbiAqIFdyaXR0ZW4gYnkgTmlrb2xheSBCZWtldG92LCAxMSAyMDE4XG4gKlxuICovXG5cbi8qIGdsb2JhbCBnbG9iYWxSb290VXJsLCBnbG9iYWxUcmFuc2xhdGUsIEZvcm0sIENvbmZpZyAqL1xuXG5jb25zdCBNb2R1bGVSSFZvaWNlID0ge1xuXHQkZm9ybU9iajogJCgnI21vZHVsZXJoLXZvaWNlLWZvcm0nKSxcblx0JGNoZWNrQm94ZXM6ICQoJyNtb2R1bGVyaC12b2ljZS1mb3JtIC51aS5jaGVja2JveCcpLFxuXHQkZHJvcERvd25zOiAkKCcjbW9kdWxlcmgtdm9pY2UtZm9ybSAudWkuZHJvcGRvd24nKSxcblx0JGRpc2FiaWxpdHlGaWVsZHM6ICQoJyNtb2R1bGVyaC12b2ljZS1mb3JtICAuZGlzYWJpbGl0eScpLFxuXHQkc3RhdHVzVG9nZ2xlOiAkKCcjbW9kdWxlLXN0YXR1cy10b2dnbGUnKSxcblx0JG1vZHVsZVN0YXR1czogJCgnI3N0YXR1cycpLFxuXHQvKipcblx0ICogRmllbGQgdmFsaWRhdGlvbiBydWxlc1xuXHQgKiBodHRwczovL3NlbWFudGljLXVpLmNvbS9iZWhhdmlvcnMvZm9ybS5odG1sXG5cdCAqL1xuXHR2YWxpZGF0ZVJ1bGVzOiB7XG5cdFx0dGV4dEZpZWxkOiB7XG5cdFx0XHRpZGVudGlmaWVyOiAndGV4dF9maWVsZCcsXG5cdFx0XHRydWxlczogW1xuXHRcdFx0XHR7XG5cdFx0XHRcdFx0dHlwZTogJ2VtcHR5Jyxcblx0XHRcdFx0XHRwcm9tcHQ6IGdsb2JhbFRyYW5zbGF0ZS5tb2RfdHBsVmFsaWRhdGVWYWx1ZUlzRW1wdHksXG5cdFx0XHRcdH0sXG5cdFx0XHRdLFxuXHRcdH0sXG5cdFx0YXJlYUZpZWxkOiB7XG5cdFx0XHRpZGVudGlmaWVyOiAndGV4dF9hcmVhX2ZpZWxkJyxcblx0XHRcdHJ1bGVzOiBbXG5cdFx0XHRcdHtcblx0XHRcdFx0XHR0eXBlOiAnZW1wdHknLFxuXHRcdFx0XHRcdHByb21wdDogZ2xvYmFsVHJhbnNsYXRlLm1vZF90cGxWYWxpZGF0ZVZhbHVlSXNFbXB0eSxcblx0XHRcdFx0fSxcblx0XHRcdF0sXG5cdFx0fSxcblx0XHRwYXNzd29yZEZpZWxkOiB7XG5cdFx0XHRpZGVudGlmaWVyOiAncGFzc3dvcmRfZmllbGQnLFxuXHRcdFx0cnVsZXM6IFtcblx0XHRcdFx0e1xuXHRcdFx0XHRcdHR5cGU6ICdlbXB0eScsXG5cdFx0XHRcdFx0cHJvbXB0OiBnbG9iYWxUcmFuc2xhdGUubW9kX3RwbFZhbGlkYXRlVmFsdWVJc0VtcHR5LFxuXHRcdFx0XHR9LFxuXHRcdFx0XSxcblx0XHR9LFxuXHR9LFxuXHQvKipcblx0ICogT24gcGFnZSBsb2FkIHdlIGluaXQgc29tZSBTZW1hbnRpYyBVSSBsaWJyYXJ5XG5cdCAqL1xuXHRpbml0aWFsaXplKCkge1xuXHRcdC8vINC40L3QuNGG0LjQsNC70LjQt9C40YDRg9C10Lwg0YfQtdC60LHQvtC60YHRiyDQuCDQstGL0L/QvtC00LDRjtGJ0LjQtSDQvNC10L3RjtGI0LrQuFxuXHRcdE1vZHVsZVJIVm9pY2UuJGNoZWNrQm94ZXMuY2hlY2tib3goKTtcblx0XHRNb2R1bGVSSFZvaWNlLiRkcm9wRG93bnMuZHJvcGRvd24oKTtcblx0XHRNb2R1bGVSSFZvaWNlLmNoZWNrU3RhdHVzVG9nZ2xlKCk7XG5cdFx0d2luZG93LmFkZEV2ZW50TGlzdGVuZXIoJ01vZHVsZVN0YXR1c0NoYW5nZWQnLCBNb2R1bGVSSFZvaWNlLmNoZWNrU3RhdHVzVG9nZ2xlKTtcblx0XHRNb2R1bGVSSFZvaWNlLmluaXRpYWxpemVGb3JtKCk7XG5cdH0sXG5cdC8qKlxuXHQgKiBDaGFuZ2Ugc29tZSBmb3JtIGVsZW1lbnRzIGNsYXNzZXMgZGVwZW5kcyBvZiBtb2R1bGUgc3RhdHVzXG5cdCAqL1xuXHRjaGVja1N0YXR1c1RvZ2dsZSgpIHtcblx0XHRpZiAoTW9kdWxlUkhWb2ljZS4kc3RhdHVzVG9nZ2xlLmNoZWNrYm94KCdpcyBjaGVja2VkJykpIHtcblx0XHRcdE1vZHVsZVJIVm9pY2UuJGRpc2FiaWxpdHlGaWVsZHMucmVtb3ZlQ2xhc3MoJ2Rpc2FibGVkJyk7XG5cdFx0XHRNb2R1bGVSSFZvaWNlLiRtb2R1bGVTdGF0dXMuc2hvdygpO1xuXHRcdH0gZWxzZSB7XG5cdFx0XHRNb2R1bGVSSFZvaWNlLiRkaXNhYmlsaXR5RmllbGRzLmFkZENsYXNzKCdkaXNhYmxlZCcpO1xuXHRcdFx0TW9kdWxlUkhWb2ljZS4kbW9kdWxlU3RhdHVzLmhpZGUoKTtcblx0XHR9XG5cdH0sXG5cdC8qKlxuXHQgKiBTZW5kIGNvbW1hbmQgdG8gcmVzdGFydCBtb2R1bGUgd29ya2VycyBhZnRlciBkYXRhIGNoYW5nZXMsXG5cdCAqIEFsc28gd2UgY2FuIGRvIGl0IG9uIFRlbXBsYXRlQ29uZi0+bW9kZWxzRXZlbnRDaGFuZ2VEYXRhIG1ldGhvZFxuXHQgKi9cblx0YXBwbHlDb25maWd1cmF0aW9uQ2hhbmdlcygpIHtcblx0XHRNb2R1bGVSSFZvaWNlLmNoYW5nZVN0YXR1cygnVXBkYXRpbmcnKTtcblx0XHQkLmFwaSh7XG5cdFx0XHR1cmw6IGAke0NvbmZpZy5wYnhVcmx9L3BieGNvcmUvYXBpL21vZHVsZXMvTW9kdWxlUkhWb2ljZS9yZWxvYWRgLFxuXHRcdFx0b246ICdub3cnLFxuXHRcdFx0c3VjY2Vzc1Rlc3QocmVzcG9uc2UpIHtcblx0XHRcdFx0Ly8gdGVzdCB3aGV0aGVyIGEgSlNPTiByZXNwb25zZSBpcyB2YWxpZFxuXHRcdFx0XHRyZXR1cm4gT2JqZWN0LmtleXMocmVzcG9uc2UpLmxlbmd0aCA+IDAgJiYgcmVzcG9uc2UucmVzdWx0ID09PSB0cnVlO1xuXHRcdFx0fSxcblx0XHRcdG9uU3VjY2VzcygpIHtcblx0XHRcdFx0TW9kdWxlUkhWb2ljZS5jaGFuZ2VTdGF0dXMoJ0Nvbm5lY3RlZCcpO1xuXHRcdFx0fSxcblx0XHRcdG9uRmFpbHVyZSgpIHtcblx0XHRcdFx0TW9kdWxlUkhWb2ljZS5jaGFuZ2VTdGF0dXMoJ0Rpc2Nvbm5lY3RlZCcpO1xuXHRcdFx0fSxcblx0XHR9KTtcblx0fSxcblx0LyoqXG5cdCAqIFdlIGNhbiBtb2RpZnkgc29tZSBkYXRhIGJlZm9yZSBmb3JtIHNlbmRcblx0ICogQHBhcmFtIHNldHRpbmdzXG5cdCAqIEByZXR1cm5zIHsqfVxuXHQgKi9cblx0Y2JCZWZvcmVTZW5kRm9ybShzZXR0aW5ncykge1xuXHRcdGNvbnN0IHJlc3VsdCA9IHNldHRpbmdzO1xuXHRcdHJlc3VsdC5kYXRhID0gTW9kdWxlUkhWb2ljZS4kZm9ybU9iai5mb3JtKCdnZXQgdmFsdWVzJyk7XG5cdFx0cmV0dXJuIHJlc3VsdDtcblx0fSxcblx0LyoqXG5cdCAqIFNvbWUgYWN0aW9ucyBhZnRlciBmb3JtcyBzZW5kXG5cdCAqL1xuXHRjYkFmdGVyU2VuZEZvcm0oKSB7XG5cdFx0TW9kdWxlUkhWb2ljZS5hcHBseUNvbmZpZ3VyYXRpb25DaGFuZ2VzKCk7XG5cdH0sXG5cdC8qKlxuXHQgKiBJbml0aWFsaXplIGZvcm0gcGFyYW1ldGVyc1xuXHQgKi9cblx0aW5pdGlhbGl6ZUZvcm0oKSB7XG5cdFx0Rm9ybS4kZm9ybU9iaiA9IE1vZHVsZVJIVm9pY2UuJGZvcm1PYmo7XG5cdFx0Rm9ybS51cmwgPSBgJHtnbG9iYWxSb290VXJsfW1vZHVsZS1yLWgtdm9pY2Uvc2F2ZWA7XG5cdFx0Rm9ybS52YWxpZGF0ZVJ1bGVzID0gTW9kdWxlUkhWb2ljZS52YWxpZGF0ZVJ1bGVzO1xuXHRcdEZvcm0uY2JCZWZvcmVTZW5kRm9ybSA9IE1vZHVsZVJIVm9pY2UuY2JCZWZvcmVTZW5kRm9ybTtcblx0XHRGb3JtLmNiQWZ0ZXJTZW5kRm9ybSA9IE1vZHVsZVJIVm9pY2UuY2JBZnRlclNlbmRGb3JtO1xuXHRcdEZvcm0uaW5pdGlhbGl6ZSgpO1xuXHR9LFxuXHQvKipcblx0ICogVXBkYXRlIHRoZSBtb2R1bGUgc3RhdGUgb24gZm9ybSBsYWJlbFxuXHQgKiBAcGFyYW0gc3RhdHVzXG5cdCAqL1xuXHRjaGFuZ2VTdGF0dXMoc3RhdHVzKSB7XG5cdFx0c3dpdGNoIChzdGF0dXMpIHtcblx0XHRcdGNhc2UgJ0Nvbm5lY3RlZCc6XG5cdFx0XHRcdE1vZHVsZVJIVm9pY2UuJG1vZHVsZVN0YXR1c1xuXHRcdFx0XHRcdC5yZW1vdmVDbGFzcygnZ3JleScpXG5cdFx0XHRcdFx0LnJlbW92ZUNsYXNzKCdyZWQnKVxuXHRcdFx0XHRcdC5hZGRDbGFzcygnZ3JlZW4nKTtcblx0XHRcdFx0TW9kdWxlUkhWb2ljZS4kbW9kdWxlU3RhdHVzLmh0bWwoZ2xvYmFsVHJhbnNsYXRlLm1vZHVsZXJoX3ZvaWNlQ29ubmVjdGVkKTtcblx0XHRcdFx0YnJlYWs7XG5cdFx0XHRjYXNlICdEaXNjb25uZWN0ZWQnOlxuXHRcdFx0XHRNb2R1bGVSSFZvaWNlLiRtb2R1bGVTdGF0dXNcblx0XHRcdFx0XHQucmVtb3ZlQ2xhc3MoJ2dyZWVuJylcblx0XHRcdFx0XHQucmVtb3ZlQ2xhc3MoJ3JlZCcpXG5cdFx0XHRcdFx0LmFkZENsYXNzKCdncmV5Jyk7XG5cdFx0XHRcdE1vZHVsZVJIVm9pY2UuJG1vZHVsZVN0YXR1cy5odG1sKGdsb2JhbFRyYW5zbGF0ZS5tb2R1bGVyaF92b2ljZURpc2Nvbm5lY3RlZCk7XG5cdFx0XHRcdGJyZWFrO1xuXHRcdFx0Y2FzZSAnVXBkYXRpbmcnOlxuXHRcdFx0XHRNb2R1bGVSSFZvaWNlLiRtb2R1bGVTdGF0dXNcblx0XHRcdFx0XHQucmVtb3ZlQ2xhc3MoJ2dyZWVuJylcblx0XHRcdFx0XHQucmVtb3ZlQ2xhc3MoJ3JlZCcpXG5cdFx0XHRcdFx0LmFkZENsYXNzKCdncmV5Jyk7XG5cdFx0XHRcdE1vZHVsZVJIVm9pY2UuJG1vZHVsZVN0YXR1cy5odG1sKGA8aSBjbGFzcz1cInNwaW5uZXIgbG9hZGluZyBpY29uXCI+PC9pPiR7Z2xvYmFsVHJhbnNsYXRlLm1vZHVsZXJoX3ZvaWNlVXBkYXRlU3RhdHVzfWApO1xuXHRcdFx0XHRicmVhaztcblx0XHRcdGRlZmF1bHQ6XG5cdFx0XHRcdE1vZHVsZVJIVm9pY2UuJG1vZHVsZVN0YXR1c1xuXHRcdFx0XHRcdC5yZW1vdmVDbGFzcygnZ3JlZW4nKVxuXHRcdFx0XHRcdC5yZW1vdmVDbGFzcygncmVkJylcblx0XHRcdFx0XHQuYWRkQ2xhc3MoJ2dyZXknKTtcblx0XHRcdFx0TW9kdWxlUkhWb2ljZS4kbW9kdWxlU3RhdHVzLmh0bWwoZ2xvYmFsVHJhbnNsYXRlLm1vZHVsZXJoX3ZvaWNlRGlzY29ubmVjdGVkKTtcblx0XHRcdFx0YnJlYWs7XG5cdFx0fVxuXHR9LFxufTtcblxuJChkb2N1bWVudCkucmVhZHkoKCkgPT4ge1xuXHRNb2R1bGVSSFZvaWNlLmluaXRpYWxpemUoKTtcbn0pO1xuXG4iXX0=