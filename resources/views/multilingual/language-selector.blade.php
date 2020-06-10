@if (isset($isModelTranslatable) && $isModelTranslatable)
    <div class="language-selector">
        <div class="btn-group btn-group-sm" role="group" data-toggle="buttons">
            @foreach(config('sitec.facilitador.multilingual.locales') as $lang)
                <label class="btn btn-primary{{ ($lang === config('sitec.facilitador.multilingual.default')) ? " active" : "" }}">
                    <input type="radio" name="i18n_selector" id="{{$lang}}" autocomplete="off"{{ ($lang === config('sitec.facilitador.multilingual.default')) ? ' checked="checked"' : '' }}> {{ strtoupper($lang) }}
                </label>
            @endforeach
        </div>
    </div>
@endif
