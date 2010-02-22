<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
require_once ("include/config.php");

check_login ();

require_once ('include/functions_gis.php');
require_once ('include/functions_ui.php');

$opt = get_parameter('opt');

switch ($opt) {
	case 'get_data_conexion':
		$returnJSON['correct'] = 1;
		$idConection = get_parameter('id_conection');
		
		$row = get_db_row_filter('tgis_map_connection', array('id_tmap_connection' => $idConection));
		
		$returnJSON['content'] = $row;
		
		echo json_encode($returnJSON);
		break;
	case 'get_new_positions':
		$id_features = get_parameter('id_features', '');
		$last_time_of_data = get_parameter('last_time_of_data');
		$layerId = get_parameter('layer_id');
		
		$returnJSON = array();
		$returnJSON['correct'] = 1;
		
		$flagGroupAll = get_db_all_rows_sql('SELECT tgrupo_id_grupo FROM tgis_map_layer WHERE id_tmap_layer = ' . $layerId . ' AND tgrupo_id_grupo = 1;'); //group 1 = all groups
		
		$defaultCoords = get_db_row_sql('SELECT default_longitude, default_latitude
			FROM tgis_map
			WHERE id_tgis_map IN (SELECT tgis_map_id_tgis_map FROM tgis_map_layer WHERE id_tmap_layer = ' . $layerId . ')');
		
		if ($flagGroupAll === false) {
			$idAgentsWithGISTemp = get_db_all_rows_sql('SELECT id_agente FROM tagente WHERE id_grupo IN
					(SELECT tgrupo_id_grupo FROM tgis_map_layer WHERE id_tmap_layer = ' . $layerId . ')
					OR id_agente IN
					(SELECT tagente_id_agente FROM tgis_map_layer_has_tagente WHERE tgis_map_layer_id_tmap_layer = ' . $layerId . ');');
			
			
			$agentsGISStatus = get_db_all_rows_sql('SELECT tagente_id_agente, stored_longitude, stored_latitude
				FROM tgis_data_status
				WHERE tagente_id_agente IN
					(SELECT id_agente FROM tagente WHERE id_grupo IN
						(SELECT tgrupo_id_grupo FROM tgis_map_layer WHERE id_tmap_layer = ' . $layerId . '))
					OR tagente_id_agente IN
						(SELECT tagente_id_agente FROM tgis_map_layer_has_tagente WHERE tgis_map_layer_id_tmap_layer = ' . $layerId . ');');
		
		}
		else {
			//All groups, all agents
			$idAgentsWithGISTemp = get_db_all_rows_sql('SELECT tagente_id_agente AS id_agente
				FROM tgis_data_status
				WHERE tagente_id_agente');
			
			
			$agentsGISStatus = get_db_all_rows_sql('SELECT tagente_id_agente, stored_longitude, stored_latitude
				FROM tgis_data_status
				WHERE tagente_id_agente');
		}
		
		foreach ($idAgentsWithGISTemp as $idAgent) {
			$idAgentsWithGIS[] = $idAgent['id_agente'];
		}
		
		$agentsGISStatus = get_db_all_rows_sql('SELECT tagente_id_agente, stored_longitude, stored_latitude
				FROM tgis_data_status
				WHERE tagente_id_agente IN (' . implode(',', $idAgentsWithGIS) . ')
				UNION
				SELECT id_agente AS tagente_id_agente,
					' . $defaultCoords['default_longitude'] . ' AS stored_longitude, ' . $defaultCoords['default_latitude'] . ' AS stored_latitude
				FROM tagente
				WHERE id_agente NOT IN (' . implode(',', $idAgentsWithGIS) . ')');
		
		if ($agentsGISStatus === false) {
			$agentsGISStatus = array();
		}
		
		$agents = null;
		foreach ($agentsGISStatus as $row) {
			$agents[$row['tagente_id_agente']] = array(
				'icon_path' => get_agent_icon_map($row['tagente_id_agente'], true),
				'name' => get_agent_name($row['tagente_id_agente']),
				'status' => get_agent_status($idAgent),
				'stored_longitude' => $row['stored_longitude'],
				'stored_latitude' => $row['stored_latitude']
			);
		}
		
		$returnJSON['content'] = json_encode($agents);
		echo json_encode($returnJSON);
		break;
	case 'point_path_info':
		$id = get_parameter('id');
		$row = get_db_row_sql('SELECT * FROM tgis_data WHERE id_tgis_data = ' . $id);
		
		$returnJSON = array();
		$returnJSON['correct'] = 1;
		$returnJSON['content'] = __('Agent') . ': <a style="font-weight: bolder;" href="?sec=estado&sec2=operation/agentes/ver_agente&id_agente=' . $row['tagente_id_agente'] . '">'.get_agent_name($row['tagente_id_agente']).'</a><br />';
		$returnJSON['content'] .= __('Position (Long, Lat, Alt)') . ': (' . $row['longitude'] . ', ' . $row['latitude'] . ', ' . $row['altitude'] . ') <br />';		
		$returnJSON['content'] .= __('Start contact') . ': ' . $row['start_timestamp'] . '<br />';
		$returnJSON['content'] .= __('Last contact') . ': ' . $row['end_timestamp'] . '<br />';
		$returnJSON['content'] .= __('Num reports') . ': '.$row['number_of_packages'].'<br />'; 
		if ($row['manual_placemen']) $returnJSON['content'] .= '<br />' . __('Manual placement') . '<br />'; 
		
		echo json_encode($returnJSON);
		
		break;
	case 'point_agent_info':
		$id = get_parameter('id');
		$row = get_db_row_sql('SELECT * FROM tagente WHERE id_agente = ' . $id);
		
		$returnJSON = array();
		$returnJSON['correct'] = 1;
		$returnJSON['content'] = __('Agent') . ': <a style="font-weight: bolder;" href="?sec=estado&sec2=operation/agentes/ver_agente&id_agente=' . $row['id_agente'] . '">'.$row['nombre'].'</a><br />';
		$returnJSON['content'] .= __('Position (Long, Lat, Alt)') . ': (' . $row['last_longitude'] . ', ' . $row['last_latitude'] . ', ' . $row['last_altitude'] . ') <br />';		
		$agent_ip_address = get_agent_address ($id_agente);
		if ($agent_ip_address || $agent_ip_address != '') {
			$returnJSON['content'] .= __('IP Address').': '.get_agent_address ($id_agente).'<br />';
		}
		$returnJSON['content'] .= __('OS').': '.print_os_icon($row['id_os'], true, true);

		$osversion_offset = strlen($row["os_version"]);
		if ($osversion_offset > 15) {
    		$osversion_offset = $osversion_offset - 15;
		}
		else {
		    $osversion_offset = 0;
		}
		$returnJSON['content'] .= '&nbsp;( <i><span title="'.$row["os_version"].'">'.substr($row["os_version"],$osversion_offset,15).'</span></i>)<br />';
		$agent_description = $row['comentarios'];
		if ($agent_description || $agent_description != '') {
			$returnJSON['content'] .= __('Description').': '.$agent_description.'<br />';
		}
		$returnJSON['content'] .= __('Group').': '.print_group_icon ($row["id_grupo"], true).'&nbsp;(<strong>'.get_group_name ($row["id_grupo"]).'</strong>)<br />';
		$returnJSON['content'] .= __('Agent Version').': '.$row["agent_version"].'<br />';
		$returnJSON['content'] .= __('Last contact')." / ".__('Remote').': '. $row["ultimo_contacto"]. " / ";
		if ($row["ultimo_contacto_remoto"] == "0000-00-00 00:00:00") {
    		$returnJSON['content'] .=__('Never');
		} else {
 			$returnJSON['content'] .= $row["ultimo_contacto_remoto"];
		}


		
		echo json_encode($returnJSON);
		
		break;
}
?>