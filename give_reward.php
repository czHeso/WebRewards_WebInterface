<?php
$config = include('config.php');

$servername = $config['mysql']['host'];
$username = $config['mysql']['username'];
$password = $config['mysql']['password'];
$dbname = $config['mysql']['dbname'];

$mc_server = $config['minecraft']['server'];
$mc_rcon_port = $config['minecraft']['rcon_port'];
$mc_rcon_password = $config['minecraft']['rcon_password'];

// Include RCON library
include('rcon.php');

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['reward_id']) && isset($_POST['reward']) && isset($_POST['player_name'])) {
    $reward_id = $_POST['reward_id'];
    $reward = $_POST['reward'];
    $player_name = $_POST['player_name'];

    $sql = "SELECT player_uuid, {$reward}_command FROM rewards_web WHERE reward_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $reward_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $player_uuid = $row['player_uuid'];
        $command = str_replace("%player%", $player_name, $row["{$reward}_command"]);

        // Connect to Minecraft server using RCON
        $rcon = new Rcon($mc_server, $mc_rcon_port, $mc_rcon_password);
        if ($rcon->connect()) {
            $rcon->send_command($command);

            // Insert into rewards_log
            $log_sql = "INSERT INTO rewards_log (player_uuid, reward_id, reward_img, reward_command, reward_label) VALUES (?, ?, ?, ?, ?)";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("sssss", $player_uuid, $reward_id, $row["{$reward}_img"], $command, $row["{$reward}_label"]);
            $log_stmt->execute();
            $log_stmt->close();

            // Delete from rewards_web
            $delete_sql = "DELETE FROM rewards_web WHERE reward_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("s", $reward_id);
            $delete_stmt->execute();
            $delete_stmt->close();

            echo json_encode(array("success" => "Reward given successfully"));
            $rcon->disconnect();
        } else {
            echo json_encode(array("error" => "Failed to connect to Minecraft server"));
        }
    } else {
        echo json_encode(array("error" => "No rewards found for the given ID"));
    }

    $stmt->close();
} else {
    echo json_encode(array("error" => "Invalid input"));
}

$conn->close();
?>
