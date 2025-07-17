<div class="space-y-6">
    @if(isset($previews['model_preview']))
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Model Preview</h3>
        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
            <pre class="text-sm text-gray-100"><code class="language-php">{{ $previews['model_preview'] }}</code></pre>
        </div>
    </div>
    @endif

    @if(isset($previews['migration_preview']))
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Migration Preview</h3>
        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
            <pre class="text-sm text-gray-100"><code class="language-php">{{ $previews['migration_preview'] }}</code></pre>
        </div>
    </div>
    @endif

    @if(isset($previews['json_resource_preview']))
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">JSON Resource Preview</h3>
        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
            <pre class="text-sm text-gray-100"><code class="language-php">{{ $previews['json_resource_preview'] }}</code></pre>
        </div>
    </div>
    @endif

    @if(isset($previews['api_controller_preview']))
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">API Controller Preview</h3>
        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
            <pre class="text-sm text-gray-100"><code class="language-php">{{ $previews['api_controller_preview'] }}</code></pre>
        </div>
    </div>
    @endif


    @if(isset($previews['form_request_preview']))
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Form Request Preview</h3>
        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
            <pre class="text-sm text-gray-100"><code class="language-php">{{ $previews['form_request_preview'] }}</code></pre>
        </div>
    </div>
    @endif

    @if(isset($previews['repository_preview']))
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Repository Preview</h3>
        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
            <pre class="text-sm text-gray-100"><code class="language-php">{{ $previews['repository_preview'] }}</code></pre>
        </div>
    </div>
    @endif

    @if(isset($previews['repository_interface_preview']))
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Repository Interface Preview</h3>
        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
            <pre class="text-sm text-gray-100"><code class="language-php">{{ $previews['repository_interface_preview'] }}</code></pre>
        </div>
    </div>
    @endif
</div>

<style>
    pre {
        white-space: pre-wrap;
        word-wrap: break-word;
    }

    code {
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        line-height: 1.5;
    }
</style>
