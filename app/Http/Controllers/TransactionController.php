<?php

namespace App\Http\Controllers;


use App\Models\Monthly_budget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request){
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        abort_if(
            $month < 1 || $month > 12 || $year < 2000 || $year > 9999,
            422,
            'Bulan tidak valid'
        );

        $budget = Monthly_budget::query()
            ->where('user_id', $request->user()->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if (!$budget){
            return view('transactions', [
                'budget'=>null,
                'transactions'=>collect(),
                'totalIncome'=>0,
                'totalExpense'=>0,
                'balanceRemaining'=>0
            ]);
        }

        $summary = $budget->transactions()
            ->selectRaw("
                COALESCE(
                    SUM(CASE WHEN type = 'income'
                        THEN amount ELSE 0 END),
                    0
                ) AS total_income,
                 COALESCE(
                    SUM(CASE WHEN type = 'expense'
                        THEN amount ELSE 0 END),
                    0
                ) AS total_expense,
            ")
            ->first();
        
            $totalIncome = (int)$summary->total_income;
            $totalExpense = (int)$summary->total_expense;

            $remainingBalance = (int)$budget->amount + $totalIncome + $totalExpense;

            $transactions = $budget->transactions()
                ->orderByDesc('transaction_date')
                ->orderByDesc('id')
                ->paginate(10);

            return view('transaction.index', compact('budget', 'transaction', 'totalIncome', 'totalExpense', 'remainingBalance'));
            
    }

    public function store(Request $request){
        $validated = $request([
            'monthly_budget_id'=>['require', 'integer', 'exist:monthly_budgets_id'],
            'description'=>['required', 'string', 'max:255'],
            'amount'=>['required', 'integer', 'min:1'],
            'type'=>['required', 'in:income,expense'],
            'transaction_date'=>['required', 'date_format:Y-m-d']
        ]);

        $budget = Monthly_budget::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($validated['monthly_budget_id']);
        
            $transactionDate = \Illuminate\Support\Carbon::createFromFormat(
                '!Y-m-d',
                $validated['transaction_date']
            );

            if($transactionDate->month !== (int) $budget->month || $transactionDate->year !== (int) $budget->year){
                return back()->withErrors(['transaction_date'=>'Tanggal harus sesuai dengan anggaran bulanan'])->withInput();
            }

        $budget->transactions()->create([
            'description'=> $validated['description'],
            'amount' => $validated['amount'],
            'type' => $validated['type'],
            'transaction_date' => $validated['transaction_date']
        ]);

        return redirect()->route('transaction.index', [
            'month'=>$budget->month,
            'year'=>$budget->year
        ])->with('success', 'Transaksi berhasil di tambahkan')
    }


}
