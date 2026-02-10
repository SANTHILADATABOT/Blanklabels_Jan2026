<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

/* =============================
   PHPExcel (PHP 5.4 compatible)
============================= */
require_once __DIR__ . '/PHPExcel/Classes/PHPExcel.php';

/* =============================
   CATEGORY ID
============================= */
define('MASTER_CATEGORY_ID', 133);

/* =============================
   DB CONNECTION
============================= */
$mysqli = new mysqli(
    "localhost",
    "stickerc_blank_staging",
    "G=?L=HwxQq6&",
    "stickerc_blank_staging"
);

if ($mysqli->connect_error) {
    die("DB Connection Error: " . $mysqli->connect_error);
}

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


function buildOptionsHTML($options) {
    $html = '';
    foreach ($options as $groupLabel => $groupOptions) {
        if (empty($groupOptions)) continue;
        $html .= '<option value="" disabled>' . $groupLabel . '</option>' . "\n";
        $html .= implode("\n", $groupOptions) . "\n";
    }
    return $html;
}


/* =============================
   1️⃣ UPLOAD FORM
============================= */
if (!isset($_FILES['excel_file']) && !isset($_POST['confirm_update'])) {
?>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="excel_file" accept=".xlsx,.xls" required>
    <button type="submit">Upload & Preview</button>
</form>
<?php
exit;
}

/* =============================
   2️⃣ EXCEL PREVIEW
============================= */
if (isset($_FILES['excel_file'])) {

    $filePath = $_FILES['excel_file']['tmp_name'];

    try {
        $excel = PHPExcel_IOFactory::load($filePath);
    } catch (Exception $e) {
        die("Excel Load Error: " . $e->getMessage());
    }

    $sheet = $excel->getActiveSheet();
    $rows  = $sheet->toArray(null, true, true, false);

    echo "<h2>Excel Preview</h2>";
    echo "<table border='1' cellpadding='6'>
        <tr style='background:#eee'>
            <th>Code</th>
            <th>Page</th>
            <th>Type</th>
            <th>Edge</th>
            <th>Slits</th>
            <th>shape</th>
        </tr>";

    $storedRows = [];
    $count = 0;

    foreach ($rows as $i => $data) {
        if ($i === 0 || empty($data[0])) continue;

        $storedRows[] = $data;
        $count++;

        echo "<tr>
            <td>{$data[0]}</td>
            <td>{$data[40]}</td>
            <td>{$data[2]}</td>
            <td>{$data[42]}</td>
            <td>{$data[43]}</td>
            <td>{$data[3]}</td>
        </tr>";
    }

    echo "</table>";
    echo "<p><b>Total Records:</b> {$count}</p>";

    $_SESSION['excel_rows'] = $storedRows;

    echo '<form method="post">
            <button type="submit" name="confirm_update">✔ Confirm & Insert</button>
          </form>';
    exit;
}

/* =============================
   3️⃣ CONFIRM & INSERT
============================= */
if (isset($_POST['confirm_update'])) {

    if (empty($_SESSION['excel_rows'])) {
        die("Session expired. Please re-upload Excel.");
    }

    $rows = $_SESSION['excel_rows'];



    $options = [
        'Full Sheets with Backslits'   => [],
        'Rectangles'                      => [],
        'Circles' => [],
        'Ovals'                   => [],
        'Bottles & Other Shapes'                   => []
    ];

    foreach ($rows as $data) {
        $code         = trim($data[0]);          // A
        $type         = strtolower(trim($data[2])); // C
        $shape        = trim($data[3]);
        $page         = trim($data[40]);         // AO
        $page1         = trim($data[41]);         // AP
        $edge         = trim($data[42]);         // AQ
        $slits        = trim($data[43]);         // AR
        $distance     = trim($data[45]);         // AT

        $label_width  = clean_stock_mm($data[6]); // G
        $label_height = clean_stock_mm($data[7]); // H
        $total_labels = trim($data[15]);          // O
        // $shape        = trim($data[11]);          // L
        $dimension    = trim($data[12]);          // M
        $selector_dimension    = trim($data[33]);  
        

        if ($code === '') continue;
        $orientation = ($edge === 'long') ? 'Vertical' : 'Horizontal';
        if ($shape === 'Backslit') {
            // $optionLine =
            //     '<option value="'.$code.'">' .
            //     $slits . ' ' . ucfirst($edge) . ' Backslits - ' .
            //     $distance . ' apart (' . $code . ')' .
            //     '</option>';
            
          $optionLine =
                '<option value="'.$code.'">' .
    $selector_dimension . ' - ' . $slits . ' (' . $code . ')' .
'</option>';

        } else{
            // $optionLine =
            //     '<option value="'.$code.'">' .
            //     $shape . ' ' . $dimension .
            //     ' - ' . $total_labels . ' labels per sheet (' . $code . ')' .
            //     '</option>';
            $optionLine = '<option value="' . $code . '">' .
                    $selector_dimension . ' - ' .
                    $total_labels . ' labels per sheet (' . $code . ')' .
                    '</option>';
        }

        // } else {
        //     continue;
        // }

        if ($shape == 'Backslit') {
            $options['Full Sheets with Backslits'][] = $optionLine;
        } elseif ($shape == 'Rectangle') {
            $options['Rectangles'][] = $optionLine;
        } 
        elseif ($shape == 'Circle') {
            $options['Circles'][] = $optionLine;
        }elseif ($shape == 'Oval') {
            $options['Ovals'][] = $optionLine;
        } else  {
            $options['Bottles & Other Shapes'][] = $optionLine;
        }
       
    }
function sortOptionsByDimensionDesc(&$groupedOptions)
{
    foreach ($groupedOptions as $cat => &$options) {

        if (!is_array($options) || empty($options)) {
            continue;
        }

        $catLower = strtolower($cat);

        /* 🔵 CIRCLES → diameter DESC */
        if ($catLower == 'circles') {

            usort($options, function ($a, $b) {

                $hasA = preg_match('/([\d\.]+)\s*mm\s*dia/i', $a, $m1);
                $hasB = preg_match('/([\d\.]+)\s*mm\s*dia/i', $b, $m2);

                if (!$hasA && !$hasB) return 0;
                if (!$hasA) return 1;
                if (!$hasB) return -1;

                $diaA = (float)$m1[1];
                $diaB = (float)$m2[1];

                // DESC (manual comparison)
                if ($diaA == $diaB) return 0;
                return ($diaA < $diaB) ? 1 : -1;
            });

            continue;
        }

        /* 🟠 FULL SHEETS WITH BACKSLITS → Width DESC, Height DESC */
        if ($catLower == 'full sheets with backslits') {

            usort($options, function ($a, $b) {

                $hasA = preg_match('/([\d\.]+)\s*x\s*([\d\.]+)\s*mm/i', $a, $m1);
                $hasB = preg_match('/([\d\.]+)\s*x\s*([\d\.]+)\s*mm/i', $b, $m2);

                if (!$hasA && !$hasB) return 0;
                if (!$hasA) return 1;
                if (!$hasB) return -1;

                $a1 = (float)$m1[1];
                $a2 = (float)$m1[2];
                $b1 = (float)$m2[1];
                $b2 = (float)$m2[2];

                // Normalize (bigger = width)
                $widthA  = max($a1, $a2);
                $heightA = min($a1, $a2);

                $widthB  = max($b1, $b2);
                $heightB = min($b1, $b2);

                // Width DESC
                if ($widthA != $widthB) {
                    return ($widthA < $widthB) ? 1 : -1;
                }

                // Height DESC
                if ($heightA == $heightB) return 0;
                return ($heightA < $heightB) ? 1 : -1;
            });

        } else {

            /* 🔴 RECTANGLES / OVALS / OTHERS → Width DESC, Height DESC */
            usort($options, function ($a, $b) {

                $hasA = preg_match('/([\d\.]+)\s*mm\s*x\s*([\d\.]+)\s*mm/i', $a, $m1);
                $hasB = preg_match('/([\d\.]+)\s*mm\s*x\s*([\d\.]+)\s*mm/i', $b, $m2);

                if (!$hasA && !$hasB) return 0;
                if (!$hasA) return 1;
                if (!$hasB) return -1;

                $wA = (float)$m1[1];
                $hA = (float)$m1[2];
                $wB = (float)$m2[1];
                $hB = (float)$m2[2];

                // Width DESC
                if ($wA != $wB) {
                    return ($wA < $wB) ? 1 : -1;
                }

                // Height DESC
                if ($hA == $hB) return 0;
                return ($hA < $hB) ? 1 : -1;
            });
        }
    }

    unset($options);
}


sortOptionsByDimensionDesc($options);
    /* =============================
       FINAL HTML
    ============================= */
    
    $finalHtml = '
<p>Our large A3 and SRA3 sheet sizes are suitable for when you need larger labels - for projects when you need something bigger than just our A4 labels. Our full A3 and SRA3 label sheets come with various backslits to make peeling them off super easy. Choose from vertical backslits, horizontal backslits or no backslits. We also have a range of diecut labels on A3 and SRA3 sheets.</p>
<p> </p>
<div class="selector">
    <h3 id="select">Quick Select A3/SR-A3 label size</h3>
    <select size="14" name="da3select" id="da3select" onchange="a3DieNo()" aria-labelledby="select">
        ' . buildOptionsHTML($options) . '
    </select>
    <div>
        <table><tbody>
            <tr><td rowspan="14">
                <h4 id="a3dieno">Die Number:</h4>
                <img src="images/select/select.gif" alt="Label Shape" id="a3dieimage">
            </td>
            <td>Top/Bottom Margin</td><td id="tm">-</td></tr>
            <tr><td>Side Margin(s)</td><td id="sm">-</td></tr>
            <tr><td>Label Width</td><td id="lw">-</td></tr>
            <tr><td>Label Height</td><td id="lh">-</td></tr>
            <tr><td>Horizontal Pitch</td><td id="hp">-</td></tr>
            <tr><td>Vertical Pitch</td><td id="vp">-</td></tr>
            <tr><td>Corner</td><td id="co">-</td></tr>
            <tr><td>Number Across</td><td id="na">-</td></tr>
            <tr><td>Number Down</td><td id="nd">-</td></tr>
            <tr><td>Total Labels</td><td id="tl">-</td></tr>
            <tr><td>Paper Size</td><td id="ps">-</td></tr>
            <tr><td>Stock Availability</td><td id="sa">-</td></tr>
            <tr><td>Bleed Warning</td><td id="bw">-</td></tr>
            <tr><td>Slit Details (BS-FS)</td><td id="pf">-</td></tr>
        </tbody></table>
        <p><a class="cssButton button" href="javascript:" id="a3sizelink">Choose Stock</a>
        <a class="cssButton button" href="javascript:" id="a3templatelink">Download Template</a></p>
    </div>
</div>
<p style="clear:both;"> </p>
<p>BlankLabels.com.au have A3 size sheets available in Matt or Gloss White Paper, Matt Vellum, Matt Fluorescent Colours, Gloss WLK202 and Gloss Opaque Blockout</p>
<p>Synthetic Matt White and Synthetic Translucent are also available, but only without backslits (See product: <a href="index.php?main_page=product_info&cPath=133&products_id=1550">A3 - A3 single sheets - no backslits</a> or <a href="index.php?main_page=product_info&cPath=133&products_id=2698">SRA3 - SRA3 single sheets - no backslits</a>).</p>
<p> </p>
<table class="tred alt codiv"><tbody>
    <tr>
        <th>White Paper Stock</th>
    </tr>
   <tr>
     <td><div style="width:66%;display:inline-block;"><p><strong>White Paper</strong> - Matt and Gloss.</p><p>These are our general purpose labels, suitable for a vast variety of different uses.</p></div>
        <div style="width:31%;display:inline-block;vertical-align:top;float:right;"><p><b style="margin:0px;text-decoration:underline;">Suitable Printers</b></p><p>Matt: Laser, Inkjet, Copier &amp; Offset.<br>Gloss: Laser and offset.</p></div></td>
   </tr>
    <tr>
        <th>Fluorescent Paper Stock</th>
    </tr>
   <tr>
     <td><div style="width:66%;display:inline-block;"><p><strong>Fluorescent Paper Labels</strong> - Matt only.</p><p>Available in Fluoro Yellow, Fluoro Orange, Fluoro Green, and Fluoro Pink</p></div>
        <div style="width:31%;display:inline-block;vertical-align:top;float:right;"><p><b style="margin:0px;text-decoration:underline;">Suitable Printers</b></p><p>Laser and offset.</p></div></td>
   </tr>
    <tr>
        <th>Specialty Paper Stock</th>
    </tr>
   <tr>
     <td><div style="width:66%;display:inline-block;"><p><strong>White WLK 202</strong> for wine bottle labels - Gloss only.</p><p>Designed by the wine industry specifically to go on to wine bottles. Enhanced adhesive and water resistance.</p></div>
        <div style="width:31%;display:inline-block;vertical-align:top;float:right;"><p><b style="margin:0px;text-decoration:underline;">Suitable Printers</b></p><p>Laser and offset.</p></div></td>
   </tr>
    <tr>
        <td><div style="width:66%;display:inline-block;"><p><strong>White Opaque</strong> (for blockout) paper labels - Gloss only.</p><p>Has a higher level of white pigment to provide an enhanced level of opacity. Ideal for when you need to block out printing or text underneath the label.</p></div>
        <div style="width:31%;display:inline-block;vertical-align:top;float:right;"><p><b style="margin:0px;text-decoration:underline;">Suitable Printers</b></p><p>Laser and offset.</p></div></td>
   </tr>
    <tr>
        <th>Synthetic Label Stock</th>
    </tr>
    <tr>
        <td><div style="width:66%;display:inline-block;"><p><strong>Synthetic White &amp; Translucent</strong> - Matt only.</p><p>Synthetic is ideal for outside or rugged conditions use. Weatherproof and often used for labels to stick on cars, etc.</p></div>
        <div style="width:31%;display:inline-block;vertical-align:top;float:right;"><p><b style="margin:0px;text-decoration:underline;">Suitable Printers</b></p><p>Laser, offset and screen print</p></div></td>
    </tr>
</tbody></table>';
    $finalHtml1 = '
<p>Our large A3 and SRA3 sheet sizes are suitable for larger label projects. Choose from backslits or diecut labels across A3 and SRA3 sheets.</p>

<div class="selector">
    <h3 id="select">Quick Select A3/SR-A3 label size</h3>
    <select size="14" id="da3select">
        ' . buildOptionsHTML($options) . '
    </select>
</div>
';

    /* =============================
       UPDATE DB
    ============================= */
  $catId = MASTER_CATEGORY_ID;

$stmt = $mysqli->prepare(
    "UPDATE categories_description SET categories_description=? WHERE categories_id=?"
);

if (!$stmt) {
    die("Prepare failed: " . $mysqli->error);
}

$stmt->bind_param("si", $finalHtml, $catId);
$stmt->execute();
$stmt->close();

unset($_SESSION['excel_rows']);

echo $finalHtml;
echo "<h2 style='color:green'>✅ Category updated successfully</h2>";

}
?>
