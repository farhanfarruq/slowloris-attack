@extends('layouts.app')

@section('title', 'Profil')
@section('subtitle', 'Atur informasi akun, password, dan opsi penghapusan akun.')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="card lg:col-span-2">
        <div class="card-header"><p class="card-title">Informasi Profil</p></div>
        <div class="p-5">
            @include('profile.partials.update-profile-information-form')
        </div>
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header"><p class="card-title">Ubah Password</p></div>
        <div class="p-5">
            @include('profile.partials.update-password-form')
        </div>
    </div>

    <div class="card lg:col-span-2 border-rose-500/30">
        <div class="card-header"><p class="card-title text-rose-300">Hapus Akun</p></div>
        <div class="p-5">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</div>

@endsection
