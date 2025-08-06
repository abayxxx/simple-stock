<div class="btn-group btn-group-sm">
    <a href="{{ route('sales.receipts.show', $row->id) }}" class="btn btn-info"><i class="fa fa-eye"></i></a>
     @if(isSuperAdmin())
    <a href="{{ route('sales.receipts.edit', $row->id) }}" class="btn btn-warning"
        @if(!isSuperAdmin()) style="pointer-events: none; opacity: 0.5;" @endif><i class="fa fa-edit"></i></a>
       
    <form action="{{ route('sales.receipts.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus tanda terima ini?')"
        @if(!isSuperAdmin()) style="pointer-events: none; opacity: 0.5;" @endif>
        @csrf @method('DELETE')
        <button class="btn btn-danger"><i class="fa fa-trash"></i></button>
    </form>
    @endif
</div>