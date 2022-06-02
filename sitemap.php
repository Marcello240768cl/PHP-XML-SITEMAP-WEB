<?php



	$start_url =$_REQUEST['url_site']; 

	
	      

	
	define ('CLI', true);

	$skip = array (
					"".$start_url,
				  );

	
	$extension = array (
						 ".html", 
						 ".php",
						 "/",
					   ); 

	// Scan frequency
	$freq = "daily";

	// Page priority
	$priority = "1.0";

	// Init end ==========================

	define ('VERSION', "1.0");                                            
	define ('NL', CLI ? "\n" : "<br>");

	function rel2abs($rel, $base) {
		if(strpos($rel,"//") === 0) {
			return "http:".$rel;
		}
		/* return if  already absolute URL */
		if  (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;
		$first_char = substr ($rel, 0, 1);
		/* queries and  anchors */
		if ($first_char == '#'  || $first_char == '?') return $base.$rel;
		/* parse base URL  and convert to local variables:
		$scheme, $host,  $path */
		extract(parse_url($base));
		/* remove  non-directory element from path */
		$path = preg_replace('#/[^/]*$#',  '', $path);
		/* destroy path if  relative url points to root */
		if ($first_char ==  '/') $path = '';
		/* dirty absolute  URL */
		$abs =  "$host$path/$rel";
		/* replace '//' or  '/./' or '/foo/../' with '/' */
		$re =  array('#(/.?/)#', '#/(?!..)[^/]+/../#');
		for($n=1; $n>0;  $abs=preg_replace($re, '/', $abs, -1, $n)) {}
		/* absolute URL is  ready! */
		return  $scheme.'://'.$abs;
	}


#Scan Site and relative anchors links (ie:Http,Ftp,Mailto ...)
	function Scan ($url) {
		global $start_url,$str_next_url_str, $scanned, $pf,$pr, $extension, $skip, $freq, $priority;

		echo $url . NL;

		$url = filter_var ($url, FILTER_SANITIZE_URL);

		if (!filter_var ($url, FILTER_VALIDATE_URL) || in_array ($url, $scanned)) {
			return;
		}

		array_push ($scanned, $url);
		
$html=file_get_contents($url);
$dom=new DomDocument();
@$dom->loadHTML($html); 
   

foreach($dom->getElementsByTagName('a') as $link) {

    
			$next_url = $link->getAttribute('href') or "";

			$fragment_split = explode ("#", $next_url);
			$next_url       = $fragment_split[0];

			if ((substr ($next_url, 0, 7) != "http://")  && 
				(substr ($next_url, 0, 8) != "https://") &&
				(substr ($next_url, 0, 6) != "ftp://")   &&
				(substr ($next_url, 0, 7) != "mailto:"))
			{
				$next_url = @rel2abs ($next_url, $url);
			}

			$next_url = filter_var ($next_url, FILTER_SANITIZE_URL);//echo $next_url;

$str_next_url_str.="  <url>\n" .
										 "    <loc>" . htmlentities ($next_url) ."</loc>\n" .
										 "    <changefreq>$freq</changefreq>\n" .
										 "    <priority>$pr</priority>\n" .
										 "  </url>\n";



			if (substr ($next_url, 0, strlen ($start_url)) == $start_url) {
				$ignore = false;

				if (!filter_var ($next_url, FILTER_VALIDATE_URL)) {
					$ignore = true;
				}

				if (in_array ($next_url, $scanned)) {
					$ignore = true;
				}

				if (isset ($skip) && !$ignore) {
					foreach ($skip as $v) {
						if (substr ($next_url, 0, strlen ($v)) == $v)
						{
							$ignore = true;
						}
					}
				}

				if (!$ignore) {
					foreach ($extension as $ext) {
						if (strpos ($next_url, $ext) > 0) {
							$pr = number_format ( round ( $priority / count ( explode( "/", trim ( str_ireplace ( array ("http://", "https://"), "", $next_url ), "/" ) ) ) + 0.5, 3 ), 1 );
							
							Scan ($next_url);
						}
					}
				}
			}
		}
	}

	



	$start_url = filter_var ($start_url, FILTER_SANITIZE_URL);

	
$str_begin="<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
				 "<?xml-stylesheet type=\"text/xsl\" href=\"http://iprodev.github.io/PHP-XML-Sitemap-Generator/xml-sitemap.xsl\"?>\n" .
				 "<!-- Created with iProDev PHP XML Sitemap Generator " . VERSION . " http://iprodev.com -->\n" .
				 "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n" .
				 "        xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
				 "        xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9\n" .
				 "        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n" .
				 "  <url>\n" .
				 "    <loc>" . htmlentities ($start_url) ."</loc>\n" .
				 "    <changefreq>$freq</changefreq>\n" .
				 "    <priority>$priority</priority>\n" .
				 "  </url>\n";

	$scanned = array ();
	Scan ($start_url);

	

file_put_contents("sitemap.xml",$str_begin.$str_next_url_str."</urlset>\n");
	//echo "Done." . NL;
	//echo "sitemap.xml created." . NL;

header("Content-Type: application/force-download; name=sitemap.xml");
header("Content-type: text/xml"); 
header("Content-Transfer-Encoding: binary");
header("Content-Disposition: attachment; filename=sitemap.xml");
header("Expires: 0");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
readfile("sitemap.xml");

         
         
#Destroy file after downloading it
//unlink("sitemap.xml");

#Exit page 
exit();




?>