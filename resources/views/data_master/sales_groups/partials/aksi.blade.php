<a href="{{ route('sales_groups.show', $row) }}" class="btn btn-info btn-sm">Detail</a>
<a href="{{ route('sales_groups.edit', $row) }}" class="btn btn-warning btn-sm">Edit</a>
<form action="{{ route('sales_groups.destroy', $row) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Yakin ingin hapus grup ini?')">
    @csrf
    @method('DELETE')
    <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
</form>