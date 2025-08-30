<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUpcommingExpenseIncomeRequest;
use App\Http\Requests\UpdateUpcommingExpenseIncomeRequest;
use App\Models\ExpenseIncomeAccount;
use App\Models\UpcommingExpenseIncome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Carbon;

class UpcommingExpenseIncomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = UpcommingExpenseIncome::query();

        // ---- Filters ----
        // Title/description search
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Type (income|expense)
        if (in_array($request->input('type'), ['income', 'expense'], true)) {
            $query->where('type', $request->input('type'));
        }

        // Linked account
        if ($request->filled('eia_id')) {
            $query->where('eia_id', (int) $request->input('eia_id'));
        }

        // Date range (inclusive)
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        if ($dateFrom && $dateTo) {
            try {
                $from = Carbon::parse($dateFrom)->startOfDay()->toDateString();
                $to   = Carbon::parse($dateTo)->endOfDay()->toDateString();
                $query->whereBetween('date', [$from, $to]);
            } catch (\Throwable $e) { /* ignore parse errors */
            }
        } elseif ($dateFrom) {
            try {
                $from = Carbon::parse($dateFrom)->startOfDay()->toDateString();
                $query->whereDate('date', '>=', $from);
            } catch (\Throwable $e) { /* ignore */
            }
        } elseif ($dateTo) {
            try {
                $to = Carbon::parse($dateTo)->endOfDay()->toDateString();
                $query->whereDate('date', '<=', $to);
            } catch (\Throwable $e) { /* ignore */
            }
        }

        $upcommingExpIncs = $query
            ->latest()
            ->paginate(15)
            ->appends($request->query());

        // Options for "Linked Account" filter
        $accounts = ExpenseIncomeAccount::select('id', 'name')
            ->orderBy('name')
            ->get();

        $filters = [
            'search'   => $request->input('search'),
            'type'     => $request->input('type'),
            'eia_id'   => $request->input('eia_id'),
            'date_from' => $request->input('date_from'),
            'date_to'  => $request->input('date_to'),
        ];

        return Inertia::render('UpcommingExpInc/Index', [
            'upcommingExpIncs' => $upcommingExpIncs,
            'filters'          => $filters,
            'accounts'         => $accounts, // for filter dropdown + name lookup
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $expIncAccounts = ExpenseIncomeAccount::all();
        return Inertia::render('UpcommingExpInc/Create', compact('expIncAccounts'));
    }


    /**
     * Store a newly created resource in storage.
     */
    // app/Http/Controllers/UpcommingExpenseIncomeController.php

    public function store(StoreUpcommingExpenseIncomeRequest $request)
    {
        // Validated payload (now includes amount & currency)
        $data = $request->validated();

        // Normalize optional FK ('' → null)
        $data['eia_id'] = $request->filled('eia_id') ? (int) $request->input('eia_id') : null;

        // Coerce amount to 2dp just to be explicit
        $data['amount'] = number_format((float) $data['amount'], 2, '.', '');

        // Ensure target folder exists: storage/app/public/upcoming
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        if (! $disk->exists('upcoming')) {
            $disk->makeDirectory('upcoming');
        }

        // Collect and store uploaded files
        $paths = [];
        $files = $request->file('attachments', []); // may be [] or UploadedFile[]

        if ($files instanceof \Illuminate\Http\UploadedFile) {
            $files = [$files];
        }

        if (is_array($files)) {
            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $paths[] = $file->store('upcoming', 'public');
                }
            }
        }

        // Persist paths as JSON array (or null if none)
        $data['attachments'] = $paths ?: null;

        \App\Models\UpcommingExpenseIncome::create($data);

        return redirect()
            ->route('upcomming-expense-income.index')
            ->with('success', 'Upcoming income/expense created successfully.');
    }




    /**
     * Display the specified resource.
     */
    public function show(UpcommingExpenseIncome $upcomming_expense_income)
    {
        return Inertia::render('UpcommingExpInc/Show', compact('upcomming_expense_income'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UpcommingExpenseIncome $upcomming_expense_income)
    {
        // Normalize attachments to array for the UI
        $attachments = is_array($upcomming_expense_income->attachments)
            ? $upcomming_expense_income->attachments
            : (empty($upcomming_expense_income->attachments) ? [] : (array) $upcomming_expense_income->attachments);

        // Provide accounts for the <select>
        $expIncAccounts = ExpenseIncomeAccount::select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('UpcommingExpInc/Edit', [
            'upcomming_expense_income' => [
                'id'          => $upcomming_expense_income->id,
                'title'       => $upcomming_expense_income->title,
                'description' => $upcomming_expense_income->description,
                'eia_id'      => $upcomming_expense_income->eia_id,
                'date'        => $upcomming_expense_income->date?->toDateString() ?? $upcomming_expense_income->date,
                'type'        => $upcomming_expense_income->type,
                'attachments' => $attachments, // array of "upcoming/...."
                // NEW
                'amount'      => $upcomming_expense_income->amount,
                'currency'    => $upcomming_expense_income->currency,
            ],
            'expIncAccounts' => $expIncAccounts,
        ]);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateUpcommingExpenseIncomeRequest $request,
        UpcommingExpenseIncome $upcomming_expense_income
    ) {
        $data = $request->validated();

        // Normalize nullable FK
        if (empty($data['eia_id'])) {
            $data['eia_id'] = null;
        }

        // Explicitly normalize amount (force 2dp)
        if (isset($data['amount'])) {
            $data['amount'] = number_format((float) $data['amount'], 2, '.', '');
        }

        // Optional new files — if provided, append to existing
        $newPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ((array) $request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $newPaths[] = $file->store('upcoming', 'public');
                }
            }
        }

        if (!empty($newPaths)) {
            $existing = (array) ($upcomming_expense_income->attachments ?? []);
            $data['attachments'] = array_values(array_unique(array_merge($existing, $newPaths)));
        } else {
            unset($data['attachments']); // keep existing attachments
        }

        $upcomming_expense_income->update($data);

        return redirect()
            ->route('upcomming-expense-income.index')
            ->with('success', 'Upcoming item updated successfully.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UpcommingExpenseIncome $upcomming_expense_income)
    {
        // (Optional) remove stored files; comment these 3 lines if you don’t want to delete files
        foreach ((array) $upcomming_expense_income->attachments as $path) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
        }

        $upcomming_expense_income->delete();

        return redirect()
            ->route('upcomming-expense-income.index')
            ->with('success', 'Upcoming item deleted.');
    }

    public function destroyAttachment(UpcommingExpenseIncome $upcomming_expense_income, int $index)
    {
        $attachments = $upcomming_expense_income->attachments ?? [];

        if (!is_array($attachments) || !isset($attachments[$index])) {
            return back()->withErrors(['attachments' => 'Attachment not found.']);
        }

        $path = $attachments[$index];

        // Delete from disk
        Storage::disk('public')->delete($path);

        // Remove from DB array
        unset($attachments[$index]);
        $upcomming_expense_income->attachments = array_values($attachments);
        $upcomming_expense_income->save();

        return back()->with('success', 'Attachment deleted.');
    }
}
