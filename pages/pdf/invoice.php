<?php
require_once __DIR__ . '/vendor/autoload.php';
use Mpdf\Mpdf;

try {
	require(realpath(__DIR__ . '/../../services/Model.php'));
	$obj = new Model();
	$token = $_GET['token'] ?? NULL;

	$account = $obj->details_by_cond('tbl_account', "agent_id='$token'");
	$datetime = new DateTime($account['entry_date']);
	$month = $datetime->format('F');
	$year = $datetime->format('Y');

	$uniqueReceiptNumber = date('YmdHis') . '-' . mt_rand(10, 99);
	$agentDetail = $obj->view_all_by_cond('tbl_agent', "ag_id=$token");

	$html = '';
	foreach ($agentDetail as $agentDetails) {
    $agg_id = $agentDetails['ag_id'];
    $diff = $obj->get_customer_dues($agg_id) - $agentDetails['taka'];

    $extraRow = '';
    if ($diff > 0) {
        $extraRow = '<tr><td>2</td><td>Previous Due</td><td>--</td><td>--</td><td>' . number_format($diff, 2) . '</td></tr>';
    } elseif ($diff < 0) {
        $extraRow = '<tr><td>2</td><td>Advanced Payment</td><td>--</td><td>--</td><td>' . number_format(abs($diff), 2) . '</td></tr>';
    }

    $types = ['Customer Copy', 'Office Copy'];
    $copyCount = 0;

    foreach ($types as $copy) {
        $copyCount++;
        $html .= '
        <head>
            <style>
                .divider { border-top: 1.5px dotted red; margin: 6px 0;width:100% }
            </style>
        </head>

        <div style="margin-bottom: 10px;">
            <div style="text-align:right; font-weight:bold; color:#2E7D32; font-size: 11pt;">' . $copy . '</div>
            <div style="font-family: Arial; font-size: 7pt; line-height: 1.2;">
                <div style="display: flex; align-items: center; gap: 5px;">
                    <img src="logo.png" width="50" />
                    <div>
                        <div style="font-size: 12pt; font-weight: bold; color:#4CAF50;">DEMO <span style="color:#2E7D32;">NET</span></div>
                        <div style="font-size: 9pt; color: #666;">Leading Broadband Internet Service Provider</div>
                        <div style="font-size: 9pt; color: red;">Hotline: 0167....., 0189627.....</div>
                        <div style="font-size: 9pt;">demo@gmail.com</div>
                    </div>
                </div>
                <hr style="margin: 4px 0;">

                <table width="100%" style="font-size:8.5pt; margin-top:3px;">
                    <tr>
                        <td>
                            <b>To:</b><br />
                            Name: ' . $agentDetails["ag_name"] . '<br />
                            Mobile: ' . $agentDetails["ag_mobile_no"] . '<br />
                            Address: ' . $agentDetails["ag_office_address"] . '<br />
                            IP: ' . $agentDetails["ag_ip"] . '<br />
                            T.Due: ' . number_format($agentDetails["taka"] + $diff, 2) . ' BDT<br />
                            Invoice No: ' . $uniqueReceiptNumber . '<br />
                            Date: ' . date("Y-m-d") . '
                        </td>
                    </tr>
                </table>

                <table width="100%" border="1" cellspacing="0" cellpadding="3" style="border-collapse:collapse; font-size:8.5pt; margin-top:5px;">
                    <tr style="background-color:#f0f0f0;">
                        <th>SL</th>
                        <th>Description</th>
                        <th>Month</th>
                        <th>Speed</th>
                        <th>Bill Amount</th>
                    </tr>
                    <tr>
                        <td>1</td>
                        <td>Bandwidth Charge - Monthly</td>
                        <td>' . $month . '</td>
                        <td>' . $agentDetails["mb"] . '</td>
                        <td>' . number_format($agentDetails["taka"], 2) . '</td>
                    </tr>
                    ' . $extraRow . '
                </table>

                <table width="100%" style="font-size:8.5pt; margin-top:5px;">
                    <tr>
                        <td style="text-align:right;"><b>Total: ' . number_format($agentDetails["taka"] + max(0, $diff), 2) . ' BDT</b></td>
                    </tr>
                    <tr>
                        <td style="text-align:right;">In Word: ' . ucwords(convert_number_to_words($agentDetails["taka"] + max(0, $diff))) . ' Taka</td>
                    </tr>
                </table>

                <div style="font-size:6pt; margin-top:8px;">
                    <p>* Pay in cash.</p>
                    <p>* Due must be cleared before 10th of the month to avoid disconnection.</p>
                </div>

                <div style="text-align:right; font-size:6.5pt; margin-top:10px;">
                    Signature: ___________________
                </div>
                <hr style="border: none;border-top: 1px dotted #000; margin: 20px 0;">
                <small style="font-size:6pt;">Powered by Bangladesh Software Development (BSD)</small>
            </div>
        </div>';
        
        // Divider only after first copy
        if ($copyCount === 1) {
            $html .= '<div class="divider"></div>';
        }
    }
}


	$mpdf = new Mpdf(['orientation' => 'P', 'format' => 'A4']);
	$mpdf->WriteHTML($html);
	$mpdf->Output();

} catch (\Mpdf\MpdfException $e) {
	echo "PDF Generation Error: " . $e->getMessage();
}

function convert_number_to_words($number) {
	$hyphen = '-';
	$conjunction = ' and ';
	$separator = ', ';
	$negative = 'negative ';
	$decimal = ' point ';
	$dictionary = [
		0 => 'zero',
		1 => 'one',
		2 => 'two',
		3 => 'three',
		4 => 'four',
		5 => 'five',
		6 => 'six',
		7 => 'seven',
		8 => 'eight',
		9 => 'nine',
		10 => 'ten',
		11 => 'eleven',
		12 => 'twelve',
		13 => 'thirteen',
		14 => 'fourteen',
		15 => 'fifteen',
		16 => 'sixteen',
		17 => 'seventeen',
		18 => 'eighteen',
		19 => 'nineteen',
		20 => 'twenty',
		30 => 'thirty',
		40 => 'forty',
		50 => 'fifty',
		60 => 'sixty',
		70 => 'seventy',
		80 => 'eighty',
		90 => 'ninety',
		100 => 'hundred',
		1000 => 'thousand',
		1000000 => 'million',
		1000000000 => 'billion'
	];

	if (!is_numeric($number)) return false;
	if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) return false;
	if ($number < 0) return $negative . convert_number_to_words(abs($number));

	$string = $fraction = null;
	if (strpos($number, '.') !== false) {
		list($number, $fraction) = explode('.', $number);
	}
	switch (true) {
		case $number < 21:
			$string = $dictionary[$number];
			break;
		case $number < 100:
			$tens = ((int) ($number / 10)) * 10;
			$units = $number % 10;
			$string = $dictionary[$tens];
			if ($units) {
				$string .= $hyphen . $dictionary[$units];
			}
			break;
		case $number < 1000:
			$hundreds = $number / 100;
			$remainder = $number % 100;
			$string = $dictionary[$hundreds] . ' hundred';
			if ($remainder) {
				$string .= $conjunction . convert_number_to_words($remainder);
			}
			break;
		default:
			foreach ([1000000000, 1000000, 1000, 100] as $base) {
				if ($number >= $base) {
					$numBaseUnits = (int) ($number / $base);
					$remainder = $number % $base;
					$string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$base];
					if ($remainder) {
						$string .= $separator . convert_number_to_words($remainder);
					}
					break;
				}
			}
			break;
	}
	if (isset($fraction) && is_numeric($fraction)) {
		$string .= $decimal;
		$words = [];
		foreach (str_split((string) $fraction) as $number) {
			$words[] = $dictionary[$number];
		}
		$string .= implode(' ', $words);
	}
	return $string;
}
