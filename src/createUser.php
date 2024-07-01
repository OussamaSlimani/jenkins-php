<?php
include 'connect.php';
include 'header.php';

$message = ""; // Initialize message variable

if (isset($_POST['submit'])) {
  $name = $_POST['name'];
  $email = $_POST['email'];
  $balance = $_POST['balance'];

  // Prepare statement
  $stmt = $conn->prepare("INSERT INTO users (name, email, balance) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $name, $email, $balance);

  // Execute statement
  try {
    if ($stmt->execute()) {
      $message = "Successfully created user.";
    } else {
      $message = "Failed to create user.";
    }
  } catch (mysqli_sql_exception $e) {
    $message = "Failed to create user: " . $e->getMessage();
  }
}
?>

<div class="container my-5">
  <?php if (!empty($message)) : ?>
    <div class="alert <?php echo (strpos($message, 'Successfully') !== false) ? 'alert-success' : 'alert-danger'; ?>" role="alert">
      <?php echo htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-4">Create User</h5>
          <form method="post" action="createUser.php">
            <div class="mb-3">
              <label for="name" class="form-label">Name</label>
              <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
              <label for="balance" class="form-label">Balance</label>
              <input type="number" class="form-control" id="balance" name="balance" required>
            </div>
            <button type="submit" class="btn btn-primary" name="submit">Create</button>
            <button type="reset" class="btn btn-secondary">Reset</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
include 'footer.php';
?>