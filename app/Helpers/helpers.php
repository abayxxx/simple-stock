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
