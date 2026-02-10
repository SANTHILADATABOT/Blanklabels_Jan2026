<?php

if (isset($_SERVER["GEOIP_COUNTRY_CODE"]) && $_SERVER["GEOIP_COUNTRY_CODE"] == '') {
    // Block access
    die("Access blocked from China");
}



ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * PHPExcel (PHP 5.4 compatible)
 * Folder must exist:
 * /blank/spread_A3/PHPExcel/Classes/PHPExcel.php
 */
require_once __DIR__ . '/PHPExcel/Classes/PHPExcel.php';

/* =========================
   Upload Form
========================= */
if (!isset($_FILES['excel_file'])) {
?>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="excel_file" required>
    <button type="submit">Upload & Generate</button>
</form>
<?php
exit;
}

/* =========================
   Load Excel
========================= */
$filePath = $_FILES['excel_file']['tmp_name'];

try {
    $excel = PHPExcel_IOFactory::load($filePath);
} catch (Exception $e) {
    die("Excel Load Error: " . $e->getMessage());
}

$sheet = $excel->getActiveSheet();

/**
 * A = column letters (safe & clear)
 */
$rows = $sheet->toArray(null, false, true, true);

echo "<pre>";
$mysqli = new mysqli(
    "localhost",
    "stickerc_blank_staging",
    "G=?L=HwxQq6&",
    "stickerc_blank_staging"
);

if ($mysqli->connect_errno) {
    die("DB Connection Failed: " . $mysqli->connect_error);
}


// function clean_stock_mm($value, $decimals = 3) {
//     if (!is_numeric($value)) return $value;
//     $num = round((float)$value, $decimals);
//     $num = rtrim(rtrim(number_format($num, $decimals, '.', ''), '0'), '.');
//     return $num . 'mm';
// }


function clean_stock_mm($value, $decimals = 3) {

    $value = trim((string)$value);

    // Empty value
    if ($value === '') {
        return '-';
    }

    // Star value
    if ($value === '*') {
        return '*mm';
    }

    // Numeric value
    if (is_numeric($value)) {
        $num = round((float)$value, $decimals);
        $num = rtrim(rtrim(number_format($num, $decimals, '.', ''), '0'), '.');
        return $num . 'mm';
    }

    // Anything else (text like Circle, Backslit, etc.)
    return $value;
}

foreach ($rows as $rowIndex => $row) {
    
   

    if ($rowIndex == 1) continue; // skip header row

    $code            = trim($row['A']);
    $page_size1      = trim($row['AQ']); // column 40
    $top_margin      =clean_stock_mm(trim($row['F']));
    $side_margin     = clean_stock_mm(trim($row['G']));
    $label_width     = clean_stock_mm(trim($row['H']));
    $label_height    = clean_stock_mm(trim($row['I']));
    $horizontal      = clean_stock_mm(trim($row['J']));
    $vertical        = clean_stock_mm(trim($row['K']));
    $corner          = clean_stock_mm(trim($row['L']));
    $label_down      = trim($row['O']);
    $label_across    = trim($row['N']);
    $total_labels    = trim($row['P']);
    $stock           = trim($row['W']);
    $bleed           = clean_stock_mm(trim($row['V']));
    $bs_fs           = clean_stock_mm(trim($row['AD']));

 $result = $mysqli->query(
        "SELECT products_id 
         FROM products 
         WHERE products_model='{$code}' 
         AND products_status='1' 
         LIMIT 1"
    );

    $products_id = '';

if ($result && $rowDb = $result->fetch_assoc()) {
    $products_id = $rowDb['products_id'];
}

$result && $result->free();

   
    if ($code === '') continue;

    echo "case '{$code}':sl='index.php?main_page=product_info&cPath=133&products_id={$products_id}';tm='{$top_margin}'; sm='{$side_margin}';lw='{$label_width}'; lh='{$label_height}';hp='{$horizontal}'; vp='{$vertical}';co='{$corner}'; na='{$label_across}'; nd='{$label_down}';tl='{$total_labels}'; ps='{$page_size1}'; sa='{$stock}';bw='{$bleed}'; pf='{$bs_fs}';break;\n";
}

echo "</pre>";
