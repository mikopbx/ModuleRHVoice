<form class="ui large grey segment form" id="modulerh-voice-form">
    <div class="ui ribbon label">
        <i class="phone icon"></i> 123456
    </div>
    <div class="ui grey top right attached label" id="status">{{ t._("modulerh_voiceDisconnected") }}</div>
    {{ form.render('id') }}

    <div class="four wide field disability">
        <label>{{ t._('modulerh_voiceLocalPort') }}</label>
        {{ form.render('local_port') }}
    </div>

    {{ partial("partials/submitbutton",['indexurl':'pbx-extension-modules/index/']) }}
</form>
