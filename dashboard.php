<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Process form submission to add an expense
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_expense'])) {
        $amount = $_POST['amount'];
        $description = $_POST['description'];
        $date = $_POST['date'];
        $group_id = isset($_POST['group']) ? $_POST['group'] : null;

        $sql = "INSERT INTO expenses (user_id, amount, description, date, group_id) VALUES ('$user_id', '$amount', '$description', '$date', '$group_id')";

        if ($conn->query($sql) === TRUE) {
            echo "Expense added successfully!";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } elseif (isset($_POST['create_group'])) {
        $group_name = $_POST['group_name'];

        $sql = "INSERT INTO groups (group_name, user_id) VALUES ('$group_name', '$user_id')";

        if ($conn->query($sql) === TRUE) {
            echo "Group created successfully!";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}


// Display expenses for the logged-in user
$sql = "SELECT * FROM expenses WHERE user_id='$user_id'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h2>Your Expenses</h2>";
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>Amount: " . $row['amount'] . " | Description: " . $row['description'] . " | Date: " . $row['date'] . " | Group: " . $row['group_id'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No expenses recorded yet.</p>";
}
// Display created groups for the logged-in user
$groupSql = "SELECT * FROM groups WHERE user_id='$user_id'";
$groupResult = $conn->query($groupSql);

if ($groupResult->num_rows > 0) {
    echo "<h2>Your Groups</h2>";
    echo "<ul>";
    while ($groupRow = $groupResult->fetch_assoc()) {
        echo "<li>Group: " . $groupRow['group_name'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No groups created yet.</p>";
}
// Display form to add expense
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Tracker - Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Expense Tracker Dashboard</h1>
    <h2>Create Group</h2>
    <form action="dashboard.php" method="POST">
        <label for="group_name">Group Name:</label>
        <input type="text" name="group_name" required><br>

        <input type="submit" name="create_group" value="Create Group">
    </form>
</body>
</html>

    <h2>Add Expense</h2>
    <form action="dashboard.php" method="POST">
        <label for="amount">Amount:</label>
        <input type="text" name="amount" required><br>

        <label for="description">Description:</label>
        <input type="text" name="description" required><br>

        <label for="date">Date:</label>
        <input type="date" name="date" required><br>

        <!-- Add a dropdown for selecting a group -->
        <label for="group">Group:</label>
        <select name="group">
            <option value="">No Group</option>
            <?php
            // Retrieve user's groups
            $groupSql = "SELECT * FROM groups WHERE user_id='$user_id'";
            $groupResult = $conn->query($groupSql);

            while ($groupRow = $groupResult->fetch_assoc()) {
                echo "<option value='" . $groupRow['id'] . "'>" . $groupRow['group_name'] . "</option>";
            }
            ?>
        </select><br>

        <input type="submit" name="add_expense" value="Add Expense">
    </form>



<?php
$conn->close();
?>
