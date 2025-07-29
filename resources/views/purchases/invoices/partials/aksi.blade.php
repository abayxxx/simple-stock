<div class="btn-group btn-group-sm" role="group">
    <a href="{{ route('purchases.invoices.show', $row->id) }}" class="btn btn-info" title="Detail">
        <i class="fa fa-eye"></i>
    </a>
    <a href="{{ route('purchases.invoices.edit', $row->id) }}" class="btn btn-warning" title="Edit">
        <i class="fa fa-edit"></i>
    </a>
    <form action="{{ route('purchases.invoices.destroy', $row->id) }}" method="POST" class="d-inline"
        onsubmit="return confirm('Yakin ingin hapus data?')">
        @csrf
        @method('DELETE')
        <button class="btn btn-danger" title="Hapus">
            <i class="fa fa-trash"></i>
        </button>
    </form>
</div>