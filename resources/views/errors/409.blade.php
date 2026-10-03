@extends('layouts.app')

@section('title', 'Konflik | Sistem Informasi UDD')

@section('content')
    <div class="container page-shell page-shell-narrow">
        <header class="page-header">
            <div class="page-header-main">
                <p class="eyebrow">HTTP 409</p>
                <h1 class="page-title">Permintaan tidak dapat diproses</h1>
            </div>
        </header>

        <p class="alert alert-error" role="alert">{{ trim($exception->getMessage()) !== '' ? $exception->getMessage() : 'Permintaan tidak dapat diproses karena konflik data.' }}</p>
    </div>
@endsection
