@if(session('success'))
  <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
    <strong class="font-bold">Sucesso!</strong>
    <span class="block sm:inline">{{ session('success') }}</span>
  </div>
@endif

@if(session('error'))
  <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
    <strong class="font-bold">Erro!</strong>
    <span class="block sm:inline">{{ session('error') }}</span>
  </div>
@endif

@if($errors->any())
  <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
    <strong class="font-bold">Erro de Validação!</strong>
    <span class="block sm:inline">{{ $errors->first() }}</span>
  </div>
@endif
