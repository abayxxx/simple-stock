<a href="{{ route('company_branches.show', $row) }}" class="btn btn-info btn-sm">Detail</a>
<a href="{{ route('company_branches.edit', $row) }}" class="btn btn-warning btn-sm">Edit</a>
<form action="{{ route('company_branches.destroy', $row) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Yakin ingin hapus cabang ini?')">
    @csrf
    @method('DELETE')
    <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
</form>