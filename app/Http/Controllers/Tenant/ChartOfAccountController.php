<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Services\TenantContext;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();

        // Get query parameters
        $search = $request->get('search', '');
        $sortBy = $request->get('sort_by', 'code');
        $sortOrder = $request->get('sort_order', 'asc');

        // Build query
        $query = ChartOfAccount::where('tenant_id', $tenant->id);

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $validSortColumns = ['code', 'name', 'type'];
        $sortBy = in_array($sortBy, $validSortColumns) ? $sortBy : 'code';
        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'asc';

        $chartOfAccounts = $query->orderBy($sortBy, $sortOrder)->get();

        // Calculate YTD for each account using efficient query (only if accounts exist)
        if ($chartOfAccounts->isNotEmpty()) {
            $accountIds = $chartOfAccounts->pluck('id');
            $ytdBalances = \App\Models\JournalLine::whereIn('chart_of_account_id', $accountIds)
                ->whereHas('journalEntry', function ($q) {
                    $q->where('is_posted', true)
                        ->whereYear('entry_date', now()->year);
                })
                ->selectRaw('chart_of_account_id, SUM(CASE WHEN type = "debit" THEN amount ELSE -amount END) as balance')
                ->groupBy('chart_of_account_id')
                ->pluck('balance', 'chart_of_account_id');

            // Attach YTD balances to accounts
            $chartOfAccounts->each(function ($account) use ($ytdBalances) {
                $account->ytd_balance = $ytdBalances[$account->id] ?? 0;
            });
        } else {
            // Ensure empty collection is properly initialized
            $chartOfAccounts = collect([]);
        }

        return view('tenant.chart-of-accounts.index', compact('chartOfAccounts', 'tenant', 'search', 'sortBy', 'sortOrder'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $parentAccounts = ChartOfAccount::where('tenant_id', $tenant->id)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('tenant.chart-of-accounts.create', compact('tenant', 'parentAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255', 'unique:chart_of_accounts,code,NULL,id,tenant_id,'.$tenant->id],
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'category' => 'nullable|in:current_asset,fixed_asset,current_liability,long_term_liability,equity,revenue,expense,cost_of_sales',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
        ]);

        ChartOfAccount::create([
            'tenant_id' => $tenant->id,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'category' => $validated['category'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('tenant.chart-of-accounts.index')
            ->with('success', 'Chart of account created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ChartOfAccount $chartOfAccount, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $chartOfAccount->load('parent', 'children', 'accounts', 'journalLines.journalEntry');

        return view('tenant.chart-of-accounts.show', compact('chartOfAccount', 'tenant'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ChartOfAccount $chartOfAccount, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $parentAccounts = ChartOfAccount::where('tenant_id', $tenant->id)
            ->whereNull('parent_id')
            ->where('id', '!=', $chartOfAccount->id)
            ->orderBy('name')
            ->get();

        return view('tenant.chart-of-accounts.edit', compact('chartOfAccount', 'tenant', 'parentAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ChartOfAccount $chartOfAccount, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255', 'unique:chart_of_accounts,code,'.$chartOfAccount->id.',id,tenant_id,'.$tenant->id],
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'category' => 'nullable|in:current_asset,fixed_asset,current_liability,long_term_liability,equity,revenue,expense,cost_of_sales',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
        ]);

        $chartOfAccount->update($validated);

        return redirect()->route('tenant.chart-of-accounts.show', $chartOfAccount)
            ->with('success', 'Chart of account updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChartOfAccount $chartOfAccount)
    {
        // Check if account has children
        if ($chartOfAccount->children()->count() > 0) {
            return redirect()->route('tenant.chart-of-accounts.index')
                ->with('error', 'Cannot delete chart of account with child accounts.');
        }

        // Check if account is used in journal entries
        if ($chartOfAccount->journalLines()->count() > 0) {
            return redirect()->route('tenant.chart-of-accounts.index')
                ->with('error', 'Cannot delete chart of account that has been used in journal entries.');
        }

        $chartOfAccount->delete();

        return redirect()->route('tenant.chart-of-accounts.index')
            ->with('success', 'Chart of account deleted successfully.');
    }
}
