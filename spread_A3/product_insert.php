<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

/* =========================
   AUTOLOAD
========================= */
// require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/PHPExcel/Classes/PHPExcel.php';


// /* =========================
//   PHPExcel CHECK
// ========================= */
// if (!class_exists('PHPExcel_IOFactory')) {
//     die('PHPExcel NOT loaded');
// }

/* =========================
   DB CONNECTION
========================= */
$mysqli = new mysqli(
    "localhost",
    "stickerc_blankad",
    "bL@nk1420",
    "stickerc_blank"
);

if ($mysqli->connect_errno) {
    die("DB Connection Failed: " . $mysqli->connect_error);
}

/* =========================
   SHOW UPLOAD FORM
========================= */
if (!isset($_FILES['excel_file']) && !isset($_POST['confirm_update'])) {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Excel Upload</title>
</head>
<body>
<h2>Upload Excel File</h2>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="excel_file" required>
    <button type="submit">Upload & Preview</button>
</form>
</body>
</html>
<?php
    exit;
}

/* =========================
   HANDLE UPLOAD & PREVIEW
========================= */
if (isset($_FILES['excel_file'])) {

    $filePath = $_FILES['excel_file']['tmp_name'];

    try {
        $objPHPExcel = PHPExcel_IOFactory::load($filePath);
    } catch (Exception $e) {
        die("<b style='color:red;'>Excel Read Error:</b> " . $e->getMessage());
    }

    $sheet = $objPHPExcel->getActiveSheet();
    $sheetName = $sheet->getTitle();

    echo "<h3>Active Sheet: {$sheetName}</h3>";

    /* =========================
       READ DATA
    ========================= */
    $data = array();

    foreach ($sheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        $rowData = array();
        foreach ($cellIterator as $cell) {
            $rowData[] = trim((string)$cell->getValue());
        }
        $data[] = $rowData;
    }

    if (count($data) <= 1) {
        die("No data rows found.");
    }

    $_SESSION['excel_data'] = $data;

    echo "<h3>Excel Preview</h3>";
    echo "<table border='1' cellpadding='5'>
        <tr style='background:#ddd'>
            <th>New Model</th>
            <th>Old Model</th>
            <th>Shape</th>
            <th>Narrow/Wide</th>
            <th>Image</th>
            <th>Status</th>
        </tr>";

    $firstRow = true;

    foreach ($data as $row) {

        if ($firstRow) {
            $firstRow = false;
            continue;
        }

        $newmodel    = trim($row[0]);
        $oldmodel    = trim($row[1]);
        $shape    = strtolower(trim($row[3])); 
        if ($newmodel == '') continue;

        $image = "select/a3-sheets/{$newmodel}.png";

        $oldEsc = $mysqli->real_escape_string($oldmodel);
        $check = $mysqli->query(
            "SELECT products_id FROM products WHERE products_model='{$oldEsc}' AND products_status='1'"
        );

        $status = ($check && $check->num_rows > 0)
            ? "<span style='color:orange'>Will Update</span>"
            : "<span style='color:green'>Will Insert</span>";


        $shapeCategoryMap = [
        'backslit'  => 160,
        'rectangle' => 161,
        'circle'    => 162,
        'oval'      => 163
    ];
    $defaultCategoryId = 164; // Default if shape not in map
    $categoryId = isset($shapeCategoryMap[$shape])
                ? $shapeCategoryMap[$shape]
                : $defaultCategoryId;
        echo "<tr>
            <td>{$newmodel}</td>
            <td>{$oldmodel}</td>
            <td>{$image}</td>
            <td>{$shape}</td>
            <td>{$categoryId}</td>
            <td>{$status}</td>
        </tr>";
    }

    echo "</table><br>";

    echo '<form method="post">
        <button type="submit" name="confirm_update">✔ Confirm Update</button>
    </form>';

    exit;
}

/* =========================
  CONFIRM UPDATE
========================= */
if (isset($_POST['confirm_update'])) {

    if (!isset($_SESSION['excel_data'])) {
        die("Session expired. Upload again.");
    }

    $data = $_SESSION['excel_data'];
    $inserted = [];
    $updated  = [];

    // Map shapes from Excel to category IDs
   

    foreach ($data as $index => $row) {

        if ($index === 0) continue; // Skip header row
        $shapeCategoryMap = [
        'backslit'  => 160,
        'rectangle' => 161,
        'circle'    => 162,
        'oval'      => 163
        ];
        $defaultCategoryId = 164; // Default if shape not in map
        
        $newmodel = trim($row[0]);
        $oldmodel = trim($row[1]);
        $shape    = strtolower(trim($row[3])); // Correct column for shape
        $nw       = strtolower(trim($row[41]));

        if ($newmodel === '') continue;

        // Get category ID from map
       $categoryId = isset($shapeCategoryMap[$shape])
                ? $shapeCategoryMap[$shape]
                : $defaultCategoryId;
        $image = "select/a3-sheets/{$newmodel}.png";

        $oldEsc = $mysqli->real_escape_string($oldmodel);
        $newEsc = $mysqli->real_escape_string($newmodel);
        $imgEsc = $mysqli->real_escape_string($image);

        // Check if product already exists
        $check = $mysqli->query(
            "SELECT products_id FROM products WHERE products_model='{$oldEsc}' AND products_status='1'"
        ) or die("SELECT ERROR: " . $mysqli->error);

        if ($check->num_rows > 0) {

            // ✅ UPDATE existing product
            $pid = $check->fetch_assoc()['products_id'];

            $mysqli->query(
                "UPDATE products SET
                    products_model       = '{$newEsc}',
                    products_sort_order  = '{$newEsc}',
                    products_image       = '{$imgEsc}',
                    master_categories_id = '{$categoryId}'  
                 WHERE products_id = '{$pid}'"
            ) or die("UPDATE ERROR: " . $mysqli->error);

          
            $mysqli->query(
            "UPDATE products_to_categories 
             SET categories_id = '{$categoryId}' 
             WHERE products_id = '{$pid}'"
            ) or die("CATEGORY UPDATE ERROR: " . $mysqli->error);


            $updated[] = $newmodel;

        } else {

            // ✅ INSERT new product
            $mysqli->query(
                "INSERT INTO products (
                    products_model,
                    products_type,
                    products_sort_order,
                    products_image,
                    products_status,
                    products_date_added,
                    master_categories_id
                ) VALUES (
                    '{$newEsc}',
                    1,
                    '{$newEsc}',
                    '{$imgEsc}',
                    1,
                    NOW(),
                    '{$categoryId}'
                )"
            ) or die("INSERT ERROR: " . $mysqli->error);

            $pid = $mysqli->insert_id;

            // Link product to category
            $mysqli->query(
                "INSERT INTO products_to_categories (products_id, categories_id)
                 VALUES ('{$pid}', '{$categoryId}')"
            ) or die("CATEGORY ERROR: " . $mysqli->error);

            $inserted[] = $newmodel;
        }
    }

    echo "<h3 style='color:green'>Inserted: " . count($inserted) . "</h3>";
    echo "<h3 style='color:orange'>Updated: " . count($updated) . "</h3>";

    unset($_SESSION['excel_data']);
}
