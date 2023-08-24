<!DOCTYPE html>
<html>
<head>
    <title>Real-Time Chat</title>
</head>
<body>
<h1>Real-Time Chat - <?php echo $_COOKIE['username']; ?></h1>
<div id="chat-box"></div>
<input type="text" id="message" placeholder="Type your message..." />
<button onclick="sendMessage()">Send</button>

<script>
    //version 1.0.0 that works
    const socket = new WebSocket('ws://localhost:8080?username=<?php echo $_COOKIE["username"]; ?>&roomId=<?php echo $_GET["roomId"]; ?>');
    const sender = '<?php echo $_COOKIE["username"]; ?>'; // Retrieve username from cookie

    socket.onopen = function(event) {
        console.log('Connected to WebSocket server');
    };
//this is for real senders
    socket.onmessage = function(event) {
    const messageData = JSON.parse(event.data);
    console.log('Message received:', messageData.sender, messageData.content);
    displayMessage(messageData.sender, messageData.content);
};

    function sendMessage() {
        console.log('Sender:', sender);
        console.log('Send button clicked');
        const messageInput = document.getElementById('message');
        const content = messageInput.value;
        const message = JSON.stringify({ sender, content, channel: '<?php echo $_GET["roomId"]; ?>' });

        socket.send(message);
        messageInput.value = '';
    }

    function displayMessage(sender, content) {
        const chatBox = document.getElementById('chat-box');
        const messageElement = document.createElement('p');
        messageElement.innerHTML = `<strong>${sender}: </strong>${content}`;
        chatBox.appendChild(messageElement);
        chatBox.scrollTop = chatBox.scrollHeight;
    }
</script>

</body>
</html>
