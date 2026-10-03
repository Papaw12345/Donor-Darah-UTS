<header class="site-header">
    <div class="container navigation {{ auth()->check() && auth()->user()->peran === 'PETUGAS' ? 'navigation-petugas' : '' }}">
        <a class="brand" href="{{ url('/') }}" aria-label="Beranda Sistem Informasi UDD">
            <span class="brand-mark" aria-hidden="true"><span>UDD</span></span>
            <span class="brand-copy">
                <strong>Sistem Donor Darah UDD</strong>
                <span>Donor dan persediaan darah</span>
            </span>
        </a>

        <nav class="nav-actions {{ auth()->check() && auth()->user()->peran === 'PETUGAS' ? 'nav-actions-petugas' : '' }}" aria-label="Navigasi utama">
            @guest
                <a
                    class="nav-link {{ request()->routeIs('login') ? 'is-active' : '' }}"
                    href="{{ route('login') }}"
                    @if (request()->routeIs('login')) aria-current="page" @endif
                >Masuk</a>
                <a
                    class="button button-primary button-small"
                    href="{{ route('register') }}"
                    @if (request()->routeIs('register')) aria-current="page" @endif
                >Daftar Pendonor</a>
            @else
                @if (auth()->user()->peran === 'PENDONOR')
                    <a class="nav-link {{ request()->routeIs('pendonor.home', 'pendonor.donor-berikutnya.*') ? 'is-active' : '' }}" href="{{ route('pendonor.home') }}">Dashboard</a>
                    <a class="nav-link {{ request()->routeIs('pendonor.jadwal.*') ? 'is-active' : '' }}" href="{{ route('pendonor.jadwal.index') }}">Jadwal Donor</a>
                    <a class="nav-link {{ request()->routeIs('pendonor.pemesanan.*', 'pendonor.kuesioner.*', 'pendonor.kode-checkin.*') ? 'is-active' : '' }}" href="{{ route('pendonor.pemesanan.index') }}">Agenda Donor</a>
                    <a class="nav-link {{ request()->routeIs('pendonor.riwayat.*') ? 'is-active' : '' }}" href="{{ route('pendonor.riwayat.index') }}">Riwayat Donor</a>
                    <a class="nav-link {{ request()->routeIs('pendonor.pemberitahuan.*') ? 'is-active' : '' }}" href="{{ route('pendonor.pemberitahuan.index') }}">Pemberitahuan</a>
                    <a class="nav-link {{ request()->routeIs('pendonor.profil.*') ? 'is-active' : '' }}" href="{{ route('pendonor.profil.show') }}">Profil</a>
                @elseif (auth()->user()->peran === 'PETUGAS')
                    <a class="nav-link {{ request()->routeIs('petugas.home') ? 'is-active' : '' }}" href="{{ route('petugas.home') }}">Dashboard</a>
                    <details class="nav-dropdown" name="petugas-navigation" aria-label="Operasional">
                        <summary class="nav-dropdown-summary nav-link {{ request()->routeIs(
                            'petugas.check-in.*', 'petugas.kuesioner.*', 'petugas.seleksi.*',
                            'petugas.penyumbangan.*', 'petugas.unit-komponen.*', 'petugas.pelulusan.*',
                            'petugas.distribusi.*'
                        ) ? 'is-active' : '' }}">Operasional</summary>
                        <div class="nav-dropdown-panel">
                            <a class="nav-link {{ request()->routeIs('petugas.check-in.*', 'petugas.kuesioner.*') ? 'is-active' : '' }}" href="{{ route('petugas.check-in.index') }}">Check-in</a>
                            <a class="nav-link {{ request()->routeIs('petugas.seleksi.*') ? 'is-active' : '' }}" href="{{ route('petugas.seleksi.index') }}">Seleksi Donor</a>
                            <a class="nav-link {{ request()->routeIs('petugas.penyumbangan.*') ? 'is-active' : '' }}" href="{{ route('petugas.penyumbangan.index') }}">Penyumbangan</a>
                            <a class="nav-link {{ request()->routeIs('petugas.unit-komponen.*') ? 'is-active' : '' }}" href="{{ route('petugas.unit-komponen.index') }}">Unit Komponen</a>
                            <a class="nav-link {{ request()->routeIs('petugas.pelulusan.*') ? 'is-active' : '' }}" href="{{ route('petugas.pelulusan.index') }}">Pelulusan</a>
                            <a class="nav-link {{ request()->routeIs('petugas.distribusi.*') ? 'is-active' : '' }}" href="{{ route('petugas.distribusi.index') }}">Distribusi</a>
                        </div>
                    </details>
                    <details class="nav-dropdown" name="petugas-navigation" aria-label="Monitoring">
                        <summary class="nav-dropdown-summary nav-link {{ request()->routeIs(
                            'petugas.jadwal.*', 'petugas.riwayat-pelayanan.*', 'petugas.persediaan.*',
                            'petugas.persediaan-rendah.*', 'petugas.pemanggilan.*', 'petugas.pemberitahuan.*'
                        ) ? 'is-active' : '' }}">Monitoring</summary>
                        <div class="nav-dropdown-panel">
                            <a class="nav-link {{ request()->routeIs('petugas.jadwal.*') ? 'is-active' : '' }}" href="{{ route('petugas.jadwal.index') }}">Jadwal Pelayanan</a>
                            <a class="nav-link {{ request()->routeIs('petugas.riwayat-pelayanan.*') ? 'is-active' : '' }}" href="{{ route('petugas.riwayat-pelayanan.index') }}">Riwayat Pelayanan</a>
                            <a class="nav-link {{ request()->routeIs('petugas.persediaan.*', 'petugas.persediaan-rendah.*') ? 'is-active' : '' }}" href="{{ route('petugas.persediaan.index') }}">Persediaan</a>
                            <a class="nav-link {{ request()->routeIs('petugas.pemanggilan.*', 'petugas.pemberitahuan.*') ? 'is-active' : '' }}" href="{{ route('petugas.pemanggilan.index') }}">Pemanggilan Pendonor</a>
                        </div>
                    </details>
                    <form class="nav-form" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="button button-primary button-small" type="submit">Keluar</button>
                    </form>
                @elseif (auth()->user()->peran === 'ADMIN')
                    <a class="nav-link {{ request()->routeIs('admin.home') ? 'is-active' : '' }}" href="{{ route('admin.home') }}">Dashboard</a>
                    <a class="nav-link {{ request()->routeIs('admin.petugas.*') ? 'is-active' : '' }}" href="{{ route('admin.petugas.index') }}">Petugas</a>
                    <a class="nav-link {{ request()->routeIs('admin.jadwal.*') ? 'is-active' : '' }}" href="{{ route('admin.jadwal.index') }}">Jadwal</a>
                    <a class="nav-link {{ request()->routeIs('admin.pertanyaan.*') ? 'is-active' : '' }}" href="{{ route('admin.pertanyaan.index') }}">Pertanyaan</a>
                    <a class="nav-link {{ request()->routeIs('admin.ambang.*') ? 'is-active' : '' }}" href="{{ route('admin.ambang.index') }}">Ambang</a>
                @endif

                @if (auth()->user()->peran !== 'PETUGAS')
                    <form class="nav-form" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="button button-primary button-small" type="submit">Keluar</button>
                    </form>
                @endif
            @endguest
        </nav>
    </div>
</header>
