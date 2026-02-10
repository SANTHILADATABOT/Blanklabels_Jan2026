<?php
require __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

/* -----------------------------
   DB CONNECTION
----------------------------- */
$mysqli = new mysqli(
    "localhost",
    "alivedir_ll22_staging",
    "tQNzYJ!i=b)z",
    "alivedir_ll22_staging"
);
if ($mysqli->connect_errno) {
    die("DB connection failed: " . $mysqli->connect_error);
}

/* -----------------------------
   Handle Excel Upload
----------------------------- */
if (isset($_FILES['excel_file'])) {

    $filePath = $_FILES['excel_file']['tmp_name'];

    try {
        $reader = IOFactory::createReader("Xlsx");
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
    } catch (Exception $e) {
        die("<b style='color:red;'>Excel Read Error:</b> " . $e->getMessage());
    }

    // ✅ ACTIVE SHEET ONLY
    $sheet = $spreadsheet->getActiveSheet();

    $groupedOptions = [
        "Others" => []
    ];

    $firstRow = true;

    foreach ($sheet->getRowIterator() as $row) {

        if ($firstRow) { $firstRow = false; continue; }

        $cells = [];
        foreach ($row->getCellIterator() as $cell) {
            $cells[] = trim((string)$cell->getValue());
        }

        // ✅ SAFE CELL ACCESS
        $newCode            = $cells[0]  ?? '';
        $shape              = $cells[2]  ?? '';
        $slits = trim($cells[43]);
        $size_word = trim($cells[45]);
        $selectorDimension  = $cells[32] ?? '';
        $page_size1   = $cells[40];
        $edge_size = ucfirst(strtolower(trim($cells[42])));

        if ($newCode === '') continue;

        // $optionLine =
        //     '<option value="' . htmlspecialchars($newCode, ENT_QUOTES) . '">' .
        //     htmlspecialchars(trim($selectorDimension . ' ' . $shape), ENT_QUOTES) .
        //     ' - ' . htmlspecialchars($total_labels, ENT_QUOTES) .
        //     ' label per sheet (' . htmlspecialchars($newCode, ENT_QUOTES) . ')' .
        //     '</option>';
        $optionLine =
            '<option value="' . htmlspecialchars($newCode, ENT_QUOTES) . '">' .
                'Backslits parallel to ' . htmlspecialchars($edge_size, ENT_QUOTES) . ' Edge' .
                ' - ' . htmlspecialchars($slits, ENT_QUOTES) . ' Slits' .
                ' - ' . htmlspecialchars($size_word, ENT_QUOTES) .
                ' (' . htmlspecialchars($newCode, ENT_QUOTES) . ')' .
            '</option>';

        // ✅ PUSH INTO OTHERS
        $groupedOptions["Others"][] = $optionLine;
    }

    /* -----------------------------
       SORT NUMERICALLY BY WIDTH
    ----------------------------- */
    usort($groupedOptions["Others"], function ($a, $b) {
        preg_match('/>([\d\.]+)\s*mm/i', $a, $m1);
        preg_match('/>([\d\.]+)\s*mm/i', $b, $m2);
        $n1 = isset($m1[1]) ? (float)$m1[1] : 0;
        $n2 = isset($m2[1]) ? (float)$m2[1] : 0;
        return $n2 <=> $n1;
    });

    $optionsHtml = implode("\n", $groupedOptions["Others"]);
    $totalOptions = count($groupedOptions["Others"]);
    ?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Preview Options</title>
</head>
<body>

<h3>Preview generated options</h3>
<p>Total options: <?= $totalOptions ?></p>

<div style="max-width:800px;border:1px solid #ccc;padding:10px;">
    <select style="width:100%;height:300px;" multiple>
        <?= $optionsHtml ?>
    </select>
</div>

<form method="post">
    <input type="hidden" name="options_html"
           value="<?= htmlspecialchars($optionsHtml, ENT_QUOTES) ?>">
    <button type="submit" name="confirm_update">✔ Update Database</button>
</form>

<form method="post" enctype="multipart/form-data" style="margin-top:10px;">
    <input type="file" name="excel_file" required>
    <button type="submit">Upload Another File</button>
</form>

</body>
</html>
<?php
exit;
}

/* -----------------------------
   Confirm Update
----------------------------- */
if (isset($_POST['confirm_update'])) {

    $optionsHtml = $_POST['options_html'] ?? '';
    if (!$optionsHtml) {
        die("No options received.");
    }
    
 $finalHtml = htmlspecialchars(
'<p>Backslit A3 self-adhesive labels. Available in various Paper Stocks including Matt White, Gloss White, WLK Wine Stock, Opaque Blockout and Fluorescent Colours.</p>

<p><b>All prices on this website are in Australian Dollars.</b></p>
<p><b class="red">Prices exclude GST and delivery.</b></p>

<div class="selector">
    <h3 id="select">Select A3 backslit size</h3>

    <select size="9"
            name="da3select"
            id="da3select"
            onchange="a3DieNo()"
            aria-labelledby="select">

        <option value="" disabled>A3 Backslits (297mm x 420mm Sheet)</option>
        ' . $optionsHtml . '
    </select>

    <div>
        <table>
            <tbody>
                <tr>
                    <td>
                        <img src="image/catalog/select.gif"
                             alt="Label Shape"
                             id="a3dieimage">
                    </td>
                    <td>
                        <table>
                            <tbody id="a3specs">
                                <tr><td>Label size</td><td>-</td></tr>
                                <tr><td>Paper size</td><td>-</td></tr>
                                <tr><td>Horizontal pitch</td><td>-</td></tr>
                                <tr><td>Vertical pitch</td><td>-</td></tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

        <p>
            <a class="btn btn-primary btn-lg" href="javascript:" id="a3sizelink">Choose Stock</a>
            <a class="btn btn-primary btn-lg" href="javascript:" id="a3templatelink">Download Template</a>
        </p>
    </div>
</div>',
ENT_QUOTES | ENT_HTML5
);    
    
 
$finalHtml1 = htmlspecialchars(
'
<p>Diecut SR-A3 self-adhesive labels. Available in various Paper Stocks including Matt White, Gloss White, WLK Wine Stock, Opaque Blockout and Fluorescent Colours.</p>

<p><b>All prices on this website are in Australian Dollars.</b></p>
<p><b class="red">Prices exclude GST and delivery.</b></p>

<div class="selector">
    <h3 id="select">Select SR-A3 diecut label size</h3>

    <select size="9"
            name="da3select"
            id="da3select"
            onchange="a3DieNo()"
            aria-labelledby="select">

        <option value="" disabled>SR-A3 Diecut Labels</option>
        ' . $optionsHtml . '
    </select>

    <div>
        <table>
            <tbody>
                <tr>
                    <td>
                        <img src="image/catalog/select.gif"
                             alt="Label Shape"
                             id="a3dieimage">
                    </td>
                    <td>
                        <table>
                            <tbody id="a3specs">
                                <tr><td>Label size</td><td>-</td></tr>
                                <tr><td>Paper size</td><td>-</td></tr>
                                <tr><td>Horizontal pitch</td><td>-</td></tr>
                                <tr><td>Vertical pitch</td><td>-</td></tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

        <p>
            <a class="btn btn-primary btn-lg"
              href="javascript:"
              id="a3sizelink">Choose Stock</a>

            <a class="btn btn-primary btn-lg"
              href="javascript:"
              id="a3templatelink">Download Template</a>
        </p>
    </div>
</div>
',
ENT_QUOTES | ENT_HTML5
);

    $category_id = 104;

    $stmt = $mysqli->prepare(
        "UPDATE category_description SET description=? WHERE category_id=?"
    );
    $stmt->bind_param("si", $finalHtml, $category_id);

    if ($stmt->execute()) {
        echo "<p style='color:green;'>Updated successfully.</p>";
    } else {
        echo "<p style='color:red;'>DB Error: {$stmt->error}</p>";
    }

    exit;
}
?>

<!doctype html>
<html>
<body>
<h3>Upload Excel</h3>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="excel_file" required>
    <button type="submit">Upload & Preview</button>
</form>
</body>
</html>
