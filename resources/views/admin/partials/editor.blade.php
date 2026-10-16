{{--
    Quill editor from the theme bound to a hidden field.
    Params: $name, $value (HTML), $height (optional)
--}}
<div class="js-editor" data-editor-target="editor-{{ $name }}">
    <div style="height: {{ $height ?? '200px' }};"
         data-toggle="quill"
         data-quill-placeholder="Escreva aqui..."
         data-quill-modules-toolbar='[["bold", "italic", "underline"], ["link", "blockquote"], [{"list": "ordered"}, {"list": "bullet"}], ["clean"]]'>{!! $value !!}</div>
</div>
<textarea name="{{ $name }}" id="editor-{{ $name }}" class="d-none">{{ $value }}</textarea>

@once
    @push('styles')
        <link type="text/css" href="{{ asset('assets/css/quill.css') }}" rel="stylesheet">
    @endpush
    @push('scripts')
        <script src="{{ asset('assets/vendor/quill.min.js') }}"></script>
        <script src="{{ asset('assets/js/quill.js') }}"></script>
        <script>
            document.querySelectorAll('.js-editor').forEach(function (wrapper) {
                var form = wrapper.closest('form');
                var editor = wrapper.querySelector('[data-toggle="quill"]');
                var target = document.getElementById(wrapper.dataset.editorTarget);

                form.addEventListener('submit', function () {
                    target.value = editor.Quill ? editor.Quill.root.innerHTML : target.value;
                });
            });
        </script>
    @endpush
@endonce
