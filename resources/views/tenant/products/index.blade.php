@extends('layouts.tenant')

@section('title', 'Products')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Products</h1>
            @perm('manage_products')
            <a href="{{ route('tenant.products.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white" style="background-color: var(--brand-primary);">
                Add Product
            </a>
            @endperm
        </div>

        @anyperm(['manage_products', 'view_products'])
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                @forelse($products as $product)
                <li>
                    <a href="{{ route('tenant.products.show', $product) }}" class="block hover:bg-gray-50">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <p class="text-sm font-medium text-indigo-600 truncate">
                                        {{ $product->name }}
                                    </p>
                                    @if($product->sku)
                                    <p class="ml-2 text-sm text-gray-500">(SKU: {{ $product->sku }})</p>
                                    @endif
                                    <p class="ml-2 flex-shrink-0 flex">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </p>
                                </div>
                                <div class="ml-2 flex-shrink-0 flex">
                                    <p class="text-sm font-medium text-gray-900">KES {{ number_format($product->price, 2) }}</p>
                                </div>
                            </div>
                            @if($product->description)
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">{{ \Illuminate\Support\Str::limit($product->description, 100) }}</p>
                            </div>
                            @endif
                            @if($product->salesAccount)
                            <div class="mt-2">
                                <p class="text-xs text-gray-400">Sales Account: {{ $product->salesAccount->name }}</p>
                            </div>
                            @endif
                        </div>
                    </a>
                </li>
                @empty
                <li class="px-4 py-8 text-center text-gray-500">
                    No products found.
                    @perm('manage_products')
                    <a href="{{ route('tenant.products.create') }}" class="text-indigo-600 hover:text-indigo-900">Add your first product</a>
                    @endperm
                </li>
                @endforelse
            </ul>
        </div>

        <div class="mt-4">
            {{ $products->links() }}
        </div>
        @else
        <div class="bg-white shadow overflow-hidden sm:rounded-md p-8 text-center text-gray-500">
            You do not have permission to view products.
        </div>
        @endanyperm
    </div>
</div>
@endsection
