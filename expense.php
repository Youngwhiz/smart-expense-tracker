<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Add Expense
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_expense'])) {
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];

    $query = "INSERT INTO expenses (user_id, category, amount, date) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isds", $user_id, $category, $amount, $date);
    $stmt->execute();
}

// Edit Expense
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_expense'])) {
    $expense_id = $_POST['expense_id'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];

    $query = "UPDATE expenses SET category=?, amount=?, date=? WHERE id=? AND user_id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sdsii", $category, $amount, $date, $expense_id, $user_id);
    $stmt->execute();
}

// Delete Expense
if (isset($_GET['delete_id'])) {
    $expense_id = $_GET['delete_id'];
    $query = "DELETE FROM expenses WHERE id=? AND user_id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $expense_id, $user_id);
    $stmt->execute();
    header("Location: expenses.php");
}

// Fetch Expenses
$query = "SELECT * FROM expenses WHERE user_id = ? ORDER BY date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Calculate Total Expenses
$totalQuery = "SELECT SUM(amount) AS total FROM expenses WHERE user_id = ?";
$stmt = $conn->prepare($totalQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$totalResult = $stmt->get_result();
$total = $totalResult->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Expense Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center text-primary">📊 Expense Tracker</h2>

        <!-- Expense Entry Form -->
        <div class="card p-4 mt-3">
            <h4>Add New Expense</h4>
            <form method="POST">
                <div class="mb-3">
                    <label>Category</label>
                    <select name="category" class="form-control" required>
                        <option value="food">Food</option>
                        <option value="clothing">Clothing</option>
                        <option value="housing">Housing</option>
                        <option value="healthcare">Healthcare</option>
                        <option value="leisure">Leisure</option>
                        <option value="personal">Personal Care</option>
                        <option value="savings">Savings</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Amount (Ksh)</label>
                    <input type="number" name="amount" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Date</label>
                    <input type="date" name="date" class="form-control" required>
                </div>
                <button type="submit" name="add_expense" class="btn btn-success">➕ Add Expense</button>
            </form>
        </div>

        <!-- Expense Summary -->
        <h3 class="text-center text-success mt-4">💰 Total Expenses: Ksh <?php echo number_format($total, 2); ?></h3>

        <!-- Expense List -->
        <div class="card p-4 mt-3">
            <h4>Expense History</h4>
            <table class="table table-striped mt-3">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Amount (Ksh)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['date']; ?></td>
                        <td><?php echo ucfirst($row['category']); ?></td>
                        <td><?php echo number_format($row['amount'], 2); ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm" onclick="editExpense(<?php echo $row['id']; ?>, '<?php echo $row['category']; ?>', <?php echo $row['amount']; ?>, '<?php echo $row['date']; ?>')">✏ Edit</button>
                            
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <a href="dashboard.php" class="btn btn-primary mt-3">🔙 Back to Dashboard</a>
        <a href="export_csv.php" class="btn btn-info mt-3">📥 Download CSV</a>
    </div>

    <!-- Edit Expense Modal -->
    <div id="editModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Expense</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="expense_id" id="edit_expense_id">
                        <div class="mb-3">
                            <label>Category</label>
                            <select name="category" id="edit_category" class="form-control">
                                <option value="food">Food</option>
                                <option value="clothing">Clothing</option>
                                <option value="housing">Housing</option>
                                <option value="healthcare">Healthcare</option>
                                <option value="leisure">Leisure</option>
                                <option value="personal">Personal Care</option>
                                <option value="savings">Savings</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Amount (Ksh)</label>
                            <input type="number" name="amount" id="edit_amount" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Date</label>
                            <input type="date" name="date" id="edit_date" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="edit_expense" class="btn btn-success">✅ Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
 

    

    <!-- Bootstrap Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editExpense(id, category, amount, date) {
            document.getElementById('edit_expense_id').value = id;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_amount').value = amount;
            document.getElementById('edit_date').value = date;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>
</body>
</html>


