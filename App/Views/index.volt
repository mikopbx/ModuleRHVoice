<form class="ui large grey segment form" id="modulerh-voice-form">
    {{ form.render('id') }}

    <div class="four wide field disability">
        <label>{{ t._('modulerh_voiceLocalPort') }}</label>
        {{ form.render('local_port') }}
    </div>
    <div class="ten wide field">
        <label>{{ t._('modulerh_voice') }}</label>
        {{ form.render('voice') }}
    </div>
    <div class="ten wide field">
        <label>{{ t._('modulerh_rate') }}</label>
        {{ form.render('rate') }}
    </div>

    <div class="ten wide field">
        <label>{{ t._('modulerh_text') }}</label>
        {{ form.render('text') }}
        <br>
        <br>
        <button class="ui right labeled icon button" id="download-button">
            <i class="download icon"></i>
            {{ t._('modulerh_download') }}
        </button>
    </div>
    {{ partial("partials/submitbutton",['indexurl':'pbx-extension-modules/index/']) }}
</form>
