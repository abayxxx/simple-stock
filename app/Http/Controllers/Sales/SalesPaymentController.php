<?php

// app/Http/Controllers/Sales/SalesPaymentController.php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\SalesInvoice;
use App\Models\SalesPayment;
use App\Models\SalesReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SalesPaymentController extends Controller
{
    public function index()
    {
        return view('sales.payments.index');
    }

    public function datatable(Request $request)
    {
        // Fitur filter berdasarkan tanggal, customer, atau lainnya bisa ditambahkan di sini
        $awal          = $request->periode_awal;
        $akhir         = $request->periode_akhir;
        $customerId    = $request->customer_id;     // from #filter_customer


        $query = SalesPayment::with('customer', 'user')->orderByDesc('id');

        if ($awal && $akhir) {
            $query->whereBetween('tanggal', [$awal . ' 00:00:00', $akhir . ' 23:59:59']);
        }

        if ($customerId) {
            $query->where('company_profile_id', $customerId);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('tanggal', fn($r) => tanggal_indo($r->tanggal))
            ->addColumn('customer', fn($r) => $r->customer->name ?? '-')
            ->addColumn('jumlah_nota', fn($r) => $r->items->count())
            ->addColumn('total_bayar', fn($r) => number_format($r->items->sum('sub_total'), 2, ',', '.'))
            ->addColumn('aksi', function ($r) {
                return view('sales.payments.partials.aksi', ['row' => $r])->render();
            })
            ->filterColumn('customer', function ($query, $keyword) {
                $query->whereHas('customer', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('tanggal', function ($query, $keyword) {
                $query->whereDate('tanggal', 'like', "%{$keyword}%");
            })
            ->filterColumn('total_bayar', function ($query, $keyword) {
                $query->whereHas('items', function ($q) use ($keyword) {
                    $q->whereRaw('CAST(sub_total AS CHAR) LIKE ?', ["%{$keyword}%"]);
                });
            })
            ->filterColumn('jumlah_nota', function ($query, $keyword) {
                $query->whereHas('items', function ($q) use ($keyword) {
                    $q->havingRaw('COUNT(*) = ?', [$keyword]);
                });
            })
            ->rawColumns(['customer', 'aksi'])
            ->make(true);
    }

    public function create()
    {
        $customers = CompanyProfile::orderBy('name')
            ->where('relationship','!=', 'supplier') // Hanya customer
            ->get();
        return view('sales.payments.create', compact('customers'));
    }

    public function tarikNotaOptions(Request $request)
    {
        $customerId = $request->input('company_profile_id');

        // FAKTUR: Yang masih ada sisa tagihan
        $invoices = SalesInvoice::where('sisa_tagihan', '>', 0)
            ->when($customerId, function ($q) use ($customerId) {
                $q->where('company_profile_id', $customerId);
            })
            ->get();

        // RETUR: Yang belum terpakai (atau bisa buat query khusus sesuai bisnis proses)
        $returns = SalesReturn::where('grand_total', '>', 0)
            ->when($customerId, function ($q) use ($customerId) {
                $q->where('company_profile_id', $customerId);
            })
            ->get();

        return response()->json([
            'invoices' => $invoices,
            'returns' => $returns,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'kode'               => 'nullable|string|max:50|unique:sales_payments,kode',
                'auto_kode'          => 'nullable|in:1',
                'tanggal'            => 'required|date',
                'company_profile_id' => 'required|exists:company_profiles,id',
                'catatan'            => 'nullable|string',
                'items'              => 'required|array|min:1',

                // item fields
                'items.*.tipe_nota'        => 'required|in:FAKTUR,RETUR',
                'items.*.sales_invoice_id' => 'nullable|exists:sales_invoices,id',
                'items.*.sales_return_id'  => 'nullable|exists:sales_returns,id',
                'items.*.nilai_nota'       => 'nullable|numeric|min:0',
                'items.*.sisa'             => 'nullable|numeric|min:0',
                'items.*.tunai'            => 'nullable|numeric|min:0',
                'items.*.bank'             => 'nullable|numeric|min:0',
                'items.*.giro'             => 'nullable|numeric|min:0',
                'items.*.cndn'             => 'nullable|numeric|min:0',
                'items.*.retur'            => 'nullable|numeric|min:0',
                'items.*.panjar'           => 'nullable|numeric|min:0',
                'items.*.lainnya'          => 'nullable|numeric|min:0',
                'items.*.sub_total'        => 'nullable|numeric|min:0', // will be overwritten server-side
                'items.*.pot_ke_no'        => 'nullable|string',
                'items.*.catatan'          => 'nullable|string|max:255',
            ]);
        } catch (\Throwable $th) {
            return back()->withErrors($th->getMessage())->withInput();
        }

        // kode handling
        if ($request->boolean('auto_kode')) {
            $data['kode'] = self::generateKode();
        } else {
            if (empty($data['kode']) || $data['kode'] === '(auto)') {
                return back()->withInput()->withErrors(['kode' => 'Kode harus diisi jika mode auto tidak dipilih']);
            }
        }

        // Extra conditional validation: require the right foreign key per tipe
        $v = \Validator::make($data, []);
        $v->after(function ($v) use ($data) {
            foreach ($data['items'] as $i => $row) {
                if (($row['tipe_nota'] ?? null) === 'FAKTUR' && empty($row['sales_invoice_id'])) {
                    $v->errors()->add("items.$i.sales_invoice_id", 'Wajib diisi untuk tipe FAKTUR.');
                }
                if (($row['tipe_nota'] ?? null) === 'RETUR' && empty($row['sales_return_id'])) {
                    $v->errors()->add("items.$i.sales_return_id", 'Wajib diisi untuk tipe RETUR.');
                }
            }
        });
        $v->validate();

        DB::transaction(function () use ($data) {
            $items = $data['items'];
            unset($data['items']);

            $payment = SalesPayment::create($data + ['user_id' => auth()->id()]);

            $totalPembayaran = 0.0;

            foreach ($items as $row) {
                // Compute subtotal on server
                $tunai   = (float)($row['tunai']   ?? 0);
                $bank    = (float)($row['bank']    ?? 0);
                $giro    = (float)($row['giro']    ?? 0);
                $cndn    = (float)($row['cndn']    ?? 0);
                $panjar  = (float)($row['panjar']  ?? 0);
                $lainnya = (float)($row['lainnya'] ?? 0);
                $retur   = (float)($row['retur']   ?? 0);

                // Your UI adds RETUR into subtotal; mirror that here:
                $computedSubtotal = $tunai + $bank + $giro + $cndn + $panjar + $lainnya ;

                // Cap by sisa if present (avoid overpay)
                // $sisa = isset($row['sisa']) ? (float)$row['sisa'] : null;
                // if ($sisa !== null) {
                //     $computedSubtotal = min($computedSubtotal, $sisa);
                // }

                // Write back the computed subtotal
                $row['sub_total'] = $computedSubtotal+ $retur; // Include retur in subtotal

                // Create item
                $item = $payment->items()->create($row);

                // Apply to linked doc with locking
                if ($row['tipe_nota'] === 'FAKTUR' && !empty($row['sales_invoice_id'])) {
                    $invoice = SalesInvoice::lockForUpdate()->find($row['sales_invoice_id']);
                    if ($invoice) {
                        $invoice->total_bayar  = ($invoice->total_bayar ?? 0) + $computedSubtotal;
                        $invoice->sisa_tagihan = max(0, ($invoice->sisa_tagihan ?? 0) - ($computedSubtotal + $retur));
                        // If you track retur at invoice level (you did in your sales update method)
                        $invoice->total_retur  = ($invoice->total_retur ?? 0) + $retur;

                        $invoice->save();
                    }
                }

                if ($row['tipe_nota'] === 'RETUR' && !empty($row['sales_return_id'])) {
                    $ret = SalesReturn::lockForUpdate()->find($row['sales_return_id']);
                    if ($ret) {
                        $ret->total_bayar  = ($ret->total_bayar ?? 0) + $computedSubtotal;
                        $ret->sisa_tagihan = max(0, ($ret->sisa_tagihan ?? 0) - ($computedSubtotal + $retur));
                        // If returns also track retur component, mirror as needed:
                        $ret->total_retur   = ($ret->total_retur ?? 0) + $retur;

                        $ret->save();
                    }
                }

                $totalPembayaran += $computedSubtotal;
            }

            // Idempotent: set header total from sum of items
            $payment->save();
        });

        return redirect()
            ->route('sales.payments.index')
            ->with('success', 'Pembayaran penjualan berhasil dibuat.');
    }


    protected static function generateKode()
    {
        $prefix = 'PP.' . date('ym') . '.';
        $last = SalesPayment::where('kode', 'like', $prefix . '%')->max('kode');
        $urut = $last ? (int)substr($last, 8) + 1 : 1;
        return $prefix . str_pad($urut, 5, '0', STR_PAD_LEFT);
    }

    public function show(SalesPayment $payment)
    {
        $payment->load('customer', 'items.invoice', 'items.return');
        return view('sales.payments.show', compact('payment'));
    }

    public function edit(SalesPayment $payment)
    {
        $payment->load('customer', 'items.invoice', 'items.return');
        $customers = CompanyProfile::orderBy('name')
            ->where('relationship', '!=', 'supplier') // Hanya customer
            ->get();
        return view('sales.payments.edit', compact('payment', 'customers'));
    }

    public function update(Request $request, SalesPayment $payment)
    {
        try {
            $data = $request->validate([
                'kode' => 'nullable|string|max:50|unique:sales_payments,kode,' . $payment->id,
                'tanggal' => 'required|date',
                'company_profile_id' => 'required|exists:company_profiles,id',
                'catatan' => 'nullable|string',
                'items' => 'required|array|min:1',

                // Existing item id (nullable for new rows)
                'items.*.id' => 'nullable|integer',

                // Item fields
                'items.*.tipe_nota' => 'required|in:FAKTUR,RETUR',
                'items.*.sales_invoice_id' => 'nullable|exists:sales_invoices,id',
                'items.*.sales_return_id' => 'nullable|exists:sales_returns,id',
                'items.*.nilai_nota' => 'nullable|numeric',
                'items.*.sisa' => 'nullable|numeric',
                'items.*.tunai' => 'nullable|numeric',
                'items.*.bank' => 'nullable|numeric',
                'items.*.giro' => 'nullable|numeric',
                'items.*.cndn' => 'nullable|numeric',
                'items.*.retur' => 'nullable|numeric',
                'items.*.panjar' => 'nullable|numeric',
                'items.*.lainnya' => 'nullable|numeric',
                'items.*.sub_total' => 'nullable|numeric',
                'items.*.pot_ke_no' => 'nullable|string',
                'items.*.catatan' => 'nullable|string|max:255',
            ]);
        } catch (\Throwable $th) {
            return back()->withErrors($th->getMessage())->withInput();
        }

        DB::transaction(function () use ($data, $payment) {
            $payment->update($data + ['user_id' => auth()->id()]);

            // Load existing items once
            $payment->load('items');
            $existingById = $payment->items->keyBy('id');

            // Track ids we saw in the incoming payload
            $seenIds = [];

            // Helper to apply +/- adjustments to an Invoice/Return
            $applyAdjustments = function ($tipeNota, $salesInvoiceId, $salesReturnId, $subDiff, $returDiff) {
                if ($subDiff == 0 && $returDiff == 0) return;

                if ($tipeNota === 'FAKTUR' && $salesInvoiceId) {
                    $invoice = SalesInvoice::lockForUpdate()->find($salesInvoiceId);
                    if ($invoice) {
                        // subDiff > 0 means add to total_bayar and reduce sisa_tagihan
                        $invoice->total_bayar = ($invoice->total_bayar ?? 0) + $subDiff;
                        $invoice->sisa_tagihan = max(0, ($invoice->sisa_tagihan ?? 0) - $subDiff);
                        $invoice->total_retur  = ($invoice->total_retur ?? 0) + $returDiff;
                        $invoice->save();
                    }
                } elseif ($tipeNota === 'RETUR' && $salesReturnId) {
                    $ret = SalesReturn::lockForUpdate()->find($salesReturnId);
                    if ($ret) {
                        $ret->total_bayar   = ($ret->total_bayar ?? 0) + $subDiff;
                        $ret->sisa_tagihan  = max(0, ($ret->sisa_tagihan ?? 0) - $subDiff);
                        $ret->total_retur   = ($ret->total_retur ?? 0) + $returDiff;
                        $ret->save();
                    }
                }
            };

            foreach ($data['items'] as $payload) {
                $id = $payload['id'] ?? null;

                // Normalize numeric fields to 0 when null
                $newSub  = (float)($payload['sub_total'] ?? 0);
                $newRet  = (float)($payload['retur'] ?? 0);
                $tipe    = $payload['tipe_nota'];
                $invId   = $payload['sales_invoice_id'] ?? null;
                $retId   = $payload['sales_return_id'] ?? null;

                if ($id && isset($existingById[$id])) {
                    // UPDATE existing item
                    $seenIds[] = $id;
                    $old = $existingById[$id];

                    $oldSub = (float)($old->sub_total ?? 0);
                    $oldRet = (float)($old->retur ?? 0);

                    $tipeChanged  = $old->tipe_nota !== $tipe;
                    $linkChanged  = ($old->sales_invoice_id != $invId) || ($old->sales_return_id != $retId);

                    if ($tipeChanged || $linkChanged) {
                        // Treat as DELETE old + CREATE new

                        // 1) reverse old
                        $applyAdjustments($old->tipe_nota, $old->sales_invoice_id, $old->sales_return_id, -$oldSub, -$oldRet);

                        // 2) apply new
                        $applyAdjustments($tipe, $invId, $retId, $newSub, $newRet);

                        // 3) update the row with all new values
                        $old->fill($payload);
                        $old->save();
                    } else {
                        // Same link/tipe: apply only diffs
                        $subDiff = $newSub - $oldSub;
                        $retDiff = $newRet - $oldRet;

                        if ($subDiff != 0 || $retDiff != 0) {
                            $applyAdjustments($tipe, $invId, $retId, $subDiff, $retDiff);
                        }

                        // Save the item changes (including any other columns)
                        $old->fill($payload);
                        $old->save();
                    }
                } else {
                    // CREATE new item
                    $created = $payment->items()->create($payload);

                    // Apply positive adjustments for the new row
                    $applyAdjustments($tipe, $invId, $retId, $newSub, $newRet);

                    if ($created->id) {
                        $seenIds[] = $created->id;
                    }
                }
            }

            // DELETE items removed from the payload (reverse their effects)
            $toDelete = $payment->items->whereNotIn('id', $seenIds);
            foreach ($toDelete as $old) {
                $oldSub = (float)($old->sub_total ?? 0);
                $oldRet = (float)($old->retur ?? 0);
                $applyAdjustments($old->tipe_nota, $old->sales_invoice_id, $old->sales_return_id, -$oldSub, -$oldRet);
                $old->delete();
            }

            // Save payment (if you compute any aggregates at payment-level elsewhere)
            $payment->save();
        });

        return redirect()
            ->route('sales.payments.index')
            ->with('success', 'Pembayaran penjualan berhasil diperbarui.');
    }

    public function destroy(SalesPayment $payment)
    {
        DB::transaction(function () use ($payment) {

            // Update invoice or return sisa_tagihan/grand_total (optional, add your logic here)
            foreach ($payment->items as $item) {
                //check if item still linked to invoice or return
                if (!$item->invoice && !$item->return) {
                    continue; // Skip if no link
                }
                if ($item->tipe_nota === 'FAKTUR' && !empty($item->sales_invoice_id)) {
                    $invoice = SalesInvoice::find($item->sales_invoice_id);
                    $invoice->total_bayar -= ($item->sub_total - $item->retur);
                    $invoice->sisa_tagihan = max(0, $invoice->sisa_tagihan + $item->sub_total);
                    $invoice->total_retur -= $item->retur;
                    $invoice->save();
                }
                if ($item->tipe_nota === 'RETUR' && !empty($item->sales_return_id)) {
                    $return = SalesReturn::find($item->sales_return_id);
                    $return->total_bayar -= ($item->sub_total - $item->retur);
                    $return->sisa_tagihan = max(0, $return->sisa_tagihan + $item->sub_total);
                    $return->total_retur -= $item->retur;
                    $return->save();
                }
            }

            // Hapus semua item terkait
            $payment->items()->delete();

            // Hapus pembayaran
            $payment->delete();
        });

        return redirect()->route('sales.payments.index')->with('success', 'Pembayaran penjualan berhasil dihapus.');
    }

    public function filterOptions(Request $request)
    {
        $awal  = $request->awal;
        $akhir = $request->akhir;

        // Ambil customer dan kolektor berdasarkan periode
        $query = SalesPayment::query();
        if ($awal && $akhir) {
            $query->whereBetween('tanggal', [$awal, $akhir]);
        }

        $customerIds = $query->distinct()->pluck('company_profile_id');

        // Ambil data customer dan kolektor
        $customers = CompanyProfile::whereIn('id', $customerIds)->orderBy('name')->get(['id', 'name']);


        return response()->json([
            'customers' => $customers,
        ]);
    }
}
