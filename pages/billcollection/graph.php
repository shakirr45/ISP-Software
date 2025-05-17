<h1>h</h1>
<div id="apex-line-test"></div>
<?php 
 $PreviousYear = $obj->rawSql('select  count(ag_id) from  tbl_agent  where YEAR(entry_date)=2023 group by MONTH(entry_date)');
 $CurrentYear = $obj->rawSql('select count(ag_id) from  tbl_agent  where YEAR(entry_date)=2024 group by MONTH(entry_date)');

$previousData =implode(',', array_map(function($item) {return $item["count(ag_id)"];}, $PreviousYear));
$currentData =implode(',', array_map(function($item) {return $item["count(ag_id)"];}, $CurrentYear));
$allData = array_merge(explode(',', $previousData),explode(',', $currentData));
var_dump($allData );
$maxValue = max(array_map('intval', $allData));
// $minData = min(array_map('intval', $allData));
$maxData = $maxValue + ($maxValue * 0.50);
?>

<?php $obj->start_script(); ?>
<script>
    colors = ["#6658dd", "#1abc9c"];
(dataColors = $("#apex-line-test").data("colors")) && (colors = dataColors.split(","));
var options = {
    chart: {
        height: 380,
        type: "line",
        zoom: {
            enabled: !1
        },
        toolbar: {
            show: !1
        }
    },
    colors: colors,
    dataLabels: {
        enabled: !0
    },
    stroke: {
        width: [3, 3],
        curve: "smooth"
    },
    series: [{
        name: "Previous - 2023",
        data: [<?php echo $previousData?>],
    }, {
        name: "current - 2024",
        data: [<?php echo $currentData?>]
    }],
    title: {
        text: "Average High & Low Temperature",
        align: "left",
        style: {
            fontSize: "14px",
            color: "#666"
        }
    },
    grid: {
        row: {
            colors: ["transparent", "transparent"],
            opacity: .2
        },
        borderColor: "#f1f3fa"
    },
    markers: {
        style: "inverted",
        size: 6
    },
    xaxis: {
        categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul","Aug","Sep","Oct","Nov","Dec"],
        title: {
            text: "Month"
        }
    },
    yaxis: {
        title: {
            text: "Temperature"
        },
        min: 0,
        max: <?php echo $maxData?>,
        tickAmount: <?php echo $maxData?>,
    },
    legend: {
        position: "top",
        horizontalAlign: "right",
        floating: !0,
        offsetY: -25,
        offsetX: -25
    },
    responsive: [{
        breakpoint: 600,
        options: {
            chart: {
                toolbar: {
                    show: !1
                }
            },
            legend: {
                show: !1
            }
        }
    }]
}
  , chart = new ApexCharts(document.querySelector("#apex-line-test"),options);
chart.render();
</script>
<?php $obj->end_script(); ?>