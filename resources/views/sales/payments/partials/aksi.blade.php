<div class="btn-group btn-group-sm">
    <a href="{{ route('sales.payments.show', $row->id) }}" class="btn btn-info" title="Detail">
        <i class="fa fa-eye"></i>
    </a>
    <a href="{{ route('sales.payments.edit', $row->id) }}" class="btn btn-warning" title="Edit">
        <i class="fa fa-edit"></i>
    </a>
    <form action="{{ route('sales.payments.destroy', $row->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Hapus data ini?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger" title="Hapus">
            <i class="fa fa-trash"></i>
        </button>
    </form>
</div>