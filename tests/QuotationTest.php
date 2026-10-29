<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/quotation_pdf.php';
require_once __DIR__ . '/../includes/quotation_booking.php';
require_once __DIR__ . '/../includes/booking_workflow.php';
require_once __DIR__ . '/../includes/customer_auth.php';

final class QuotationTest extends TestCase
{
    protected function setUp(): void
    {
        if (DB_NAME !== 'ict_quotation_rehearsal_20261023' || !in_array(DB_HOST, ['127.0.0.1','localhost'], true)) { $this->markTestSkipped('Quotation integration tests require the named disposable schema.'); }
        ensure_customer_accounts_schema(); ensure_site_settings_schema();
        $this->assertTrue(quote_ready()); db()->beginTransaction();
    }
    protected function tearDown(): void { if (DB_NAME === 'ict_quotation_rehearsal_20261023') { $this->assertTrue(db()->inTransaction(), 'Service operations must preserve the rollback-only test transaction.'); if (db()->inTransaction()) { db()->rollBack(); } } }
    private function document(): array
    {
        $d=quote_defaults();$d['customer']=['name'=>'Synthetic Traveler','phone'=>'+94110000000','email'=>'traveler@example.invalid','country'=>'Example','adults'=>2,'children'=>0,'ages'=>''];
        $d['title']='Hill country and coast';$d['arrival']='2027-12-09T23:30';$d['departure']='2027-12-14T16:00';
        $d['options']['A']['amount']='1930.00';$d['options']['B']['amount']='1210.00';
        $d['days']=[['start'=>0,'end'=>1,'date'=>'2027-12-09','title'=>'Airport to Nuwara Eliya','plan'=>'Airport welcome and private transfer. Rest in the hill country.','overnight'=>'Nuwara Eliya','travel_time'=>'Approx. 5 hours','distance'=>'Approx. 170 km','image'=>'assets/images/country-market/nuwara-eliya-tea.jpg','destination_id'=>0,'image_manual'=>false,'activities'=>[['name'=>'Scenic train journey','description'=>'A rail journey through the hills.','image'=>'','status'=>['A'=>'included','B'=>'optional']]]],['start'=>2,'end'=>2,'date'=>'2027-12-11','title'=>'Nanu Oya to Ella','plan'=>'Travel by train and continue toward Yala.','overnight'=>'Yala','travel_time'=>'','distance'=>'','image'=>'assets/images/country-market/nine-arch-bridge.jpg','destination_id'=>0,'image_manual'=>false,'activities'=>[]]];
        $d['hotels']=[['day'=>0,'name'=>'Example Hill Hotel','alternative'=>'Example Tea Lodge','location'=>'Nuwara Eliya','nights'=>2,'star'=>'3 star','room'=>'Double room','occupancy'=>'Two adults sharing','meals'=>'Breakfast and dinner']];
        return quote_document($d);
    }
    private function issued(?array $document=null): int { $id=quote_create(101,$document??$this->document());quote_issue($id,1,101);return $id; }
    private function acceptance(int $plan,int $revision,string $option='B'): int
    {
        $snap=ict_policy_acceptance_snapshot(ict_policy_published_documents());
        return quote_guest_accept($plan,['customer_name'=>'Synthetic Traveler','channel'=>'whatsapp','evidence_reference'=>'SYN-MESSAGE-1','accepted_at'=>str_replace(' ','T',substr(quote_now(),0,16)),'all_policies'=>'1','policy_snapshot_hash'=>$snap['hash'],'revision_id'=>$revision,'option_key'=>$option],101);
    }
    private function confirmation(int $acceptance): array
    {
        $snap=ict_policy_acceptance_snapshot(ict_policy_published_documents());return ['quote_reference'=>'SYN-Q1','payment_status'=>'funds_received','payment_reference'=>'SYN-P1','supplier_reference'=>'SYN-S1','payment_verified'=>'1','supplier_verified'=>'1','final_services_reviewed'=>'1','accepted_snapshot_hash'=>$snap['hash'],'acceptance_record_id'=>(string)$acceptance];
    }
    public function testMoneyUsesExactDecimalsAndRejectsFloatingNotation(): void
    {
        $this->assertSame(193000,quote_cents('1930'));$this->assertSame(10,quote_cents('0.1'));$this->assertSame('0.01',quote_money(1));
        $d=$this->document();$d['options']['B']['basis']='person';$d['options']['B']['adult_rate']='0.10';$d['customer']['children']=1;$d['options']['B']['child_rate']='0.20';$this->assertSame(40,quote_option_total($d,'B'));
        $this->expectException(InvalidArgumentException::class);quote_cents('1e3');
    }
    public function testDraftIncompleteAndConflictPreservesLatest(): void
    {
        $id=quote_create(101);$d=quote_defaults();quote_save($id,1,3,$d,101);$this->assertSame(3,(int)quote_get($id)['step']);
        try{quote_save($id,1,2,$d,101);$this->fail('Stale write allowed');}catch(DomainException){$this->assertSame(2,(int)quote_get($id)['lock_version']);}
        $this->expectException(InvalidArgumentException::class);quote_issue($id,2,101);
    }
    public function testIssuedPdfAndImagesAreArchivedAndRevisionsIndependent(): void
    {
        $id=$this->issued();$r=quote_get($id);$this->assertStringStartsWith('%PDF-',$r['pdf_bytes']);$this->assertSame(hash('sha256',$r['pdf_bytes']),$r['pdf_sha256']);$this->assertStringContainsString('data:image/jpeg;base64,',$r['assets_json']);
        $next=quote_revise($id,101);$this->assertSame(2,(int)quote_get($next)['revision']);$d=$r['document'];$d['title']='Changed title';quote_save($next,1,1,$d,101);$this->assertSame($r['document_json'],quote_get($id)['document_json']);$this->assertSame($r['pdf_bytes'],quote_get($id)['pdf_bytes']);
        $this->expectException(DomainException::class);quote_save($id,2,1,$d,101);
    }
    public function testTemplatesAndDefaultsDoNotRewriteQuotes(): void
    {
        $d=$this->document();$id=quote_create(101,$d);$day=$d['days'][0];$lib=quote_library_save('day','Synthetic day',$day,0);$this->assertGreaterThan(0,$lib);
        $master=fetch_one("SELECT lock_version FROM quotation_library WHERE kind='defaults' AND name='master'");quote_library_save('defaults','master',['intro'=>'New default','footer'=>'Signature','inclusions'=>$d['inclusions']],(int)($master['lock_version']??0));$this->assertSame('New default',quote_defaults()['intro']);$this->assertNotSame('New default',quote_get($id)['document']['intro']);
        $this->expectException(DomainException::class);quote_library_save('day','Synthetic day',$day,0);
    }
    public function testSingleOptionAndDayZeroArePreserved(): void
    {
        $d=$this->document();$d['options']['A']['enabled']=false;$d['hotels']=[];$id=$this->issued($d);$plan=quote_convert($id,'B',101);
        $days=quote_booking_days($plan);$this->assertSame(0,(int)$days[0]['day_number']);$this->assertSame(1,(int)$days[0]['day_end']);$this->assertStringContainsString('Optional - extra charge',$days[0]['summary']);$this->assertSame($plan,quote_convert($id,'B',101));
    }
    public function testGuestAcceptanceAndConfirmationAreSeparate(): void
    {
        $id=$this->issued();$plan=quote_convert($id,'B',101);$this->assertNull(fetch_one('SELECT customer_user_id FROM customer_itinerary_plans WHERE id=:id',['id'=>$plan])['customer_user_id']);
        $acceptance=$this->acceptance($plan,$id);$p=fetch_one('SELECT * FROM customer_itinerary_plans WHERE id=:id',['id'=>$plan]);$this->assertSame('accepted',$p['booking_status']);$this->assertNull($p['booking_confirmed_at']);
        $this->assertGreaterThan(0,ict_confirm_booking($plan,101,$this->confirmation($acceptance)));$this->assertSame('confirmed',fetch_one('SELECT booking_status FROM customer_itinerary_plans WHERE id=:id',['id'=>$plan])['booking_status']);
    }
    public function testSameEmailCannotClaimGuestInquiryOrPlan(): void
    {
        $id=$this->issued();$plan=quote_convert($id,'B',101);$p=fetch_one('SELECT * FROM customer_itinerary_plans WHERE id=:id',['id'=>$plan]);
        execute_query("INSERT INTO customer_users (google_sub,email,name) VALUES (:sub,'traveler@example.invalid','Another account')",['sub'=>'quote-test-'.bin2hex(random_bytes(8))]);$customer=['id'=>(int)last_insert_id(),'email'=>'traveler@example.invalid'];customer_link_inquiries_by_email($customer['id'],$customer['email']);
        $this->assertNull(fetch_one('SELECT customer_user_id FROM contact_inquiries WHERE id=:id',['id'=>$p['contact_inquiry_id']])['customer_user_id']);$this->assertSame([],fetch_customer_inquiries($customer));$this->assertNull(find_customer_inquiry((int)$p['contact_inquiry_id'],$customer));$this->assertNull(find_customer_plan($plan,$customer));
    }
    public function testChangedServicesBlockConfirmation(): void
    {
        $id=$this->issued();$plan=quote_convert($id,'B',101);$acceptance=$this->acceptance($plan,$id);execute_query("UPDATE customer_itinerary_days SET summary='Changed route' WHERE plan_id=:id",['id'=>$plan]);
        $this->expectException(RuntimeException::class);ict_confirm_booking($plan,101,$this->confirmation($acceptance));
    }
    public function testChangedPoliciesBlockConfirmation(): void
    {
        $id=$this->issued();$plan=quote_convert($id,'B',101);$acceptance=$this->acceptance($plan,$id);execute_query("UPDATE static_pages SET content=CONCAT(content,'<p>Changed policy.</p>') WHERE slug='privacy-policy'");
        $this->expectException(RuntimeException::class);ict_confirm_booking($plan,101,$this->confirmation($acceptance));
    }
    public function testChangedTripSupportBlocksRequestForAcceptance(): void
    {
        execute_query("INSERT INTO customer_users (google_sub,email,name) VALUES (:sub,'support-quote@example.invalid','Account Traveler')",['sub'=>'quote-support-'.bin2hex(random_bytes(8))]);$customer=(int)last_insert_id();$id=$this->issued();$plan=quote_convert($id,'A',101,$customer);
        execute_query("UPDATE customer_itinerary_plans SET trip_support_type='self_booked_adventure' WHERE id=:id",['id'=>$plan]);$this->expectException(RuntimeException::class);ict_request_booking_acceptance($plan,101,true);
    }
    public function testNewIssuedRevisionRequiresReviewedReconversionAndFreshAcceptance(): void
    {
        $id=$this->issued();$plan=quote_convert($id,'B',101);$a=$this->acceptance($plan,$id);$next=quote_revise($id,101);quote_issue($next,1,101);
        try{ict_confirm_booking($plan,101,$this->confirmation($a));$this->fail('Old revision confirmed');}catch(RuntimeException){$this->addToAssertionCount(1);}
        try{quote_convert($next,'B',101);$this->fail('Silent replacement');}catch(DomainException){$this->addToAssertionCount(1);}
        $this->assertSame($plan,quote_convert($next,'B',101,0,true));$this->assertSame('planning',fetch_one('SELECT booking_status FROM customer_itinerary_plans WHERE id=:id',['id'=>$plan])['booking_status']);
    }
    public function testConversionFailureRollsBackAllWrites(): void
    {
        $id=$this->issued();$before=(int)fetch_one('SELECT COUNT(*) n FROM contact_inquiries')['n'];
        db()->exec('SET @ict_quote_fail_conversion=1');
        try{quote_convert($id,'B',101);$this->fail('Late write failure was ignored');}catch(PDOException){$this->assertSame($before,(int)fetch_one('SELECT COUNT(*) n FROM contact_inquiries')['n']);$this->assertNull(fetch_one('SELECT id FROM quotation_bookings WHERE revision_id=:id',['id'=>$id]));}finally{db()->exec('SET @ict_quote_fail_conversion=0');}
    }
    public function testRemoteAndTraversalImagePathsAreRejected(): void
    {
        foreach(['https://example.invalid/image.jpg','assets/images/../../config/test.jpg','file:///test.jpg'] as $path){try{quote_image_bytes($path);$this->fail('Unsafe image accepted');}catch(InvalidArgumentException){$this->addToAssertionCount(1);}}
        $this->assertNull(quote_image_bytes('assets/images/quotation-missing.jpg'));
    }
    public function testAccountQuotationAcceptanceBindsDisplayedPriceAndRevision(): void
    {
        execute_query("INSERT INTO customer_users (google_sub,email,name) VALUES (:sub,'account-quote@example.invalid','Account Traveler')",['sub'=>'quote-account-'.bin2hex(random_bytes(8))]);$customer=(int)last_insert_id();
        $id=$this->issued();$plan=quote_convert($id,'A',101,$customer);ict_request_booking_acceptance($plan,101,true);
        $snapshot=ict_policy_acceptance_snapshot(ict_policy_published_documents());$input=array_fill_keys(['accept_booking_terms','accept_cancellation_policy','accept_payment_policy','accept_privacy_policy'],'1');$input['policy_snapshot_hash']=$snapshot['hash'];
        try{ict_accept_booking_policies($plan,$customer,$input,null,'Synthetic');$this->fail('Acceptance without displayed quote binding allowed');}catch(RuntimeException){$this->assertSame('awaiting_acceptance',fetch_one('SELECT booking_status FROM customer_itinerary_plans WHERE id=:id',['id'=>$plan])['booking_status']);}
        $input['quotation_token']=quote_customer_token($plan);$acceptance=ict_accept_booking_policies($plan,$customer,$input,null,'Synthetic');$this->assertNotNull(fetch_one('SELECT * FROM quotation_customer_acceptances WHERE acceptance_id=:id',['id'=>$acceptance]));$this->assertGreaterThan(0,ict_confirm_booking($plan,101,$this->confirmation($acceptance)));
    }
    public function testConfirmedBookingCannotBeReplaced(): void
    {
        $id=$this->issued();$plan=quote_convert($id,'B',101);$a=$this->acceptance($plan,$id);ict_confirm_booking($plan,101,$this->confirmation($a));$next=quote_revise($id,101);quote_issue($next,1,101);$this->expectException(DomainException::class);quote_convert($next,'A',101,0,true);
    }
    public function testIssueRejectsDayOverlapInvalidDatesAndConflictingInclusions(): void
    {
        $d=$this->document();$d['days'][1]['start']=1;try{quote_validate_issue($d);$this->fail('Overlap allowed');}catch(InvalidArgumentException){$this->addToAssertionCount(1);}
        $d=$this->document();$d['departure']='2027-12-08T12:00';try{quote_validate_issue($d);$this->fail('Date mismatch allowed');}catch(InvalidArgumentException){$this->addToAssertionCount(1);}
        $d=$this->document();$d['inclusions'][]=['name'=>'Scenic train journey','status'=>['A'=>'not_included','B'=>'included']];$this->expectException(InvalidArgumentException::class);quote_validate_issue($d);
    }
}
