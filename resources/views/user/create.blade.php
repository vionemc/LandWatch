<!--suppress CheckEmptyScriptTag, HtmlUnknownTag -->
<x-app-layout>
    <x-slot name="header">{{ __('Create user') }}</x-slot>
    <div class="row">
        <div class="col-sm-6">
            <x-forms.validated-form method="post" action="{{ route('users.store') }}">
                <div class="row mb-3">
                    <x-forms.label class="col-sm-3" :for-column="true" for="name">{{ __('Name') }}</x-forms.label>
                    <div class="col-sm-9">
                        <x-forms.input type="text" name="name" id="name" :value="old('name')" required autofocus />
                    </div>
                </div>
                <div class="row mb-3">
                    <x-forms.label class="col-sm-3" :for-column="true" for="email">{{ __('Email') }}</x-forms.label>
                    <div class="col-sm-9">
                        <x-forms.input type="email" name="email" id="email" :value="old('email')" required />
                    </div>
                </div>
                <div class="row mb-3">
                    <x-forms.label class="col-sm-3" :for-column="true" for="password">{{ __('Password') }}</x-forms.label>
                    <div class="col-sm-9">
                        <x-forms.input type="password" name="password" id="password" required />
                    </div>
                </div>
                <div class="row mb-3">
                    <x-forms.label class="col-sm-3" :for-column="true" for="password_confirmation">{{ __('Repeat password') }}</x-forms.label>
                    <div class="col-sm-9">
                        <x-forms.input type="password" name="password_confirmation" id="password_confirmation" required />
                    </div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary mb-3">{{ __('Save') }}</button>
                </div>
            </x-forms.validated-form>
        </div>
    </div>
</x-app-layout>
