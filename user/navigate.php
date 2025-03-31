<?php
include '../function.php';
include_once '../session.php';
Session::init();

$function = new Functions();

//---ADDING SECTION---//

if (isset($_POST['btn-update-status'])) {
	$po_number = $_POST['po_number'];
	$status = $_POST['status'];

	if ($function->updateOrderStatus($po_number, $status)) {
		Session::set("msg", "<div class='toast-message success'>Order status updated successfully!</div>");
	} else {
		Session::set("msg", "<div class='toast-message error'>Failed to update order status!</div>");
	}
	header('Location: index.php');
	exit;
}
	

?>