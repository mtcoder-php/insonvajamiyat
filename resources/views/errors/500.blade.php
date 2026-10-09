@extends('errors.layout')

@section('code', '500')
@section('title', 'Serverda kutilmagan xatolik')
@section('message', "Xatolik haqida ma'lumot tizim administratoriga yetkazildi. Birozdan keyin qayta urinib ko'ring.")
@section('note', 'Внутренняя ошибка сервера. · Internal server error.')
@section('action')
    <a class="btn" href="/">Bosh sahifaga qaytish</a>
@endsection
