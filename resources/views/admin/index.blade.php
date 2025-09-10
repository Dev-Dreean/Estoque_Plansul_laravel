@extends('layouts.app')

@section('content')
<div class="card">
  <div class="card-header">Admin</div>
  <div class="card-body">
    <p>Bem-vindo ao painel — usuário: {{ Auth::user()->NMLOGIN ?? Auth::user()->name }}</p>
  </div>
</div>
@endsectio