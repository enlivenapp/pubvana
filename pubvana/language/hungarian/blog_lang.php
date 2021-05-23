<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Name:	  Blog Lang - Hungarian
* Author: eaposztrof
*	  eapo@valto.ro
*	  https://github.com/eapo/
*
* Location: http://github.com/benedmunds/ion_auth/
*
* Created:  24.05.2021
*
* Description: Hungarian language file for Blog views
*
*/


// headers
$lang['recent_posts']					= "Legutóbbi Bejegyzések";
$lang['older_posts']					= "Régebbi bejegyzések";
$lang['connect_hdr']					= "Csatlakozás";
$lang['blog_links_hdr']					= "Hivatkozások";
$lang['links_hdr']					= "Linkek";
$lang['archives_hdr']					= "Archívum";
$lang['categories_hdr']					= "Kategóriák";
$lang['blog_notices_hdr']				= "Feliratkozás";
$lang['blog_notices_unsub_hdr']				= "Leiratkozás";
				
$lang['category_hdr']					= "Bejegyzések a kategóriában ";
$lang['archives_for_hdr']				= "Archívum ";
$lang['blog_notices_help_txt']				= "E-mail küldése új tartalom hozzáadásakor.";
$lang['blog_notices_help_unsub_txt']			= "Adja meg az e-mail címet a leiratkozáshoz.";


// General Buttons
$lang['btn_read_more'] 					= "Tovább&hellip;";
$lang['btn_edit']						= "Szerkeszt";
$lang['btn_remove']						= "Eltávolít";
$lang['btn_unpub']						= "Közzététel visszavonása";
$lang['submit']							= "Beküldés";


// Comments
$lang['comments_disabled']				= "Hozzászólások le vannak tiltva.";
$lang['comments_disabled_post']			= "A hozzászólások le vannak tiltva ehhez a bejegyzéshez.";
$lang['comments']						= "Hozzászólások";
$lang['comment']						= "Hozzászólás";
$lang['comments_title']					= "Hozzászólás";
$lang['comment_submit']					= "Hozzászólás Beküldése";
$lang['no_comments']					= "Még Nincsenek Hozzászólások";
$lang['btn_approve_comment']			= "Hozzászólás Jóváhagyása"; // Approve modded comment
$lang['btn_remove_comment']				= "Hozzászólás törése"; // Deletes the comment
$lang['btn_hide_comment']				= "Hozzászólás Elrejtése";  // Hides Comment, provides link to 'view anyway'
$lang['btn_flag_comment']				= "Hozzászólás Megjelölése";  // flag as inappropriate, unhelpful, or SPAM
$lang['comment_help_text']				= "Megjegyzés: HTML nélkül";



// Misc
$lang['add_to']						= "Hozzáadás:";
$lang['posted_in']					= "Közzétéve";
$lang['in']							= "itt:";
$lang['by'] 						= "által";

// old pagination?
$lang['older_entries']					= "&laquo; Régebbi Bejegyzések";
$lang['newer_entries']					= "Újabb Bejegyzések &raquo;";




// Single post
$lang['leave_reply']					= "Hozzászólás";
$lang['leave_reply_description']			= "Ossza meg gondolatait a bejegyzéssel kapcsolatban.";

$lang['responses_to']					= "Válaszok (%d) ";
$lang['on']							= ", ";
$lang['at']							= ", ";
$lang['nickname']					= "Név";
$lang['email']						= "Email";
//$lang['website']					= "Website";  // Depreciated
$lang['confirmation_image']				= "Megerősítő kép";
$lang['confirmation_code']				= "Megerősítő kód";


// Tags
$lang['posts_tagged_with']				= "Bejegyzés cimkézve";

// Search
$lang['search_results_for']				= "Keresési eredmények";
$lang['no_results']					= "Nincs találat erre a keresési kifejezésre.";
$lang['in']						= "itt";

// Errors
$lang['error']					= "Hiba";
$lang['not_found']				= "Nem található.";
$lang['no_posts_for_this_tag']			= "Nincsenek bejegyzések ehhez a címkéhez.";
$lang['no_posts_for_this_date']			= "Nincsenek bejegyzések ezen a dátumon.";
$lang['no_posts_found']				= "Nem található bejegyzés";
$lang['blog_no_posts']				= "Nincsenek blog-bejegyzések.";
$lang['invalid_confirmation_code']		= "A megadott megerősítő kód érvénytelen.";
$lang['error_404_heading']			= "Hoppá!";
$lang['error_404_message']			= "Hiba történt. Kérjük, ellenőrizze az URL-t, és próbálja újra.";
$lang['recaptcha']				= "reCAPTCHA";

// Success
$lang['add_comment_success']			= "Sikeres Hozzászólás.";
$lang['add_comment_success_modded']		= "Sikeres Hozzászólás. Adminisztrátor jóváhagyása után megjelenik.";


// Emails
$lang['email_new_comment_sbj']			= "Új Hozzászólás";
$lang['email_new_comment_msg']			= "Új Hozzászólás Érkezett. A Vezérlőpulton kezelhető <br><br>Az új Hozzászólás:<br><br>";


// notices
$lang['notices_enter_email_address']		= "Email cím megadása";
$lang['notices_get_notices']			= "Értesítés!";
$lang['notices_unsub_btn']			= "Leiratkozás";
$lang['notices_no_post_data']			= "Hiba. Próbálkozzon újra.";
$lang['email_address']				= "Email cím";
$lang['notify_new_notification']		= 'Értesítése Igénylése';
$lang['notices_email_verify_msg']		= "Someone requested this email address to receive notifications when we post new content on our website.  If this is correct, click or follow the link below to verify the email, if you did not, you can ignore this email and we will not send you any notices. <br><br>Verification Link: ";
$lang['notices_verify_failed']			= 'It seems the verification failed for some reason.  Please check your email and try again.';
$lang['notices_verify_success']			= "Thank you for verifying your email address.  We'll send you a confirmation email too!";
$lang['notify_success']				= 'Sikeres ellenőrzés';
$lang['notices_success_verifed_msg']		= 'Thank you for verifying your email address.  We will begin sending you a notice when we add content to our blog.';
$lang['notices_add_success']			= 'Thank you for your interest in our content!  Please check your email for further instructions to verify your email address.';
$lang['notices_email_exists']			= 'Looks like we already have this address.';
$lang['notices_email_not_exists']		= "Looks like we don't have that address.  Try again?";
$lang['notify_unsub_success']			= "Successfully Unsubscribed to new content. We're sorry to see you go";
$lang['notify_unsub_sbj']			= 'Leiratkozott';
$lang['notices_success_unsub_msg']		= 'This email is to confirm successfully unsubscribing from new content.  <b>We will miss you!</b><br><br>This will be the last email you receive unless you re-subscribe.';

//$lang['']	= '';

// public language
$lang['language_changed_successfully']	= 'Sikeres nyelvválasztás';
$lang['language_not_available']			= 'A választott nyelv nem elérhető.';

// new for Pubvana
// Please translate

$lang['search_hdr']					= 'Keresés';
$lang['search_results_hdr']				= 'Keresési találatok ';
$lang['search_btn']					= 'Keres!';
$lang['search_txt']					= 'Kifejezés vagy szavak keresése:';
$lang['search_again']					= 'Újrakeresés';
$lang['page']						= "Oldal";
$lang['pages']						= "Oldalak";	
$lang['post']						= "Bejegyzés";
$lang['posts']						= "Bejegyzések";	
$lang['search_in']					= "Itt: ";
$lang['title']						= "Cím";
$lang['body']						= "Tartalom";
$lang['search_term']					= "Keresési Kifejezés";

// shiny new contact form text
$lang['contact_hdr']					= 'Kapcsolat';
$lang['contact_sent']					= "Thank you for contacting me. I will respond as soon as I can."; 
$lang['contact_prob']					= "There was a problem sending your message.  Please try again."; 

$lang['contact_txt']					= 'Thank you for your interest in contacting me.  Please fill in the form and I will return your email as soon as possible.';
$lang['contact_name']					= 'Név';
$lang['contact_email']					= 'Email';
$lang['contact_msg']					= 'Üzenet';

// contact email...
$lang['contact_subject']				= 'New Website Contact';
$lang['contact_pt1']					= "You've received a new message from: ";
$lang['contact_pt2']					= "<br><br>Email Address: ";
$lang['contact_pt3']					= "<br><br>The Message: ";
$lang['contact_pt4']					= "<br><br>You may also view the message on your dashboard.";

// js validator text

$lang['name_empty']					= "A Név megadása szükséges, nem lehet üres";
$lang['email_empty']					= 'E-mail cím megadása szükséges';
$lang['email_invalid']					= 'Az e-mail cím érvénytelen';
$lang['msg_empty']					= 'Az Üzenet szükséges, nem lehet üres';

// misc texts
$lang['updated']					= 'Utoljára Frissítve: ';

$lang['featured']					= 'Kiemelt';

$lang['featured']					= 'Kiemelt';
$lang['sticky']						= 'Elsőbbségi';
