<?php
$conn = mysqli_connect("localhost", "root", "", "kientruc_v1");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
$result = mysqli_query($conn, "SHOW TABLES LIKE 'project_journal'");
if (mysqli_num_rows($result) > 0) {
    echo "Table project_journal exists in kientruc_v1\n";
} else {
    echo "Table project_journal DOES NOT exist in kientruc_v1\n";
}
mysqli_close($conn);
?>
