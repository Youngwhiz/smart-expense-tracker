<?php
include "db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch total expenses
$totalQuery = "SELECT SUM(amount) AS total FROM expenses WHERE user_id = ?";
$stmt = $conn->prepare($totalQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$totalResult = $stmt->get_result()->fetch_assoc();
$totalExpenses = $totalResult['total'] ?? 0;

// Fetch monthly expenses
$monthQuery = "SELECT SUM(amount) AS total FROM expenses WHERE user_id = ? AND MONTH(date) = MONTH(CURRENT_DATE())";
$stmt = $conn->prepare($monthQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$monthResult = $stmt->get_result()->fetch_assoc();
$monthlyExpenses = $monthResult['total'] ?? 0;

// Fetch category-wise expense data
$categoryQuery = "SELECT category, SUM(amount) AS total FROM expenses WHERE user_id = ? GROUP BY category";
$stmt = $conn->prepare($categoryQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$categoryResult = $stmt->get_result();

$categories = [];
$totals = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categories[] = $row['category'];
    $totals[] = $row['total'];
}

// Find highest spending category
$highestQuery = "SELECT category, SUM(amount) AS total FROM expenses WHERE user_id = ? GROUP BY category ORDER BY total DESC LIMIT 1";
$stmt = $conn->prepare($highestQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$highestResult = $stmt->get_result()->fetch_assoc();
$highestCategory = $highestResult['category'] ?? 'None';
$highestAmount = $highestResult['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            display: flex;
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            background: #343a40;
            color: white;
            padding-top: 20px;
            position: fixed;
        }
        .sidebar a {
            padding: 15px;
            display: block;
            color: white;
            text-decoration: none;
        }
        .sidebar a:hover {
            background: #495057;
        }
        .content {
            margin-left: 260px;
            padding: 20px;
            width: 100%;
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <h3 class="text-center">Expense Tracker</h3>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="expense.php?category=food">🍽 Food</a>
        <a href="expense.php?category=clothing">👕 Clothing</a>
        <a href="expense.php?category=housing">🏡 Housing</a>
        <a href="expense.php?category=healthcare">🏥 Healthcare</a>
        <a href="expense.php?category=leisure">🎉 Leisure</a>
        <a href="expense.php?category=personal">🛀 Personal Care</a>
        <a href="expense.php?category=savings">💰 Savings</a>
        <a href="logout.php" class="text-danger">🚪 Logout</a>
    </div>

    <!-- Main Content -->
    <div class="content">
        <h2 class="text-primary">Dashboard</h2>

        <!-- Summary Cards -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-white bg-success shadow-sm">
                    <div class="card-body">
                        <h5>Total Expenses</h5>
                        <h2>Ksh <?php echo number_format($totalExpenses, 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info shadow-sm">
                    <div class="card-body">
                        <h5>This Month's Spending</h5>
                        <h2>Ksh <?php echo number_format($monthlyExpenses, 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning shadow-sm">
                    <div class="card-body">
                        <h5>Top Spending Category</h5>
                        <h2><?php echo ucfirst($highestCategory); ?> (Ksh <?php echo number_format($highestAmount, 2); ?>)</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mt-5">
            <div class="col-md-6">
                <h4 class="text-center">Expenses by Category</h4>
                <canvas id="expensePieChart"></canvas>
            </div>
            <div class="col-md-6">
                <h4 class="text-center">Monthly Expense Comparison</h4>
                <canvas id="expenseBarChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Expense Pie Chart
        var ctxPie = document.getElementById('expensePieChart').getContext('2d');
        var expensePieChart = new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($categories); ?>,
                datasets: [{
                    data: <?php echo json_encode($totals); ?>,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4CAF50', '#FF5733', '#8E44AD', '#3498DB']
                }]
            }
        });

        // Expense Bar Chart
        var ctxBar = document.getElementById('expenseBarChart').getContext('2d');
        var expenseBarChart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($categories); ?>,
                datasets: [{
                    label: 'Expense Amount (Ksh)',
                    data: <?php echo json_encode($totals); ?>,
                    backgroundColor: '#007BFF'
                }]
            }
        });
    </script>

</body>
</html>
