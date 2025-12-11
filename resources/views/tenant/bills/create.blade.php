@extends('layouts.tenant')

@section('title', 'Create Bill')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Create Bill</h1>

        <form method="POST" action="{{ route('tenant.bills.store') }}" class="bg-white shadow-sm rounded-lg p-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="contact_id" class="block text-sm font-medium text-gray-700">Supplier *</label>
                    <select name="contact_id" id="contact_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select a supplier</option>
                        @foreach($contacts as $contact)
                        <option value="{{ $contact->id }}" data-tax-rate="{{ $contact->tax_rate ?? 0 }}" {{ old('contact_id') == $contact->id ? 'selected' : '' }}>
                            {{ $contact->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('contact_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="bill_date" class="block text-sm font-medium text-gray-700">Bill Date *</label>
                    <input type="date" name="bill_date" id="bill_date" value="{{ old('bill_date', date('Y-m-d')) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('bill_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                    <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('due_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Line Items *</label>
                <div id="line-items">
                    <div class="line-item border rounded p-4 mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Product</label>
                                <select name="line_items[0][product_id]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm product-select">
                                    <option value="">Select product</option>
                                    @foreach($products as $product)
                                    <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                                        {{ $product->name }} - {{ number_format($product->price, 2) }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description *</label>
                                <input type="text" name="line_items[0][description]" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Quantity *</label>
                                <input type="number" name="line_items[0][quantity]" value="1" step="0.01" min="0.01" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm quantity-input">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Unit Price *</label>
                                <input type="number" name="line_items[0][unit_price]" step="0.01" min="0" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm price-input">
                            </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tax Rate (%)</label>
                                    <input type="number" name="line_items[0][tax_rate]" value="0" step="0.01" min="0" max="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm tax-rate-input">
                                </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Expense Account</label>
                                <select name="line_items[0][expense_account_id]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">Select account</option>
                                    @foreach($expenseAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" id="add-line-item" class="text-sm text-indigo-600 hover:text-indigo-900">+ Add Line Item</button>
            </div>

            <div class="mb-6">
                <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('tenant.bills.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white" style="background-color: var(--brand-primary);">
                    Create Bill
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let lineItemIndex = 1;
document.getElementById('add-line-item').addEventListener('click', function() {
    const template = document.querySelector('.line-item').cloneNode(true);
    const contactSelect = document.getElementById('contact_id');
    const contactTaxRate = contactSelect.options[contactSelect.selectedIndex]?.dataset.taxRate || 0;
    
    template.querySelectorAll('input, select').forEach(input => {
        const name = input.getAttribute('name');
        if (name) {
            input.setAttribute('name', name.replace(/\[0\]/, `[${lineItemIndex}]`));
        }
        if (input.type === 'text' || input.type === 'number') {
            // Set tax rate for new line items
            if (input.classList.contains('tax-rate-input')) {
                input.value = contactTaxRate;
            } else {
                input.value = '';
            }
        } else if (input.tagName === 'SELECT') {
            input.selectedIndex = 0;
        }
    });
    document.getElementById('line-items').appendChild(template);
    lineItemIndex++;
});

// Auto-fill price when product is selected
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('product-select')) {
        const priceInput = e.target.closest('.line-item').querySelector('.price-input');
        const selectedOption = e.target.options[e.target.selectedIndex];
        if (selectedOption.dataset.price) {
            priceInput.value = selectedOption.dataset.price;
        }
    }
});

// Auto-populate tax rate from selected contact
document.getElementById('contact_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const contactTaxRate = selectedOption.dataset.taxRate || 0;
    
    // Update all tax rate inputs in line items
    document.querySelectorAll('.tax-rate-input').forEach(input => {
        if (!input.value || input.value === '0') {
            input.value = contactTaxRate;
        }
    });
});

// Auto-populate tax rate on page load if contact is pre-selected
document.addEventListener('DOMContentLoaded', function() {
    const contactSelect = document.getElementById('contact_id');
    if (contactSelect.value) {
        contactSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection
