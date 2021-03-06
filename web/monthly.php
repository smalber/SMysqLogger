<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<!--
    Copyright (C) 2011 Vincent Deconinck (known on google mail as user vdeconinck)

    This file is part of the SMySqLogger project.
	
    SMySqLogger is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
	
    SMySqLogger is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with SMySqLogger.  If not, see <http://www.gnu.org/licenses/>.
-->

<?php
    session_start();
    
    // Connect
    $conn = mysql_pconnect("localhost", "sma", "<SMA_PASSWORD>") or die ("Connection Failure to Database");
    mysql_select_db("sma", $conn) or die ("Database not found.");

    // Get monthly totals
    $query="select concat(year(logdate), '-', month(logdate)), year(logdate), month(logdate), max(e_total_kwh) - min(e_total_kwh) from logged_values group by year(logdate), month(logdate) order by year(logdate), month(logdate)";
    $result = mysql_query($query) or die("Failed Query : ".$query);
    $serEtot = "";
	$nbMonths = 0;
	$periodTotal = 0;
	$yearMonthArray = array();
    while ($thisrow=mysql_fetch_row($result)) {
		$year=$thisrow[1];
		$month=$thisrow[2];
		if (empty($yearMonthArray[$year])) {
			$yearMonthArray[$year] = array();
		}
		$yearMonthArray[$year][$month] = $thisrow[3];
		
        if ($serEtot != "") {
            $serEtot = $serEtot.", ";
        }
        $serEtot = $serEtot.$thisrow[3];
				
		$nbMonths++;
		$periodTotal += $thisrow[3];
    }
	if ($thisrow[0] == date_format(new DateTime(), "Y-m")) {
		// Ignore this month for average production calculation (because it is probably not complete)
		$nbMonths--;
		$periodTotal -= $thisrow[3];
	}
	
    mysql_free_result($result);

    mysql_close($conn);
?>

<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <meta name="description" content="Power Graph">
        <title>Power Graph</title>
		<link type="text/css" href="css/ui-lightness/jquery-ui-1.8.13.custom.css" rel="stylesheet" />	
		<script type="text/javascript" src="js/jquery-1.5.2.min.js"></script>
		<script type="text/javascript" src="js/jquery-ui-1.8.13.custom.min.js"></script>
        <script type="text/javascript" src="js/highcharts.js"></script>
        <script type="text/javascript" src="js/themes/gray.js"></script>
        <script type="text/javascript">

var historyChart;
$(document).ready(function() {
   
   historyChart = new Highcharts.Chart({
        chart: {
            renderTo: 'totalChart',
            defaultSeriesType: 'column'
        },
        title: {
            text: 'Monthly totals'
        },
        subtitle: {
            text: null
        },
        xAxis: {
            categories: ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"]
        },        
        yAxis: {
            min: 0,
            title: {
                text: 'Energy (kWh)'
            }
        },
        legend: {
				layout: 'vertical',
                align: 'right',
                verticalAlign: 'top',
                floating: true,
                shadow: true              
        },
        tooltip: {
            formatter: function() {
                return ''+this.x +' '+this.series.name+': '+ (Math.floor(100*this.y))/100 +' kWh';
            }
        },
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0
            }
        },
        series: [
<?php 
	$first = true;
	foreach ($yearMonthArray as $year => $monthValues) {
		if (!$first) {
			echo ",";
		}
		$first = false;
?>
			{
				name: '<?php echo $year ?>',
				events: {
					click: function(event) {
						// Link to details of the month
						var start = event.point.series.name + '%2F' + event.point.category + "%2F01"; // First day of this month
						// Compute end of the month
						var endDate = new Date(event.point.series.name, event.point.category, 1); // First day of this month
						endDate.setMonth(endDate.getMonth() + 1); // First day of next month
						endDate.setDate(endDate.getDate() - 1); // Last day of this month
						window.location.replace('daily.php?startDate=' + start + "%2F01&endDate=" + endDate.getFullYear() + '%2F' + (endDate.getMonth()<10?'0':'') + endDate.getMonth() + '%2F' + (endDate.getDate()<10?'0':'') + endDate.getDate());
					}
				},
				data: [<?php 
					for ($month=1; $month < 13; $month++) {
						if (empty($yearMonthArray[$year][$month])) {
							echo 0;
						}
						else {
							echo $yearMonthArray[$year][$month];
						}
						if ($month < 12) {
							echo ", ";
						}
					}
				?>]
			}
<?php
	}
?>
		]
    });
   
   
});


        </script>
    </head>
    <body> 
	<?php include "header.inc.php" ?>
        <form id="dateForm">
            <table width="100%"><tr>
			<td align="center">Average on this period : <?php echo number_format($periodTotal/$nbMonths, 2, ',', ' '); ?> kWh/month</td>
            </tr></table>
        </form>
        <div id="totalChart" style="width: 100%; height: 90%"></div>
		<!--
		<?php 
			foreach ($yearMonthArray as $year => $monthValues) {
				echo $year;
				for ($month=1; $month < 13; $month++) {
					if (empty($yearMonthArray[$year][$month])) {
						echo " ", $month, ":", 0;
					}
					else {
						echo " ", $month, ":", $yearMonthArray[$year][$month];
					}
				}
			}
		?>
		-->
    </body>
</html>