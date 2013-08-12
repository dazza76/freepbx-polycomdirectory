<?php

/* A script to write out all of the extensions in a format for iSymphony.
 * This just basically writes out the extensions (and their associated device) to a file which the iSymphony server can read.
 */

function generate_phone_directory ($mac_address)
{
	require_once('/var/www/html/admin/functions.inc.php');

	$amp_conf = parse_amportal_conf("/etc/amportal.conf");
	mysql_connect($amp_conf["AMPDBHOST"], $amp_conf["AMPDBUSER"], $amp_conf["AMPDBPASS"]);
	mysql_select_db($amp_conf["AMPDBNAME"]);

	// Open the file to write the iSymphony config to
	$configfile = fopen ($mac_address . "-directory.xml", "w");

	// Write the opening XML to the file
	$xmlfile = "<?xml version=\"1.0\" standalone=\"yes\"?>
<directory>
	<item_list>";

	fwrite ($configfile, $xmlfile);

	// Now write out all the extensions
	fwrite ($configfile, get_extensions_and_devices());

	// Write out the trailing XML
	$xmlfile = "\n		</item_list>
</directory>\n";

	fwrite ($configfile, $xmlfile);

}

// Return an array containing all the extensions and their devices
function get_extensions_and_devices()
{
	$SQL = "SELECT u.name, u.extension
		FROM users u
		ORDER BY u.name ASC";

	$result = mysql_query ($SQL);


	$xml = generate_xml_config ("PKUP", "PKUP", "*8", "1", "0");
	$xml .= generate_xml_config ("VM", "VM", "*97", "2", "0");
	$xml .= generate_xml_config ("Reboot", "Phone", "*93", "3", "0");

	$i = 4;

	while ($extension = mysql_fetch_array($result, MYSQL_ASSOC))
	{
		$ext = $extension['extension'];

		// Now break the name into first name and last name
		$name = $extension['name'];
		$first_name = trim (substr ($name, 0, strpos ($name, " ")));
		$last_name = trim (substr ($name, strpos ($name, " ")));
	
		
		$xml .= generate_xml_config ($first_name, $last_name, $ext, $i, 1);
		$i++;
	}


	return $xml;
}

function generate_xml_config ($first_name, $last_name, $extension, $speed_dial, $buddy_watch)
{
	$xml = "
		<item>
			<ln>$last_name</ln>
			<fn>$first_name</fn>
			<ct>$extension</ct>
			<sd>$speed_dial</sd>
			<bw>$buddy_watch</bw>
		</item>";
	return $xml;
}

?>


