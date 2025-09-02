@props([
    'id' => 'editor-' . uniqid(),
    'name' => 'content',
    'value' => '',
    'height' => 400,
    'placeholder' => 'Enter your content here...',
    'placeholders' => [],
    'livewireComponent' => null,
    'livewireProperty' => 'content'
])

<div {{ $attributes->merge(['class' => 'html5-editor-wrapper']) }} wire:ignore>
    <textarea 
        id="{{ $id }}"
        name="{{ $name }}"
        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 resize-none"
        placeholder="{{ $placeholder }}"
        style="display: none;"
    >{{ $value }}</textarea>
</div>

@once
    @push('scripts')
        <script src="{{ mix('js/html5-editor.js') }}"></script>
    @endpush
@endonce

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editorId = '{{ $id }}';
    const placeholders = @json($placeholders);
    const livewireComponent = '{{ $livewireComponent }}';
    const livewireProperty = '{{ $livewireProperty }}';
    
    const editor = new HTML5Editor('#' + editorId, {
        height: {{ $height }},
        placeholders: placeholders,
        onChange: function(content) {
            @if($livewireComponent)
                if (window.livewire && window.livewire.find) {
                    window.livewire.find(livewireComponent).set(livewireProperty, content);
                }
            @endif
        },
        onKeyup: function(content) {
            @if($livewireComponent)
                if (window.livewire && window.livewire.find) {
                    window.livewire.find(livewireComponent).set(livewireProperty, content);
                }
            @endif
        },
        onInit: function(editorInstance) {
            @if($livewireComponent)
                if (window.livewire && window.livewire.find) {
                    const initialContent = window.livewire.find(livewireComponent).get(livewireProperty);
                    if (initialContent) {
                        editorInstance.setContent(initialContent);
                    }
                }
            @endif
        }
    });
    
    // Store editor instance globally for access
    window['editor_' + editorId.replace('-', '_')] = editor;
});
</script>
@endpush