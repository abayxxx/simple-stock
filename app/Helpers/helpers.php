<?php



function tanggal_indo($tgl)
{
    if (!$tgl) return '';
    $bulan = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'Mei',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Agu',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des'
    ];
    $exp = explode('-', $tgl);
    return $exp[2] . ' ' . $bulan[(int)$exp[1]] . ' ' . $exp[0];
}

if (!function_exists('terbilang')) {
    function terbilang($angka)
    {
        $angka = abs($angka);
        $baca = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
        if ($angka < 0) {
            return "Minus " . terbilang(-$angka);
        }
        
        if ($angka == 0) {
            return "Nol";
        }

        $terbilang = "";
        if ($angka < 12) {
            $terbilang = " " . $baca[$angka];
        } else if ($angka < 20) {
            $terbilang = terbilang($angka - 10) . " Belas ";
        } else if ($angka < 100) {
            $terbilang = terbilang($angka / 10) . " Puluh " . terbilang($angka % 10);
        } else if ($angka < 200) {
            $terbilang = " Seratus" . terbilang($angka - 100);
        } else if ($angka < 1000) {
            $terbilang = terbilang($angka / 100) . " Ratus " . terbilang($angka % 100);
        } else if ($angka < 2000) {
            $terbilang = " Seribu" . terbilang($angka - 1000);
        } else if ($angka < 1000000) {
            $terbilang = terbilang($angka / 1000) . " Ribu " . terbilang($angka % 1000);
        } else if ($angka < 1000000000) {
            $terbilang = terbilang($angka / 1000000) . " Juta " . terbilang($angka % 1000000);
        } else if ($angka < 1000000000000) {
            $terbilang = terbilang($angka / 1000000000) . " Miliar " . terbilang($angka % 1000000000);
        }
        return trim($terbilang);
    }
}

// function to check if a sales invoice is already in the table sales receipts
function isSalesInvoiceLocked($invoiceId)
{
    return \App\Models\SalesReceipt::with('receiptItems')
        ->whereHas('receiptItems', function ($query) use ($invoiceId) {
            $query->where('sales_invoice_id', $invoiceId);
        })
        ->where('is_locked', true)
        ->exists();
}

function isSuperAdmin()
{
    return auth()->check() && auth()->user()->role === 'superadmin';
}

function isAdmin()
{
    return auth()->check() && auth()->user()->role === 'admin';
}

function isSales()
{
    return auth()->check() && auth()->user()->role === 'sales';
}



if (!function_exists('extractDocumentCodes')) {
    /**
     * Ambil semua kode dokumen dari string.
     *
     * @param string $text
     * @param array|null $prefixes  // Contoh: ['RT', 'SI', 'PI'] atau null untuk bebas
     * @return array
     */
    function extractDocumentCodes(string $text, array $prefixes = null): array
    {
        if ($prefixes && count($prefixes) > 0) {
            // Gabungkan semua prefix jadi 1 pola regex
            $pattern = '/(?:' . implode('|', array_map('preg_quote', $prefixes)) . ')\.\d{4}\.\d{5}/';
        } else {
            // Tanpa filter prefix → semua huruf kapital 2 huruf
            $pattern = '/[A-Z]{2}\.\d{4}\.\d{5}/';
        }

        preg_match_all($pattern, $text, $matches);
        return $matches[0] ?? [];
    }
}
