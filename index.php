<?php
$config = include('config.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://www.youtube.com/iframe_api"></script>
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            text-align: center;
        }
        #countdown {
            font-weight: bold;
            color: red;
        }
        .reward {
            display: inline-block;
            margin: 10px;
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
            cursor: pointer;
        }
        .reward img {
            max-width: 100px;
            max-height: 100px;
        }
        #playerNameDisplay {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 18px;
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.5);
            padding: 5px 10px;
            border-radius: 5px;
            color: white;
            font-size: 14px;
            text-align: center;
        }
        .footer a {
            color: #FFD700;
            text-decoration: none;
        }
        /* Custom Popup */
        .custom-popup {
            display: none;
            position: fixed;
            z-index: 2;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 300px;
            padding: 20px;
            background-color: #4CAF50;
            color: white;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        .custom-popup button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: white;
            color: #4CAF50;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .custom-popup button:hover {
            background-color: #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div id="videoModal" class="modal">
            <div class="modal-content">
                <div id="player"></div>
                <p>You need to watch the video for 5 seconds to proceed. Time left: <span id="countdown">5</span></p>
            </div>
        </div>
        <div id="inputContainer">
            <h1>Enter your Reward ID</h1>
            <input type="text" id="rewardIdInput" placeholder="Enter your reward ID">
            <input type="text" id="playerNameInput" placeholder="Enter your player name">
            <button onclick="submitForm()">Submit</button>
        </div>
        <div id="playerNameDisplay"></div>
        <div id="rewardsContainer"></div>
        <div class="footer">
            Developed with Love By <a href="https://discord.gg/NvbuhzP95J" target="_blank">Heso</a>
        </div>
        <!-- Custom Popup -->
        <div id="customPopup" class="custom-popup">
            <p>Reward claimed successfully!</p>
            <button onclick="closePopup()">Close</button>
        </div>
    </div>

    <script>
        let player;
        let countdownInterval;
        let countdown = 5;

        // Set the video ID from PHP config
        const videoId = '<?php echo $config['youtube']['video_id']; ?>';

        function onYouTubeIframeAPIReady() {
            player = new YT.Player('player', {
                height: '360',
                width: '640',
                videoId: videoId,
                events: {
                    'onStateChange': onPlayerStateChange
                }
            });
        }

        function onPlayerStateChange(event) {
            if (event.data == YT.PlayerState.PLAYING && !countdownInterval) {
                countdownInterval = setInterval(() => {
                    countdown--;
                    document.getElementById('countdown').textContent = countdown;
                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        player.stopVideo();
                        document.getElementById('videoModal').style.display = 'none';
                    }
                }, 1000);
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById('videoModal').style.display = 'block';
            const urlParams = new URLSearchParams(window.location.search);
            const rewardId = urlParams.get('reward_id');
            if (rewardId) {
                document.getElementById('rewardIdInput').value = rewardId;
            }
        });

        function submitForm() {
            const playerName = document.getElementById('playerNameInput').value;
            const rewardId = document.getElementById('rewardIdInput').value;

            if (!playerName || !rewardId) {
                alert('Please enter both your player name and reward ID.');
                return;
            }

            document.getElementById('playerNameDisplay').textContent = `Player: ${playerName}`;
            document.getElementById('inputContainer').style.display = 'none';
            fetchRewards(rewardId);
        }

        function fetchRewards(rewardId) {
            fetch(`get_rewards.php?reward_id=${rewardId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                    } else {
                        displayRewards(data);
                    }
                });
        }

        function displayRewards(data) {
            const rewardsContainer = document.getElementById('rewardsContainer');
            rewardsContainer.innerHTML = `
                <h2>Choose your reward</h2>
                <div class="reward" onclick="claimReward('${data.reward_id}', 'reward1')">
                    <img src="${data.reward1_img}" alt="${data.reward1_label}">
                    <p>${data.reward1_label}</p>
                </div>
                <div class="reward" onclick="claimReward('${data.reward_id}', 'reward2')">
                    <img src="${data.reward2_img}" alt="${data.reward2_label}">
                    <p>${data.reward2_label}</p>
                </div>
                <div class="reward" onclick="claimReward('${data.reward_id}', 'reward3')">
                    <img src="${data.reward3_img}" alt="${data.reward3_label}">
                    <p>${data.reward3_label}</p>
                </div>
            `;
        }

        function claimReward(rewardId, reward) {
            const playerName = document.getElementById('playerNameInput').value;
            fetch('give_reward.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `reward_id=${rewardId}&reward=${reward}&player_name=${playerName}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showPopup();
                } else {
                    alert(data.error);
                }
            });
        }

        function showPopup() {
            const popup = document.getElementById('customPopup');
            popup.style.display = 'block';
        }

        function closePopup() {
            const popup = document.getElementById('customPopup');
            popup.style.display = 'none';
        }
    </script>
</body>
</html>
