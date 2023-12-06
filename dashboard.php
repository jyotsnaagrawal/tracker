<?php
session_start();
require_once('db.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Process form submission to add an expense, create a group, or add a member
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
    } elseif (isset($_POST['view_expense'])) {
        $selectedExpenseId = $_POST['expense_dropdown'];

        $selectedExpenseSql = "SELECT * FROM expenses WHERE id='$selectedExpenseId' AND user_id='$user_id'";
        $selectedExpenseResult = $conn->query($selectedExpenseSql);

        if ($selectedExpenseResult->num_rows > 0) {
            $selectedExpense = $selectedExpenseResult->fetch_assoc();
            echo "<h3>Selected Expense Details</h3>";
            echo "Amount: " . $selectedExpense['amount'] . " | Description: " . $selectedExpense['description'] . " | Date: " . $selectedExpense['date'];
        } else {
            echo "<p>Expense not found or does not belong to the user.</p>";
        }
    } // Add member to group
    // Add member to group
    elseif (isset($_POST['add_member'])) {
        $group_id = $_POST['group_dropdown'];
        $member_username = $_POST['member_username'];

        // Step 1: Check if the member exists
        $checkMemberSql = "SELECT id FROM users WHERE username='$member_username'";
        $checkMemberResult = $conn->query($checkMemberSql);

        if ($checkMemberResult->num_rows > 0) {
            $memberRow = $checkMemberResult->fetch_assoc();
            $member_id = $memberRow['id'];

            // Step 2: Check if the member is already in the group
            $checkGroupMemberSql = "SELECT id FROM group_members WHERE group_id='$group_id' AND member_id='$member_id'";
            $checkGroupMemberResult = $conn->query($checkGroupMemberSql);

            if ($checkGroupMemberResult->num_rows === 0) {
                // Step 3: Add the member to the group
                $addMemberSql = "INSERT INTO group_members (group_id, member_id) VALUES ('$group_id', '$member_id')";
                if ($conn->query($addMemberSql) === TRUE) {
                    echo "Member added to the group successfully!";
                } else {
                    echo "Error: " . $addMemberSql . "<br>" . $conn->error;
                }
            } else {
                echo "Member is already in the group.";
            }
        } else {
            echo "Member not found.";
        }
    }
}


// Display expenses for the logged-in user
$sql = "SELECT expenses.id, expenses.amount, expenses.description, expenses.date, expenses.group_id, groups.group_name
        FROM expenses
        LEFT JOIN groups ON expenses.group_id = groups.id
        WHERE expenses.user_id='$user_id'";
$result = $conn->query($sql);
$expenses = [];

while ($row = $result->fetch_assoc()) {
    $expenses[] = $row;
}

// Display created groups for the logged-in user
$groupSql = "SELECT * FROM groups WHERE user_id='$user_id'";
$groupResult = $conn->query($groupSql);
$groups = [];

while ($groupRow = $groupResult->fetch_assoc()) {
    $groups[] = $groupRow;
}

$conn->close();
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

    <h2>Add Expense</h2>
    <form action="dashboard.php" method="POST">
        <label for="amount">Amount:</label>
        <input type="text" name="amount" required><br>

        <label for="description">Description:</label>
        <input type="text" name="description" required><br>

        <label for="date">Date:</label>
        <input type="date" name="date" required><br>

        <label for="group">Group:</label>
        <select name="group">
            <option value="">No Group</option>
            <?php
            foreach ($groups as $groupRow) {
                $selected = isset($_POST['group']) && $_POST['group'] == $groupRow['id'] ? 'selected' : '';
                echo "<option value='" . $groupRow['id'] . "' $selected>" . $groupRow['group_name'] . "</option>";
            }
            ?>
        </select><br>

        <input type="submit" name="add_expense" value="Add Expense">
    </form>
    <h2>Your Groups</h2>
    <label for='group_dropdown'>Select Group:</label>
    <select name='group_dropdown'>
        <option value=''>Select a Group</option>
        <?php
        foreach ($groups as $groupRow) {
            echo "<option value='" . $groupRow['id'] . "'>Group: " . $groupRow['id'] . " | Group Name: " . $groupRow['group_name'] . "</option>";
        }
        ?>
    </select>

    <!-- Form to add a member to the selected group -->
    <h2>Add Member to Group</h2>
    <form action="dashboard.php" method="POST">
        <label for="group_dropdown">Select Group:</label>
        <select name="group_dropdown">
            <option value="">Select a Group</option>
            <?php
            foreach ($groups as $groupRow) {
                echo "<option value='" . $groupRow['id'] . "'>Group: " . $groupRow['id'] . " | Group Name: " . $groupRow['group_name'] . "</option>";
            }
            ?>
        </select><br>

        <label for="member_username">Member Username:</label>
        <input type="text" name="member_username" required><br>

        <input type="submit" name="add_member" value="Add Member">
    </form>

    <?php
    if (!empty($expenses)) {
        echo "<h2>Your Expenses</h2>";
        echo "<form action='dashboard.php' method='POST'>";
        echo "<label for='expense_dropdown'>Select Expense:</label>";
        echo "<select name='expense_dropdown'>";
        echo "<option value=''>Select an Expense</option>";

        foreach ($expenses as $row) {
            $expenseId = $row['id'];
            $groupId = isset($row['group_id']) ? "Group: " . $row['group_id'] : "No Group";
            $groupName = isset($row['group_name']) ? " | Group Name: " . $row['group_name'] : "";
            $optionText = "Amount: " . $row['amount'] . " | Description: " . $row['description'] . " | Date: " . $row['date'] . " | $groupId$groupName";

            $selected = isset($_POST['expense_dropdown']) && $_POST['expense_dropdown'] == $expenseId ? 'selected' : '';

            echo "<option value='$expenseId' $selected>$optionText</option>";
        }

        echo "</select>";
        echo "<input type='submit' name='view_expense' value='View Expense'>";
        echo "</form>";
    } else {
        echo "<p>No expenses recorded yet.</p>";
    }

    echo "<h2>Your Groups</h2>";
    echo "<label for='group_dropdown'>Select Group:</label>";
    echo "<select name='group_dropdown'>";
    echo "<option value=''>Select a Group</option>";

    foreach ($groups as $groupRow) {
        echo "<option value='" . $groupRow['id'] . "'>Group: " . $groupRow['id'] . " | Group Name: " . $groupRow['group_name'] . "</option>";
    }

    echo "</select>";

    ?>

</body>

</html>