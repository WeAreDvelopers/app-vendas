@extends('layouts.panel')
@section('title','Filas e Jobs')
@section('page-title','Filas / Monitor')
@section('page-subtitle','Jobs pendentes e falhas (Database Queue)')

@section('content')
<div class="row g-3">
  <div class="col-lg-6">
    <div class="notion-card">
      <div class="fw-semibold mb-2">Jobs pendentes</div>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th>ID</th><th>Fila</th><th>Tent.</th><th>Disponível</th><th>Criado</th></tr></thead>
          <tbody>
          @foreach($jobs as $j)
            <tr>
              <td>{{ $j->id }}</td>
              <td>{{ $j->queue }}</td>
              <td>{{ $j->attempts }}</td>
              <td>{{ \Carbon\Carbon::createFromTimestamp($j->available_at) }}</td>
              <td>{{ $j->created_at }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="notion-card">
      <div class="fw-semibold mb-2">Jobs com falha</div>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th>ID</th><th>Fila</th><th>Quando</th><th>Erro</th></tr></thead>
          <tbody>
          @foreach($failed as $f)
            <tr>
              <td>{{ $f->id }}</td>
              <td>{{ $f->queue }}</td>
              <td>{{ $f->failed_at }}</td>
              <td style="max-width:550px;white-space:pre-wrap">{{ \Illuminate\Support\Str::limit($f->exception, 700) }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
