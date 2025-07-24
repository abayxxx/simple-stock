<div class="btn-group btn-group-sm">
    <a href="{{ route('returns.show', $row->id) }}" class="btn btn-info"><i class="fa fa-eye"></i></a>
    <a href="{{ route('returns.edit', $row->id) }}" class="btn btn-warning"><i class="fa fa-edit"></i></a>
    <form action="{{ route('returns.destroy', $row->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Hapus retur ini?')">
        @csrf @method('DELETE')
        <button class="btn btn-danger"><i class="fa fa-trash"></i></button>
    </form>
</div>