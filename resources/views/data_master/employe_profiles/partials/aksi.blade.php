<a href="{{ route('employe_profiles.show', $row) }}" class="btn btn-info btn-sm">Detail</a>
<a href="{{ route('employe_profiles.edit', $row) }}" class="btn btn-warning btn-sm">Edit</a>
<form action="{{ route('employe_profiles.destroy', $row) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Yakin ingin hapus pegawai ini?')">
    @csrf
    @method('DELETE')
    <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
</form>