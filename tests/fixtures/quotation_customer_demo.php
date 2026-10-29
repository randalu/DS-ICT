<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('DB_NAME')!=='ict_quotation_rehearsal_20261023'){exit(2);}
require_once __DIR__.'/../../includes/quotation_pdf.php';
require_once __DIR__.'/../../includes/quotation_booking.php';
require_once __DIR__.'/../../includes/booking_workflow.php';
if(!in_array(DB_HOST,['127.0.0.1','localhost'],true)){exit(2);}
$ids=[];
foreach(['owner','other'] as $role){$sub='quotation-browser-'.$role;$row=fetch_one('SELECT id FROM customer_users WHERE google_sub=:sub',['sub'=>$sub]);if(!$row){execute_query('INSERT INTO customer_users (google_sub,email,name) VALUES (:sub,:email,:name)',['sub'=>$sub,'email'=>'quotation-'.$role.'@example.invalid','name'=>'Quotation browser '.$role]);$row=['id'=>(int)last_insert_id()];}$ids[$role]=(int)$row['id'];}
$d=quote_defaults();$d['customer']=['name'=>'Account Browser Fixture','phone'=>'+94110000000','email'=>'quotation-owner@example.invalid','country'=>'Example','adults'=>2,'children'=>0,'ages'=>''];$d['title']='Account quotation browser check';$d['arrival']='2027-12-09T23:30';$d['departure']='2027-12-11T10:00';$d['options']['A']['enabled']=false;$d['options']['B']['amount']='1210.00';
$d['days']=[['start'=>0,'end'=>1,'date'=>'2027-12-09','title'=>'Airport to Nuwara Eliya','plan'=>'A synthetic private transfer and hill-country stay.','overnight'=>'Nuwara Eliya','distance'=>'Approx. 170 km','travel_time'=>'Approx. 5 hours','image'=>'assets/images/country-market/nuwara-eliya-tea.jpg','image_manual'=>false,'destination_id'=>0,'activities'=>[]]];
$ids['revision']=quote_create(101,quote_document($d));quote_issue($ids['revision'],1,101);$ids['plan']=quote_convert($ids['revision'],'B',101,$ids['owner']);ict_request_booking_acceptance($ids['plan'],101,true);
file_put_contents(dirname(__DIR__,2).'/output/quotation/customer-demo.json',quote_json($ids));echo quote_json($ids),"\n";
