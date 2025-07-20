<a href="{{ route('company_profiles.show', $row) }}" class="btn btn-info btn-sm">Lihat</a>
<a href="{{ route('company_profiles.edit', $row) }}" class="btn btn-warning btn-sm">Edit</a>
<form action="{{ route('company_profiles.destroy', $row) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Anda yakin ingin menghapus?')">
    @csrf
    @method('DELETE')
    <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
</form>