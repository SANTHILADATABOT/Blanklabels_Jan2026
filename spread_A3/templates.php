<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

session_start();

/* =============================
   PHPExcel (PHP 5.4 compatible)
============================= */
require_once __DIR__ . '/PHPExcel/Classes/PHPExcel.php';

/* =============================
   HELPERS
============================= */
function clean_stock_mm($value, $decimals = 3) {
    $value = trim((string)$value);

    if ($value === '') return '';
    if ($value === '*') return '*mm';

    if (is_numeric($value)) {
        $num = round((float)$value, $decimals);
        $num = rtrim(rtrim(number_format($num, $decimals, '.', ''), '0'), '.');
        return $num . 'mm';
    }
    return $value;
}

function buildOptionsHTML(array $options) {
    $html = '';
    foreach ($options as $groupLabel => $groupOptions) {
        if (empty($groupOptions)) continue;

        $html .= '<optgroup label="' . htmlspecialchars($groupLabel) . '">' . "\n";
        $html .= implode("\n", $groupOptions) . "\n";
        $html .= "</optgroup>\n";
    }
    return $html;
}

/* =============================
   1️⃣ UPLOAD FORM
============================= */
if (!isset($_FILES['excel_file'])) {
?>
<form method="post" enctype="multipart/form-data">
    <h2>Upload Excel</h2>
    <input type="file" name="excel_file" accept=".xlsx,.xls" required>
    <button type="submit">Upload & Show Options</button>
</form>
<?php
exit;
}

/* =============================
   2️⃣ READ EXCEL
============================= */
$filePath = $_FILES['excel_file']['tmp_name'];

try {
    $excel = PHPExcel_IOFactory::load($filePath);
} catch (Exception $e) {
    die("Excel Load Error: " . $e->getMessage());
}

$sheet = $excel->getActiveSheet();
$rows  = $sheet->toArray(null, true, true, false);

/* =============================
   3️⃣ BUILD OPTIONS
============================= */
$options = [
    'Full Sheets with Backslits'    => [],
    'Rectangles' => [],
    'Circles' => [],
    'Ovals' => [],
    'Bottles & Other Shapes' => []
];

foreach ($rows as $i => $data) {
    if ($i === 0 || empty($data[0])) continue;

    $code = trim($data[0]);              // A
    $shape = trim($data[3]);             // D
    $total_labels = trim($data[15]);     // P
    $selector_dimension = trim($data[33]); // AG
    $slits = trim($data[43]);             // AR

    if ($code === '') continue;


        // $optionLine = '<option value="' . $code . '">' .
        //     $selector_dimension . ' - ' .
        //     $total_labels . ' labels per sheet (' . $code . ')' .
        //     '</option>';
        $optionLine = '<option value="' . $code . '">' .
    'Label Size ' . $selector_dimension . ' - ' .
    $total_labels . ' labels per sheet (' . $code . ')' .
    '</option>';

if ($shape === 'Backslit') {
            $options['Full Sheets with Backslits'][] = $optionLine;
        }
        elseif ($shape === 'Rectangle') {
            $options['Rectangles'][] = $optionLine;
        } elseif ($shape === 'Circle') {
            $options['Circles'][] = $optionLine;
        } elseif ($shape === 'Oval') {
            $options['Ovals'][] = $optionLine;
        } else {
            $options['Bottles & Other Shapes'][] = $optionLine;
        }
    
}

/* =============================
   5️⃣ PRINT AS TEXT (OPTIONAL)
============================= */
// echo '<hr><h2>Plain Text Preview</h2>';

// foreach ($options as $shape => $list) {
//     if (empty($list)) continue;
//     echo '<h3>' . htmlspecialchars($shape) . '</h3><ul>';
//     foreach ($list as $opt) {
//         echo '<li>' . htmlspecialchars(strip_tags($opt)) . '</li>';
//     }
//     echo '</ul>';
// }
echo '<table border="1" cellpadding="6" cellspacing="0" width="100%">';
echo '<tbody>';

foreach ($options as $shape => $list) {
    if (empty($list)) continue;

    // Shape header row
    echo '<tr>';
    echo '<th colspan="2" style="text-align:left;">' . htmlspecialchars($shape) . '</th>';
    echo '</tr>';

    // Data rows
    foreach ($list as $opt) {
        // Extract code from the option
        if (preg_match('/value="([^"]+)"/', $opt, $matches)) {
            $code = $matches[1];
        } else {
            $code = 'N/A';
        }

        // Extract description text
        $desc = strip_tags($opt);
        $desc = preg_replace('/\s*\(' . preg_quote($code, '/') . '\)\s*$/', '', $desc); // remove code at end
        $desc = trim($desc);

        // Output table row
        echo '<tr>';
        echo '<td>' . htmlspecialchars($code . ' - ' . $desc) . '</td>';
        echo '<td><a href="/images/templates-a3/' . $code . '.pdf" target="_blank">Download ' . $code . ' Template</a></td>';
        echo '</tr>';
    }

    // Spacer row
    echo '<tr><td colspan="2">&nbsp;</td></tr>';
}

echo '</tbody>';
echo '</table>';


