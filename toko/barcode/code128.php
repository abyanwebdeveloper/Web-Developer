<?php
/**
 * Generator barcode Code 128 (subset B) murni PHP — menghasilkan SVG.
 * Tidak butuh library eksternal; cocok untuk mencetak label produk toko.
 */

/** Tabel pola Code 128: lebar batang (dalam modul) untuk nilai 0..106. */
function code128_patterns(): array
{
    static $patterns = null;
    if ($patterns === null) {
        $patterns = [
            '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
            '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
            '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
            '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
            '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
            '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
            '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
            '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
            '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
            '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
            '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
        ];
    }
    return $patterns;
}

/**
 * Bangun string modul (1 = batang, 0 = spasi) untuk teks Code 128B.
 * Mengembalikan false bila ada karakter di luar tabel Code B (32..126).
 */
function code128_modules(string $text): string|false
{
    if ($text === '') {
        return false;
    }
    $patterns = code128_patterns();
    $values = [104]; // Start Code B
    foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) as $char) {
        $code = mb_ord($char, 'UTF-8');
        if ($code < 32 || $code > 126) {
            return false; // karakter di luar Code B
        }
        $values[] = $code - 32;
    }
    // Checksum: start + Σ(posisi × nilai) mod 103
    $sum = 104;
    foreach (array_slice($values, 1) as $i => $value) {
        $sum += $value * ($i + 1);
    }
    $values[] = $sum % 103;
    $values[] = 106; // Stop

    $modules = '';
    foreach ($values as $value) {
        $pattern = $patterns[$value];
        $isBar = true;
        foreach (str_split($pattern) as $width) {
            $modules .= str_repeat($isBar ? '1' : '0', (int) $width);
            $isBar = !$isBar;
        }
    }
    return $modules . '0'; // quiet zone akhir 1 modul
}

/**
 * Render teks sebagai SVG barcode Code 128B.
 *
 * @param int $moduleWidth Lebar 1 modul dalam piksel (faktor ketajaman cetak).
 * @param int $height Tinggi batang.
 * @param bool $showText Tampilkan teks kode di bawah barcode.
 */
function code128_svg(string $text, int $moduleWidth = 2, int $height = 46, bool $showText = true): string
{
    $text = strtoupper(trim($text));
    $modules = code128_modules($text);
    if ($modules === false) {
        return '<span style="color:#b03a2e;font-size:12px">Kode tidak valid untuk Code 128B: ' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</span>';
    }
    $quiet = 10; // quiet zone standar 10 modul di kiri
    $totalModules = strlen($modules) + $quiet * 2;
    $width = $totalModules * $moduleWidth;
    $textHeight = $showText ? 15 : 0;
    $svgHeight = $height + $textHeight;

    $rects = '';
    $x = $quiet * $moduleWidth;
    $run = 0;
    foreach (str_split($modules) as $module) {
        if ($module === '1') {
            $run++;
        } else {
            if ($run > 0) {
                $rects .= '<rect x="' . ($x - $run * $moduleWidth) . '" y="0" width="' . ($run * $moduleWidth) . '" height="' . $height . '"/>';
                $run = 0;
            }
            $x += $moduleWidth;
        }
    }
    if ($run > 0) {
        $rects .= '<rect x="' . ($x - $run * $moduleWidth) . '" y="0" width="' . ($run * $moduleWidth) . '" height="' . $height . '"/>';
    }

    $label = $showText
        ? '<text x="' . ($width / 2) . '" y="' . ($height + 12) . '" text-anchor="middle" font-family="Consolas, monospace" font-size="12" fill="#000">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</text>'
        : '';

    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $svgHeight . '" viewBox="0 0 ' . $width . ' ' . $svgHeight . '" role="img" aria-label="Barcode ' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '">'
        . '<rect width="' . $width . '" height="' . $svgHeight . '" fill="#fff"/>'
        . '<g fill="#000" transform="translate(0,' . ($showText ? 0 : 0) . ')">' . $rects . '</g>'
        . $label
        . '</svg>';
}
