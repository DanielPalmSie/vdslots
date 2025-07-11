@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')

    <div class="card card-primary border border-primary">
        <div class="card-header">
            <h3 class="card-title">Edit user data</h3>
        </div>
        <div class="card-body">
            <div class="row">
                @if(p('change.contact.info'))
                    <div class="col-12 col-sm-6 col-md-4">
                        @include('admin.user.partials.form.basic')
                    </div>
                @endif
                @if(p('change.contact.info') || p('user.login.countries') || p('user.edit.privacy.settings'))
                    <div class="col-12 col-sm-6 col-md-4">
                        @if(p('change.contact.info'))
                            @include('admin.user.partials.form.settings')
                        @endif
                        @if(p('user.edit.privacy.settings'))
                            @include('admin.user.partials.form.privacy-settings')
                        @endif
                        @if(p('user.login.countries'))
                            @include('admin.user.partials.form.login-countries')
                        @endif
                    </div>
                @endif
                @if(p('user.inout.defaults') || p('edit.forums') || p('user.disable.deposit.methods'))
                    <div class="col-12 col-sm-6 col-md-4">
                        @if(p('user.disable.deposit.methods'))
                            @include('admin.user.partials.form.manage-deposits-methods')
                        @endif
                        @if(p('user.inout.defaults'))
                            @include('admin.user.partials.form.payment-information')
                        @endif
                        @if(p('edit.forums'))
                            @include('admin.user.partials.form.popular-forums')
                        @endif
                         @if( licSetting('show_document_information_in_admin', $user->id))
                            @include('admin.user.partials.form.document-information')
                         @endif
                    </div>
                @endif
                @if(p('user.casino.settings'))
                    <div class="col-12 col-sm-6 col-md-4">
                        @include('admin.user.partials.form.other-settings')
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
