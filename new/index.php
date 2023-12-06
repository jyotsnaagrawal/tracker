<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch individual expenses for the user
$individualExpensesSql = "SELECT * FROM expenses WHERE user_id='$user_id'";
$individualExpensesResult = $conn->query($individualExpensesSql);
$individualExpenses = [];

while ($row = $individualExpensesResult->fetch_assoc()) {
    $individualExpenses[] = $row;
}

// Fetch tax report for the user
$taxReportSql = "SELECT SUM(amount) AS total_expenses, SUM(amount * 0.2) AS tax_deductible, SUM(amount * 0.8) AS taxable_income FROM expenses WHERE user_id='$user_id'";
$taxReportResult = $conn->query($taxReportSql);
$taxReport = $taxReportResult->fetch_assoc();

// Fetch group members
$groupMembersSql = "SELECT * FROM group_members WHERE group_id IN (SELECT group_id FROM group_members WHERE member_id='$user_id')";
$groupMembersResult = $conn->query($groupMembersSql);
$groupMembers = [];

while ($row = $groupMembersResult->fetch_assoc()) {
    $groupMembers[] = $row;
}

// Fetch group balances
$groupBalancesSql = "SELECT username, SUM(amount) AS balance FROM users
                    JOIN group_members ON users.id = group_members.member_id
                    LEFT JOIN expenses ON group_members.member_id = expenses.user_id
                    GROUP BY username";
$groupBalancesResult = $conn->query($groupBalancesSql);
$groupBalances = [];

while ($row = $groupBalancesResult->fetch_assoc()) {
    $groupBalances[] = $row;
}

// Fetch group expense details
$groupExpenseDetailsSql = "SELECT expenses.id, expenses.amount, expenses.description, expenses.group_id, groups.group_name, users.username
                            FROM expenses
                            LEFT JOIN groups ON expenses.group_id = groups.id
                            LEFT JOIN users ON expenses.user_id = users.id
                            WHERE expenses.group_id IN (SELECT group_id FROM group_members WHERE member_id='$user_id')";
$groupExpenseDetailsResult = $conn->query($groupExpenseDetailsSql);
$groupExpenseDetails = [];

while ($row = $groupExpenseDetailsResult->fetch_assoc()) {
    $groupExpenseDetails[] = $row;
}

// Fetch group settlement details
$groupSettlementSql = "SELECT users.username, SUM(amount) AS total_owed
                        FROM group_members
                        JOIN users ON group_members.member_id = users.id
                        LEFT JOIN expenses ON group_members.member_id = expenses.user_id
                        GROUP BY users.username";
$groupSettlementResult = $conn->query($groupSettlementSql);
$groupSettlements = [];

while ($row = $groupSettlementResult->fetch_assoc()) {
    $groupSettlements[] = $row;
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

    <h2>Individual Expenses for User</h2>
    <?php
    foreach ($individualExpenses as $expense) {
        echo "- " . $expense['description'] . ", " . $expense['amount'] . ", " . $expense['category'] . "<br>";
    }
    ?>

    <h2>Tax Report for User</h2>
    <p>Total Expenses: <?php echo $taxReport['total_expenses']; ?></p>
    <p>Tax Deductible: <?php echo $taxReport['tax_deductible']; ?></p>
    <p>Taxable Income: <?php echo $taxReport['taxable_income']; ?></p>

    <h2>Group Members</h2>
    <ul>
        <?php
        foreach ($groupMembers as $member) {
            echo "<li>" . $member['username'] . "</li>";
        }
        ?>
    </ul>

    <h2>Group Balances</h2>
    <table>
        <tr>
            <th>Member</th>
            <th>Balance</th>
        </tr>
        <?php
        foreach ($groupBalances as $balance) {
            echo "<tr>";
            echo "<td>" . $balance['username'] . "</td>";
            echo "<td>" . $balance['balance'] . "</td>";
            echo "</tr>";
        }
        ?>
    </table>

    <h2>Group Expense Details</h2>
    <table>
        <tr>
            <th>Expense ID</th>
            <th>Amount</th>
            <th>Description</th>
            <th>Group</th>
            <th>Paid by</th>
        </tr>
        <?php
        foreach ($groupExpenseDetails as $expense) {
            echo "<tr>";
            echo "<td>" . $expense['id'] . "</td>";
            echo "<td>" . $expense['amount'] . "</td>";
            echo "<td>" . $expense['description'] . "</td>";
            echo "<td>" . $expense['group_name'] . "</td>";
            echo "<td>" . $expense['username'] . "</td>";
            echo "</tr>";
        }
        ?>
    </table>

    <h2>Group Settlement Details</h2>
    <?php
    foreach ($groupSettlements as $settlement) {
        echo $settlement['username'] . " owes " . $settlement['total_owed'] . " for group expenses.<br>";
    }
    ?>

</body>

</html>
