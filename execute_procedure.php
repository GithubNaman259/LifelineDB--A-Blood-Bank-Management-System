<?php
// Display errors in development (you can disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set content type
header('Content-Type: text/plain');

// ✅ STEP 1: Read and decode JSON input
$raw_input = file_get_contents('php://input');
file_put_contents("row_input_log.txt", $raw_input . "\n", FILE_APPEND);
$input = json_decode($raw_input, true);
file_put_contents("decoded_log.txt", print_r($input, true), FILE_APPEND);

// ✅ STEP 2: Validate input
$procedure = $input['procedure'] ?? null;
$params = $input['params'] ?? [];

if (!$procedure) {
    echo "❌ Procedure not specified.";
    exit;
}

// ✅ STEP 3: Connect to Oracle
$conn = oci_connect("SYSTEM", "naman123", "localhost/XE");
if (!$conn) {
    $e = oci_error();
    echo "❌ Connection failed: " . $e['message'];
    exit;
}

// ✅ STEP 4: Build procedure call
function build_call($proc) {
    switch ($proc) {
        case "1": return "BEGIN insert_donor(:1, :2, :3, TO_DATE(:4, 'YYYY-MM-DD'), :5, :6); END;";
        case "2": return "BEGIN insert_patient(:1, :2, :3, :4, :5, :6); END;";
        case "3": return "BEGIN add_donation(:1, :2, TO_DATE(:3, 'YYYY-MM-DD'), :4, :5, :6); END;";
        case "4": return "BEGIN request_blood(:1, :2, :3, TO_DATE(:4, 'YYYY-MM-DD'), :5); END;";
        case "5": return "BEGIN show_donors(); END;";
        case "6": return "BEGIN show_patients_by_hospital(:1); END;";
        case "7": return "BEGIN show_inventory_summary(); END;";
        case "8": return "BEGIN show_requests(); END;";
        case "9": return "BEGIN show_rewards(:1); END;";
        case "10": return "BEGIN find_donors_by_blood_group(:1); END;";
        default: return null;
    }
}

$sql = build_call($procedure);
if (!$sql) {
    echo "❌ Invalid procedure selected.";
    exit;
}

// ✅ STEP 5: Prepare and bind parameters
$stmt = oci_parse($conn, $sql);
for ($i = 0; $i < count($params); $i++) {
    oci_bind_by_name($stmt, ":" . ($i + 1), $params[$i]);
}

// ✅ STEP 6: Enable DBMS_OUTPUT and execute
oci_execute(oci_parse($conn, 'BEGIN DBMS_OUTPUT.ENABLE(NULL); END;'));

$success = oci_execute($stmt);
if (!$success) {
    $e = oci_error($stmt);
    file_put_contents("error_log.txt", print_r($e, true), FILE_APPEND);
    echo "❌ Execution failed: " . $e['message'];
    exit;
}

// ✅ STEP 7: Fetch DBMS_OUTPUT (if any)
$output = '';
$line = '';
$status = 0;
$stid = oci_parse($conn, "BEGIN DBMS_OUTPUT.GET_LINE(:line, :status); END;");
oci_bind_by_name($stid, ':line', $line, 32767);
oci_bind_by_name($stid, ':status', $status);

while (true) {
    oci_execute($stid);
    if ($status != 0) break;
    $output .= $line . "\n";
}

// ✅ Final Output
echo "✅ Procedure executed successfully.\n";
if (trim($output)) {
    echo "📝 DBMS_OUTPUT:\n" . trim($output) . "\n";
}

// ✅ Cleanup
oci_commit($conn);
oci_free_statement($stmt);
oci_close($conn);
?>
