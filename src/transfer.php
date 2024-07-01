<?php
include 'connect.php';
include 'header.php';

$sql = "SELECT * FROM users";
$result = mysqli_query($conn, $sql);

if (!$result) {
  // Query execution failed
  echo "Error: " . mysqli_error($conn);
  exit;
}
?>

<div class="container my-5">
  <div class="table-container">
    <h1 class="heading">Transfer Money</h1>
    <table class="table">
      <thead>
        <tr>
          <th>Id</th>
          <th>Name</th>
          <th>Email</th>
          <th>Balance</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        while ($rows = mysqli_fetch_assoc($result)) {
        ?>
          <tr>
            <td data-label="Id"><?php echo $rows['id'] ?></td>
            <td data-label="Name"><?php echo $rows['name'] ?></td>
            <td data-label="Email"><?php echo $rows['email'] ?></td>
            <td data-label="Balance" class="text-balance"><?php echo $rows['balance'] ?></td>
            <td class="btn"><a class="btn btn-primary" href="transaction.php?id=<?php echo $rows['id']; ?>">Transfer</a></td>
          </tr>
        <?php
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<?php
include 'footer.php';
?>