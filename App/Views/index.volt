<form class="ui large grey segment form" id="modulerh-voice-form">
    {{ form.render('id') }}

    <div class="ten wide field">
        <label>{{ t._('modulerh_voice') }}</label>
        {{ form.render('voice') }}
        <input type="hidden" id="rhvoice-installed-voices" value="{{ installedVoices }}">
        <div style="margin-top: .6em;">
            <button class="ui small basic button" id="download-voice-button" type="button">
                <i class="cloud download icon"></i>
                <span id="download-voice-button-text">{{ t._('modulerh_downloadVoice') }}</span>
            </button>
            <span id="download-voice-status" style="margin-left: .5em;"></span>
        </div>
        <div class="ui small indicating progress" id="download-voice-progress" style="display:none; margin-top:.6em;">
            <div class="bar"><div class="progress"></div></div>
            <div class="label" id="download-voice-progress-label"></div>
        </div>
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
        <button class="ui right labeled icon button" id="play-button" type="button">
            <i class="play icon"></i>
            {{ t._('modulerh_play') }}
        </button>
        <button class="ui right labeled icon button" id="download-button" type="button">
            <i class="download icon"></i>
            {{ t._('modulerh_download') }}
        </button>
        <div style="margin-top: .8em;">
            <audio id="tts-audio" controls preload="none" style="display:none; width:100%;"></audio>
        </div>
    </div>
    {{ partial("partials/submitbutton",['indexurl':'pbx-extension-modules/index/']) }}
</form>
