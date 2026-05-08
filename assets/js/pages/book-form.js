function previewFile() {
    const preview = document.getElementById('previewImage');
    const fileInput = document.querySelector('input[type=file]');
    const file = fileInput?.files?.[0];
    const placeholder = document.getElementById('uploadPlaceholder');

    if (!file || !preview || !placeholder) {
        return;
    }

    const reader = new FileReader();
    reader.addEventListener(
        'load',
        function () {
            preview.src = reader.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        },
        false
    );

    reader.readAsDataURL(file);
}
