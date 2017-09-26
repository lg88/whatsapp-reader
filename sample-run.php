<!DOCTYPE html>
<html>
<body>

<h2>WA Reader Sample Run</h2>

<ul>
    <li>Upload any text file in order to check if the file is valid or not.</li>
    <li>You can find a sample file here ("/sample/whatsapp_conversation.en.txt")</li>
    <li>If you get a success : True and your chat in a parsed format after uploading, your chat file is accurate</li>
    <li>If you get a success : False, your chat file might not be accurate or WA Reader was not able to detect the chat. <strong>Kindly open an issue at <a href="https://github.com/prabhakar267/whatsapp-reader/">GitHub Repo</a></strong></li>
</ul>

<form enctype="multipart/form-data" action="upload-file.php" method="POST">
    <input type="file" name="0" required> <br>
    <input type="submit" value="Submit">
</form>

<ul>
    <li>Use <a href="http://prabhakargupta.com/projects/whatsapp-reader/">WA Reader</a></li>
    <li><a href="https://github.com/prabhakar267/whatsapp-reader">Source Code</a></li>
    <li><a href="http://prabhakargupta.com/">About Me</a></li>
</ul>

</body>
</html>