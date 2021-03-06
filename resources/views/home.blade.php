@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    You are logged in!
                </div>
            </div>
        </div>

        <div class="col-md-8 mt-2">
            <passport-clients></passport-clients>
        </div>

        <div class="col-md-8 mt-2">
            <passport-authorized-clients></passport-authorized-clients>
        </div>

        <div class="col-md-8 mt-2 mb-2">
            <passport-personal-access-tokens></passport-personal-access-tokens>
        </div>
        <example-component></example-component>

    </div>
</div>
@endsection





