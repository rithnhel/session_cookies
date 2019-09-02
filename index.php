<?php
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$sessionStatus = [
	PHP_SESSION_DISABLED => 'PHP_SESSION_DISABLED',
	PHP_SESSION_NONE => 'PHP_SESSION_NONE',
	PHP_SESSION_ACTIVE => 'PHP_SESSION_ACTIVE',
];

session_start();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '/', 36000,
        $params["path"], $params["domain"],
        false, false
    );
}

//Manage actions sent as GET parameter
$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
$url = strtok($url, '?');
if (isset($_GET["action"])) {
	switch ($_GET["action"]) {
		case "setcookie":
			setcookie( trim($_GET["k"]), $_GET["v"], strtotime( '+1 days' ) );
			header("Location: $url");
			break;
		case "setsession":
			$_SESSION[trim($_GET["k"])] = $_GET["v"];
			session_write_close();
			header("Location: $url");
			break;
		case "destroy": 
			setcookie( session_name(), "", time() - 3600);
			session_destroy();
			header("Location: $url");
			break;
	}
}
?>
<html lang="en">
<head>
	<title>Sessions and cookies</title>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<link href="styles.css" rel="stylesheet">
</head>
	
<body>

<table border="1" width="100%">
 <tr>
  <td width="50%" style="vertical-align:top;">
   <h3>Client (browser)</h3>
   <p>Current cookies (on this domain):</p>
   <ul id="lstCookies"></ul>
  </td>
  
  <td width="50%" style="vertical-align:top;">
   <h3>Server (PHP)</h3>
   <h4>Cookies</h4>
  <input type="text" id="ckey" placeholder="Cookie key">
  <input type="text" id="cvalue" placeholder="Cookie value">
  <button onclick="location.href=`index.php?action=setcookie&k=${document.getElementById('ckey').value}&v=${document.getElementById('cvalue').value}`" >Set in cookie</button>
	<p>Content of $_COOKIE variable:</p>
	<ul>
	<?php
	foreach ($_COOKIE as $key => $value) {
		echo " <li>$key = $value</li>";
	}
	?>
	</ul>
   
   <hr />
   
   <h4>Session</h4>
  <button onclick="location.href='index.php?action=destroy'" <?php echo !isset($_SESSION)?"disabled":"";?>>Destroy the session</button><br />
  <input type="text" id="skey" placeholder="Session key">
  <input type="text" id="svalue" placeholder="Session value">
  <button onclick="location.href=`index.php?action=setsession&k=${document.getElementById('skey').value}&v=${document.getElementById('svalue').value}`" >Set in session</button>

   <p>Folder where the sessions are stored: <tt><?php echo session_save_path();?></tt></p>
   <p>Session status: <tt><?php echo $sessionStatus[session_status()];?></tt></p>
   
	<p>Content of $_SESSION variable: <?php (empty($_SESSION))?"<b>EMPTY</b>":""; ?>
	</p>
	<ul>
	<?php
	foreach ($_SESSION as $key => $value) {
		echo " <li>$key = $value</li>";
	}
	?>
	</ul>

<p>Current sessions (on this server):</p>
<ul>
<?php $allSessions = listAllSessions();
foreach ($allSessions as $key => $value) {
	echo " <li><span id='$key' class='inspector'>&#128270;</span> $key</li>";
}
?>
</ul>

  </td>
 </tr>
</table>

<!-- The Modal -->
<div id="frmSessionInspector" class="modal">

  <!-- Modal content -->
  <div class="modal-content">
    <span class="close">&times;</span>
    <div id="remoteContent">
		<img src="loading.gif" />
	</div>
  </div>

</div>

</body>

<script type="text/javascript">
//Modal management -------------------------------------------------
var modal = document.getElementById("frmSessionInspector");
var span = document.getElementsByClassName("close")[0];
// When the user clicks on <span> (x), close the modal
span.onclick = function() {
  document.getElementById("remoteContent").innerHTML = "<img src='loading.gif' />";
  modal.style.display = "none";
}
// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target == modal) {
	document.getElementById("remoteContent").innerHTML = "<img src='loading.gif' />";
    modal.style.display = "none";
  }
}
//Modal management -------------------------------------------------

//List all cookies on the browser for the current domain
function listCookies(target) {
    let allCookies = document.cookie.split(';');
	let ul = document.getElementById(target);
    let aString = '';
    for (let i = 1 ; i <= allCookies.length; i++) {
        let node = document.createElement('li');
		node.innerText = allCookies[i-1];
		ul.appendChild(node);
    }
}

listCookies("lstCookies");

var inspectors = document.getElementsByClassName("inspector");
Array.from(inspectors).forEach(function(element) {
  element.addEventListener('click', function (e) {
	  modal.style.display = "block";
		fetch(`inspect.php?name=${this.id}`)
		.then(function(response) {
		  if (!response.ok) {
			throw new Error('HTTP error, status = ' + response.status);
		  }
		  return response.text();
		})
		.then(function(response) {
		  let contentNode = document.getElementById("remoteContent");
		  contentNode.innerHTML = response;
		});
  });
});
</script>

</html>


<?php
//List the sessions stored on the server (e.g. C:\wamp64\tmp)
//Return an array of sessions indexed by session id
function listAllSessions() {
	$allSessions = [];
	$sessionNames = scandir(session_save_path());

	foreach($sessionNames as $sessionName) {
		$sessionName = str_replace("sess_","",$sessionName);
		$sessionName = str_replace("ci_session","",$sessionName);
		if(strpos($sessionName,".") === false) { //This skips temp files that aren't sessions
			try {
			@session_id($sessionName);
			@session_start();
			$allSessions[$sessionName] = $_SESSION;
			@session_abort();
			} catch (Exception $e) {}
		}
	}
	return $allSessions;
}

?>