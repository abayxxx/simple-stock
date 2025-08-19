<a href="{{ route('stock.show', ['type' => $type, 'stock' => $row->id]) }}" class="btn btn-info btn-sm">Detail</a>
<a href="{{ route('stock.edit', ['type' => $type, 'stock' => $row->id]) }}" class="btn btn-warning btn-sm">Edit</a>
<form action="{{ route('stock.delete', ['type' => $type, 'stock' => $row->id]) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Yakin ingin hapus data ini?')">
    @csrf
    @method('DELETE')
    <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
</form>