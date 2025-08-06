<div class="btn-group">
    <a href="{{ route('management.users.edit', $row->id) }}" class="btn btn-warning btn-sm mr-1">Edit</a>
    <form action="{{ route('management.users.destroy', $row->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Hapus user?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
    </form>
</div>