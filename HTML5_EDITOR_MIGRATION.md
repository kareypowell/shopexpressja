# HTML5 Editor Migration

This document outlines the migration from TinyMCE to a custom HTML5 Editor while maintaining all placeholder functionality.

## Changes Made

### 1. Removed TinyMCE Dependencies
- Removed `tinymce` from `package.json`
- Removed TinyMCE API key from `.env`
- Removed TinyMCE asset copying from `webpack.mix.js`

### 2. Created Custom HTML5 Editor
- **File**: `resources/js/html5-editor.js`
- **Features**:
  - Rich text editing with toolbar (Bold, Italic, Underline, Alignment, Lists)
  - Placeholder insertion via dropdown menu
  - @ symbol trigger for placeholder suggestions
  - Livewire integration for real-time content sync
  - Responsive design with Tailwind CSS classes

### 3. Added Editor Styles
- **File**: `resources/sass/html5-editor.scss`
- **Includes**:
  - Toolbar styling
  - Editor content area styling
  - Placeholder visual styling (blue badges)
  - Dropdown menu styling
  - Responsive adjustments

### 4. Updated Build Configuration
- Modified `webpack.mix.js` to compile the new editor JavaScript
- Added SCSS import to `resources/sass/app.scss`

### 5. Updated Broadcast Composer
- **File**: `resources/views/livewire/admin/broadcast-composer.blade.php`
- Replaced TinyMCE initialization with HTML5 Editor
- Maintained all placeholder functionality
- Preserved Livewire integration

## Features Maintained

### Placeholder System
The HTML5 Editor maintains full compatibility with the existing placeholder system:

- **Available Placeholders**:
  - `{customer.first_name}` - Customer first name
  - `{customer.last_name}` - Customer last name  
  - `{customer.full_name}` - Customer full name
  - `{customer.email}` - Customer email address
  - `{customer.phone}` - Customer phone number
  - `{customer.address}` - Customer address
  - `{customer.city}` - Customer city
  - `{customer.country}` - Customer country
  - `{company.name}` - Company name
  - `{company.email}` - Company email
  - `{current.date}` - Current date
  - `{current.time}` - Current time

### Insertion Methods
1. **Dropdown Menu**: Click "Placeholders" button in toolbar
2. **@ Symbol**: Type `@` to trigger placeholder suggestions
3. **Visual Styling**: Placeholders appear as blue badges

### Livewire Integration
- Real-time content synchronization
- Automatic content updates on change/keyup events
- Proper initialization with existing content

## Installation & Build

### 1. Install Dependencies
```bash
npm install
```

### 2. Build Assets
```bash
# Development build
npm run dev

# Watch for changes
npm run watch

# Production build
npm run prod
```

### 3. Test the Editor
Visit `/test-html5-editor` (in local/testing environments) to test the editor functionality.

## Technical Implementation

### HTML5 Editor Class
The `HTML5Editor` class provides:

```javascript
// Initialize editor
const editor = new HTML5Editor('#selector', {
    height: 400,
    placeholders: [...],
    onChange: function(content) { /* callback */ },
    onKeyup: function(content) { /* callback */ },
    onInit: function(editor) { /* callback */ }
});

// Methods
editor.getContent()        // Get HTML content
editor.setContent(html)    // Set HTML content
editor.insertPlaceholder(placeholder) // Insert placeholder
editor.destroy()           // Clean up editor
```

### Styling Classes
Key CSS classes for customization:

- `.html5-editor-container` - Main editor wrapper
- `.html5-editor-toolbar` - Toolbar styling
- `.html5-editor-content` - Content area
- `.html5-editor-btn` - Toolbar buttons
- `.placeholder` - Placeholder styling

## Browser Compatibility

The HTML5 Editor uses modern web standards:
- `contentEditable` for rich text editing
- `document.execCommand` for formatting (being replaced with modern APIs)
- CSS Grid/Flexbox for layout
- ES6+ JavaScript features

**Supported Browsers**:
- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## Migration Benefits

1. **Reduced Bundle Size**: No external editor dependency
2. **Better Performance**: Lighter weight implementation
3. **Full Control**: Complete customization capability
4. **Tailwind Integration**: Consistent styling with the rest of the application
5. **No API Keys**: No external service dependencies

## Future Enhancements

Potential improvements for the HTML5 Editor:

1. **Additional Formatting**: Tables, images, links
2. **Keyboard Shortcuts**: Ctrl+B, Ctrl+I, etc.
3. **Undo/Redo**: Command history management
4. **Paste Handling**: Clean HTML from clipboard
5. **Accessibility**: ARIA labels and keyboard navigation
6. **Mobile Optimization**: Touch-friendly interface

## Troubleshooting

### Common Issues

1. **Assets Not Loading**
   ```bash
   npm run dev
   php artisan cache:clear
   ```

2. **Livewire Integration Issues**
   - Ensure Livewire is properly loaded
   - Check browser console for JavaScript errors
   - Verify component ID is correct

3. **Styling Issues**
   - Rebuild CSS assets: `npm run dev`
   - Check Tailwind CSS compilation
   - Verify SCSS import in `app.scss`

### Debug Mode
Enable console logging by adding to the editor initialization:
```javascript
const editor = new HTML5Editor('#content', {
    // ... other options
    debug: true // Add this for console logging
});
```