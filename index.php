<?php
function getDOMNodeFrom($url, $nodename) {
	$dom = new DOMDocument;
	$dom->preserveWhiteSpace = false;
	if (@!$dom->load($url)) {
		return;
	}
	$search = $dom->getElementsByTagName($nodename);
	if ($search->length < 1) {
		return;
	}
	return $search->item(0);
}
function findPHPUser($username) {
	$retval = @file_get_contents("https://master.php.net/fetch/user.php?username=" . $username);
	if (!$retval) {
		list($protocol, $errcode, $errmsg) = explode(" ", $http_response_header[0], 3);
		error($errmsg);
		exit;
	}
	$json = json_decode($retval, true);
	if (isset($json["error"])) {
		error($json["error"]);
	}
	return $json;
}
function findGitHubUser($fullname) {
	$username = getDOMNodeFrom("http://github.com/api/v2/xml/user/search/" . urlencode($fullname), "username");
	if (!$username) {
		return;
	}

	$content = file_get_contents("http://github.com/api/v2/xml/user/show/" . $username->nodeValue);

	$r = new XMLReader;
	$r->XML($content);

	$retval = array();
	while($r->read()) {
		if ($r->nodeType == XMLReader::ELEMENT) {
			$key = $r->name;
		} elseif ($r->nodeType == XMLReader::TEXT) {
			$retval[$key] = $r->value;
		}
	}
	return $retval;

}
function findPEARUser($username) {
	$geo = getDOMNodeFrom("http://pear.php.net/map/locationREST.php?handle=" . $username, "based_near");
	if (!$geo) {
		return;
	}
	return array(
		"lat"  => $geo->getAttribute("geo:lat"),
		"long" => $geo->getAttribute("geo:long"),
	);
}
function error($errormsg) {
  echo '<p class="warning error">', $errormsg, "</p></body></html>";
  exit;
}
$USERNAME = filter_input(INPUT_GET, "username", FILTER_SANITIZE_ENCODED, FILTER_FLAG_STRIP_HIGH);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">
<html
  xml:lang="en"
  xmlns="http://www.w3.org/1999/xhtml" 
  xmlns:foaf="http://xmlns.com/foaf/0.1/"
  xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#"
>
<head>
  <title>PHP: Hypertext Preprocessor</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link type="text/css" media="all" rel="stylesheet" href="styles.css" />
  <link rel="shortcut icon" href="http://php.net/favicon.ico" />
</head>

<body>
  <h1 id="header"><a href="http://php.net/index.php">PHP: Hypertext Preprocessor</a> - Profiles</h1>
  <ul id="mainmenu">
    <li><a href="http://php.net/downloads.php">Downloads</a></li>
    <li><a href="http://php.net/docs.php">Documentation</a></li>
    <li><a href="http://php.net/FAQ.php">Faq</a></li>
    <li><a href="http://php.net/support.php">Getting Help</a></li>
    <li><a href="http://php.net/mailing-lists.php">Mailing Lists</a></li>
    <li><a href="http://bugs.php.net">Reporting Bugs</a></li>
    <li><a href="http://php.net/sites.php">Php.net Sites</a></li>
    <li><a href="http://php.net/links.php">Links</a></li>
    <li><a href="http://php.net/conferences/">Conferences</a></li>
    <li><a href="http://php.net/my.php">My Php.net</a></li>
  </ul>
  <!--<p class="warning"><strong>WARNING</strong>: This is just for fun.</p>-->

<?php
$NFO      = findPHPUser($USERNAME);
$PEAR     = findPEARUser($USERNAME);
$GITHUB   = findGitHubUser($NFO["name"]);
$email    = $NFO["enable"] ? $NFO["username"].'@php.net' : "";
$location = isset($PEAR["long"], $PEAR["lat"]) ? $PEAR["lat"] . ", " . $PEAR["long"] : null;
?>
<div about="#me" typeof="foaf:Person" id="profile">
<?php if ($email): ?>
  <span rel="foaf:img"><img rel="foaf:img" src="https://secure.gravatar.com/avatar.php?gravatar_id=<?php echo md5($email) ?>" alt="Picture of <?php echo $NFO["name"]?>" height="80" width="80" /></span>
<?php endif ?>
<?php if ($NFO["name"]): ?>
  <span property="foaf:name"><?php echo $NFO["name"]?></span>
<?php endif ?>
  (<span property="foaf:nick"><?php echo $NFO["username"]?></span>)
  a member of <a href="http://www.php.net" rel="foaf:Organization">PHP</a><!--
  and works on <a href="http://doc.php.net/phd/" rel="foaf:currentProject">PhD</a>-->
<?php if (isset($GITHUB["company"])): ?>
, currently working for <?php echo $GITHUB["company"]?>
<?php endif ?>
<?php if (isset($GITHUB["location"])): ?>
, living in
  <?php if($location): ?>
  <?php $q = urlencode($location) ?>
	<a href="http://maps.google.com/?q=<?php echo $q ?>"><?php echo $GITHUB["location"] ?></a>
  <?php else: ?>
	<?php echo $GITHUB["location"] ?>
  <?php endif ?>
<?php endif ?>
. 
  <dl>
<?php if ($email): ?>
	<dt>Email</dt>
	<dd><a rel="foaf:mbox" href="mailto:<?php echo $email ?>"><?php echo $email ?></a></dd>
<?php endif ?>
<?php if (isset($GITHUB["blog"])): ?>
    <dt>Weblog</dt>
    <dd><a rel="foaf:weblog" href="<?php echo $GITHUB["blog"]?>"><?php echo $GITHUB["blog"]?></a></dd>
<?php endif ?>
<?php if (isset($GITHUB["company"])): ?>
	<dt>Employer</dt>
	<dd><?php echo $GITHUB["company"]?></dd>
<?php endif ?>
<?php if (isset($GITHUB["location"])): ?>
	<dt>Location</dt>
	<dd><?php echo $GITHUB["location"] ?></dd>
<?php endif ?>
<?php if (isset($PEAR["long"], $PEAR["lat"])): ?>
    <dt>Geo location</dt>
	<?php $q = urlencode($location) ?>
	<dd><a href="http://maps.google.com/?q=<?php echo $q ?>"><span property="geo:lat"><?php echo $PEAR["lat"]?></span>, <span property="geo:long"><?php echo $PEAR["long"]?></span></a></dt>
<?php endif ?>
  </dl>

<?php if ($NFO["notes"]): ?>
  <h2 id="notes">Notes:</h2>
<?php endif ?>
<?php foreach($NFO["notes"] as $note): ?>
  <div class="note">
	<?php echo $note["entered"] ?>:
  	<?php echo $note["note"] ?>
  </div>
<?php endforeach ?>
</div>

</body>
</html>
