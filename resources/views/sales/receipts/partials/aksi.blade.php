<div class="btn-group btn-group-sm">
    <a href="{{ route('sales.receipts.show', $row->id) }}" class="btn btn-info"><i class="fa fa-eye"></i></a>
    <a href="{{ route('sales.receipts.edit', $row->id) }}" class="btn btn-warning"><i class="fa fa-edit"></i></a>
    <form action="{{ route('sales.receipts.destroy', $row->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Yakin hapus tanda terima ini?')">
        @csrf @method('DELETE')
        <button class="btn btn-danger"><i class="fa fa-trash"></i></button>
    </form>
</div>