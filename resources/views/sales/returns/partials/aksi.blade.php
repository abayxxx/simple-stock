<div class="btn-group btn-group-sm">
    <a href="{{ route('sales.returns.show', $row->id) }}" class="btn btn-info"><i class="fa fa-eye"></i></a>
    <a href="{{ route('sales.returns.edit', $row->id) }}" class="btn btn-warning"><i class="fa fa-edit"></i></a>
    <form action="{{ route('sales.returns.destroy', $row->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Hapus retur ini?')">
        @csrf @method('DELETE')
        <button class="btn btn-danger"><i class="fa fa-trash"></i></button>
    </form>
</div>