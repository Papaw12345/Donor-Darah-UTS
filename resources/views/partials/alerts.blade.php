@if (session('success'))
    <div class="alert alert-success" role="status">
        <strong>Berhasil</strong>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-error" role="alert">
        <strong>Periksa kembali data yang diisi.</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
