<?php
session_start();

if (!empty($_SESSION['UserId'])) {

    //========================================
    include '../../model/oop.php';
    include '../../model/Bill.php';
    $obj = new Controller();
    $bill = new Bill();
//========================================

    date_default_timezone_set('Asia/Dhaka');
    $date_time = date('Y-m-d g:i:sA');

    $user_id = isset($_SESSION['UserId']) ? $_SESSION['UserId'] : NULL;
    $FullName = isset($_SESSION['FullName']) ? $_SESSION['FullName'] : NULL;
    $UserName = isset($_SESSION['UserName']) ? $_SESSION['UserName'] : NULL;
    $PhotoPath = isset($_SESSION['PhotoPath']) ? $_SESSION['PhotoPath'] : NULL;
    $ty = isset($_SESSION['UserType']) ? $_SESSION['UserType'] : NULL;

    //taking month and years
    $day = date('M-Y');

    if (isset($_GET['zone'])) {
        $zone = $_GET['zone'];
        $allDuePayment = $obj->view_all_by_cond("vw_agent", "ag_status='1' AND pay_status='1' AND due_status='0' AND 
    zone = $zone AND hold_money_status = '0'  ORDER BY `vw_agent`.`ag_id` DESC");

    }
    elseif (isset($_GET['bid'])) {

        $bid = $_GET['bid'];
        $allDuePayment = $obj->view_all_by_cond("vw_agent", "ag_status='1' AND pay_status='1' AND due_status='0' AND 
    billing_person_id = $bid  AND hold_money_status = '0' ORDER BY `vw_agent`.`ag_id` DESC");


    }

     else {

        /* =============================================
         * =============================================
         *  Here we check_bill_create_or_not by check in the
         *  "monthly_bill_making_check" table if this month bill is
         *  generated or not. If not generated mean ($cnt!=1)
         *  then make all the customer in a due user by pay_status = 1
         *  and insert new row in "monthly_bill_making_check".
         * =============================================
         * =============================================*/

        $cnt = $obj->Total_Count("monthly_bill_making_check", "month_year='$day'");
        if ($cnt != 1) {

            $form_data = array(
                'pay_status' => 1, //Customer didn't pay yet
                'due_status' => 0 //no dues
            );
            $obj->Update_data("tbl_agent", $form_data, "pay_status!=5");

            $form_data2 = array(
                'month_year' => $day //Insert current month date for future check weather bill is created or notification
            );
            $obj->Insert_data("monthly_bill_making_check", $form_data2);
        }


        /* =============================================
         * =============================================
         *  Here we check all the customer one by one
         *  if they paid or not by get_customer_dues() function
         *  if they don't have dues then they are removed by
         *  pay_status and due_status both = 0.
         *  if they have dues then they are pay_status = 1
         *  N.B pay_status = 1 mean customer has to pay.
         * =============================================
         * =============================================*/

// ========== Now remove the customer from bill collection =================
// ========== Who paid already or no dues =================
        foreach ($obj->view_all_by_cond("tbl_agent", "ag_status='1'") as $detailsid) {
            extract($detailsid);
            $id = $detailsid['ag_id'];
            $taka = $detailsid['taka'];
            $dues = $bill->get_customer_dues($id);
            //customer remove from bill collection list who have advanced paid or no dues
            if ($dues <= 0) {
                $form_data = array(
                    'pay_status' => 0, //Customer paid
                    'due_status' => 0 //no dues
                );
                $obj->Update_data("tbl_agent", $form_data, "where ag_id='$id'");
            }
            // this condition for if delete any given entry then comes bill list
            if ($dues > 0) {
                $form_data = array(
                    'pay_status' => 1, //Customer not paid 
                );
                $obj->Update_data("tbl_agent", $form_data, "where ag_id='$id'");
                if($dues > $taka)
                {
                    $holdMoney = $obj->Update_data("tbl_agent", ['hold_money_status' => 0], "where ag_id='$id'");
                }
            }
        }

        $allDuePayment = $obj->view_all_by_cond("vw_agent", "ag_status='1' and pay_status='1' AND due_status='0' AND hold_money_status = '0'  ORDER BY `vw_agent`.`ag_id` ASC");
    }

    $duePaymentArrForJson = array();
    $total_due_amount = 0;

    foreach ($allDuePayment as $value) {

        $all_due = $bill->get_customer_dues(isset($value['ag_id']) ? $value['ag_id'] : NULL);

        $previousDue = ( $all_due - (isset($value['taka']) ? $value['taka'] : 0) );

        if( $previousDue < 0) { $previousDue = 0; }

        $total_due_amount += $all_due;
        $bill_ag_id = $value['ag_id'];
      $bill_date = $obj->details_by_cond("tbl_agent","ag_id='$bill_ag_id'");
      $monthname =  $bill_date['bill_date']."-".date("M").",".date("y");
        $duePaymentArrForJson[] = array(
            'agent_id' => $value['ag_id'],
            'agent_name' => $value['ag_name'],
            'customer_id' => $value['cus_id'],
            'agent_address' => $value['ag_office_address'],
            'zone' => $value['zone_name'],
            'mobile' => $value['ag_mobile_no'],
            'speed' => $value['mb'],
            'previous_due' => $previousDue,
            'connection_date' => $value['connection_date'],
            'bill_date' => $monthname,
            'bill' => $value['taka'],
            'total_due' => intval($all_due),
            'ip' => $value['ip'],
        );

    }// foreach loop ends here


    echo json_encode((array('data' => $duePaymentArrForJson, 'total_bill' => $total_due_amount)));

} else {
    header("location: include/login.php");
}
?>