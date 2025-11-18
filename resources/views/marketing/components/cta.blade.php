@props(['primary' => true, 'href' => null, 'text' => 'Start free trial'])

@if($href)
    <a href="{{ $href }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white {{ $primary ? '' : 'bg-gray-600 hover:bg-gray-700' }}" style="{{ $primary ? 'background-color: var(--brand-primary);' : '' }}">
        {{ $text }}
    </a>
@else
    <button type="button" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white {{ $primary ? '' : 'bg-gray-600 hover:bg-gray-700' }}" style="{{ $primary ? 'background-color: var(--brand-primary);' : '' }}">
        {{ $text }}
    </button>
@endif

