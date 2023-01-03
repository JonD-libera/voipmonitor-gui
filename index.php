<?php include('config.php'); ?>
<?php 
//Replace links with just the clickable text for csv exports
function unlick($text){
    if (strpos($text, "<a href=") !== FALSE ) {
		return preg_replace('~(<a href="[^"]*">)([^<]*)(</a>)~', '$2', $text);;
	}	else {
		return $text;
	}
}
if (isset($_GET['debug'])) { 
    $debug = $_GET['debug'];
} else { 
    $debug = 'false';
} 

$locallink = new mysqli($localdbhost, $localdbuser, $localdbpass, $localdbname);

if($locallink->connect_errno > 0)
{
    die('Unable to connect to database [' . $locallink->connect_error . ']');
}
//create connection

//test if connection failed
if(mysqli_connect_errno()){
    die("connection failed: "
        . mysqli_connect_error()
        . " (" . mysqli_connect_errno()
        . ")");
}
$package  = mysqli_real_escape_string($locallink,$_GET['package']);
if (isset($_GET['export'])) { 
    $export = $_GET['export'];
} else { 
    $export = 'html';
} 
if ($export == 'html') {
	
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<meta http-equiv="refresh" content="<?echo $refresh;?>">
	<title>Voipmon</title>	
	<link rel="icon" type="image/png" href="./favicon.ico">
	<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="http://www.datatables.net/rss.xml">
    <link rel="stylesheet" type="text/css" href="./style.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.css">
    <script type="text/javascript" language="javascript" src="//code.jquery.com/jquery-1.12.4.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.js"></script>
    <script type="text/javascript" class="init">
        $(document).ready(function() {
            // Setup - add a text input to each header cell            
            $('#primary tfoot td').each( function () {
                var title = $(this).text();
                $(this).html('<input style="width: 75%;" type="text" placeholder="Search ' + title + '" />');
            } );
        
            // DataTable
            var table = $('#primary').DataTable({
                dom: 'Blefrtip',
                "lengthChange": true,
                "lengthMenu": [[20, 40, 100, -1], [20, 40, 100, "All"]],
                "order": [0,"desc"],
                initComplete: function () {
                    // Apply the search
                    this.api()
                        .columns()
                        .every(function () {
                            var that = this;
                            $('input', this.footer()).on('keyup change clear', function () {
                                if (that.search() !== this.value) {
                                    that.search(this.value).draw();
                                }
                            });
                        });
                },
            });
        
        } );
    </script>
</head>

<body>
<div id="chartwrap">
<H1>Voipmon cdr</H1>
<div id="localheader"></div>

<div id="chartwrap">
<?php
}

?>
<?php
$query = "
SELECT cdr.calldate, 
cdr.caller, 
cdr.called,
connect_duration, 
inet_ntoa(cdr.sipcallerip) as 'caller ip', 
inet_ntoa(cdr.sipcalledip) as 'called ip',
cdr.a_maxjitter as 'caller jitter',
cdr.b_maxjitter as 'called jitter',
cdr.a_mos_min_mult10/10 as 'mos caller side',
cdr.b_mos_min_mult10/10 as 'mos called side',
concat('<a href=\"http://fsaza-1147531985.phl.coredial.com/cgi-bin/get_pcap.cgi?to=',cdr.called,'&from=',cdr.caller,'&file=',LEFT(cdr.calldate, 10),'/',RIGHT(LEFT(cdr.calldate, 13),2),'/',RIGHT(LEFT(cdr.calldate, 16),2),'/SIP/',cdr_next.fbasename,'.pcap\">pcap</>') as pcap
FROM cdr INNER JOIN cdr_next ON cdr.ID = cdr_next.cdr_ID WHERE 1
ORDER BY cdr.calldate DESC LIMIT 1000";
$result = mysqli_query($locallink,$query);

$all_property = array();  //declare an array for saving property
//showing property
if ($export == 'html') {

if ($debug == 'true') {echo $query."<br>";}

echo '<table id="primary" class="display" cellspacing="0" width="100%">
        <thead><tr>';  //initialize table tag
while ($property = mysqli_fetch_field($result)) {
    echo '<th>' . $property->name . '</th>';  //get field name for header
    array_push($all_property, $property->name);  //save those to array
}
echo '</tr></thead>'; //end tr tag

//showing all data
while ($row = mysqli_fetch_array($result)) {
    echo "<tr>";
    foreach ($all_property as $item) {
        echo '<td>' . $row[$item] . '</td>'; //get items using property value
    }
    echo '</tr>'."\n";
}
echo "<tfoot><tr>";
mysqli_field_seek($result, 0);
while ($property = mysqli_fetch_field($result)) {
    echo '<td>' . $property->name . '</td>';  //get field name for header
    array_push($all_property, $property->name);  //save those to array
}
echo "</tfoot></table>";


?>
	<script type="text/javascript">
				  var _gaq = _gaq || [];
				  _gaq.push(['_setAccount', 'UA-365466-5']);
				  _gaq.push(['_trackPageview']);

				  (function() {
					var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
					ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
					var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
				  })();
	</script>
</div>
</div>
</body>
</html>
<?php
die();
}

if ($export == 'csv') {
$headers = array();
$fp = fopen('php://output', 'w');
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="export.csv"');
header('Pragma: no-cache');    
header('Expires: 0');
while ($property = mysqli_fetch_field($result)) {
    $headers[] = $property->name;  //get field name for header
}
fputcsv($fp, $headers); 
while  ($row = mysqli_fetch_assoc($result)) {
    fputcsv($fp, unlick($row));
}
die; 
}
?>
