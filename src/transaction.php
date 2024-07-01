<?php
include 'header.php';
include 'connect.php';

if (isset($_POST['submit'])) {
  $from = (int) $_GET['id'];
  $to = (int) $_POST['to'];
  $amount = (float) $_POST['amount'];

  // Check if amount is non-negative
  if ($amount <= 0) {
    $message = "Invalid amount. Please enter a positive number.";
  } else {
    // Fetch sender's details
    $sql = "SELECT * FROM users WHERE id = $from";
    $result = mysqli_query($conn, $sql);
    $sql1 = mysqli_fetch_array($result);

    // Fetch receiver's details
    $sql = "SELECT * FROM users WHERE id = $to";
    $result = mysqli_query($conn, $sql);
    $sql2 = mysqli_fetch_array($result);

    // Check if sufficient balance is available
    if ($amount > $sql1['balance']) {
      $message = "Insufficient Balance";
    } else {
      // Perform transaction
      $newbalanceSender = $sql1['balance'] - $amount;
      $newbalanceReceiver = $sql2['balance'] + $amount;

      // Update sender's balance
      $sql = "UPDATE users SET balance = $newbalanceSender WHERE id = $from";
      mysqli_query($conn, $sql);

      // Update receiver's balance
      $sql = "UPDATE users SET balance = $newbalanceReceiver WHERE id = $to";
      mysqli_query($conn, $sql);

      // Record transaction
      $sender = $sql1['name'];
      $receiver = $sql2['name'];
      $sql = "INSERT INTO transaction (sender, receiver, balance) VALUES ('$sender', '$receiver', $amount)";
      $query = mysqli_query($conn, $sql);

      if ($query) {
        $message = "Transaction Successful";
      } else {
        $message = "Transaction Failed. Please try again later.";
      }
    }
  }
}
?>

<div class="container my-5">
  <?php if (!empty($message)) : ?>
    <div class="alert <?php echo (strpos($message, 'Successful') !== false) ? 'alert-success' : 'alert-danger'; ?>" role="alert">
      <?php echo htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>

  <div class="row justify-content-center p-2">

    <!-- Sender information table -->
    <div class="col-md-6">
      <div class="table-container">
        <h3>The sender information</h3>
        <?php
        $sid = (int) $_GET['id'];
        $sql = "SELECT * FROM users WHERE id = $sid";
        $result = mysqli_query($conn, $sql);

        if (!$result) {
          echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        } else {
          $rows = mysqli_fetch_assoc($result);
        ?>

          <table class="table table-striped">
            <thead>
              <tr>
                <th>Id</th>
                <th>Name</th>
                <th>Email</th>
                <th>Balance</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><?php echo $rows['id']; ?></td>
                <td><?php echo $rows['name']; ?></td>
                <td><?php echo $rows['email']; ?></td>
                <td class="text-balance"><?php echo $rows['balance']; ?></td>
              </tr>
            </tbody>
          </table>
        <?php } ?>
      </div>
    </div>
    <!-- End of sender information table -->

    <!-- Start of transfer form -->
    <div class="col-md-6">

      <div class="card">
        <div class="card-body">
          <div class="forms">
            <div class="title my-3">
              <h4> Transfer Money</h4>
            </div>
            <form method="post" name="tcredit">
              <div class="mb-2">
                <input name="amount" type="number" class="form-control" placeholder="Enter Amount" step="any" required />
              </div>
              <div class="mb-2">
                <select name="to" class="form-control transfer-user" required>
                  <option value="" disabled selected>Choose</option>
                  <?php
                  $sid = (int) $_GET['id'];
                  $sql = "SELECT * FROM users WHERE id != $sid";
                  $result = mysqli_query($conn, $sql);
                  if (!$result) {
                    echo "Error: " . $sql . "<br>" . mysqli_error($conn);
                  } else {
                    while ($rows = mysqli_fetch_assoc($result)) {
                      echo '<option value="' . $rows['id'] . '">' . $rows['name'] . ' (Balance: ' . $rows['balance'] . ' - Email: ' . $rows['email'] . ')</option>';
                    }
                  }
                  ?>
                </select>
              </div>
              <div class="mb-2">
                <input name="submit" type="submit" class="btn btn-primary" value="Transfer" />
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <!-- End of transfer form -->
  </div>


</div>

<?php
include 'footer.php';
?>