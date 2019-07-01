<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Name:  Ion Auth Lang - Slovak
*
* Author: Kristián Feldsam
* 		  kristian@feldsam.cz
*
*
* Created:  11.05.2012
*
* Description:  Slovak language file for Ion Auth messages and errors
*
*/

// Account Creation
$lang['account_creation_successful'] 	  	 = 'Účet bol úspešne vytvorený';
$lang['account_creation_unsuccessful'] 	 	 = 'Nie je možné vytvoriť účet';
$lang['account_creation_duplicate_email'] 	 = 'E-mail už existuje alebo je neplatný';
$lang['account_creation_duplicate_identity'] = 'Užívateľské meno už existuje alebo je neplatné';

// TODO Please Translate
$lang['account_creation_missing_default_group'] = 'Defaultná skupina nie je nastavená';
$lang['account_creation_invalid_default_group'] = 'Nastavený chybný názov defaultnej skupiny';

// Password
$lang['password_change_successful'] 	 	 = 'Heslo bolo úspešne zmenené';
$lang['password_change_unsuccessful'] 	  	 = 'Nie je možné zmeniť heslo';
$lang['forgot_password_successful'] 	 	 = 'Heslo bolo odoslané na e-mail';
$lang['forgot_password_unsuccessful'] 	 	 = 'Nie je možné obnoviť heslo';

// Activation
$lang['activate_successful'] 		  	     = 'Účet bol aktivovaný';
$lang['activate_unsuccessful'] 		 	     = 'Nie je možné aktivovať účet';
$lang['deactivate_successful'] 		  	     = 'Účet bol deaktivovaný';
$lang['deactivate_unsuccessful'] 	  	     = 'Nie je možné deaktivovať účet';
$lang['activation_email_successful'] 	  	 = 'Aktivačný e-mail bol odoslaný';
$lang['activation_email_unsuccessful']   	 = 'Nedá sa odoslať aktivačný e-mail';

// Login / Logout
$lang['login_successful'] 		  	         = 'Úspešne prihlásený';
$lang['login_unsuccessful'] 		  	     = 'Nesprávny e-mail alebo heslo';
$lang['login_unsuccessful_not_active'] 		 = 'Účet je neaktívny';
$lang['login_timeout']                       = 'Dočasne zablokované.  Skúste prosím neskôr.';
$lang['logout_successful'] 		 	         = 'Úspešné odhlásenie';

// Account Changes
$lang['update_successful'] 		 	         = 'Informácie o účte boli úspešne aktualizované';
$lang['update_unsuccessful'] 		 	     = 'Informácie o účte sa nedájú aktualizovať';
$lang['delete_successful'] 		 	         = 'Užívateľ bol zmazaný';
$lang['delete_unsuccessful'] 		 	     = 'Užívateľ sa nedá zmazať ';

// Groups
$lang['group_creation_successful']  = 'Skupina bola úspešne vytvorená';
$lang['group_already_exists']       = 'Názov skupiny už existuje';
$lang['group_update_successful']    = 'Detail skupiny bol upravený';
$lang['group_delete_successful']    = 'Skupina bola vymazaná';
$lang['group_delete_unsuccessful'] 	= 'Nebolo možné zmazať skupinu';
$lang['group_delete_notallowed']    = 'Nie je možné zmazať admin skupinu';
$lang['group_name_required'] 		= 'Meno skupiny je povinné pole';
$lang['group_name_admin_not_alter'] = 'Meno admin skupiny nie je možné zmeniť';

// Activation Email
$lang['email_activation_subject']            = 'Aktivácia účtu';
$lang['email_activate_heading']    = 'Aktivuj účet pre %s';
$lang['email_activate_subheading'] = 'Prosím kliknite na odkaz pre %s.';
$lang['email_activate_link']       = 'Aktivujte si svoj účet';
// Forgot Password Email
$lang['email_forgotten_password_subject']    = 'Overenie zabudnutého hesla';
$lang['email_forgot_password_heading']    = 'Resetovať heslo pre %s';
$lang['email_forgot_password_subheading'] = 'Prosím kliknite na odkaz pre %s.';
$lang['email_forgot_password_link']       = 'Resetovať heslo';
// New Password Email
$lang['email_new_password_subject']          = 'Nové heslo';
$lang['email_new_password_heading']    = 'Nové heslo pre %s';
$lang['email_new_password_subheading'] = 'Vaše heslo bolo zresetované na: %s';
