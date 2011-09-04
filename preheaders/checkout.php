<?php
	global $wpdb, $besecure, $discountcode, $pmpro_level, $pmpro_msg, $pmpro_msgt, $skip_account_fields;
	
	//what level are they purchasing? (discount code passed)
	if($_REQUEST['level'] && $_REQUEST['discountcode'])
	{
		$discountcode = preg_replace("/[^A-Za-z0-9]/", "", $_REQUEST['discountcode']);
		//check code
		$code_check = pmpro_checkDiscountCode($discountcode, (int)$_REQUEST['level'], true);		
		if($code_check[0] == false)
		{
			//error
			$pmpro_msg = $code_check[1];
			$pmpro_msgt = "pmpro_error";
			
			//don't use this code
			$use_discount_code = false;
		}
		else
		{			
			$sqlQuery = "SELECT l.id, cl.*, l.name, l.description, l.allow_signups FROM $wpdb->pmpro_discount_codes_levels cl LEFT JOIN $wpdb->pmpro_membership_levels l ON cl.level_id = l.id LEFT JOIN $wpdb->pmpro_discount_codes dc ON dc.id = cl.code_id WHERE dc.code = '" . $discountcode . "' AND cl.level_id = '" . (int)$_REQUEST['level'] . "' LIMIT 1";			
			$pmpro_level = $wpdb->get_row($sqlQuery);
			
			$use_discount_code = true;
		}	
	}
	
	//what level are they purchasing? (no discount code)
	if(!$pmpro_level && $_REQUEST['level'])
	{
		$pmpro_level = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = '" . $wpdb->escape($_REQUEST['level']) . "' AND allow_signups = 1 LIMIT 1");	
	}
	
	if(!$pmpro_level)
	{
		wp_redirect(pmpro_url("levels"));
		exit(0);
	}		
	
	global $wpdb, $current_user, $pmpro_requirebilling;
	if(!pmpro_isLevelFree($pmpro_level))
	{
		//just skip them through
		$pagetitle = "Checkout: Payment Information";
		$pmpro_requirebilling = true;
		$besecure = true;			
	}	
	else
	{
		//no payment so we don't need ssl
		$pagetitle = "Setup Your Account";
		$besecure = false;		
	}
	
	//by default we show the account fields if the user isn't logged in
	if($current_user->ID)
	{
		$skip_account_fields = true;
	}
	else
	{
		$skip_account_fields = false;
	}	
	//in case people want to have an account created automatically
	$skip_account_fields = apply_filters("pmpro_skip_account_fields", $skip_account_fields, $current_user);
	
	//some options
	global $tospage;
	$tospage = pmpro_getOption("tospage");
	if($tospage)
		$tospage = get_post($tospage);
	
	//load em up (other fields)
	global $username, $password, $password2, $bfirstname, $blastname, $baddress1, $bcity, $bstate, $bzipcode, $bphone, $bemail, $bconfirmemail, $CardType, $AccountNumber, $ExpirationMonth,$ExpirationYear;
	
	$order_id = $_REQUEST['order_id'];
	$bfirstname = $_REQUEST['bfirstname'];	
	$blastname = $_REQUEST['blastname'];	
	$fullname = $_REQUEST['fullname'];		//honeypot for spammers
	$baddress1 = $_REQUEST['baddress1'];
	$baddress2 = $_REQUEST['baddress2'];
	$bcity = $_REQUEST['bcity'];
	$bstate = $_REQUEST['bstate'];
	$bzipcode = $_REQUEST['bzipcode'];
	$bphone = $_REQUEST['bphone'];
	$bemail = $_REQUEST['bemail'];
	$bconfirmemail = $_REQUEST['bconfirmemail'];
	$CardType = $_REQUEST['CardType'];
	$AccountNumber = $_REQUEST['AccountNumber'];
	$ExpirationMonth = $_REQUEST['ExpirationMonth'];
	$ExpirationYear = $_REQUEST['ExpirationYear'];
	$CVV = $_REQUEST['CVV'];
	
	$discountcode = $_REQUEST['discountcode'];
	$username = $_REQUEST['username'];
	$password = $_REQUEST['password'];
	$password2 = $_REQUEST['password2'];
	$tos = $_REQUEST['tos'];		
	
	//_x stuff in case they clicked on the image button with their mouse
	$submit = $_REQUEST['submit-checkout'];
	if(!$submit) $submit = $_REQUEST['submit-checkout_x'];	
	if($submit === "0") $submit = true;
		
	//check their fields if they clicked continue
	if($submit && $pmpro_msgt != "pmpro_error")
	{		
		//if we're skipping the account fields and there is no user, we need to create a username and password
		if($skip_account_fields && !$current_user->ID)
		{
			$username = pmpro_generateUsername($bfirstname, $blastname, $bemail);
			$password = pmpro_getDiscountCode() . pmpro_getDiscountCode();	//using two random discount codes
			$password2 = $password;
		}
		
		
		if($pmpro_requirebilling && (!$bfirstname || !$blastname || !$baddress1 || !$bcity || !$bstate || !$bzipcode || !$bphone || !$bemail || !$CardType || !$AccountNumber || !$ExpirationMonth || !$ExpirationYear || !$CVV))
		{
			//krumo(array($bname, $baddress1, $bcity, $bstate, $bzipcode, $bemail, $name, $address1, $city, $state, $zipcode));
			$pmpro_msg = "Please complete all required fields.";
			$pmpro_msgt = "pmpro_error";
		}
		elseif(!$current_user->ID && (!$username || !$password || !$password2))
		{
			$pmpro_msg = "Please complete all account fields.";
			$pmpro_msgt = "pmpro_error";
		}
		elseif($password != $password2)
		{
			$pmpro_msg = "Your passwords do not match. Please try again.";
			$pmpro_msgt = "pmpro_error";
		}
		elseif($bemail != $bconfirmemail)
		{
			$pmpro_msg = "Your email addresses do not match. Please try again.";
			$pmpro_msgt = "pmpro_error";
		}		
		elseif($bemail && !is_email($bemail))
		{
			$pmpro_msg = "The email address entered is in an invalid format. Please try again.";	
			$pmpro_msgt = "pmpro_error";
		}
		elseif($tospage && !$tos)
		{
			$pmpro_msg = "Please check the box to agree to the " . $tospage->post_title . ".";	
			$pmpro_msgt = "pmpro_error";
		}
		elseif($fullname)
		{
			$pmpro_msg = "Are you a spammer?";
			$pmpro_msgt = "pmpro_error";
		}
		else
		{
			//user supplied requirements
			$pmpro_continue_registration = apply_filters("pmpro_registration_checks", true);
						
			if($pmpro_continue_registration)
			{							
				//if creating a new user, check that the email and username are available
				if(!$current_user->ID)
				{
					$oldusername = $wpdb->get_var("SELECT user_login FROM $wpdb->users WHERE user_login = '" . $wpdb->escape($username) . "' LIMIT 1");
					$oldemail = $wpdb->get_var("SELECT user_email FROM $wpdb->users WHERE user_email = '" . $wpdb->escape($bemail) . "' LIMIT 1");
					
					//this hook can be used to allow multiple accounts with the same email address
					$oldemail = apply_filters("pmpro_checkout_oldemail", $oldemail);
				}
				
				if($oldusername)
				{
					$pmpro_msg = "That username is already taken. Please try another.";
					$pmpro_msgt = "pmpro_error";
				}
				elseif($oldemail)
				{
					$pmpro_msg = "That email address is already taken. Please try another.";
					$pmpro_msgt = "pmpro_error";
				}
				else
				{			
					//check recaptch first
					global $recaptcha;
					if(!$skip_account_fields && ($recaptcha == 2 || ($recaptcha == 1 && !(float)$pmpro_level->billing_amount && !(float)$pmpro_level->trial_amount)))
					{
						global $recaptcha_privatekey;					
						$resp = recaptcha_check_answer($recaptcha_privatekey,
									$_SERVER["REMOTE_ADDR"],
									$_POST["recaptcha_challenge_field"],
									$_POST["recaptcha_response_field"]);
							
						if(!$resp->is_valid) 
						{
							$pmpro_msg = "reCAPTCHA failed. (" . $resp->error . ") Please try again.";
							$pmpro_msgt = "pmpro_error";
						} 
						else 
						{
							// Your code here to handle a successful verification
							$pmpro_msg = "All good!";
						}
					}
					else
						$pmpro_msg = "All good!";
							
					//no errors yet
					if($pmpro_msgt != "pmpro_error")
					{				
						if($pmpro_requirebilling)
						{
							$morder = new MemberOrder();			
							$morder->membership_id = $pmpro_level->id;
							$morder->membership_name = $pmpro_level->name;
							$morder->discountcode = $discountcode;
							$morder->InitialPayment = $pmpro_level->initial_payment;
							$morder->PaymentAmount = $pmpro_level->billing_amount;
							$morder->ProfileStartDate = date("Y-m-d") . "T0:0:0";
							$morder->BillingPeriod = $pmpro_level->cycle_period;
							$morder->BillingFrequency = $pmpro_level->cycle_number;
									
							if($pmpro_level->billing_limit)
								$morder->TotalBillingCycles = $pmpro_level->billing_limit;
						
							if(pmpro_isLevelTrial($pmpro_level))
							{
								$morder->TrialBillingPeriod = $pmpro_level->cycle_period;
								$morder->TrialBillingFrequency = $pmpro_level->cycle_number;
								$morder->TrialBillingCycles = $pmpro_level->trial_limit;
								$morder->TrialAmount = $pmpro_level->trial_amount;
							}
							
							//credit card values
							$morder->cardtype = $CardType;
							$morder->accountnumber = $AccountNumber;
							$morder->expirationmonth = $ExpirationMonth;
							$morder->expirationyear = $ExpirationYear;
							$morder->ExpirationDate = $ExpirationMonth . $ExpirationYear;
							$morder->ExpirationDate_YdashM = $ExpirationYear . "-" . $ExpirationMonth;
							$morder->CVV2 = $CVV;												
							
							//not saving email in order table, but the sites need it
							$morder->Email = $bemail;
							
							//sometimes we need these split up
							$morder->FirstName = $bfirstname;
							$morder->LastName = $blastname;						
							$morder->Address1 = $baddress1;
							$morder->Address2 = $baddress2;						
							
							//other values
							$morder->billing->name = $bfirstname . " " . $blastname;
							$morder->billing->street = trim($baddress1 . " " . $baddress2);
							$morder->billing->city = $bcity;
							$morder->billing->state = $bstate;
							$morder->billing->country = "US";
							$morder->billing->zip = $bzipcode;
							$morder->billing->phone = $bphone;
									
							$gateway = pmpro_getOption("gateway");										
							
							//setup level var
							$morder->getMembershipLevel();
							
							//tax
							$morder->subtotal = $morder->InitialPayment;
							$morder->getTax();						
														
							if($morder->process())
							{
								$pmpro_msg = "Payment accepted.";
								$pmpro_msgt = "pmpro_success";																	
							}			
							else
							{
								$pmpro_msg = $morder->error;
								if(!$pmpro_msg)
									$pmpro_msg = "Unknown error generating account. Please contact us to setup your membership.";
								$pmpro_msgt = "pmpro_error";								
							}	
														
						}	//end if($pmpro_requirebilling)
					}
					
					//must be all good. create/update the user.
					if($pmpro_msgt != "pmpro_error")
					{
						//do we need to create a user account?
						if(!$current_user->ID)
						{
							// create user
							require_once( ABSPATH . WPINC . '/registration.php');
							$user_id = wp_insert_user(array(
											"user_login" => $username,							
											"user_pass" => $password,
											"user_email" => $bemail)
											);
							if (!$user_id) {
								$pmpro_msg = "Your payment was accepted, but there was an error setting up your account. Please contact us.";
								$pmpro_msgt = "pmpro_error";
							} else {
							
								//check pmpro_wp_new_user_notification filter before sending the default WP email
								if(apply_filters("pmpro_wp_new_user_notification", true, $user_id, $pmpro_level->id))
									wp_new_user_notification($user_id, $password);								
						
								$wpuser = new WP_User(0, $username);
						
								//make the user a subscriber
								$wpuser->set_role("subscriber");
													
								//okay, log them in to WP							
								$creds = array();
								$creds['user_login'] = $username;
								$creds['user_password'] = $password;
								$creds['remember'] = true;
								$user = wp_signon( $creds, false );																	
							}
						}
						else
							$user_id = $current_user->ID;	
						
						if($user_id)
						{				
							//calculate the end date
							if($pmpro_level->expiration_number)
							{
								$enddate = "'" . date("Y-m-d", strtotime("+ " . $pmpro_level->expiration_number . " " . $pmpro_level->expiration_period)) . "'";
							}
							else
							{
								$enddate = "NULL";
							}
							
							//update membership_user table.
							$sqlQuery = "REPLACE INTO $wpdb->pmpro_memberships_users (user_id, membership_id, initial_payment, billing_amount, cycle_number, cycle_period, billing_limit, trial_amount, trial_limit, startdate, enddate) 
								VALUES('" . $user_id . "',
								'" . $pmpro_level->id . "',
								'" . $pmpro_level->initial_payment . "',
								'" . $pmpro_level->billing_amount . "',
								'" . $pmpro_level->cycle_number . "',
								'" . $pmpro_level->cycle_period . "',
								'" . $pmpro_level->billing_limit . "',
								'" . $pmpro_level->trial_amount . "',
								'" . $pmpro_level->trial_limit . "',
								NOW(),
								" . $enddate . ")";
							
							if($wpdb->query($sqlQuery) !== false)
							{
								//we're good
								//add an item to the history table, cancel old subscriptions						
								if($morder)
								{
									$morder->user_id = $user_id;
									$morder->membership_id = $pmpro_level->id;
																
									$morder->saveOrder();																
									
									//cancel any other subscriptions they have
									$other_order_ids = $wpdb->get_col("SELECT id FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . $current_user->ID . "' AND id <> '" . $morder->id . "' AND status = 'success' ORDER BY id DESC");
									foreach($other_order_ids as $order_id)
									{
										$c_order = new MemberOrder($order_id);
										$c_order->cancel();		
									}						
								}
							
								//update the current user
								global $current_user;
								if(!$current_user->ID && $user->ID)
									$current_user = $user;		//in case the user just signed up
								pmpro_set_current_user();
							
								//add discount code use
								if($discountcode && $use_discount_code)
								{
									$discountcode_id = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . $discountcode . "' LIMIT 1");
									$wpdb->query("INSERT INTO $wpdb->pmpro_discount_codes_uses (code_id, user_id, order_id, timestamp) VALUES('" . $discountcode_id . "', '" . $current_user->ID . "', '" . $morder->id . "', now())");
								}
							
								//save billing info ect, as user meta																		
								$meta_keys = array("pmpro_bfirstname", "pmpro_blastname", "pmpro_baddress1", "pmpro_baddress2", "pmpro_bcity", "pmpro_bstate", "pmpro_bzipcode", "pmpro_bphone", "pmpro_bemail", "pmpro_CardType", "pmpro_AccountNumber", "pmpro_ExpirationMonth", "pmpro_ExpirationYear");
								$meta_values = array($bfirstname, $blastname, $baddress1, $baddress2, $bcity, $bstate, $bzipcode, $bphone, $bemail, $CardType, hideCardNumber($AccountNumber), $ExpirationMonth, $ExpirationYear);						
								pmpro_replaceUserMeta($user_id, $meta_keys, $meta_values);	
													
								//show the confirmation
								$ordersaved = true;
													
								//hook
								do_action("pmpro_after_checkout", $user_id);						
								do_action("pmpro_after_change_membership_level", $pmpro_level->id, $user_id);																									
								//send email
								$pmproemail = new PMProEmail();
								if($morder)
									$invoice = new MemberOrder($morder->id);						
								else
									$invoice = NULL;
								$user->membership_level = $pmpro_level;		//make sure they have the right level info
								$pmproemail->sendCheckoutEmail($current_user, $invoice);
											
								//redirect to confirmation
								wp_redirect(pmpro_url("confirmation"));
								exit;
							}
							else
							{
								//uh oh. we charged them then the membership creation failed
								if($morder->cancel())
								{
									$pmpro_msg = "IMPORTANT: Something went wrong during membership creation. Your credit card authorized, but we cancelled the order immediately. You should not try to submit this form again. Please contact the site owner to fix this issue.";
									$morder = NULL;
								}
								else
									$pmpro_msg = "IMPORTANT: Something went wrong during membership creation. Your credit card was charged, but we couldn't assign your membership. You should not submit this form again. Please contact the site owner to fix this issue.";
								$pmpro_error;
							}												
						}
					}						
				}
			}	//endif($pmpro_continue_registration)
		}
	}
	elseif(!$submit)
	{
		//show message if the payment gateway is not setup yet
		if($pmpro_requirebilling && !pmpro_getOption("gateway"))
		{
			if(pmpro_isAdmin())			
				$pmpro_msg = "You must <a href=\"" . home_url('/wp-admin/admin.php?page=pmpro-membershiplevels&view=payment') . "\">setup a Payment Gateway</a> before any payments will be processed.";
			else
				$pmpro_msg = "A Payment Gateway must be setup before any payments will be processed.";
			$pmpro_msgt = "";
		}
		
		//default values from DB
		$bfirstname = get_user_meta($current_user->ID, "pmpro_bfirstname", true);
		$blastname = get_user_meta($current_user->ID, "pmpro_blastname", true);
		$baddress1 = get_user_meta($current_user->ID, "pmpro_baddress1", true);
		$baddress2 = get_user_meta($current_user->ID, "pmpro_baddress2", true);
		$bcity = get_user_meta($current_user->ID, "pmpro_bcity", true);
		$bstate = get_user_meta($current_user->ID, "pmpro_bstate", true);
		$bzipcode = get_user_meta($current_user->ID, "pmpro_bzipcode", true);
		$bphone = get_user_meta($current_user->ID, "pmpro_bphone", true);
		$bemail = get_user_meta($current_user->ID, "pmpro_bemail", true);
		$bconfirmemail = get_user_meta($current_user->ID, "pmpro_bconfirmemail", true);
		$CardType = get_user_meta($current_user->ID, "pmpro_CardType", true);
		//$AccountNumber = hideCardNumber(get_user_meta($current_user->ID, "pmpro_AccountNumber", true), false);
		$ExpirationMonth = get_user_meta($current_user->ID, "pmpro_ExpirationMonth", true);
		$ExpirationYear = get_user_meta($current_user->ID, "pmpro_ExpirationYear", true);	
	}
?>