<?php
require __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$mysqli = new mysqli(
    "localhost",
    "alivedir_ll22_staging",
    "tQNzYJ!i=b)z",
    "alivedir_ll22_staging"
);

if ($mysqli->connect_errno) {
    die("DB Connection Failed: " . $mysqli->connect_error);
}

// =====================
// SHOW UPLOAD FORM
// =====================
if (!isset($_FILES['excel_file'])) {
?>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="excel_file" required>
    <button type="submit">Upload & Generate SEO</button>
</form>
<?php
    exit;
}

// =====================
// LOAD EXCEL
// =====================
$filePath = $_FILES['excel_file']['tmp_name'];

try {
    $spreadsheet = IOFactory::load($filePath);
} catch (Exception $e) {
    die("Excel error: " . $e->getMessage());
}

// ✅ ACTIVE SHEET ONLY
$sheet = $spreadsheet->getActiveSheet();
$rows  = $sheet->toArray();

echo "<h3>Active Sheet: " . $sheet->getTitle() . "</h3>";
echo "<table border='1' cellpadding='6'>
<tr>
<th>Model</th>
<th>SEO URL</th>
<th>Status</th>
</tr>";

$total = 0;

// =====================
// PROCESS ROWS
// =====================
foreach ($rows as $i => $row) {

    if ($i === 0) continue; // skip header

    $code        = trim($row[0]);
    // $page_size   = a3;
    // $page_size1   = A3;
    $page_size = strtolower(trim($row[40]));
    $edge_size = trim($row[42]);
    $slits = trim($row[43]);
    $size = trim($row[44]);
    $page_size1   = $row[40];
    $selector = trim($row[39]);
    $total_labels= trim($row[14]);
    $title_diamention = trim($row[31]);
    $horizontal          = trim($row[8]);
    $vertical   = trim($row[9]);
    if ($code === '') continue;

    // ✅ BUILD SEO URL (NO .html)
    $keyword = "{$code}-{$page_size}-label-size-{$selector}-{$total_labels}-labels-per-sheet.html";
     $keyword1 = "{$code}-backslit-{$edge_size}-edge-{$slits}-slits.html";
     $keyword2 = "{$code}-no-backslits.html";

   
    $check = $mysqli->query("
        SELECT product_id 
        FROM product 
        WHERE model='{$mysqli->real_escape_string($code)}'
        AND status='1'
        LIMIT 1
    ");

    if (!$check || $check->num_rows === 0) {
        echo "<tr><td>{$code}</td><td>-</td><td style='color:red'>PRODUCT NOT FOUND</td></tr>";
        continue;
    }

    $pid = (int)$check->fetch_assoc()['product_id'];

    // =====================
    // CHECK URL ALIAS
    // =====================
    $aliasCheck = $mysqli->query("
        SELECT url_alias_id
        FROM url_alias
        WHERE query='product_id={$pid}'
        LIMIT 1
    ");

    if ($aliasCheck && $aliasCheck->num_rows > 0) {

        // 🔁 UPDATE
        $alias_id = (int)$aliasCheck->fetch_assoc()['url_alias_id'];

        $mysqli->query("
            UPDATE url_alias
            SET keyword='{$mysqli->real_escape_string($keyword1)}'
            WHERE url_alias_id={$alias_id}
        ");

        $status = "<span style='color:green'>UPDATED</span>";

    } else {

        // ➕ INSERT
        $mysqli->query("
            INSERT INTO url_alias (query, keyword, language_id)
            VALUES (
                'product_id={$pid}',
                '{$mysqli->real_escape_string($keyword1)}',
                1
            )
        ");

        $status = "<span style='color:orange'>INSERTED</span>";
    }

    echo "<tr>
        <td>{$code}</td>
        <td>{$keyword}</td>
        <td>{$keyword1}</td>
        <td>{$status}</td>
    </tr>";

    $total++;
}

echo "</table>";
echo "<h3 style='color:green'>✔ Completed: {$total} SEO URLs processed</h3>";
