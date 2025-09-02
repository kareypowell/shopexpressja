<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HTML5 Editor Test</title>
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">HTML5 Editor Test</h1>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <form>
                <div class="mb-6">
                    <label for="test-content" class="block text-sm font-medium text-gray-700 mb-2">
                        Test Content
                    </label>
                    <textarea 
                        id="test-content"
                        name="content"
                        rows="12"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Enter your test content here..."
                    ></textarea>
                </div>
                
                <div class="flex gap-4">
                    <button type="button" onclick="getContent()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Get Content
                    </button>
                    <button type="button" onclick="setContent()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        Set Test Content
                    </button>
                    <button type="button" onclick="clearContent()" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        Clear Content
                    </button>
                </div>
            </form>
            
            <div id="output" class="mt-6 p-4 bg-gray-50 rounded-md hidden">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Editor Content:</h3>
                <pre class="text-sm text-gray-700 whitespace-pre-wrap"></pre>
            </div>
        </div>
    </div>

    <script src="{{ mix('js/html5-editor.js') }}"></script>
    <script>
        // Test placeholders
        const testPlaceholders = [
            { text: '{customer.first_name}', value: '{customer.first_name}', description: 'Customer first name' },
            { text: '{customer.last_name}', value: '{customer.last_name}', description: 'Customer last name' },
            { text: '{customer.full_name}', value: '{customer.full_name}', description: 'Customer full name' },
            { text: '{customer.email}', value: '{customer.email}', description: 'Customer email address' },
            { text: '{company.name}', value: '{company.name}', description: 'Company name' },
            { text: '{current.date}', value: '{current.date}', description: 'Current date' }
        ];

        let editor;

        document.addEventListener('DOMContentLoaded', function() {
            editor = new HTML5Editor('#test-content', {
                height: 400,
                placeholders: testPlaceholders,
                onChange: function(content) {
                    console.log('Content changed:', content);
                },
                onKeyup: function(content) {
                    console.log('Key up:', content);
                }
            });
        });

        function getContent() {
            const content = editor.getContent();
            const output = document.getElementById('output');
            const pre = output.querySelector('pre');
            pre.textContent = content;
            output.classList.remove('hidden');
        }

        function setContent() {
            const testContent = `
                <h2>Test Content</h2>
                <p>Hello <span class="placeholder">{customer.first_name}</span>,</p>
                <p>This is a <strong>test message</strong> from <span class="placeholder">{company.name}</span>.</p>
                <ul>
                    <li>Item 1</li>
                    <li>Item 2</li>
                </ul>
                <p>Best regards,<br>The Team</p>
            `;
            editor.setContent(testContent);
        }

        function clearContent() {
            editor.setContent('');
            document.getElementById('output').classList.add('hidden');
        }
    </script>
</body>
</html>