<?php
error_debug("including url.php");

function url_action($matches, $key="action") {
	//don't know whether this is any good.  matches possible $_GET["action"] values
	global $_GET;
	if (isset($_GET[$key])) {
		$matches = explode(",", $matches);
		foreach ($matches as $m) if ($_GET[$key] == trim($m)) return true;
	}
	return false;
}

function url_action_add($action=false, $go=false) {
	return url_query_add(array("action"=>$action), $go);
}

function url_base() {
	global $_josh;
	return $_josh["request"]["protocol"] . "://" . $_josh["request"]["host"];
}

function url_change($target="") {
	global $_josh, $_POST;
	
	if ($target === false) { //if redirect is really set to FALSE, send to site home
		$target = "/";
	} elseif (empty($target)) { //if redirect is an empty string, refresh the page by sending back to same URL
		$target = $_josh["request"]["path_query"];
	}
	if ($_josh["slow"]) {
		error_debug("<b>url_change</b> (slow) to " . $target);
		if ($_josh["debug"]) db_close(); //exit early so you can see log
		echo "<html><head>
				<script language='javascript'>
					<!--
					location.href='" . $target . "';
					//-->
				</script>
			</head><body></body></html>";
	} else {
		error_debug("<b>url_change</b> (fast) to " . $target);
		if ($_josh["debug"]) db_close(); //exit early so you can see log
		header("Location: " . $target);
	}
	db_close();
}

function url_change_get($target="") {
	global $_GET;
	if (isset($_GET["return_to"]) && !empty($_GET["return_to"])) $target = $_GET["return_to"];
	url_change($target);
}

function url_change_post($target="") {
	global $_POST;
	if (isset($_POST["return_to"]) && !empty($_POST["return_to"])) $target = $_POST["return_to"];
	url_change($target);
}

function url_drop($deletes=false, $go=true) {
	//alias for url_query_drop
	return url_query_drop($deletes, $go);
}

function url_id($index="id") {
	global $_GET;
	//check to see whether there's an id and if so, if it's an integer
	if (isset($_GET[$index]) && format_check($_GET[$index])) return true;
	unset($_GET[$index]);
	return false;
}

function url_parse($url) {
	error_debug("<b>url_parse</b> running for  " . $url);
	global $_GET;
	$gtlds = explode(',', str_replace(' ', '', "aero, biz, com, coop, info,
	jobs, museum, name, net, org, pro, travel, gov, edu, mil, int, site"));

	$ctlds = explode(',', str_replace(' ', '', "ac, ad, ae, af, ag, ai, al,
	am, an, ao, aq, ar, as, at, au, aw, az, ax, ba, bb, bd, be, bf, bg, bh,
	bi, bj, bm, bn, bo, br, bs, bt, bv, bw, by, bz, ca, cc, cd, cf, cg, ch,
	ci, ck, cl, cm, cn, co, cr, cs, cu, cv, cx, cy, cz, de, dj, dk, dm, do,
	dz, ec, ee, eg, eh, er, es, et, eu, fi, fj, fk, fm, fo, fr, ga, gb, gd,
	ge, gf, gg, gh, gi, gl, gm, gn, gp, gq, gr, gs, gt, gu, gw, gy, hk, hm,
	hn, hr, ht, hu, id, ie, il, im, in, io, iq, ir, is, it, je, jm, jo, jp,
	ke, kg, kh, ki, km, kn, kp, kr, kw, ky, kz, la, lb, lc, li, lk, lr, ls,
	lt, lu, lv, ly, ma, mc, md, mg, mh, mk, ml, mm, mn, mo, mp, mq, mr, ms,
	mt, mu, mv, mw, mx, my, mz, na, nc, ne, nf, ng, ni, nl, no, np, nr, nu,
	nz, om, pa, pe, pf, pg, ph, pk, pl, pm, pn, pr, ps, pt, pw, py, qa, re,
	ro, ru, rw, sa, sb, sc, sd, se, sg, sh, si, sj, sk, sl, sm, sn, so, sr,
	st, sv, sy, sz, tc, td, tf, tg, th, tj, tk, tl, tm, tn, to, tp, tr, tt,
	tv, tw, tz, ua, ug, uk, um, us, uy, uz, va, vc, ve, vg, vi, vn, vu, wf,
	ws, ye, yt, yu, za, zm, zw"));

	//add protocol if missing.  when would this be missing?
	if (!strstr($url, 'http://') && !strstr($url, 'https://')) $url = "http://" . $url; 

	$subs			= ''; 
	$domainname		= ''; 
	$tld			= ''; 
	$tldarray		= array_merge($gtlds, $ctlds); 
	$tld_isReady	= false;
	$return			= parse_url(trim($url));
	$domainarray	= explode('.', $return["host"]);
	$top			= count($domainarray);
	
	for ($i = 0; $i < $top; $i++) {
		$_domainPart = array_pop($domainarray);
		if (!$tld_isReady) {
			if (in_array($_domainPart, $tldarray)) {
				$tld = ".$_domainPart" . $tld;
			} else {
				$domainname = $_domainPart;
				$tld_isReady = 1;
			}
		} else {
			$subs = ".$_domainPart" . $subs;
		}
	}

	if (!isset($return["path"])) $return["path"] = "";
	$return["domainname"]	= $domainname;
	$return["domain"]		= $domainname . $tld;
	$return["usingwww"]		= (substr($return["host"], 0, 4) == "www.") ? 1 : 0;
	$return["sanswww"]		= ($return["usingwww"]) ? substr($return["host"], 4) : $return["host"];
	$return["subdomain"]	= substr($subs, 1);
	$return["path"]			= str_replace("index.php", "", $return["path"]);
	$return["path_query"]	= $return["path"];
	
	//get folder, subfolder
	$urlparts = explode("/", $return["path_query"]);
	$urlcount = count($urlparts);

	if ($urlcount < 3) {
		$return["folder"]		= false;
		$return["subfolder"]	= false;
		$return["subsubfolder"]	= false;
	} elseif ($urlcount == 3) {
		$return["folder"]		= $urlparts[1];
		$return["subfolder"]	= false;
		$return["subsubfolder"]	= false;
	} elseif ($urlcount == 4) {
		$return["folder"]		= $urlparts[1];
		$return["subfolder"]	= $urlparts[2];
		$return["subsubfolder"]	= false;
	} else {
		$return["folder"]		= $urlparts[1];
		$return["subfolder"]	= $urlparts[2];
		$return["subsubfolder"]	= $urlparts[3];
	}
	
	//add query string to path_query
	//don't use $_GET because we might be parsing a different address
	if (isset($return["query"])) {
		$return["path_query"] .= "?" . $return["query"];
	} else {
		$return["query"] = false;
	}
	
	//protocol is a better word than scheme
	$return["protocol"] = $return["scheme"];
	
	//get full browser address
	$return["url"]			= $return["protocol"] . "://" . $return["host"] . $return["path_query"];
	
	//handle possible mod_rewrite slots
	if (isset($_GET["slot1"])) {
		$return["folder"] = $_GET["slot1"];
		$return["path"] = "/" . $_GET["slot1"] . "/";
		if (isset($_GET["slot2"])) {
			$return["subfolder"] = $_GET["slot2"];
			$return["path"] .= $_GET["slot2"] . "/";
			if (isset($_GET["slot3"])) {
				$return["subsubfolder"] = $_GET["slot3"];
				$return["path"] .= $_GET["slot3"] . "/";
			}
		}
		$return["path_query"] = $return["path"];
	}


	ksort($return);
	//die(draw_array($return));
	return $return;
}

function url_query_add($adds, $go=true, $path=false) { //add specified query arguments
	global $_josh, $_GET;
	$target = url_base() . (($path) ? $path : $_josh["request"]["path"]);
	//$adds = array_unique(array_merge($_GET, $adds));
	$adds = array_merge($_GET, $adds);
	if (count($adds)) {
		foreach ($adds as $key=>$value) {
			if ($value) $pairs[] = $key . "=" . urlencode($value);
		}
	}
	sort($pairs);
	if (count($pairs)) $target .= "?" . implode("&", $pairs);
	if ($go) url_change($target);
	return $target;
}

function url_query_drop($deletes=false, $go=true) {
	//purpose: clear specified query arguments, or clear everything if unspecified
	//called by: lots of pages on the intranet, eg /staff/view.php
	//accepts: $deletes is a one-dimensional array of keys, eg array("id", "action", "chicken"), $go is boolean
	//deletes could also be a comma-separated list
	global $_josh, $_GET;
	$get = $_GET;
	$target = url_base() . $_josh["request"]["path"];
	if ($deletes) {
		if (!is_array($deletes)) $deletes = explode(",", $deletes);
		foreach ($deletes as $key) {
			$key = trim($key);
			if (array_key_exists($key, $get)) unset($get[$key]);
		}
		$pairs = array();
		reset($get);
		while (list($key, $value) = each($get)) if ($value) $pairs[] = $key . "=" . $value;
		sort($pairs);
		if (count($pairs)) $target .= "?" . implode("&", $pairs);
	}
	if ($go) url_change($target);
	return $target;
}

function url_query_require($target="./", $index="id") {
	//requires a _GET variable to be defined or eject page
	if (!url_id($index)) url_change($target);
}

function url_query_parse($querystring) {
	$pairs = explode("&", $querystring);
	$return = array();
	foreach ($pairs as $pair) {
		list($key, $value) = explode("=", $pair);
		$return[$key] = urldecode($value);
	}
	return $return;
}

?>