<?php

session_start();

$userid = $user_id = isset($_SESSION['UserId']) ? $_SESSION['UserId'] : NULL;
$FullName = isset($_SESSION['FullName']) ? $_SESSION['FullName'] : NULL;
$UserName = isset($_SESSION['UserName']) ? $_SESSION['UserName'] : NULL;
$PhotoPath = isset($_SESSION['PhotoPath']) ? $_SESSION['PhotoPath'] : NULL;
$ty = isset($_SESSION['UserType']) ? $_SESSION['UserType'] : NULL;

if (!empty($_SESSION['UserId'])) {

//========================================
    include '../../model/oop.php';
    include '../../model/Bill.php';
    include('../../model/Mikrotik.php');
    $obj = new Controller();
    $bill = new Bill();


    $mikrotikConnect = false;

    if ($obj->tableExists('mikrotik_user')) {

        $mikrotikLoginData = $obj->details_by_cond('mikrotik_user', 'id = 1');
        $mikrotik = new Mikrotik($mikrotikLoginData['mik_ip'], $mikrotikLoginData['mik_username'], $mikrotikLoginData['mik_password']);

        if ($mikrotik->checkConnection()) {

            $mikrotikConnect = true;
        }
    }

//======= Object Created from Class ======
//========================================
    date_default_timezone_set('Asia/Dhaka');
    $date_time = date('Y-m-d g:i:sA');
    $ip_add = $_SERVER['REMOTE_ADDR'];
if (isset($_GET['hold'])) {
    $hold = $_GET['hold'];
    //$agentDetails = $obj->details_by_cond("tbl_agent", "ag_id='$hold'");
    $holdMoney = $obj->Update_data("tbl_agent", ['hold_money_status' => 1], "where ag_id='$hold'");
} 
    if (isset($_GET['token']) && isset($_GET['amount'])) {
        $token = $_GET['token'];
        $bill_amount = $_GET['amount'];
        $agentDetails = $obj->details_by_cond("tbl_agent", "ag_id='$token'");
        $bill_per_id = $agentDetails['billing_person_id'];

        $mobile = isset($agentDetails['ag_mobile_no']) ? $agentDetails['ag_mobile_no'] : NULL;
  $cus = isset($agentDetails['ip']) ? $agentDetails['ip'] : NULL;
        $form_data = array(
            'agent_id' => $token,
            'acc_amount' => $_GET['amount'],
            'acc_type' => '3',
            'acc_description' => date('M') . " Months Bill collection of " . $agentDetails['ag_name'] . ' IP: ' . $agentDetails['ip'] . '',
            'entry_by' => $userid,
            'entry_date' => $date_time,
            'update_by' => $userid,
            'billing_person_id' => $bill_per_id
        );
        $accounts_add = $obj->Reg_user_cond("tbl_account", $form_data, " ");

        if ($accounts_add) {


            $paidAgent = $obj->Update_data("tbl_agent", ['pay_status' => 0], "ag_id='$token'");

            $postUrl = "http://api.bulksms.icombd.com/api/v3/sendsms/xml";

    $smsbody = "Dear Sir/Madam,Your Internet Bill-$bill_amount taka has been paid successfully. Thank you. ".$obj->getSettingValue('sms', 'company_name'). ".Support-".$obj->getSettingValue('sms', 'support_num').".";


    $xmlString = "<SMS>
            <authentification>
                <username>" . $obj->getSettingValue('sms', 'user') . "</username>
                <password>" . $obj->getSettingValue('sms', 'pass') . "</password>
            </authentification>
            <message>
            <sender>".$obj->getSettingValue('sms', 'sender')."</sender>
                <text>$smsbody</text>
            </message>
            <recipients>
                <gsm>88.$mobile</gsm>
            </recipients>
        </SMS>";

            $fields = "XML=" . urlencode($xmlString);


            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $postUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            $response = curl_exec($ch);
            curl_close($ch);

            if ($bill->get_customer_dues($token) <= 0) {

                if (isset($mikrotik)) {

                    if ($mikrotikConnect) {

                        if (!$mikrotik->checkSecretActive($agentDetails['ip'])) {

                            $mikrotik->enableSingleSecret($agentDetails['ip']);
                        }
                    }
                }
            }

        } else {

            echo false;
        }
    }

    if (isset($_POST['check'])) {

        $checkIp = $_POST['check'];
        $findData = $obj->Total_Count('tbl_agent', "`ip` = '$checkIp'");
        if ($findData == 0) {
            echo '<p class="bg-success"><span  class="glyphicon glyphicon-remove text-success"></span> OK, You can proceed. </p>';
        } else {
            echo '<p class="bg-danger"><span  class="glyphicon glyphicon-remove text-danger"></span> Sorry this Client Id is already exists. </p>';
        }
    }

} else {
    header("location: include/login.php");
}