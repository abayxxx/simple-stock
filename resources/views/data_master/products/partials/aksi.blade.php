<a href="{{ route('products.edit', $row) }}" class="btn btn-warning btn-sm">Edit</a>
<a href="{{ route('products.show', $row) }}" class="btn btn-info btn-sm">Detail</a>
<form action="{{ route('products.destroy', $row) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Anda yakin ingin menghapus?')">
    @csrf
    @method('DELETE')
    <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
</form>