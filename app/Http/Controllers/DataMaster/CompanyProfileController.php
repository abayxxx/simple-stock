<?php

namespace App\Http\Controllers\DataMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CompanyProfile;
use Yajra\DataTables\Facades\DataTables;


class CompanyProfileController extends Controller
{
    public function datatable(Request $request)
    {
        $data = CompanyProfile::query();

        return DataTables::of($data)
            ->addColumn('actions', function ($row) {
                return view('data_master.company_profiles.partials.actions', compact('row'))->render();
            })
            ->editColumn('relationship', function ($row) {
                return ucfirst($row->relationship);
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    // List all company profiles
    public function index()
    {
        $companyProfiles = CompanyProfile::all();
        return view('data_master.company_profiles.index', compact('companyProfiles'));
    }

    // Show creation form (if needed)
    public function create()
    {
        return view('data_master.company_profiles.create');
    }

    // Store a new company profile and its external data
    public function store(Request $request)
    {
        [$profileData, $externalData] = $this->validateAndExtract($request);

        // If 'code' is auto-generated in the model, remove from $profileData
        // unset($profileData['code']);

        $companyProfile = CompanyProfile::create($profileData);

        $companyProfile->externalData()->create($externalData);

        return redirect()->route('company_profiles.index')
            ->with('success', 'Company Profile created successfully.');
    }

    // Show a single company profile and its external data
    public function show(CompanyProfile $company_profile)
    {
        $company_profile->load('externalData');
        return view('data_master.company_profiles.show', compact('company_profile'));
    }

    // Show the edit form
    public function edit(CompanyProfile $company_profile)
    {
        $company_profile->load('externalData');
        return view('data_master.company_profiles.edit', compact('company_profile'));
    }

    // Update profile and its external data
    public function update(Request $request, CompanyProfile $company_profile)
    {
        [$profileData, $externalData] = $this->validateAndExtract($request, $company_profile->id);

        $company_profile->update($profileData);

        // Only update fields that are actually present in the request for external data
        if (!empty($externalData)) {
            $company_profile->externalData()->update($externalData);
        }

        return redirect()->route('company_profiles.index')
            ->with('success', 'Company Profile updated successfully.');
    }

    // Delete a company profile and its external data
    public function destroy(CompanyProfile $company_profile)
    {
        // Delete external data first or ensure cascade in DB migration
        $company_profile->externalData()->delete();
        $company_profile->delete();

        return redirect()->route('company_profiles.index')
            ->with('success', 'Company Profile deleted successfully.');
    }

    // API: get only the external data as JSON
    public function getExternalData(CompanyProfile $company_profile)
    {
        $externalData = $company_profile->externalData;
        return response()->json($externalData);
    }

    // API: update only external data (from JSON or AJAX)
    public function updateExternalData(Request $request, CompanyProfile $company_profile)
    {
        [, $externalData] = $this->validateAndExtract($request, $company_profile->id, $externalOnly = true);

        $company_profile->externalData()->update($externalData);

        return redirect()->route('company_profiles.show', $company_profile)
            ->with('success', 'External data updated successfully.');
    }

    /**
     * Validate request and separate profile and external data.
     * Also handles unique code logic for update.
     */
    private function validateAndExtract(Request $request, $companyId = null, $externalOnly = false)
    {
        $profileRules = [

            'name' => 'required|string',
            'address' => 'nullable|string',
            'spesific_location' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'website' => 'nullable|url',
            'relationship' => 'required|in:customer,supplier,other',
            'npwp' => 'nullable|string',
            'tax_invoice_to' => 'nullable|string',
            'tax_invoice_address' => 'nullable|string',
        ];

        $externalRules = [
            'total_receivable_now' => 'nullable|numeric',
            'unpaid_sales_invoices_count' => 'nullable|integer',
            'last_sales_date' => 'nullable|date',
            'giro_received' => 'nullable|numeric',
            'due_receivables' => 'nullable|numeric',
            'due_sales_invoices_count' => 'nullable|integer',
            'grand_total_sales' => 'nullable|numeric',
            'grand_total_sales_returns' => 'nullable|numeric',
            'total_debt_now' => 'nullable|numeric',
            'unpaid_purchase_invoices_count' => 'nullable|integer',
            'last_purchase_date' => 'nullable|date',
            'giro_paid' => 'nullable|numeric',
            'due_debt' => 'nullable|numeric',
            'due_purchase_invoices_count' => 'nullable|integer',
            'grand_total_purchases' => 'nullable|numeric',
            'grand_total_purchase_returns' => 'nullable|numeric',
        ];

        $rules = $externalOnly ? $externalRules : ($profileRules + $externalRules);
        $validated = $request->validate($rules);

        $externalFields = array_keys($externalRules);
        $externalData = [];

        foreach ($externalFields as $field) {
            if ($request->has($field)) {
                $val = $validated[$field];
                // For numeric/integer, set to 0 if empty
                if (in_array($field, [
                    'total_receivable_now',
                    'unpaid_sales_invoices_count',
                    'giro_received',
                    'due_receivables',
                    'due_sales_invoices_count',
                    'grand_total_sales',
                    'grand_total_sales_returns',
                    'total_debt_now',
                    'unpaid_purchase_invoices_count',
                    'giro_paid',
                    'due_debt',
                    'due_purchase_invoices_count',
                    'grand_total_purchases',
                    'grand_total_purchase_returns'
                ])) {
                    $externalData[$field] = ($val === null || $val === '' ? 0 : $val);
                } else {
                    $externalData[$field] = $val;
                }
            }
        }

        $profileData = [];
        if (!$externalOnly) {
            foreach ($profileRules as $field => $rule) {
                if (isset($validated[$field])) {
                    $profileData[$field] = $validated[$field];
                }
            }
        }

        return [$profileData, $externalData];
    }
}
