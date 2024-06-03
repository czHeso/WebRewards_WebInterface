<?php
$config = include('config.php');

$servername = $config['mysql']['host'];
$username = $config['mysql']['username'];
$password = $config['mysql']['password'];
$dbname = $config['mysql']['dbname'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['reward_id'])) {
    $reward_id = $_GET['reward_id'];

    $sql = "SELECT * FROM rewards_web WHERE reward_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $reward_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode($row);
    } else {
        echo json_encode(array("error" => "No rewards found for the given ID"));
    }

    $stmt->close();
} else {
    echo json_encode(array("error" => "No reward ID provided"));
}

$conn->close();
?>
