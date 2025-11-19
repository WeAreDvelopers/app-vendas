@extends('layouts.panel')
@section('title','Novo Fornecedor')
@section('page-title','Novo Fornecedor')
@section('page-subtitle','Cadastre um novo fornecedor no sistema')

@section('content')
<div class="row">
  <div class="col-lg-6">
    <div class="notion-card">
      <form method="POST" action="{{ route('panel.suppliers.store') }}">
        @csrf

        <div class="mb-3">
          <label class="form-label fw-semibold">Nome do Fornecedor</label>
          <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                 value="{{ old('name') }}" required>
          @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Código Único</label>
          <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                 value="{{ old('code') }}" placeholder="Ex: ACME, DIST01" required>
          <small class="text-muted">Código curto para identificação interna</small>
          @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Descrição</label>
          <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                    rows="3">{{ old('description') }}</textarea>
          @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="active" id="active"
                   value="1" {{ old('active', true) ? 'checked' : '' }}>
            <label class="form-check-label" for="active">Fornecedor ativo</label>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-dark">Criar Fornecedor</button>
          <a href="{{ route('panel.suppliers.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
