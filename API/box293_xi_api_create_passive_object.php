#!/usr/bin/php -q
<?php

/*
Nagios XI API script to create passive host and service objects
Copyright (c) 2017 Troy Lea aka Box293
Portions Copyright (c) others - see source code
License: GPL v3

The purpose of this script is to create passive host and service objects in Nagios XI.
Specifically these objects reflect the defaults that are used when running the wizard on the Unconfigured Objects page in Nagios XI.
The script is intended to be used when observing the nagios.log, it records when host or service objects do not exist when.
check results are received for them. This script would be used as an EXEC command in an OUTPUT in Nagios Log Server.

Version 2017-09-04
*/
	
/* Call the function that does it all */
doit();


function check_xi_object_exists($object_type) {
	global $apikey;
	global $host;
	global $service;

	$curl_record_count = '';

	if ($object_type == 'host') {
		$api_url_data = 'api/v1/objects/host?apikey='.$apikey.'&host_name='.urlencode($host);
		$curl_output = curl_xget($api_url_data);
		$curl_record_count = $curl_output['hostlist']['recordcount'];
		}
	else {
		$api_url_data = 'api/v1/objects/service?apikey='.$apikey.'&host_name='.urlencode($host).'&service_description='.urlencode($service);
		$curl_output = curl_xget($api_url_data);
		$curl_record_count = $curl_output['servicelist']['recordcount'];
		}
	//print_r("\n" . $curl_record_count . "\n");
	return $curl_record_count;
	} /* End function check_xi_object_exists($object_type) { */


function create_object($object_type, $apply_value) {
	global $apikey;

	$api_url_data;
	$api_object_data = define_object($object_type, $apply_value);
	
	if ($object_type == 'host') {
		$api_url_data = 'api/v1/config/host?apikey='.$apikey;
		}
	else {
		/* Need to do a query first to determine if a host object exists */
		$api_url_data = 'api/v1/config/service?apikey='.$apikey;
		}
	
	$curl_output = curl_xpost($api_url_data, $api_object_data);
	print_r("$curl_output");
	}


function curl_verify() {
	global $api_ssl;
	global $url;
	global $apikey;

	/* Determine if SSL is used */
	$ssl_test = substr($url, 0, 5);
	if ($ssl_test == 'https') {
		$api_ssl = 1;
		} /* End if ($ssl_test == 'https') { */
	else {
		$api_ssl = 0;
		} /* End else { */

	/* Define Timeout */
	global $api_timeout;
	$api_timeout = 120;

	/* Ensure url ends with a / */
	if (substr($url, -1) != '/') {
		$url .= '/';
		} /* End if (substr($url, -1) != '/') { */

	$url_data = 'api/v1/system/status?apikey=' . $apikey;
	
	/* Perform a test api request */
	/* Define the curl options */
	$curl_handle = curl_init();
	curl_setopt($curl_handle, CURLOPT_URL, $url.$url_data);
	curl_setopt($curl_handle, CURLOPT_HEADER, 0);
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl_handle, CURLOPT_TIMEOUT, $api_timeout);
	if ($api_ssl == 1) {
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
		} /* End if ($api_ssl == 1) { */

	/* Execute the Curl Request */
	$curl_output = curl_exec($curl_handle);
	$curl_output_array = json_decode($curl_output, true);

	/* Get the Curl Info */
	$curl_info = curl_getinfo($curl_handle);

	/* Close the Curl handle */
	curl_close($curl_handle);
	
	if ($curl_info['http_code'] == 200 AND isset($curl_output_array['instance_id'])) {
		/* The API responded */
		} /* End if ($curl_info['http_code'] == 200 AND isset($curl_output_array['instance_id'])) { */
	else {
		print_r("\nThe XI API test failed with the following output, aborting!\n");
		print_r($curl_output);
		print_r("\n");
		exit(1);
		} /* End else { */
	} /* End function curl_verify() { */


function curl_xget($api_url_data) {
	global $api_ssl;
	global $url;
	global $api_timeout;
	
	/* Perform the api xget request */
	/* Define the curl options */
	$curl_handle = curl_init();
	curl_setopt($curl_handle, CURLOPT_URL, $url.$api_url_data);
	curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($curl_handle, CURLOPT_HEADER, 0);
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl_handle, CURLOPT_TIMEOUT, $api_timeout);
	if ($api_ssl == 1) {
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
		} /* End if ($api_ssl == 1) { */
	
	/* Execute the Curl Request */
	$curl_output = curl_exec($curl_handle);

	/* Get the Curl Info */
	$curl_info = curl_getinfo($curl_handle);

	/* Close the Curl handle */
	curl_close($curl_handle);

	return(json_decode($curl_output,true));
	} /* End function curl_xget($api_url_data) { */


function curl_xpost($api_url_data, $api_object_data) {
	global $api_ssl;
	global $url;
	global $api_timeout;
	
	/* Perform the api xpost request */
	/* Define the curl options */
	$curl_handle = curl_init();
	curl_setopt($curl_handle, CURLOPT_URL, $url.$api_url_data);
	curl_setopt($curl_handle, CURLOPT_HEADER, 0);
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl_handle, CURLOPT_TIMEOUT, $api_timeout);
	if ($api_ssl == 1) {
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
		} /* End if ($api_ssl == 1) { */
	if ($api_object_data != '') {
		curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $api_object_data);
		} /* End if ($api_object_data != '') { */
	
	/* Execute the Curl Request */
	$curl_output = curl_exec($curl_handle);

	/* Get the Curl Info */
	$curl_info = curl_getinfo($curl_handle);

	/* Close the Curl handle */
	curl_close($curl_handle);

	return($curl_output);
	} /* End function curl_xpost($url_data, $api_object_data) { */


function define_object($object_type, $apply_value) {
	global $host;
	global $service;

	$api_object_data = array();

	$api_object_data['host_name'] = $host;
	$api_object_data['retry_interval'] = '1';
	$api_object_data['check_period'] = 'xi_timeperiod_24x7';
	$api_object_data['notification_period'] = 'xi_timeperiod_24x7';
	$api_object_data['notification_interval'] = '60';
	$api_object_data['stalking_options'] = 'n';
	$api_object_data['force'] = 1;
	
	if ($object_type == 'host') {
		$api_object_data['address'] = $host;
		$api_object_data['use'] = 'xiwizard_passive_host';
		$api_object_data['max_check_attempts'] = '5';
		$api_object_data['check_interval'] = '5';
		$api_object_data['icon_image'] = 'passiveobject.png';
		$api_object_data['statusmap_image'] = 'passiveobject.png';
		}
	else {
		$api_object_data['service_description'] = $service;
		$api_object_data['use'] = 'xiwizard_passive_service';
		$api_object_data['max_check_attempts'] = '1';
		$api_object_data['check_interval'] = '1';
		}
	
	if ($apply_value == 'true') {
		$api_object_data['applyconfig'] = 1;
		}
	else {
		$api_object_data['applyconfig'] = 0;
		}

	return $api_object_data;
	}


function doit() {
	/* This gets the arguments supplied to the script */
	global $argv;
	global $type;
	global $apply;
	get_arguments(parse_argv($argv));

	/* Verify the API Responds OK */
	curl_verify();

	if ($type == 'host') {
		/* Determine if host object already exists */
		if (check_xi_object_exists($type) == 0) {
			/* Create a host object */
			create_object($type, $apply);
			}
		else {
			object_exists($type);
			}
		}
	else {
		/* Determine if a host object needs to be created first */
		if (check_xi_object_exists('host') > 0) {
			/* Host already exists, create service */
			/* Determine if service object already exists */
			if (check_xi_object_exists($type) == 0) {
				create_object($type, $apply);
				}
			else {
				object_exists($type);
				}
			}
		else {
			/* Host does not exist, create it first without an apply */
			create_object('host', 'false');

			/* Determine if service object already exists */
			if (check_xi_object_exists($type) == 0) {
				/* Now create the service */
				create_object($type, $apply);
				}
			else {
				object_exists($type);
				}
			}
		}
	} /* End function doit() { */


function get_arguments($args) {
	global $argv;

	/* Define variables */
	global $url;
	global $apikey;
	global $host;
	global $service;
	global $type;
	global $apply;
	global $help;
	global $license;

	/* Test varible for the arguments */
	$arg_test = false;
	
	 /* Get all values */
    $url = grab_array_var($args, "url");
    $apikey = grab_array_var($args, "apikey");
    $host = grab_array_var($args, "host");
    $service = grab_array_var($args, "service");
    $type = grab_array_var($args, "type");
    $apply = grab_array_var($args, "apply", 'true');
	$help = grab_array_var($args, "help");
	$license = grab_array_var($args, "license");
    
	/* Make sure they are valid */
	if ($url == "" || $apikey == "" || $host == "" || $type == "") {
		$arg_test = true;
		}
	elseif ($type == "service" and $service == "") {
		$arg_test = true;
		}

	if ($license) {
		print_license();
		exit(0);
		}
	
	if ($help) {
		print_help();
		exit(0);
		}
	
	if ($arg_test) {
		print_help();
		echo "\nOoops something went wrong!\n";
		exit(1);
		}
	} /* End function get_arguments($args) { */


function grab_array_var($arr, $varname, $default="") {
	/*
	The grab_array_var function was copied from send_nrdp.php
	https://github.com/NagiosEnterprises/nrdp/blob/master/clients/send_nrdp.php
	Copyright (c) 2010-2017 - Nagios Enterprises, LLC.
	The license is located here:
	https://github.com/NagiosEnterprises/nrdp/blob/master/LICENSE.rst
	*/
	
    $v = $default;
    if (is_array($arr)) {
        if (array_key_exists($varname, $arr)) {
            $v = $arr[$varname];
			}
		}
    return $v;
	}


function object_exists($object_type) {
	global $host;
	global $service;

	if ($object_type == 'host') {
		print_r("The XI Host '$host' already exists, not proceeding.\n");
		}
	else {
		print_r("The XI Service '$service' for Host '$host' already exists, not proceeding.\n");
		}
	}

	
function parse_argv($argv) {
	/*
	The parse_argv function was copied from send_nrdp.php
	https://github.com/NagiosEnterprises/nrdp/blob/master/clients/send_nrdp.php
	Copyright (c) 2010-2017 - Nagios Enterprises, LLC.
	The license is located here:
	https://github.com/NagiosEnterprises/nrdp/blob/master/LICENSE.rst
	*/
	
    array_shift($argv);
    $out = array();

    foreach ($argv as $arg) {
        if (substr($arg, 0, 2) == '--') {
            $eq = strpos($arg, '=');
            if ($eq === false) {
                $key = substr($arg, 2);
                $out[$key] = isset($out[$key]) ? $out[$key] : true;
				}
			else {
                $key = substr($arg, 2, $eq-2);
                $out[$key] = substr($arg, $eq+1);
				}
			}
        else if (substr($arg, 0, 1) == '-') {
            if (substr($arg, 2, 1) == '=') {
                $key = substr($arg, 1, 1);
                $out[$key] = substr($arg, 3);
				}
           else {
                $chars = str_split(substr($arg, 1));
                foreach ($chars as $char) {
                    $key = $char;
                    $out[$key] = isset($out[$key]) ? $out[$key] : true;
					}
				}
			}
        else {
            $out[] = $arg;
			}
		}
    return $out;
	}


function print_help() {
	global $argv;
	
	echo $argv[0]." - Nagios XI API script to create passive host and service objects.\n";
	echo "Copyright (c) 2017 Troy Lea aka Box293 \n";
	echo "Portions Copyright (c) others - see source code\n";
	echo "License: GPL v3\n";
	echo "\n";
	echo "Usage: ".$argv[0]." --url=<url> --apikey=<apikey> --type=<type> --host=<host> [--service=<service>] [--apply=<apply>]\n";
	echo "\n";
	echo "   <url>     = The URL used to access the Nagios XI server, for example 'http://10.25.5.2/nagiosxi/'.\n";
	echo "   <apikey>  = The API Key used to access the Nagios XI REST API.\n";
	echo "   <type>    = Create a 'host' or 'service' object.\n";
	echo "   <host>    = The name of the host object being created OR the host object to associate the service object with.\n";
	echo "   <service> = The name of the service object being created.\n";
	echo "   <apply>   = By default this script will initiate an apply configuration on Nagios XI, set this to 'false' if you do not want this behaviour.\n";
	echo "\n";
	echo "   --help    = Print the help messsage.\n";
	echo "   --license = Print the GPL v3 license.\n";
	echo "\n";
	echo "The purpose of this script is to create passive host and service objects in Nagios XI.\n";
	echo "Specifically these objects reflect the defaults that are used when running the wizard on the Unconfigured Objects page in Nagios XI.\n";
	echo "The script is intended to be used when observing the nagios.log, it records when host or service objects do not exist when.\n";
	echo "check results are received for them. This script would be used as an EXEC command in an OUTPUT in Nagios Log Server.\n";
	}


function print_license() {
	global $argv;
	$LicenseMessage = "GNU GENERAL PUBLIC LICENSE\n";
	$LicenseMessage .= "Version 3, 29 June 2007";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "Copyright (C) 2007 Free Software Foundation, Inc. <http://fsf.org/>";
	$LicenseMessage .= "Everyone is permitted to copy and distribute verbatim copies of this license document, but changing it is not allowed.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "Preamble";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "The GNU General Public License is a free, copyleft license for software and other kinds of works.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "The licenses for most software and other practical works are designed to take away your freedom to share and change the works.  By contrast, the GNU General Public License is intended to guarantee your freedom to share and change all versions of a program--to make sure it remains free software for all its users.  We, the Free Software Foundation, use the GNU General Public License for most of our software; it applies also to any other work released this way by its authors.  You can apply it to your programs, too.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "When we speak of free software, we are referring to freedom, not price.  Our General Public Licenses are designed to make sure that you have the freedom to distribute copies of free software (and charge for them if you wish), that you receive source code or can get it if you want it, that you can change the software or use pieces of it in new free programs, and that you know you can do these things.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "To protect your rights, we need to prevent others from denying you these rights or asking you to surrender the rights.  Therefore, you have certain responsibilities if you distribute copies of the software, or if you modify it: responsibilities to respect the freedom of others.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "For example, if you distribute copies of such a program, whether gratis or for a fee, you must pass on to the recipients the same freedoms that you received.  You must make sure that they, too, receive or can get the source code.  And you must show them these terms so they know their rights.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "Developers that use the GNU GPL protect your rights with two steps: (1) assert copyright on the software, and (2) offer you this License giving you legal permission to copy, distribute and/or modify it.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "For the developers' and authors' protection, the GPL clearly explains that there is no warranty for this free software.  For both users' and authors' sake, the GPL requires that modified versions be marked as changed, so that their problems will not be attributed erroneously to authors of previous versions.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "Some devices are designed to deny users access to install or run modified versions of the software inside them, although the manufacturer can do so.  This is fundamentally incompatible with the aim of protecting users' freedom to change the software.  The systematic pattern of such abuse occurs in the area of products for individuals to use, which is precisely where it is most unacceptable.  Therefore, we have designed this version of the GPL to prohibit the practice for those products.  If such problems arise substantially in other domains, we stand ready to extend this provision to those domains in future versions of the GPL, as needed to protect the freedom of users.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "Finally, every program is threatened constantly by software patents. States should not allow patents to restrict development and use of software on general-purpose computers, but in those that do, we wish to avoid the special danger that patents applied to a free program could make it effectively proprietary.  To prevent this, the GPL assures that patents cannot be used to render the program non-free. ";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "The precise terms and conditions for copying, distribution and modification follow.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "TERMS AND CONDITIONS";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "0. Definitions.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "\"This License\" refers to version 3 of the GNU General Public License.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "\"Copyright\" also means copyright-like laws that apply to other kinds of works, such as semiconductor masks.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "\"The Program\" refers to any copyrightable work licensed under this License.  Each licensee is addressed as \"you\".  \"Licensees\" and \"recipients\" may be individuals or organizations.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "To \"modify\" a work means to copy from or adapt all or part of the work in a fashion requiring copyright permission, other than the making of an exact copy.  The resulting work is called a \"modified version\" of the earlier work or a work \"based on\" the earlier work.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "A \"covered work\" means either the unmodified Program or a work based on the Program.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "To \"propagate\" a work means to do anything with it that, without permission, would make you directly or secondarily liable for infringement under applicable copyright law, except executing it on a computer or modifying a private copy.  Propagation includes copying, distribution (with or without modification), making available to the public, and in some countries other activities as well.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "To \"convey\" a work means any kind of propagation that enables other parties to make or receive copies.  Mere interaction with a user through a computer network, with no transfer of a copy, is not conveying.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "An interactive user interface displays \"Appropriate Legal Notices\" to the extent that it includes a convenient and prominently visible feature that (1) displays an appropriate copyright notice, and (2) tells the user that there is no warranty for the work (except to the extent that warranties are provided), that licensees may convey the work under this License, and how to view a copy of this License.  If the interface presents a list of user commands or options, such as a menu, a prominent item in the list meets this criterion.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "1. Source Code.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "The \"source code\" for a work means the preferred form of the work for making modifications to it.  \"Object code\" means any non-source form of a work.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "A \"Standard Interface\" means an interface that either is an official standard defined by a recognized standards body, or, in the case of interfaces specified for a particular programming language, one that is widely used among developers working in that language.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "The \"System Libraries\" of an executable work include anything, other than the work as a whole, that (a) is included in the normal form of packaging a Major Component, but which is not part of that Major Component, and (b) serves only to enable use of the work with that Major Component, or to implement a Standard Interface for which an implementation is available to the public in source code form.  A \"Major Component\", in this context, means a major essential component (kernel, window system, and so on) of the specific operating system (if any) on which the executable work runs, or a compiler used to produce the work, or an object code interpreter used to run it.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "The \"Corresponding Source\" for a work in object code form means all the source code needed to generate, install, and (for an executable work) run the object code and to modify the work, including scripts to control those activities.  However, it does not include the work's System Libraries, or general-purpose tools or generally available free programs which are used unmodified in performing those activities but which are not part of the work.  For example, Corresponding Source includes interface definition files associated with source files for the work, and the source code for shared libraries and dynamically linked subprograms that the work is specifically designed to require, such as by intimate data communication or control flow between those subprograms and other parts of the work. ";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "The Corresponding Source need not include anything that users can regenerate automatically from other parts of the Corresponding Source.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "The Corresponding Source for a work in source code form is that same work.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "2. Basic Permissions.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "All rights granted under this License are granted for the term of copyright on the Program, and are irrevocable provided the stated conditions are met.  This License explicitly affirms your unlimited permission to run the unmodified Program.  The output from running a covered work is covered by this License only if the output, given its content, constitutes a covered work.  This License acknowledges your rights of fair use or other equivalent, as provided by copyright law.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "You may make, run and propagate covered works that you do not convey, without conditions so long as your license otherwise remains in force.  You may convey covered works to others for the sole purpose of having them make modifications exclusively for you, or provide you with facilities for running those works, provided that you comply with the terms of this License in conveying all material for which you donot control copyright.  Those thus making or running the covered works for you must do so exclusively on your behalf, under your direction and control, on terms that prohibit them from making any copies of your copyrighted material outside their relationship with you.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "Conveying under any other circumstances is permitted solely under the conditions stated below.  Sublicensing is not allowed; section 10 makes it unnecessary.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "3. Protecting Users' Legal Rights From Anti-Circumvention Law.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "No covered work shall be deemed part of an effective technological measure under any applicable law fulfilling obligations under article 11 of the WIPO copyright treaty adopted on 20 December 1996, or similar laws prohibiting or restricting circumvention of such measures.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "When you convey a covered work, you waive any legal power to forbid circumvention of technological measures to the extent such circumvention is effected by exercising rights under this License with respect to the covered work, and you disclaim any intention to limit operation or modification of the work as a means of enforcing, against the work's users, your or third parties' legal rights to forbid circumvention of technological measures.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "4. Conveying Verbatim Copies.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "You may convey verbatim copies of the Program's source code as you receive it, in any medium, provided that you conspicuously and appropriately publish on each copy an appropriate copyright notice; keep intact all notices stating that this License and any non-permissive terms added in accord with section 7 apply to the code; keep intact all notices of the absence of any warranty; and give all recipients a copy of this License along with the Program.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "You may charge any price or no price for each copy that you convey, and you may offer support or warranty protection for a fee.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "5. Conveying Modified Source Versions.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "You may convey a work based on the Program, or the modifications to produce it from the Program, in the form of source code under the terms of section 4, provided that you also meet all of these conditions:";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "a) The work must carry prominent notices stating that you modified it, and giving a relevant date.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "b) The work must carry prominent notices stating that it is released under this License and any conditions added under section 7.  This requirement modifies the requirement in section 4 to \"keep intact all notices\".";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "c) You must license the entire work, as a whole, under this License to anyone who comes into possession of a copy.  This License will therefore apply, along with any applicable section 7 additional terms, to the whole of the work, and all its parts, regardless of how they are packaged.  This License gives no permission to license the work in any other way, but it does not invalidate such permission if you have separately received it.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "d) If the work has interactive user interfaces, each must display Appropriate Legal Notices; however, if the Program has interactive interfaces that do not display Appropriate Legal Notices, your work need not make them do so.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "A compilation of a covered work with other separate and independent works, which are not by their nature extensions of the covered work, and which are not combined with it such as to form a larger program, in or on a volume of a storage or distribution medium, is called an \"aggregate\" if the compilation and its resulting copyright are not used to limit the access or legal rights of the compilation's users beyond what the individual works permit.  Inclusion of a covered work in an aggregate does not cause this License to apply to the other parts of the aggregate.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "6. Conveying Non-Source Forms.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "You may convey a covered work in object code form under the terms of sections 4 and 5, provided that you also convey the machine-readable Corresponding Source under the terms of this License, in one of these ways:";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "a) Convey the object code in, or embodied in, a physical product (including a physical distribution medium), accompanied by the Corresponding Source fixed on a durable physical medium customarily used for software interchange.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "b) Convey the object code in, or embodied in, a physical product (including a physical distribution medium), accompanied by a written offer, valid for at least three years and valid for as long as you offer spare parts or customer support for that product model, to give anyone who possesses the object code either (1) a copy of the Corresponding Source for all the software in the product that is covered by this License, on a durable physical medium customarily used for software interchange, for a price no more than your reasonable cost of physically performing this conveying of source, or (2) access to copy the Corresponding Source from a network server at no charge.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "c) Convey individual copies of the object code with a copy of the written offer to provide the Corresponding Source.  This alternative is allowed only occasionally and noncommercially, and only if you received the object code with such an offer, in accord with subsection 6b.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "d) Convey the object code by offering access from a designated place (gratis or for a charge), and offer equivalent access to the Corresponding Source in the same way through the same place at no further charge.  You need not require recipients to copy the Corresponding Source along with the object code.  If the place to copy the object code is a network server, the Corresponding Source may be on a different server (operated by you or a third party) that supports equivalent copying facilities, provided you maintain clear directions next to the object code saying where to find the Corresponding Source.  Regardless of what server hosts the Corresponding Source, you remain obligated to ensure that it is available for as long as needed to satisfy these requirements.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "e) Convey the object code using peer-to-peer transmission, provided you inform other peers where the object code and Corresponding Source of the work are being offered to the general public at no charge under subsection 6d.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "A separable portion of the object code, whose source code is excluded from the Corresponding Source as a System Library, need not be included in conveying the object code work.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "A \"User Product\" is either (1) a \"consumer product\", which means any tangible personal property which is normally used for personal, family, or household purposes, or (2) anything designed or sold for incorporation into a dwelling.  In determining whether a product is a consumer product, doubtful cases shall be resolved in favor of coverage.  For a particular product received by a particular user, \"normally used\" refers to a typical or common use of that class of product, regardless of the status of the particular user or of the way in which the particular user actually uses, or expects or is expected to use, the product.  A product is a consumer product regardless of whether the product has substantial commercial, industrial or non-consumer uses, unless such uses represent the only significant mode of use of the product.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "\"Installation Information\" for a User Product means any methods, procedures, authorization keys, or other information required to install and execute modified versions of a covered work in that User Product from a modified version of its Corresponding Source.  The information must suffice to ensure that the continued functioning of the modified object code is in no case prevented or interfered with solely because modification has been made.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "If you convey an object code work under this section in, or with, or specifically for use in, a User Product, and the conveying occurs as part of a transaction in which the right of possession and use of the User Product is transferred to the recipient in perpetuity or for a fixed term (regardless of how the transaction is characterized), the Corresponding Source conveyed under this section must be accompanied by the Installation Information.  But this requirement does not apply if neither you nor any third party retains the ability to install modified object code on the User Product (for example, the work has been installed in ROM).";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "The requirement to provide Installation Information does not include a requirement to continue to provide support service, warranty, or updates for a work that has been modified or installed by the recipient, or for the User Product in which it has been modified or installed.  Access to a network may be denied when the modification itself materially and adversely affects the operation of the network or violates the rules and protocols for communication across the network.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "Corresponding Source conveyed, and Installation Information provided, in accord with this section must be in a format that is publicly documented (and with an implementation available to the public in source code form), and must require no special password or key for unpacking, reading or copying.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "7. Additional Terms.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "\"Additional permissions\" are terms that supplement the terms of this License by making exceptions from one or more of its conditions. Additional permissions that are applicable to the entire Program shall be treated as though they were included in this License, to the extent that they are valid under applicable law.  If additional permissions apply only to part of the Program, that part may be used separately under those permissions, but the entire Program remains governed by this License without regard to the additional permissions.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "When you convey a copy of a covered work, you may at your option remove any additional permissions from that copy, or from any part of it.  (Additional permissions may be written to require their own removal in certain cases when you modify the work.)  You may place additional permissions on material, added by you to a covered work, for which you have or can give appropriate copyright permission.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "Notwithstanding any other provision of this License, for material you add to a covered work, you may (if authorized by the copyright holders of that material) supplement the terms of this License with terms:";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "a) Disclaiming warranty or limiting liability differently from the terms of sections 15 and 16 of this License; or";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "b) Requiring preservation of specified reasonable legal notices or author attributions in that material or in the Appropriate Legal Notices displayed by works containing it; or";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "c) Prohibiting misrepresentation of the origin of that material, or requiring that modified versions of such material be marked in reasonable ways as different from the original version; or";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "d) Limiting the use for publicity purposes of names of licensors or authors of the material; or";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "e) Declining to grant rights under trademark law for use of some trade names, trademarks, or service marks; or";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "f) Requiring indemnification of licensors and authors of that material by anyone who conveys the material (or modified versions of it) with contractual assumptions of liability to the recipient, for any liability that these contractual assumptions directly impose on those licensors and authors.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "All other non-permissive additional terms are considered \"further restrictions\" within the meaning of section 10.  If the Program as you received it, or any part of it, contains a notice stating that it is governed by this License along with a term that is a further restriction, you may remove that term.  If a license document contains a further restriction but permits relicensing or conveying under this License, you may add to a covered work material governed by the terms of that license document, provided that the further restriction does not survive such relicensing or conveying.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "If you add terms to a covered work in accord with this section, you must place, in the relevant source files, a statement of the additional terms that apply to those files, or a notice indicating where to find the applicable terms.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "Additional terms, permissive or non-permissive, may be stated in the form of a separately written license, or stated as exceptions; the above requirements apply either way.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "8. Termination.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "You may not propagate or modify a covered work except as expressly provided under this License.  Any attempt otherwise to propagate or modify it is void, and will automatically terminate your rights under this License (including any patent licenses granted under the third paragraph of section 11).";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "However, if you cease all violation of this License, then your license from a particular copyright holder is reinstated (a) provisionally, unless and until the copyright holder explicitly and finally terminates your license, and (b) permanently, if the copyright holder fails to notify you of the violation by some reasonable means prior to 60 days after the cessation.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "Moreover, your license from a particular copyright holder is reinstated permanently if the copyright holder notifies you of the violation by some reasonable means, this is the first time you have received notice of violation of this License (for any work) from that copyright holder, and you cure the violation prior to 30 days after your receipt of the notice.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "Termination of your rights under this section does not terminate the licenses of parties who have received copies or rights from you under this License.  If your rights have been terminated and not permanently reinstated, you do not qualify to receive new licenses for the same material under section 10.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "9. Acceptance Not Required for Having Copies.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "You are not required to accept this License in order to receive or run a copy of the Program.  Ancillary propagation of a covered work occurring solely as a consequence of using peer-to-peer transmission to receive a copy likewise does not require acceptance.  However, nothing other than this License grants you permission to propagate or modify any covered work.  These actions infringe copyright if you do not accept this License.  Therefore, by modifying or propagating a covered work, you indicate your acceptance of this License to do so.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "10. Automatic Licensing of Downstream Recipients.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "Each time you convey a covered work, the recipient automatically receives a license from the original licensors, to run, modify and propagate that work, subject to this License.  You are not responsible for enforcing compliance by third parties with this License.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "An \"entity transaction\" is a transaction transferring control of an organization, or substantially all assets of one, or subdividing an organization, or merging organizations.  If propagation of a covered work results from an entity transaction, each party to that transaction who receives a copy of the work also receives whatever licenses to the work the party's predecessor in interest had or could give under the previous paragraph, plus a right to possession of the Corresponding Source of the work from the predecessor in interest, if the predecessor has it or can get it with reasonable efforts.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "You may not impose any further restrictions on the exercise of the rights granted or affirmed under this License.  For example, you may not impose a license fee, royalty, or other charge for exercise of rights granted under this License, and you may not initiate litigation (including a cross-claim or counterclaim in a lawsuit) alleging that any patent claim is infringed by making, using, selling, offering for sale, or importing the Program or any portion of it.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "11. Patents.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "A \"contributor\" is a copyright holder who authorizes use under this License of the Program or a work on which the Program is based.  The work thus licensed is called the contributor's \"contributor version\".";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "A contributor's \"essential patent claims\" are all patent claims owned or controlled by the contributor, whether already acquired or hereafter acquired, that would be infringed by some manner, permitted by this License, of making, using, or selling its contributor version, but do not include claims that would be infringed only as a consequence of further modification of the contributor version.  For purposes of this definition, \"control\" includes the right to grant patent sublicenses in a manner consistent with the requirements of this License.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "Each contributor grants you a non-exclusive, worldwide, royalty-free patent license under the contributor's essential patent claims, to make, use, sell, offer for sale, import and otherwise run, modify and propagate the contents of its contributor version.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "In the following three paragraphs, a \"patent license\" is any express agreement or commitment, however denominated, not to enforce a patent (such as an express permission to practice a patent or covenant not to sue for patent infringement).  To \"grant\" such a patent license to a party means to make such an agreement or commitment not to enforce a patent against the party.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "If you convey a covered work, knowingly relying on a patent license, and the Corresponding Source of the work is not available for anyone to copy, free of charge and under the terms of this License, through a publicly available network server or other readily accessible means, then you must either (1) cause the Corresponding Source to be so available, or (2) arrange to deprive yourself of the benefit of the patent license for this particular work, or (3) arrange, in a manner consistent with the requirements of this License, to extend the patent license to downstream recipients.  \"Knowingly relying\" means you have actual knowledge that, but for the patent license, your conveying the covered work in a country, or your recipient's use of the covered work in a country, would infringe one or more identifiable patents in that country that you have reason to believe are valid.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "If, pursuant to or in connection with a single transaction or arrangement, you convey, or propagate by procuring conveyance of, a covered work, and grant a patent license to some of the parties receiving the covered work authorizing them to use, propagate, modify or convey a specific copy of the covered work, then the patent license you grant is automatically extended to all recipients of the covered work and works based on it.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "A patent license is \"discriminatory\" if it does not include within the scope of its coverage, prohibits the exercise of, or is conditioned on the non-exercise of one or more of the rights that are specifically granted under this License.  You may not convey a covered work if you are a party to an arrangement with a third party that is in the business of distributing software, under which you make payment to the third party based on the extent of your activity of conveying the work, and under which the third party grants, to any of the parties who would receive the covered work from you, a discriminatory patent license (a) in connection with copies of the covered work conveyed by you (or copies made from those copies), or (b) primarily for and in connection with specific products or compilations that contain the covered work, unless you entered into that arrangement, or that patent license was granted, prior to 28 March 2007.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "Nothing in this License shall be construed as excluding or limiting any implied license or other defenses to infringement that may otherwise be available to you under applicable patent law.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "12. No Surrender of Others' Freedom.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "If conditions are imposed on you (whether by court order, agreement or otherwise) that contradict the conditions of this License, they do not excuse you from the conditions of this License.  If you cannot convey a covered work so as to satisfy simultaneously your obligations under this License and any other pertinent obligations, then as a consequence you may not convey it at all.  For example, if you agree to terms that obligate you to collect a royalty for further conveying from those to whom you convey the Program, the only way you could satisfy both those terms and this License would be to refrain entirely from conveying the Program.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "13. Use with the GNU Affero General Public License.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "Notwithstanding any other provision of this License, you have permission to link or combine any covered work with a work licensed under version 3 of the GNU Affero General Public License into a single combined work, and to convey the resulting work.  The terms of this License will continue to apply to the part which is the covered work, but the special requirements of the GNU Affero General Public License, section 13, concerning interaction through a network will apply to the combination as such.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "14. Revised Versions of this License.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "The Free Software Foundation may publish revised and/or new versions of the GNU General Public License from time to time.  Such new versions will be similar in spirit to the present version, but may differ in detail to address new problems or concerns.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "Each version is given a distinguishing version number.  If the Program specifies that a certain numbered version of the GNU General Public License \"or any later version\" applies to it, you have the option of following the terms and conditions either of that numbered version or of any later version published by the Free Software Foundation.  If the Program does not specify a version number of the GNU General Public License, you may choose any version ever published by the Free Software Foundation.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "If the Program specifies that a proxy can decide which future versions of the GNU General Public License can be used, that proxy's public statement of acceptance of a version permanently authorizes you to choose that version for the Program.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "Later license versions may give you additional or different permissions.  However, no additional obligations are imposed on any author or copyright holder as a result of your choosing to follow a later version.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "15. Disclaimer of Warranty.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "THERE IS NO WARRANTY FOR THE PROGRAM, TO THE EXTENT PERMITTED BY APPLICABLE LAW.  EXCEPT WHEN OTHERWISE STATED IN WRITING THE COPYRIGHT HOLDERS AND/OR OTHER PARTIES PROVIDE THE PROGRAM \"AS IS\" WITHOUT WARRANTY OF ANY KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE.  THE ENTIRE RISK AS TO THE QUALITY AND PERFORMANCE OF THE PROGRAM IS WITH YOU.  SHOULD THE PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF ALL NECESSARY SERVICING, REPAIR OR CORRECTION.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "16. Limitation of Liability.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "IN NO EVENT UNLESS REQUIRED BY APPLICABLE LAW OR AGREED TO IN WRITING WILL ANY COPYRIGHT HOLDER, OR ANY OTHER PARTY WHO MODIFIES AND/OR CONVEYS THE PROGRAM AS PERMITTED ABOVE, BE LIABLE TO YOU FOR DAMAGES, INCLUDING ANY GENERAL, SPECIAL, INCIDENTAL OR CONSEQUENTIAL DAMAGES ARISING OUT OF THE USE OR INABILITY TO USE THE PROGRAM (INCLUDING BUT NOT LIMITED TO LOSS OF DATA OR DATA BEING RENDERED INACCURATE OR LOSSES SUSTAINED BY YOU OR THIRD PARTIES OR A FAILURE OF THE PROGRAM TO OPERATE WITH ANY OTHER PROGRAMS), EVEN IF SUCH HOLDER OR OTHER PARTY HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "17. Interpretation of Sections 15 and 16.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "If the disclaimer of warranty and limitation of liability provided above cannot be given local legal effect according to their terms, reviewing courts shall apply local law that most closely approximates an absolute waiver of all civil liability in connection with the Program, unless a warranty or assumption of liability accompanies a copy of the Program in return for a fee.";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "END OF TERMS AND CONDITIONS";
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "\n"; 
	$LicenseMessage .= "To see the license type \"$argv[0] --license | more\"\n";
	$LicenseMessage .= "To see the help type \"$argv[0] --help | more\"\n";
	$LicenseMessage .= "\n";
	
	/* Echo the LicenseMessage */ 
	echo $LicenseMessage;
	} /* End of function print_license() { */

?>
