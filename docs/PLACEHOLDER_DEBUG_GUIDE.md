# Placeholder Debugging Guide

## Issue Resolution

The placeholder replacement issue has been fixed with the following changes:

### 1. HTMLPurifier Configuration Updated
**File**: `app/Http/Livewire/Admin/BroadcastComposer.php`

**Problem**: HTMLPurifier was stripping the `class` attribute from `<span>` elements, which broke placeholder detection.

**Solution**: Updated the allowed HTML configuration to include `span[class|style]` instead of just `span[style]`.

```php
// Before
$config->set('HTML.Allowed', '...span[style]');

// After  
$config->set('HTML.Allowed', '...span[class|style]');
```

### 2. Enhanced Placeholder Replacement Logic
**File**: `app/Services/BroadcastMessageService.php`

**Problem**: The regex pattern was too restrictive and didn't handle all HTML structures.

**Solution**: Added multiple patterns to handle different span structures:

```php
$patterns = [
    // Match span with class containing "placeholder"
    '/<span[^>]*class="[^"]*placeholder[^"]*"[^>]*>' . preg_quote($placeholder, '/') . '<\/span>/i',
    // Match span with any attributes containing placeholder text
    '/<span[^>]*>' . preg_quote($placeholder, '/') . '<\/span>/i'
];
```

### 3. Enhanced Debugging
Added comprehensive logging to track placeholder replacement:

```php
\Log::info('Placeholder replacement debug', [
    'customer_id' => $recipient->id,
    'customer_first_name' => $recipient->first_name,
    'original_content' => $broadcastMessage->content,
    'processed_content' => $personalizedContent,
    'contains_placeholder_spans' => strpos($broadcastMessage->content, 'class="placeholder') !== false,
    'contains_raw_placeholders' => strpos($broadcastMessage->content, '{customer.first_name}') !== false,
]);
```

## Testing Steps

### 1. Test the HTML5 Editor
1. Visit `/test-html5-editor` (in local environment)
2. Try inserting placeholders using:
   - The "Placeholders" dropdown button
   - Typing `@` to trigger suggestions
3. Verify placeholders appear as blue badges

### 2. Test Broadcast Composer
1. Go to the broadcast composer page
2. Create a new message
3. Insert placeholders using the HTML5 Editor
4. Preview the message to see placeholder replacement
5. Send a test message to verify email content

### 3. Check Logs
Monitor `storage/logs/laravel.log` for debugging information:

```bash
tail -f storage/logs/laravel.log | grep "Placeholder replacement debug"
```

## Expected HTML Structure

The HTML5 Editor creates placeholders with this structure:

```html
<span class="placeholder bg-blue-100 text-blue-800 px-1 py-0.5 rounded text-sm font-medium">
    {customer.first_name}
</span>
```

This should be preserved by HTMLPurifier and processed correctly by the replacement logic.

## Troubleshooting

### If placeholders still don't work:

1. **Check HTMLPurifier**: Verify the `class` attribute is preserved
   ```php
   // Add temporary debugging in BroadcastComposer.php
   \Log::info('Content after sanitization', ['content' => $this->sanitizeHtmlContent($this->content)]);
   ```

2. **Check Content Storage**: Verify placeholders are saved correctly in the database
   ```sql
   SELECT content FROM broadcast_messages ORDER BY created_at DESC LIMIT 1;
   ```

3. **Check Replacement Logic**: The enhanced patterns should catch all variations
   - Pattern 1: Matches spans with `class="...placeholder..."`
   - Pattern 2: Fallback for any span containing placeholder text

### Common Issues:

1. **Content Sanitization**: HTMLPurifier strips unsupported attributes
2. **Regex Patterns**: Must handle various HTML structures
3. **JavaScript Sync**: Ensure Livewire receives the correct HTML content

## Verification Commands

```bash
# Rebuild assets
npm run dev

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Check logs
tail -f storage/logs/laravel.log
```

## Success Indicators

✅ **HTML5 Editor**: Placeholders appear as blue badges  
✅ **Content Preservation**: Span elements with class attributes are maintained  
✅ **Preview**: Placeholders are replaced with actual values in preview  
✅ **Email**: Recipients receive personalized content  
✅ **Logs**: Debug information shows successful replacement  

The placeholder system should now work identically to the previous TinyMCE implementation while using the new HTML5 Editor.