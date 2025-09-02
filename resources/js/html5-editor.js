/**
 * HTML5 Editor with Placeholder Support
 * Replacement for TinyMCE with similar functionality
 */
class HTML5Editor {
    constructor(selector, options = {}) {
        this.selector = selector;
        this.element = document.querySelector(selector);
        this.options = {
            height: 400,
            placeholders: [],
            onChange: null,
            onKeyup: null,
            onInit: null,
            ...options
        };
        
        this.editor = null;
        this.toolbar = null;
        this.placeholderDropdown = null;
        
        this.init();
    }
    
    init() {
        if (!this.element) {
            console.error('HTML5Editor: Element not found for selector:', this.selector);
            return;
        }
        
        this.createEditor();
        this.createToolbar();
        this.setupEventListeners();
        
        if (this.options.onInit) {
            this.options.onInit(this);
        }
    }
    
    createEditor() {
        // Hide the original textarea
        this.element.style.display = 'none';
        
        // Create editor container
        const container = document.createElement('div');
        container.className = 'html5-editor-container border border-gray-300 rounded-md overflow-hidden';
        
        // Create toolbar container
        this.toolbar = document.createElement('div');
        this.toolbar.className = 'html5-editor-toolbar bg-gray-50 border-b border-gray-300 p-2 flex flex-wrap gap-1';
        
        // Create editor
        this.editor = document.createElement('div');
        this.editor.className = 'html5-editor-content p-3 focus:outline-none';
        this.editor.contentEditable = true;
        this.editor.style.minHeight = this.options.height + 'px';
        this.editor.style.maxHeight = '600px';
        this.editor.style.overflowY = 'auto';
        
        // Set initial content
        this.editor.innerHTML = this.element.value || '';
        
        // Append elements
        container.appendChild(this.toolbar);
        container.appendChild(this.editor);
        this.element.parentNode.insertBefore(container, this.element.nextSibling);
    }
    
    createToolbar() {
        const buttons = [
            { command: 'bold', icon: 'B', title: 'Bold', className: 'font-bold' },
            { command: 'italic', icon: 'I', title: 'Italic', className: 'italic' },
            { command: 'underline', icon: 'U', title: 'Underline', className: 'underline' },
            { type: 'separator' },
            { command: 'justifyLeft', icon: '⬅', title: 'Align Left' },
            { command: 'justifyCenter', icon: '⬌', title: 'Align Center' },
            { command: 'justifyRight', icon: '➡', title: 'Align Right' },
            { type: 'separator' },
            { command: 'insertUnorderedList', icon: '• List', title: 'Bullet List' },
            { command: 'insertOrderedList', icon: '1. List', title: 'Numbered List' },
            { type: 'separator' },
            { command: 'removeFormat', icon: '✗', title: 'Remove Formatting' }
        ];
        
        buttons.forEach(button => {
            if (button.type === 'separator') {
                const separator = document.createElement('div');
                separator.className = 'w-px bg-gray-300 mx-1';
                this.toolbar.appendChild(separator);
            } else {
                const btn = this.createToolbarButton(button);
                this.toolbar.appendChild(btn);
            }
        });
        
        // Add placeholders dropdown if placeholders are provided
        if (this.options.placeholders && this.options.placeholders.length > 0) {
            const separator = document.createElement('div');
            separator.className = 'w-px bg-gray-300 mx-1';
            this.toolbar.appendChild(separator);
            
            this.createPlaceholderDropdown();
        }
    }
    
    createToolbarButton(button) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `html5-editor-btn px-2 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 ${button.className || ''}`;
        btn.innerHTML = button.icon;
        btn.title = button.title;
        
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            this.execCommand(button.command);
        });
        
        return btn;
    }
    
    createPlaceholderDropdown() {
        const container = document.createElement('div');
        container.className = 'relative inline-block';
        
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'html5-editor-btn px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 flex items-center gap-1';
        button.innerHTML = 'Placeholders <span class="text-xs">▼</span>';
        
        const dropdown = document.createElement('div');
        dropdown.className = 'absolute top-full left-0 mt-1 w-64 bg-white border border-gray-300 rounded-md shadow-lg z-50 hidden';
        dropdown.style.maxHeight = '200px';
        dropdown.style.overflowY = 'auto';
        
        this.options.placeholders.forEach(placeholder => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'w-full text-left px-3 py-2 text-sm hover:bg-gray-100 border-b border-gray-100 last:border-b-0';
            item.innerHTML = `
                <div class="font-medium text-gray-900">${placeholder.text}</div>
                <div class="text-xs text-gray-500">${placeholder.description || ''}</div>
            `;
            
            item.addEventListener('click', (e) => {
                e.preventDefault();
                this.insertPlaceholder(placeholder.value);
                dropdown.classList.add('hidden');
            });
            
            dropdown.appendChild(item);
        });
        
        button.addEventListener('click', (e) => {
            e.preventDefault();
            dropdown.classList.toggle('hidden');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!container.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
        
        container.appendChild(button);
        container.appendChild(dropdown);
        this.toolbar.appendChild(container);
        
        this.placeholderDropdown = dropdown;
    }
    
    execCommand(command, value = null) {
        this.editor.focus();
        document.execCommand(command, false, value);
        this.updateTextarea();
    }
    
    insertPlaceholder(placeholder) {
        this.editor.focus();
        
        const selection = window.getSelection();
        const range = selection.getRangeAt(0);
        
        const span = document.createElement('span');
        span.className = 'placeholder bg-blue-100 text-blue-800 px-1 py-0.5 rounded text-sm font-medium';
        span.textContent = placeholder;
        span.contentEditable = false;
        
        range.deleteContents();
        range.insertNode(span);
        
        // Move cursor after the placeholder
        range.setStartAfter(span);
        range.setEndAfter(span);
        selection.removeAllRanges();
        selection.addRange(range);
        
        this.updateTextarea();
    }
    
    setupEventListeners() {
        // Update textarea on content change
        this.editor.addEventListener('input', () => {
            this.updateTextarea();
            if (this.options.onChange) {
                this.options.onChange(this.getContent());
            }
        });
        
        this.editor.addEventListener('keyup', () => {
            this.updateTextarea();
            if (this.options.onKeyup) {
                this.options.onKeyup(this.getContent());
            }
        });
        
        // Handle @ symbol for placeholder insertion
        this.editor.addEventListener('keydown', (e) => {
            if (e.key === '@' && this.options.placeholders && this.options.placeholders.length > 0) {
                e.preventDefault();
                this.showPlaceholderSuggestions();
            }
        });
        
        // Prevent line breaks in placeholders
        this.editor.addEventListener('keydown', (e) => {
            const selection = window.getSelection();
            if (selection.rangeCount > 0) {
                const range = selection.getRangeAt(0);
                const container = range.commonAncestorContainer;
                const placeholder = container.nodeType === Node.TEXT_NODE 
                    ? container.parentElement 
                    : container;
                
                if (placeholder && placeholder.classList && placeholder.classList.contains('placeholder')) {
                    if (e.key === 'Enter' || e.key === 'Backspace' || e.key === 'Delete') {
                        e.preventDefault();
                        if (e.key === 'Backspace' || e.key === 'Delete') {
                            placeholder.remove();
                            this.updateTextarea();
                        }
                    }
                }
            }
        });
    }
    
    showPlaceholderSuggestions() {
        if (this.placeholderDropdown) {
            this.placeholderDropdown.classList.remove('hidden');
        }
    }
    
    updateTextarea() {
        this.element.value = this.getContent();
        
        // Trigger change event for form validation
        const event = new Event('change', { bubbles: true });
        this.element.dispatchEvent(event);
    }
    
    getContent() {
        return this.editor.innerHTML;
    }
    
    setContent(content) {
        this.editor.innerHTML = content || '';
        this.updateTextarea();
    }
    
    destroy() {
        if (this.editor && this.editor.parentNode) {
            const container = this.editor.parentNode;
            container.remove();
            this.element.style.display = '';
        }
    }
}

// Export for use in other files
window.HTML5Editor = HTML5Editor;