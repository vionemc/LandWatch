<!--suppress CheckEmptyScriptTag, HtmlUnknownTag -->
<x-guest-layout>
    <main class="form-login">
        <x-forms.validated-form method="post" action="{{ route('login') }}">
            <div class="mb-3">
                <x-forms.input type="email" id="email" name="email" :value="old('email')" placeholder="{{ __('Email address') }}" required autofocus />
            </div>
            <div class="mb-3">
                <x-forms.input type="password" id="password" name="password" placeholder="{{ __('Password') }}" required autocomplete="current-password" />
            </div>
            <div class="mb-3 row">
                <div class="col">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">
                            {{ __('Remember me') }}
                        </label>
                    </div>
                </div>
                <div class="col text-end">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Login') }}
                    </button>
                </div>
            </div>
        </x-forms.validated-form>
    </main>
</x-guest-layout>
