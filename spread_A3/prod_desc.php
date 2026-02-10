<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/PHPExcel/Classes/PHPExcel.php';
session_start();

/* ==========================
   DB CONNECTION
========================== */
$mysqli = new mysqli(
    "localhost",
    "stickerc_blankad",
    "bL@nk1420",
    "stickerc_blank"
);

if ($mysqli->connect_errno) {
    die("DB Connection Failed: " . $mysqli->connect_error);
}

/* ==========================
   HELPERS
========================== */
function clean_stock_mm($value, $decimals = 3) {
    $value = trim((string)$value);
    if ($value === '') return '-';
    if ($value === '*') return '*mm';

    if (is_numeric($value)) {
        $num = round((float)$value, $decimals);
        $num = rtrim(rtrim(number_format($num, $decimals, '.', ''), '0'), '.');
        return $num . 'mm';
    }
    return $value;
}

/* ==========================
   1️⃣ UPLOAD FORM
========================== */
if (!isset($_FILES['excel_file']) && !isset($_POST['confirm_update'])) {
?>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="excel_file" required>
    <button type="submit">Upload & Preview</button>
</form>
<?php
exit;
}

/* ==========================
   2️⃣ READ EXCEL & PREVIEW
========================== */
if (isset($_FILES['excel_file'])) {

    $filePath = $_FILES['excel_file']['tmp_name'];

    try {
        $objPHPExcel = PHPExcel_IOFactory::load($filePath);
        $sheet = $objPHPExcel->getActiveSheet();
    } catch (Exception $e) {
        die("<b style='color:red;'>Excel Read Error:</b> " . $e->getMessage());
    }

    $rows = [];
    $count = 0;

    echo "<h2>Excel Preview</h2>";
    echo "<table border='1' cellpadding='5'>";

    foreach ($sheet->getRowIterator() as $rowIndex => $row) {

        if ($rowIndex == 1) continue; // skip header

        $cells = $row->getCellIterator();
        $cells->setIterateOnlyExistingCells(false);

        $data = [];
        foreach ($cells as $cell) {
            $data[] = trim($cell->getValue());
        }

        if (empty($data[0])) continue;

        $rows[] = $data;
        $count++;

        echo "<tr>
                <td>{$data[0]}</td>
                <td>{$data[8]}</td>
                <td>{$data[9]}</td>
                <td>{$data[14]}</td>
                <td>{$data[32]}</td>
                <td>{$data[40]}</td>
              </tr>";
    }

    echo "</table>";
    echo "<p><b>Total Records:</b> $count</p>";

    $_SESSION['excel_rows'] = $rows;

    echo '<form method="post">
            <button type="submit" name="confirm_update">✔ Update Database</button>
          </form>';
    exit;
}

/* ==========================
   3️⃣ UPDATE DATABASE
========================== */
if (isset($_POST['confirm_update'])) {

    if (empty($_SESSION['excel_rows'])) {
        die("Session expired. Please re-upload Excel.");
    }

    $rows = $_SESSION['excel_rows'];
    $updated = 0;

    echo "<h2>Updating Database…</h2>";
    echo "<table border='1' cellpadding='5'>
            <tr><th>Model</th><th>Status</th></tr>";

    foreach ($rows as $data) {

        /* ===== Excel Column Mapping (0-based) ===== */
        $code               = trim($data[0]);   // A
        $shape    =    strtolower(trim($data[3])); 
        $top_margin         = clean_stock_mm($data[5]);  // E
        $side_margin        = clean_stock_mm($data[6]);  // F
        $label_width        = clean_stock_mm($data[7]);  // G
        $label_height       = clean_stock_mm($data[8]);  // H
        $horizontal         = clean_stock_mm($data[9]);  // I
        $vertical           = clean_stock_mm($data[10]);  // J
        $corner             = trim($data[11]);           // K
        $label_across       = trim($data[13]);           // M
        $label_down         = trim($data[14]);           // N
        $total_labels       = trim($data[15]); 
         $bleed       = clean_stock_mm($data[21]);
          $slit_details       = clean_stock_mm($data[29]);// O
        $selector_dimension = trim($data[33]);           // AG
        $prod_head        = trim($data[40]);           // AO
        $page_size         = trim($data[42]);

        
        if ($code === '') continue;
        $shapeCategoryMap = [
            'backslit'  => 160,
            'rectangle' => 161,
            'circle'    => 162,
            'oval'      => 163
        ];
        $defaultCategoryId = 164; 
        $categoryId = isset($shapeCategoryMap[$shape]) 
                        ? $shapeCategoryMap[$shape] 
                        : $defaultCategoryId;

        $name = "{$prod_head}";

        /* ===== RAW DESCRIPTION ===== */
        $description = <<<HTML
<div class='bl_mainbox'>
    <div class='bl_line_top'><img src='./BL_extra_pics/1px_black.gif' width='100%' height='1'></div>

    <div class='bl_heading'>{$prod_head}</div>

    <div class='bl_title'>Top margin</div><div class='bl_text'>{$top_margin}</div>
    <div class='bl_title'>Side margin(s)</div><div class='bl_text'>{$side_margin}</div>
    <div class='bl_title'>Label width</div><div class='bl_text'>{$label_width}</div>
    <div class='bl_title'>Label height</div><div class='bl_text'>{$label_height}</div>
    <div class='bl_title'>Horizontal pitch</div><div class='bl_text'>{$horizontal}</div>
    <div class='bl_title'>Vertical pitch</div><div class='bl_text'>{$vertical}</div>
    <div class='bl_title'>Corner</div><div class='bl_text'>{$corner}</div>
    <div class='bl_title'>Number across</div><div class='bl_text'>{$label_across}</div>
    <div class='bl_title'>Number down</div><div class='bl_text'>{$label_down}</div>
    <div class='bl_title'>Total labels</div><div class='bl_text'>{$total_labels} per sheet</div>
    <div class='bl_row'>
    <div class='bl1_title'>Bleed Warning</div><div class='bl1_text'>{$bleed}</div>
    </div>
    <div class='bl_row'>
    <div class='bl1_title'>Slit Details (BS-FS)</div><div class='bl1_text'>{$slit_details}</div>
    </div>
    <div class='bl_line_bottom'><img src='./BL_extra_pics/1px_black.gif' width='100%' height='1'></div>
</div>

<p>
BlankLabels.com.au have {$page_size} size sheets available in Matt or Gloss White Paper,
Matt Vellum, Matt Fluorescent Colours, Gloss WLK202, and Gloss Opaque Blockout.
<a href="index.php?main_page=index&amp;cPath={$categoryId}">here</a>.
</p>

<p class='bl_template'>
<a href='./images/templates-a3/{$code}.pdf' target='_blank'>Download PDF template here</a>
</p>

<p>
We can also print your labels for you.
Please click <a href='index.php?main_page=index&cPath=3'>here</a> for more info.
</p>
HTML;
// } elseif ($page1 === 'Backslit') {
//     $name = "{$code} - {$page_size} single sheets - {$slits} {$orientation} backslits {$size_number}";
    
//     $description = <<<HTML
// <div class="bl_mainbox">
//     <div class="bl_line_top">
//         <img src="./BL_extra_pics/1px_black.gif" alt="BlankLabels.com.au - Be Creative, with the largest range of self adhesive labels in the world!" width="100%" height="1">
//     </div>

//     <div class="bl_heading">{$code}-{$total_labels} {$shapes} Label {$selector_dimension}</div>

//   <div class='bl_title'>Top margin</div>
//     <div class='bl_text'>{$top_margin}</div>

//     <div class='bl_title'>Side margin(s)</div>
//     <div class='bl_text'>{$side_margin}</div>

//     <div class='bl_title'>Label width</div>
//     <div class='bl_text'>{$label_width}</div>

//     <div class='bl_title'>Label Height</div>
//     <div class='bl_text'>{$label_height}</div>

//     <div class='bl_title'>Horizontal pitch</div>
//     <div class='bl_text'>{$horizontal}</div>

//     <div class='bl_title'>Vertical pitch</div>
//     <div class='bl_text'>{$vertical}</div>

//     <div class='bl_title'>Corner</div>
//     <div class='bl_text'>{$corner}</div>

//     <div class='bl_title'>Number across</div>
//     <div class='bl_text'>{$label_across}</div>

//     <div class='bl_title'>Number down</div>
//     <div class='bl_text'>{$label_down}</div>

//     <div class='bl_title'>Total labels</div>
//     <div class='bl_text'>{$total_labels} per sheet</div>

//     <div class="bl_line_bottom">
//         <img src="./BL_extra_pics/1px_black.gif" alt="BlankLabels.com.au - Be Creative, with the largest range of self adhesive labels in the world!" width="100%" height="1">
//     </div>
// </div>

// <p>
//     BlankLabels.com.au have {$page_size} size sheets available in Matt or Gloss White Paper,
//     Matt Vellum, Matt Fluorescent Colours, Gloss WLK202, and Gloss Opaque Blockout.
//     You can see more detailed descriptions
//     <a href="index.php?main_page=index&amp;cPath=133">here</a>.
// </p>

// <p>
//     We can also print your labels for you.
//     Please click <a href="index.php?main_page=index&amp;cPath=3">here</a> for more information.
// </p>
// HTML;

    

    
echo $name;
echo $description;

        $description = $mysqli->real_escape_string($description);
$name        = $mysqli->real_escape_string($name);

/* ===== FIND ALL PRODUCTS BY MODEL ===== */
$check = $mysqli->query("
    SELECT products_id
    FROM products
    WHERE products_model = '$code'
");

if ($check && $check->num_rows > 0) {

    while ($row = $check->fetch_assoc()) {

        $pid = (int)$row['products_id'];

        /* ===== CHECK DESCRIPTION EXISTS ===== */
        $descCheck = $mysqli->query("
            SELECT products_id
            FROM products_description
            WHERE products_id = $pid
            LIMIT 1
        ");

        if ($descCheck && $descCheck->num_rows > 0) {

            /* ===== UPDATE ===== */
            $mysqli->query("
                UPDATE products_description
                SET
                    products_name = '$name',
                    products_description = '$description'
                WHERE products_id = $pid
            ");

            echo "<tr>
                    <td>$code (ID: $pid)</td>
                    <td style='color:green;'>UPDATED</td>
                  </tr>";

        } else {

            /* ===== INSERT ===== */
            $mysqli->query("
                INSERT INTO products_description
                (products_id, products_name, products_description)
                VALUES
                ($pid, '$name', '$description')
            ");

            echo "<tr>
                    <td>$code (ID: $pid)</td>
                    <td style='color:orange;'>INSERTED</td>
                  </tr>";
        }

        $updated++;
    }

} else {

    echo "<tr>
            <td>$code</td>
            <td style='color:red;'>PRODUCT NOT FOUND</td>
          </tr>";
}


    }

    echo "</table>";
    echo "<h3 style='color:green;'>✔ Database Updated ($updated products)</h3>";

    unset($_SESSION['excel_rows']);
}
?>
