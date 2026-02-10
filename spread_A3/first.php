<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * PHPExcel (PHP 5.4 compatible)
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
$rows  = $sheet->toArray(null, true, true, true);

/* =========================
   Helper Function
========================= */
function clean_stock_mm($value, $decimals = 3) {

    $value = trim((string)$value);

    if ($value === '') {
        return '';
    }

    if ($value === '*') {
        return '*mm';
    }

    if (is_numeric($value)) {
        $num = round((float)$value, $decimals);
        $num = rtrim(rtrim(number_format($num, $decimals, '.', ''), '0'), '.');
        return $num . 'mm';
    }

    return $value;
}

/* =========================
   START HTML OUTPUT
========================= */
echo '<p>&nbsp;</p>';
echo '<h2>A3/SRA3 Die Templates</h2>';
echo '<table class="trhover" cellspacing="0" cellpadding="2" border="0">';
echo '<tbody>';

$currentSection = ''; // IMPORTANT

/* =========================
   Loop Excel Rows
========================= */
foreach ($rows as $rowIndex => $row) {

    if ($rowIndex == 1) continue; // skip header row

    $code         = trim($row['A']);
    $section      = trim($row['AO']); // Section name
    $width        = clean_stock_mm($row['G']);
    $height       = clean_stock_mm($row['H']);
    $total_labels = trim($row['O']);

    if ($code === '' || $section === '') continue;

    /* ===== Section Header ===== */
    if ($section !== $currentSection) {

        if ($currentSection !== '') {
            echo '<tr><td><p> </p></td></tr>';
        }

        $anchor = strtolower(str_replace([' ', '/'], '', $section));

        echo "
        <tr>
            <th colspan='2' style='text-align:left;'>
                <a name='{$anchor}'>{$section}</a>
            </th>
        </tr>";

        $currentSection = $section;
    }

    /* ===== Label Size ===== */
    if ($width !== '' && $height !== '') {
        $labelSize = "{$width} x {$height}";
    } else {
        $labelSize = $width;
    }

    /* ===== Data Row ===== */
   echo "
        <tr>
            <td>{$code} - Label Size {$width} x {$height} - {$total_labels} label per sheet</td>
            <td>
                <a href='/images/templates-a3/{$code}.pdf' target='_blank'>
                    Download {$code} Template
                </a>
            </td>
        </tr>";
}

/* =========================
   END HTML OUTPUT
========================= */
echo '</tbody>';
echo '</table>';
echo '<p>&nbsp;</p>';
