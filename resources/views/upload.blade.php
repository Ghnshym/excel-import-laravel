<!-- resources/views/upload.blade.php -->
<form action="{{ route('import') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="file" required>
    <button type="submit">Import Excel</button>
</form>