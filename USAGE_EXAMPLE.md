# HTML5 Editor Usage Examples

## Basic Usage

### 1. Using the Blade Component

```blade
<x-html5-editor 
    id="content"
    name="content"
    :value="$content"
    :placeholders="$availablePlaceholders"
    livewire-component="{{ $this->id }}"
    livewire-property="content"
    height="400"
    placeholder="Enter your message content here..."
/>
```

### 2. Direct JavaScript Initialization

```javascript
const editor = new HTML5Editor('#content', {
    height: 400,
    placeholders: [
        { text: '{customer.name}', value: '{customer.name}', description: 'Customer name' },
        { text: '{company.name}', value: '{company.name}', description: 'Company name' }
    ],
    onChange: function(content) {
        console.log('Content changed:', content);
    },
    onKeyup: function(content) {
        console.log('Key up event:', content);
    }
});
```

## Livewire Integration

### Component Setup

```php
<?php

namespace App\Http\Livewire;

use Livewire\Component;

class MessageComposer extends Component
{
    public $content = '';
    
    protected $availablePlaceholders = [
        ['text' => '{customer.first_name}', 'value' => '{customer.first_name}', 'description' => 'Customer first name'],
        ['text' => '{customer.email}', 'value' => '{customer.email}', 'description' => 'Customer email'],
        ['text' => '{company.name}', 'value' => '{company.name}', 'description' => 'Company name'],
    ];
    
    public function render()
    {
        return view('livewire.message-composer', [
            'placeholders' => $this->availablePlaceholders
        ]);
    }
}
```

### Blade Template

```blade
<div>
    <form wire:submit.prevent="save">
        <div class="mb-4">
            <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                Message Content
            </label>
            
            <x-html5-editor 
                id="content"
                name="content"
                wire:model.defer="content"
                :placeholders="$placeholders"
                livewire-component="{{ $this->id }}"
                livewire-property="content"
                placeholder="Enter your message content..."
            />
        </div>
        
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">
            Save Message
        </button>
    </form>
</div>
```

## Advanced Configuration

### Custom Toolbar

```javascript
const editor = new HTML5Editor('#content', {
    height: 500,
    placeholders: placeholders,
    customToolbar: true, // Disable default toolbar
    onChange: function(content) {
        // Handle content changes
    }
});

// Add custom toolbar buttons
editor.addToolbarButton({
    command: 'insertHTML',
    icon: '📎',
    title: 'Insert Link',
    onClick: function() {
        const url = prompt('Enter URL:');
        if (url) {
            editor.execCommand('insertHTML', `<a href="${url}">${url}</a>`);
        }
    }
});
```

### Placeholder Processing

```php
// In your Livewire component or controller
public function processPlaceholders($content, $customer = null)
{
    $placeholders = [
        '{customer.first_name}' => $customer->first_name ?? 'Customer',
        '{customer.email}' => $customer->email ?? 'customer@example.com',
        '{company.name}' => config('app.name'),
        '{current.date}' => now()->format('F j, Y'),
    ];
    
    return str_replace(array_keys($placeholders), array_values($placeholders), $content);
}
```

## Styling Customization

### Custom CSS

```scss
// Override editor styles
.html5-editor-container {
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    
    .html5-editor-toolbar {
        background: linear-gradient(to right, #f8fafc, #e2e8f0);
    }
    
    .html5-editor-content {
        font-family: 'Inter', sans-serif;
        
        .placeholder {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
    }
}
```

### Dark Mode Support

```scss
@media (prefers-color-scheme: dark) {
    .html5-editor-container {
        .html5-editor-toolbar {
            background-color: #374151;
            border-color: #4b5563;
        }
        
        .html5-editor-content {
            background-color: #1f2937;
            color: #f9fafb;
        }
        
        .html5-editor-btn {
            background-color: #4b5563;
            color: #f9fafb;
            border-color: #6b7280;
            
            &:hover {
                background-color: #6b7280;
            }
        }
    }
}
```

## API Reference

### HTML5Editor Class

#### Constructor
```javascript
new HTML5Editor(selector, options)
```

**Parameters:**
- `selector` (string): CSS selector for the textarea element
- `options` (object): Configuration options

**Options:**
- `height` (number): Editor height in pixels (default: 400)
- `placeholders` (array): Array of placeholder objects
- `onChange` (function): Callback for content changes
- `onKeyup` (function): Callback for keyup events
- `onInit` (function): Callback after initialization

#### Methods

```javascript
// Get current content
const content = editor.getContent();

// Set content
editor.setContent('<p>New content</p>');

// Insert placeholder
editor.insertPlaceholder('{customer.name}');

// Execute formatting command
editor.execCommand('bold');

// Destroy editor
editor.destroy();
```

### Placeholder Object Structure

```javascript
{
    text: '{customer.name}',        // Display text
    value: '{customer.name}',       // Actual value to insert
    description: 'Customer name'    // Tooltip/description
}
```

## Migration from TinyMCE

### Before (TinyMCE)
```javascript
tinymce.init({
    selector: '#content',
    height: 400,
    plugins: ['lists', 'link'],
    toolbar: 'bold italic | bullist numlist',
    setup: function(editor) {
        editor.on('change', function() {
            // Handle change
        });
    }
});
```

### After (HTML5 Editor)
```javascript
const editor = new HTML5Editor('#content', {
    height: 400,
    onChange: function(content) {
        // Handle change
    }
});
```

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## Performance Tips

1. **Lazy Loading**: Initialize editors only when needed
2. **Debounced Updates**: Use debouncing for frequent updates
3. **Content Validation**: Validate content on the server side
4. **Memory Management**: Call `destroy()` when removing editors

```javascript
// Debounced content sync
let updateTimeout;
const editor = new HTML5Editor('#content', {
    onChange: function(content) {
        clearTimeout(updateTimeout);
        updateTimeout = setTimeout(() => {
            // Sync with backend
            livewire.set('content', content);
        }, 300);
    }
});
```