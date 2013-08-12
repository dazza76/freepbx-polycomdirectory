<?php
/**
 * This script writes out a directory for Polycom Phones
 * 
 * @copyright 2007 OfficeLink+
 */
define("DIRECTORY_DIR",'/tftpboot/contacts/');
define("DEFAULT_DIRECTORY", '/tftpboot/contacts/000000000000-directory.xml');
require_once( $amp_conf['AMPWEBROOT'].'/admin/modules/polycomdirectory/retrieve_polcom_directory_from_mysql.php');
?>


<script type="text/javascript">
function clearLine(number) {
   document.getElementById('ln' + number).value = '';
   document.getElementById('fn' + number).value = '';
   document.getElementById('ct' + number).value = '';
   document.getElementById('sd' + number).value = '';
}
</script>
<table border="0" cellpadding="3" cellspacing='0' width='80%' align="center">
<?
//form processing:

if (isset($_POST['adddirectory']))
{
   $macaddress = $_POST['macaddress'];
   if (strlen($macaddress) == 12)
   {
      $exec = "touch /var/ftp/$macaddress-directory.xml";
      system($exec,$returnVal);
      echo "<tR><td colspan=5><b><i>Executed: $exec [ret: $returnVal]</i></d></td></tr>";
   }
}
if (isset($_POST['deldirectory']))
{
   $macaddress = $_POST['macaddress'];
   $exec = "rm /var/ftp/$macaddress-directory.xml";
   system($exec,$returnVal);
   echo "<tR><td colspan=5><b><i>Executed: $exec [ret: $returnVal]</i></d></td></tr>";
}
if (isset($_POST['UpdateContacts']))
{
   writeXML();
}

if (isset($_POST['Populate']))
{
   if (strcmp ($_GET['mac'], "000000000000") == 0)
   {
      $fileToWrite = DEFAULT_DIRECTORY;
   }
   else
   {
      $fileToWrite = DIRECTORY_DIR . $_GET['mac'];
   }
   generate_phone_directory ($fileToWrite);
}
$num = 0;


if (!isset($_GET['mac'])) {?>
<tr><th  colspan='4'>Choose a Phone Directory</th></tr>
<tr class=\"$sub_heading_id\" align = "left"><th>Mac address</th><th>Extension(s)</th><th>Description</th></tr>
    <?
$highlight_class = $num % 2? "odd":"even";

	$url = $_SERVER['PHP_SELF'] . "?mac=000000000000";
    echo "
      <tr class='$highlight_class'>
      <td>&nbsp;&nbsp;&nbsp;<a href='$url'>000000000000</a>$exists</td>
      <td>Global Directory</td>
      <td>Any phone that doesn't have a personalised directory below will download this directory.</td>";
    $phones_array = getPhonesByOfficeGroup();

    foreach ($phones_array as $officegroup => $phones) {
       echo "<tr class='officegroup'><td colspan='5'><b>Office Group: $officegroup</b></td></tr>";
       foreach ($phones as $num => $phone) {
          $description = $phone['description'];
          if (empty($description)) $description = "N/A";
          $filename = $phone['macaddress'].'-directory.xml';

          if (file_exists(DIRECTORY_DIR . $filename)) {
             $exists = " [Directory Exists]";
          } else {
             $exists = "";
          }

          $url = $_SERVER['PHP_SELF'] . "?display=polycomdirectory&mac=" . $phone['macaddress'];

          $highlight_class = $num % 2? "odd":"even";

          echo "
            <tr >
                <td >&nbsp;&nbsp;&nbsp;<a href='$url'>{$phone['macaddress']}</a>$exists</td>
                <td>{$phone['device']}</td>
                <td>$description</td>
            </tr>";
       }
    }

    ?>
    <tr><td><br><br></td></tr>
</table>
<? } else {

   if (!empty($_POST)) echo "<tr class='updated'><th colspan='5'>Updated Directory</th></tr>";
   $fileToEdit = $_GET['mac'];
   echo "<form method='post'>
                <input type=hidden name=xmlFile value='$fileToEdit'>
                <tr><th class='mainheading' align=center colspan=5>Editing Directory for Phone:$fileToEdit</th></tr>";
//   echo "<tr class='heading'><th colspan=5>Editing: $fileToEdit</th></tr>";
   echo "<tr class='officegroup'><td>Last Name</td><td>First Name</td><td>Number</td><td>Speed Dial</td><td>&nbsp;</td></tr>";

   $filename = DIRECTORY_DIR . $fileToEdit . '-directory.xml';

   if (!file_exists($filename)) $filename = DEFAULT_DIRECTORY; //If a file hasn't been created load the default directory.

   $xml = simplexml_load_file($filename);
   $list = $xml->xpath("/directory/item_list");
   $list = (array)$list[0]; //Cast the object as an array
   $list = $list['item']; //We only want the item array
   if (is_object($list)) $list = array($list);
   usort($list,"sortDirectory"); //Sort the directory by speed dial

   $curNum=0;
   foreach ($list as $line) {
      $highlight_class = $curNum % 2? "odd":"even";
      echo "<tr class='$highlight_class'>
                        <td><input type=text size=50 id=ln$curNum name=ln$curNum value=\"{$line->ln}\"></td>
                        <td><input type=text size=50 id=fn$curNum name=fn$curNum value=\"{$line->fn}\"></td>
                        <td><input type=text id=ct$curNum name=ct$curNum value=\"{$line->ct}\"></td>
                        <td><input type=text id=sd$curNum name=sd$curNum value=\"{$line->sd}\"></td>
                        <td><input type='button' onclick='clearLine($curNum)' value='Clear' /></td>
                    </tr>";
      $curNum++;

   }
   for ($ii = 0; $ii < 10; $ii++)
   {
      $highlight_class = $curNum % 2? "odd":"even";
      echo "<tr class='$highlight_class'>
                        <td><input type=text size=50 name=ln$curNum></td>
                        <td><input type=text size=50 name=fn$curNum></td>
                        <td><input type=text name=ct$curNum></td>
                        <td><input type=text name=sd$curNum></td>
                        <td>&nbsp;</td>
                    </tr>";
      $curNum+=1;
   }
    ?>
    <tr><td><a href=<?php echo $_SERVER['PHP_SELF']."?display=polycomdirectory"; ?>>Return to main</a></td><td colspan=2><input 
type=submit name=Populate value='Populate directory from extension list'></td><td><input type=submit 
name=UpdateContacts value='Update Directory'></td></tr>
</form>
</table>    
<? } ?>
<?php

function writeXML() {
   $empty = true;
   $xmlFile = escapeshellcmd($_POST['xmlFile']).'-directory.xml';

   $outputXML = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<directory>\n\t<item_list>";
   foreach ($_POST as $varname => $varvalue)
   {
      if (isset($varvalue))
      {
         if (strcmp($varvalue,"xmlFile") != 0 && strcmp($varvalue,"UpdateContacts") != 0)
         {

	    // Escape ampersands

	    $varvalue = preg_replace('/&(?![#]?[a-z0-9]+;)/i', "&amp;$1", $varvalue); 
            if (substr($varname,0,2)=="fn")
            {
               $curBitID = substr($varname,2,1);
               $fn = $varvalue;
            }
            if (substr($varname,0,2)=="ln")
            {
               $curBitID = substr($varname,2,1);
               $ln = $varvalue;
            }
            if (substr($varname,0,2)=="ct")
            {
               $curBitID = substr($varname,2,1);
               $ct = $varvalue;
            }
            if (substr($varname,0,2)=="sd" && $fn != "" &&  $ct != "")
            {
               $empty = false;
               $curBitID = substr($varname,2,1);
               $sd = $varvalue;
	       $bw = 1;
               $outputXML .= "\n\t\t<item>\n\t\t\t<ln>$ln</ln>\n\t\t\t<fn>$fn</fn>\n\t\t\t<ct>$ct</ct>\n\t\t\t<sd>$sd</sd>\n\t\t\t<bw>$bw</bw>\n\t\t</item>";
            }
         }
      }
   }
   //   system("rm -rf /var/ftp/$xmlFile");
   $outputXML .= "\n\t</item_list>\n</directory>";

   if (strcmp ($_POST['xmlFile'], "000000000000") == 0)
   {
      $fileToWrite = DEFAULT_DIRECTORY;
   }
   else
   {
      $fileToWrite = DIRECTORY_DIR . $xmlFile;
   }

   $file = fopen ($fileToWrite, "w");
   if ($empty and file_exists($fileToWrite)) {
      unlink($fileToWrite);
   } else {
      fwrite($file, $outputXML);
      fclose($file);
   }
}

function getPhonesByOfficeGroup() {
   $sql = "select mac as macaddress ,ext as device ,''as officgroup,description from endpointman_line_list join  endpointman_mac_list on (mac_id = endpointman_mac_list.id) where mac like '0004%';";

   $phones_array = array();

   $res = mysql_query($sql);

   if ($res) {
      while ($row = mysql_fetch_assoc($res)) {
         $officegroup = $row['officegroup'];
         if ($officegroup === null) {
            $officegroup = 'None';
         }
         $phones_array[$officegroup][] = $row;
      }
   }
   return $phones_array;
}

function sortDirectory($a,$b) {
   $a = (int)$a->sd;
   $b = (int)$b->sd;

   return ($a >= $b);
}

function isExtension($number) {
   $number = mysql_real_escape_string($number);
   $sql = "SELECT id FROM devices WHERE id = '$number'";
   $res = mysql_query($sql);
   $numrows = mysql_num_rows($res);
   mysql_free_result($res);
   if ($numrows > 0) return true;
   return false;
}


