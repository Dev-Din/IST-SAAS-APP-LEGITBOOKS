@extends('layouts.tenant')

@section('title', 'Edit Bill — ' . $bill->bill_number)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Edit Bill — {{ $bill->bill_number }}</h1>

        @if($errors->any())
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @perm('manage_bills')
        <form method="POST" action="{{ route('tenant.bills.update', $bill) }}" class="bg-white shadow-sm rounded-lg p-6" id="bill-edit-form">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="contact_id" class="block text-sm font-medium text-gray-700">Supplier *</label>
                    <select name="contact_id" id="contact_id" required class="mt-1 block w-full h-12 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select a supplier</option>
                        @foreach($contacts as $contact)
                        <option value="{{ $contact->id }}" data-tax-rate="{{ $contact->tax_rate ?? 0 }}" {{ old('contact_id', $bill->contact_id) == $contact->id ? 'selected' : '' }}>
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
                    <input type="date" name="bill_date" id="bill_date" value="{{ old('bill_date', $bill->bill_date->format('Y-m-d')) }}" required class="mt-1 block w-full h-12 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('bill_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                    <input type="date" name="due_date" id="due_date" value="{{ old('due_date', $bill->due_date ? $bill->due_date->format('Y-m-d') : '') }}" class="mt-1 block w-full h-12 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('due_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Line Items *</label>
                <div id="line-items">
                    @php
                        $lineItems = old('line_items', $bill->lineItems);
                        $lineItemIndex = 0;
                    @endphp
                    @if(count($lineItems) > 0)
                        @foreach($lineItems as $lineItem)
                        <div class="line-item border rounded p-4 mb-4" data-index="{{ $lineItemIndex }}">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Product</label>
                                    <select name="line_items[{{ $lineItemIndex }}][product_id]" class="mt-1 block w-full h-12 rounded-md border-gray-300 shadow-sm product-select">
                                        <option value="">Select product</option>
                                        @foreach($products as $product)
                                        <option value="{{ $product->id }}" 
                                                data-price="{{ $product->price }}"
                                                {{ old("line_items.{$lineItemIndex}.product_id", is_object($lineItem) ? $lineItem->product_id : ($lineItem['product_id'] ?? '')) == $product->id ? 'selected' : '' }}>
                                            {{ $product->name }} - {{ number_format($product->price, 2) }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Description *</label>
                                    <textarea name="line_items[{{ $lineItemIndex }}][description]" required rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old("line_items.{$lineItemIndex}.description", is_object($lineItem) ? $lineItem->description : ($lineItem['description'] ?? '')) }}</textarea>
                                    @error("line_items.{$lineItemIndex}.description")
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Quantity *</label>
                                    <input type="number" name="line_items[{{ $lineItemIndex }}][quantity]" 
                                           value="{{ old("line_items.{$lineItemIndex}.quantity", is_object($lineItem) ? $lineItem->quantity : ($lineItem['quantity'] ?? 1)) }}" 
                                           step="0.01" min="0.01" required 
                                           class="mt-1 block w-full h-12 rounded-md border-gray-300 shadow-sm quantity-input">
                                    @error("line_items.{$lineItemIndex}.quantity")
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Unit Price *</label>
                                    <input type="number" name="line_items[{{ $lineItemIndex }}][unit_price]" 
                                           value="{{ old("line_items.{$lineItemIndex}.unit_price", is_object($lineItem) ? $lineItem->unit_price : ($lineItem['unit_price'] ?? '')) }}" 
                                           step="0.01" min="0" required 
                                           class="mt-1 block w-full h-12 rounded-md border-gray-300 shadow-sm price-input">
                                    @error("line_items.{$lineItemIndex}.unit_price")
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tax Rate (%)</label>
                                    <input type="number" name="line_items[{{ $lineItemIndex }}][tax_rate]" 
                                           value="{{ old("line_items.{$lineItemIndex}.tax_rate", is_object($lineItem) ? $lineItem->tax_rate : ($lineItem['tax_rate'] ?? 0)) }}" 
                                           step="0.01" min="0" max="100" 
                                           class="mt-1 block w-full h-12 rounded-md border-gray-300 shadow-sm tax-rate-input">
                                    @error("line_items.{$lineItemIndex}.tax_rate")
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Expense Account</label>
                                    <select name="line_items[{{ $lineItemIndex }}][expense_account_id]" class="mt-1 block w-full h-12 rounded-md border-gray-300 shadow-sm">
                                        <option value="">Select account</option>
                                        @foreach($expenseAccounts as $account)
                                        <option value="{{ $account->id }}" 
                                                {{ old("line_items.{$lineItemIndex}.expense_account_id", is_object($lineItem) ? $lineItem->expense_account_id : ($lineItem['expense_account_id'] ?? '')) == $account->id ? 'selected' : '' }}>
                                            {{ $account->code }} - {{ $account->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error("line_items.{$lineItemIndex}.expense_account_id")
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <div class="mt-3 flex justify-end">
                                <button type="button" class="remove-line-item text-sm text-red-600 hover:text-red-900" aria-label="Remove line item">
                                    Remove
                                </button>
                            </div>
                        </div>
                        @php $lineItemIndex++; @endphp
                        @endforeach
                    @else
                        <div class="line-item border rounded p-4 mb-4" data-index="0">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Product</label>
                                    <select name="line_items[0][product_id]" class="mt-1 block w-full h-12 rounded-md border-gray-300 shadow-sm product-select">
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
                                    <textarea name="line_items[0][description]" required rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Quantity *</label>
                                    <input type="number" name="line_items[0][quantity]" value="1" step="0.01" min="0.01" required class="mt-1 block w-full h-12 rounded-md border-gray-300 shadow-sm quantity-input">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Unit Price *</label>
                                    <input type="number" name="line_items[0][unit_price]" step="0.01" min="0" required class="mt-1 block w-full h-12 rounded-md border-gray-300 shadow-sm price-input">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tax Rate (%)</label>
                                    <input type="number" name="line_items[0][tax_rate]" value="0" step="0.01" min="0" max="100" class="mt-1 block w-full h-12 rounded-md border-gray-300 shadow-sm tax-rate-input">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Expense Account</label>
                                    <select name="line_items[0][expense_account_id]" class="mt-1 block w-full h-12 rounded-md border-gray-300 shadow-sm">
                                        <option value="">Select account</option>
                                        @foreach($expenseAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mt-3 flex justify-end">
                                <button type="button" class="remove-line-item text-sm text-red-600 hover:text-red-900" aria-label="Remove line item">
                                    Remove
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
                <button type="button" id="add-line-item" class="text-sm text-indigo-600 hover:text-indigo-900">+ Add Line Item</button>
            </div>

            <div class="mb-6 bg-gray-50 rounded-lg p-4">
                <div class="flex justify-end">
                    <div class="w-full md:w-64 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-medium" id="subtotal-display" aria-live="polite">KES {{ number_format($bill->subtotal ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Tax:</span>
                            <span class="font-medium" id="tax-display" aria-live="polite">KES {{ number_format($bill->tax_amount ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold border-t pt-2">
                            <span class="text-gray-900">Total:</span>
                            <span class="text-gray-900" id="total-display" aria-live="polite">KES {{ number_format($bill->total ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea name="notes" id="notes" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $bill->notes) }}</textarea>
                @error('notes')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('tenant.bills.show', $bill) }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white" style="background-color: var(--brand-primary);">
                    Save Changes
                </button>
            </div>
        </form>
        @else
        <div class="bg-white shadow rounded-lg p-8 text-center">
            <p class="text-gray-600 mb-4">You do not have permission to edit bills.</p>
            <a href="{{ route('tenant.bills.show', $bill) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Back to Bill
            </a>
        </div>
        @endperm
    </div>
</div>

<script>
(function() {
    let lineItemIndex = {{ $lineItemIndex ?? count($bill->lineItems ?? []) }};

    document.getElementById('add-line-item')?.addEventListener('click', function() {
        const lineItemsContainer = document.getElementById('line-items');
        const firstLineItem = lineItemsContainer.querySelector('.line-item');
        if (!firstLineItem) return;

        const template = firstLineItem.cloneNode(true);
        template.setAttribute('data-index', lineItemIndex);
        
        template.querySelectorAll('input, select, textarea').forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                input.setAttribute('name', name.replace(/\[\d+\]/, `[${lineItemIndex}]`));
            }
            if (input.type === 'text' || input.type === 'number') {
                input.value = input.type === 'number' && input.classList.contains('tax-rate-input') ? '0' : '';
            } else if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
            } else if (input.tagName === 'TEXTAREA') {
                input.value = '';
            }
        });

        lineItemsContainer.appendChild(template);
        lineItemIndex++;
        calculateTotals();
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-line-item')) {
            const lineItem = e.target.closest('.line-item');
            const lineItemsContainer = document.getElementById('line-items');
            
            if (lineItemsContainer.querySelectorAll('.line-item').length <= 1) {
                alert('At least one line item is required.');
                return;
            }
            
            lineItem.remove();
            calculateTotals();
        }
    });

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('product-select')) {
            const priceInput = e.target.closest('.line-item').querySelector('.price-input');
            const selectedOption = e.target.options[e.target.selectedIndex];
            if (selectedOption.dataset.price && priceInput && !priceInput.value) {
                priceInput.value = selectedOption.dataset.price;
                calculateTotals();
            }
        }
    });

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('quantity-input') || 
            e.target.classList.contains('price-input') || 
            e.target.classList.contains('tax-rate-input')) {
            calculateTotals();
        }
    });

    function calculateTotals() {
        let subtotal = 0;
        let taxAmount = 0;

        document.querySelectorAll('.line-item').forEach(lineItem => {
            const quantity = parseFloat(lineItem.querySelector('.quantity-input')?.value || 0);
            const unitPrice = parseFloat(lineItem.querySelector('.price-input')?.value || 0);
            const taxRate = parseFloat(lineItem.querySelector('.tax-rate-input')?.value || 0);

            const lineTotal = quantity * unitPrice;
            const lineTax = lineTotal * (taxRate / 100);

            subtotal += lineTotal;
            taxAmount += lineTax;
        });

        const total = subtotal + taxAmount;

        const subtotalEl = document.getElementById('subtotal-display');
        const taxEl = document.getElementById('tax-display');
        const totalEl = document.getElementById('total-display');
        
        if (subtotalEl) subtotalEl.textContent = 'KES ' + subtotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        if (taxEl) taxEl.textContent = 'KES ' + taxAmount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        if (totalEl) totalEl.textContent = 'KES ' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    calculateTotals();

    // Auto-populate tax rate from selected contact
    document.getElementById('contact_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const contactTaxRate = selectedOption.dataset.taxRate || 0;
        
        // Update all tax rate inputs in line items that are empty or zero
        document.querySelectorAll('.tax-rate-input').forEach(input => {
            if (!input.value || input.value === '0') {
                input.value = contactTaxRate;
                calculateTotals();
            }
        });
    });

    // Auto-populate tax rate on page load if contact is pre-selected
    const contactSelect = document.getElementById('contact_id');
    if (contactSelect.value) {
        contactSelect.dispatchEvent(new Event('change'));
    }
})();
</script>
@endsection

