<?php 
namespace PTTU\QueryBadges;
error_reporting(E_ALL);

class QueryBadges extends \ExternalModules\AbstractExternalModule {
    
    function redcap_project_home_page($project_id) {
        $drw_enabled = $this->is_drw_enabled($project_id);
        if (!isset($drw_enabled) || $drw_enabled != 2) {
            echo "<div class='header'>Data Resolution Workflow is not enabled for this project</div>";
            return;
        }
        $username = USERID;
        $dag = $_GET['dag'];
        $user_queries = $this->number_user_queries($project_id, $username);
        if (isset($dag)) {
            $dag_queries = $this->number_dag_queries($project_id, $dag);
        }
        
        echo "<div class='red'>DAG: $dag, User: $username</div>";
        echo "<div class='green'>This module will show the queries for $project_id</div>";
        echo "<div class='blue'>Is DRW enabled? $drw_enabled</div>";
        echo var_dump($drw_enabled, $username, $dag, $user_queries, $dag_queries);
    }

    function number_user_queries($project_id, $username) {
        // Number of open queries for the current user
        $sql = "SELECT count(*) as 'openqueries'
                FROM redcap_data_quality_status dqs
                INNER JOIN redcap_user_information ui
                ON dqs.assigned_user_id = ui.ui_id
                AND dqs.query_status = 'OPEN'
                AND dqs.project_id = ?
                AND ui.username = ?";
        $result = $this->query($sql, [$project_id, $username]);
        $num_queries = 0;

        while ($row = $result->fetch_assoc()) {
            $num_queries = $row['openqueries'];
        }
        return $num_queries;
    }

    function number_dag_queries($project_id, $dag) {
        // Number of queries for the DAG of the current user
        $dag_user_ids = implode(", ", $this->get_user_ids_in_dag($project_id, $dag));
        $sql = "SELECT count(*) as 'openqueries'
                FROM redcap_data_quality_status dqs
                INNER JOIN redcap_user_information ui
                ON dqs.assigned_user_id = ui.ui_id
                AND dqs.query_status = 'OPEN'
                AND dqs.project_id = ?
                AND ui.ui_id IN (?)";
        $result = $this->query($sql, [$project_id, $dag_user_ids]);
        $num_queries = 0;

        while ($row = $result->fetch_assoc()) {
            $num_queries = $row['openqueries'];
        }
        return $num_queries;
    }

    function is_drw_enabled($project_id) {
        // Has this project enabled Data Resolution Worflow
        $sql = 'SELECT data_resolution_enabled
                FROM redcap_projects
                WHERE project_id = ?';
        $result = $this->query($sql, [$project_id]);
        $enabled = '';
        while ($row = $result->fetch_assoc()) {
            $enabled = $row['data_resolution_enabled'];
        }
        return $enabled;
    }

    function get_user_ids_in_dag($project_id, $dag) {
        $sql = 'SELECT ui.ui_id
                FROM redcap_data_access_groups_users dagu
                INNER JOIN redcap_user_information ui
                ON dagu.username = ui.username
                WHERE dagu.project_id = ?
                AND dagu.group_id = ?';
        $result = $this->query($sql, [$project_id, $dag]);
        $dag_user_ids = array();
        while ($row = $result->fetch_assoc()) {
            array_push($dag_user_ids, $row['username']);
        }
        return $dag_user_ids;
    }

    function get_user_id($username) {
        // Get the User ID of the current user
        $sql = 'SELECT ui_id FROM redcap_user_information WHERE username = ?';
        $result = $this->query($sql, [$username]);
        $ui_id = 0;
        while ($row = $result->fetch_assoc()) {
            $ui_id = $row['ui_id'];
        }
        return $ui_id;
    }
}