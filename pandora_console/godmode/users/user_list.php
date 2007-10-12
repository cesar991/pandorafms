<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2007
// Raul Mateos <raulofpandora@gmail.com>, 2005-2007

// Load globar vars
require("include/config.php");

if (comprueba_login() == 0) 
	if (give_acl($id_user, 0, "UM")==1) {
		if (isset($_GET["borrar_usuario"])){ // if delete user
			$nombre= entrada_limpia($_GET["borrar_usuario"]);
			// Delete user
			// Delete cols from table tgrupo_usuario
			
			$query_del1="DELETE FROM tgrupo_usuario WHERE usuario = '".$nombre."'";
			$query_del2="DELETE FROM tusuario WHERE id_usuario = '".$nombre."'";
			$resq1=mysql_query($query_del1);
			$resq1=mysql_query($query_del2);
			if (! $resq1)
				echo "<h3 class='error'>".$lang_label["delete_user_no"]."</h3>";
			else
				echo "<h3 class='suc'>".$lang_label["delete_user_ok"]."</h3>";
		}
?>

<h2><?php echo $lang_label["user_management"] ?> &gt; 
<?php echo $lang_label["users"] ?></h2>
 
<table width="700" cellpadding="4" cellspacing="4" class="databox">
<th width="80px"><?php echo $lang_label["user_ID"]?></th>
<th width="155px"><?php echo $lang_label["last_contact"]?></th>
<th width="45px"><?php echo $lang_label["profile"]?></th>
<th width="120px"><?php echo $lang_label["name"]?></th>
<th><?php echo $lang_label["description"]?></th>
<th width="30px"><?php echo $lang_label["delete"]?></th>

<?php
$query1="SELECT * FROM tusuario";
$resq1=mysql_query($query1);
// Init vars
$nombre = "";
$nivel = "";
$comentarios = "";
$fecha_registro = "";
$color=1;

while ($rowdup=mysql_fetch_array($resq1)){
	$name = $rowdup["id_usuario"];
	$nivel = $rowdup["nivel"];
	$real_name = $rowdup["nombre_real"];
	$comments = $rowdup["comentarios"];
	$fecha_registro = $rowdup["fecha_registro"];
	if ($color == 1){
		$tdcolor = "datos";
		$tip= "tip";
		$color = 0;
	}
	else {
		$tdcolor = "datos2";
		$tip= "tip2";
		$color = 1;
	}
	echo "<tr><td class='$tdcolor'>";
	echo "<a href='index.php?sec=gusuarios&sec2=godmode/users/configure_user&id_usuario_mio=".$name."'><b>".$name."</b></a>";
	echo "<td class='$tdcolor'>".$fecha_registro;
	echo "<td class='$tdcolor'>";
	if ($nivel == 1) 
		echo "<img src='images/user_suit.png'>";
	else
		echo "<img src='images/user_green.png'>";
	
	$sql1='SELECT * FROM tusuario_perfil WHERE id_usuario = "'.$name.'"';
	$result=mysql_query($sql1);
	echo "<a href='#' class='$tip'>&nbsp;<span>";
	if (mysql_num_rows($result)){
		while ($row=mysql_fetch_array($result)){
			echo dame_perfil($row["id_perfil"])."/ ";
			echo dame_grupo($row["id_grupo"])."<br>";
		}
	}
	else { echo $lang_label["no_profile"]; }
	echo "</span></a>";
	
	echo "<td class='$tdcolor' width='100'>".substr($real_name,0,16)."</td>";
	echo "<td class='$tdcolor'>".$comments."</td>";
	echo "<td class='$tdcolor' align='center'><a href='index.php?sec=gagente&sec2=godmode/users/user_list&borrar_usuario=".$name."' onClick='if (!confirm(\' ".$lang_label["are_you_sure"]."\')) return false;'><img border='0' src='images/cross.png'></a></td>";
}
	echo "</tr></table>";
	echo "<table width=700>";
	echo "<tr><td align='right'>";
	echo "<form method=post action='index.php?sec=gusuarios&sec2=godmode/users/configure_user&alta=1'>";
	echo "<input type='submit' class='sub next' name='crt' value='".$lang_label["create_user"]."'>";
	echo "</form></td></tr></table>";

echo "</table>";

} // end verify security of page
 else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access User Management");
		require ("general/noaccess.php");
}        
?>