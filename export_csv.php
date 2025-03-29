<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user expenses
$query = "SELECT date, category, amount FROM expenses WHERE user_id = ? ORDER BY date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Set headers for file download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=expense_report.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Write column headers
fputcsv($output, ['Date', 'Category', 'Amount (Ksh)']);

// Write expense data
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [$row['date'], ucfirst($row['category']), number_format($row['amount'], 2)]);
}

// Close file pointer
fclose($output);
exit();
?>
