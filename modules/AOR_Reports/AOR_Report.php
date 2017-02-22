<?php

/**
 *
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.
 *
 * SuiteCRM is an extension to SugarCRM Community Edition developed by SalesAgility Ltd.
 * Copyright (C) 2011 - 2017 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo and "Supercharged by SuiteCRM" logo. If the display of the logos is not
 * reasonably feasible for  technical reasons, the Appropriate Legal Notices must
 * display the words  "Powered by SugarCRM" and "Supercharged by SuiteCRM".
 */
include_once __DIR__.DIRECTORY_SEPARATOR.'rootPath.php';
require_once ROOTPATH.'/modules/AOW_WorkFlow/aow_utils.php';
require_once ROOTPATH.'/modules/AOR_Reports/aor_utils.php';
require_once ROOTPATH.'/modules/AOR_Reports/models/report/ReportFactory.php';
require_once ROOTPATH.'/modules/AOR_Reports/models/ModelAORReports.php';

use modules\AOR_Reports\models\ModelAORReports as Model;
use modules\AOR_Reports\models\report\ReportFactory as ReportFactory;

class AOR_Report extends Basic
{
    const CHART_TYPE_PCHART = 'pchart';
    const CHART_TYPE_CHARTJS = 'chartjs';
    const CHART_TYPE_RGRAPH = 'rgraph';

    private $reportFactory;
    public $new_schema = true;
    public $module_dir = 'AOR_Reports';
    public $object_name = 'AOR_Report';
    public $table_name = 'aor_reports';
    public $importable = true;
    public $disable_row_level_security = true;

    public $id;
    public $name;
    public $date_entered;
    public $date_modified;
    public $modified_user_id;
    public $modified_by_name;
    public $created_by;
    public $created_by_name;
    public $description;
    public $deleted;
    public $created_by_link;
    public $modified_user_link;
    public $assigned_user_id;
    public $assigned_user_name;
    public $assigned_user_link;
    public $report_module;

    public function __construct()
    {
        parent::__construct();
        $this->load_report_beans();
        $this->reportFactory = new ReportFactory();
    }

    /**
     * @deprecated deprecated since version 7.6, PHP4 Style Constructors are deprecated and will be remove in 7.8, please update your code, use __construct instead
     */
    public function AOR_Report()
    {
        $deprecatedMessage = 'PHP4 Style Constructors are deprecated and will be remove in 7.8, please update your code';
        if (isset($GLOBALS['log'])) {
            $GLOBALS['log']->deprecated($deprecatedMessage);
        } else {
            trigger_error($deprecatedMessage, E_USER_DEPRECATED);
        }
        self::__construct();
    }


    /**
     * @param $interface
     * @return bool
     */
    public function bean_implements($interface)
    {
        switch ($interface) {
            case 'ACL':
                return true;
        }

        return false;
    }

    /**
     * @param bool $check_notify
     */
    public function save($check_notify = false)
    {

        // TODO: process of saveing the fields and conditions is too long so we will have to make some optimization on save_lines functions
        set_time_limit(3600);

        if (empty($this->id)) {
            unset($_POST['aor_conditions_id']);
            unset($_POST['aor_fields_id']);
        }

        parent::save($check_notify);

        require_once('modules/AOR_Fields/AOR_Field.php');
        $field = new AOR_Field();
        $field->save_lines($_POST, $this, 'aor_fields_');

        require_once('modules/AOR_Conditions/AOR_Condition.php');
        $condition = new AOR_Condition();
        $condition->save_lines($_POST, $this, 'aor_conditions_');

        require_once('modules/AOR_Charts/AOR_Chart.php');
        $chart = new AOR_Chart();
        $chart->save_lines($_POST, $this, 'aor_chart_');
    }

    /**
     * @param string $view
     * @param string $is_owner
     * @param string $in_group
     * @return bool
     */
    public function ACLAccess($view, $is_owner = 'not_set', $in_group = 'not_set')
    {
        $result = parent::ACLAccess($view, $is_owner, $in_group);
        if ($result && $this->report_module !== '') {
            $result = ACLController::checkAccess($this->report_module, 'list', true);
        }

        return $result;
    }


    /**
     *
     */
    public function load_report_beans()
    {
        global $beanList, $app_list_strings;

        $app_list_strings['aor_moduleList'] = $app_list_strings['moduleList'];

        foreach ($app_list_strings['aor_moduleList'] as $mkey => $mvalue) {
            if (!isset($beanList[$mkey]) || str_begin($mkey, 'AOR_') || str_begin($mkey, 'AOW_')) {
                unset($app_list_strings['aor_moduleList'][$mkey]);
            }
        }

        $app_list_strings['aor_moduleList'] = array_merge((array)array('' => ''),
            (array)$app_list_strings['aor_moduleList']);

        asort($app_list_strings['aor_moduleList']);
    }


    /**
     * @return array
     */
    public function getReportFields()
    {
        $fields = array();
        foreach ($this->get_linked_beans('aor_fields', 'AOR_Fields') as $field) {
            $fields[] = $field;
        }
        usort($fields, function ($a, $b) {
            return $a->field_order - $b->field_order;
        });

        return $fields;
    }

    /**
     * @param int $offset
     * @param bool $links
     * @param int $level
     * @param array $path
     * @return null|string
     * @throws Exception
     */
    public function buildMultiGroupReport($offset = -1, $links = true, $level = 2, $path = array())
    {
        global $beanList;

        $rows = $this->getGroupDisplayFieldByReportId($this->id, $level);

        if (count($rows) > 1) {
            $GLOBALS['log']->fatal('ambiguous group display for report ' . $this->id);
        } else {
            if (count($rows) == 1) {
                $rows[0]['module_path'] = unserialize(base64_decode($rows[0]['module_path']));
                if (!$rows[0]['module_path'][0]) {
                    $module = new $beanList[$this->report_module]();
                    $rows[0]['field_id_name'] = $module->field_defs[$rows[0]['field']]['id_name'] ? $module->field_defs[$rows[0]['field']]['id_name'] : $module->field_defs[$rows[0]['field']]['name'];
                    $rows[0]['module_path'][0] = $module->table_name;
                } else {
                    $rows[0]['field_id_name'] = $rows[0]['field'];
                }
                $path[] = $rows[0];

                if ($level > 10) {
                    $msg = 'Too many nested groups';
                    $GLOBALS['log']->fatal($msg);

                    return null;
                }

                return $this->buildMultiGroupReport($offset, $links, $level + 1, $path);
            } else {
                if (!$rows) {
                    if ($path) {
                        $html = '';
                        foreach ($path as $pth) {
                            $_fieldIdName = $this->db->quoteIdentifier($pth['field_id_name']);
                            $query = "SELECT $_fieldIdName FROM " . $this->db->quoteIdentifier($pth['module_path'][0]) . " GROUP BY $_fieldIdName;";
                            $values = $this->dbSelect($query);

                            foreach ($values as $value) {
                                $moduleFieldByGroupValue = $this->getModuleFieldByGroupValue($beanList,
                                    $value[$pth['field_id_name']]);
                                $moduleFieldByGroupValue = $this->addDataIdValueToInnertext($moduleFieldByGroupValue);
                                $html .= $this->getMultiGroupFrameHTML($moduleFieldByGroupValue,
                                    $this->build_group_report($offset, $links));
                            }
                        }

                        return $html;
                    } else {
                        return $this->build_group_report($offset, $links);
                    }
                } else {
                    throw new Exception('incorrect results');
                }
            }
        }
        throw new Exception('incorrect state');
    }

    /**
     * @param null $reportId
     * @param int $level
     * @return array
     */
    private function getGroupDisplayFieldByReportId($reportId = null, $level = 1)
    {

        // set the default values

        if (is_null($reportId)) {
            $reportId = $this->id;
        }

        if (!$level) {
            $level = 1;
        }

        // escape values for query

        $_id = $this->db->quote($reportId);
        $_level = (int)$level;

        // get results array

        $query = "SELECT id, field, module_path FROM aor_fields WHERE aor_report_id = '$_id' AND group_display = $_level AND deleted = 0;";
        $rows = $this->dbSelect($query);

        return $rows;
    }


    /**
     * @param $query
     * @return array
     */
    private function dbSelect($query)
    {
        $results = $this->db->query($query);

        $rows = array();
        while ($row = $this->db->fetchByAssoc($results)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param $header
     * @param $body
     * @return string
     */
    private function getMultiGroupFrameHTML($header, $body)
    {
        $html = '<div class="multi-group-list" style="border: 1px solid black; padding: 10px;">
                    <h3>' . $header . '</h3>
                    <div class="multi-group-list-inner">' . $body . '</div>
                </div>';

        return $html;
    }

    /**
     * @param $html
     * @return mixed
     */
    private function addDataIdValueToInnertext($html)
    {
        preg_match('/\sdata-id-value\s*=\s*"([^"]*)"/', $html, $match);
        $html = preg_replace('/(>)([^<]*)(<\/\w+>$)/', '$1$2' . $match[1] . '$3', $html);

        return $html;
    }


    /**
     * @param int $offset
     * @param bool $links
     * @param array $extra
     * @return string
     */
    public function build_group_report($offset = -1, $links = true, $extra = array())
    {
        global $beanList, $timedate;

        $html = '';
        $query = '';
        $query_array = array();
        $module = new $beanList[$this->report_module]();

        $sql = "SELECT id FROM aor_fields WHERE aor_report_id = '" . $this->id . "' AND group_display = 1 AND deleted = 0 ORDER BY field_order ASC";
        $field_id = $this->db->getOne($sql);

        if (!$field_id) {
            $query_array['select'][] = $module->table_name . ".id AS '" . $module->table_name . "_id'";
        }

        if ($field_id != '') {
            $field = new AOR_Field();
            $field->retrieve($field_id);

            $field_label = str_replace(' ', '_', $field->label);

            $path = unserialize(base64_decode($field->module_path));

            $field_module = $module;
            $table_alias = $field_module->table_name;
            if (!empty($path[0]) && $path[0] != $module->module_dir) {
                foreach ($path as $rel) {
                    $new_field_module = new $beanList[getRelatedModule($field_module->module_dir, $rel)];
                    $oldAlias = $table_alias;
                    $table_alias = $table_alias . ":" . $rel;

                    $query_array = $this->buildReportQueryJoin($rel, $table_alias, $oldAlias, $field_module,
                        'relationship', $query_array, $new_field_module);
                    $field_module = $new_field_module;
                }
            }

            $data = $field_module->field_defs[$field->field];

            if ($data['type'] == 'relate' && isset($data['id_name'])) {
                $field->field = $data['id_name'];
            }

            if ($data['type'] == 'currency' && !stripos($field->field,
                    '_USD') && isset($field_module->field_defs['currency_id'])
            ) {
                if ((isset($field_module->field_defs['currency_id']['source']) && $field_module->field_defs['currency_id']['source'] == 'custom_fields')) {
                    $query['select'][$table_alias . '_currency_id'] = $table_alias . '_cstm' . ".currency_id AS '" . $table_alias . "_currency_id'";
                } else {
                    $query_array['select'][$table_alias . '_currency_id'] = $table_alias . ".currency_id AS '" . $table_alias . "_currency_id'";
                }
            }

            if ((isset($data['source']) && $data['source'] == 'custom_fields')) {
                $select_field = $this->db->quoteIdentifier($table_alias . '_cstm') . '.' . $field->field;
                // Fix for #1251 - added a missing parameter to the function call
                $query_array = $this->buildReportQueryJoin($table_alias . '_cstm', $table_alias . '_cstm',
                    $table_alias, $field_module, 'custom', $query);
            } else {
                $select_field = $this->db->quoteIdentifier($table_alias) . '.' . $field->field;
            }

            if ($field->sort_by != '') {
                $query_array['sort_by'][] = $field_label . ' ' . $field->sort_by;
            }

            $select_field = $this->BuildDataForDateType($field, $data, $select_field, $timedate);

            if ($field->field_function != null) {
                $select_field = $field->field_function . '(' . $select_field . ')';
            }

            if ($field->group_by == 1) {
                $query_array['group_by'][] = $select_field;
            }

            $query_array['select'][] = $select_field . " AS '" . $field_label . "'";
            if (isset($extra['select']) && $extra['select']) {
                foreach ($extra['select'] as $selectField => $selectAlias) {
                    if ($selectAlias) {
                        $query_array['select'][] = $selectField . " AS " . $selectAlias;
                    } else {
                        $query_array['select'][] = $selectField;
                    }
                }
            }
            $query_array['where'][] = $select_field . " IS NOT NULL AND ";


            try {
                $query_array = $this->buildQueryArrayWhere($query_array, $extra);
            } catch (Exception $e) {
            }

            $query = $this->buildSqlQuerySelect($query_array, $query);

            $query .= ' FROM ' . $module->table_name . ' ';

            $query = $this->buildSqlQueryJoin($query_array, $query);
            $query = $this->buildSqlQueryWhere($query_array, $query);

            if (isset($query_array['group_by'])) {
                $query_group_by = '';
                foreach ($query_array['group_by'] as $group_by) {
                    $query_group_by .= ($query_group_by == '' ? 'GROUP BY ' : ', ') . $group_by;
                }
                $query .= ' ' . $query_group_by;
            }

            $query = $this->buildSqlQuerySortBy($query_array, $query);
            $result = $this->db->query($query);

            while ($row = $this->db->fetchByAssoc($result)) {
                if ($html != '') {
                    $html .= '<br />';
                }

                $html .= $this->build_report_html($offset, $links, $row[$field_label], '', $extra);

            }
        }

        if ($html == '') {
            $html = $this->build_report_html($offset, $links);
        }

        return $html;

    }


    /**
     * @param int $offset
     * @param bool $links
     * @param string $group_value
     * @param string $tableIdentifier
     * @param array $extra
     * @return string
     */
    public function build_report_html($offset = -1, $links = true, $group_value = '', $tableIdentifier = '', $extra = array())
    {

        global $beanList, $sugar_config;

        $_group_value = $this->db->quote($group_value);

        try {
            $report_sql = $this->buildReportQuery($_group_value, $extra);
        } catch (Exception $e) {
        }

        // Fix for issue 1232 - items listed in a single report, should adhere to the same standard as ListView items.
        if ($sugar_config['list_max_entries_per_page'] != '') {
            $max_rows = $sugar_config['list_max_entries_per_page'];
        } else {
            $max_rows = 20;
        }

        $total_rows = 0;
        $count_sql = explode('ORDER BY', $report_sql);
        $count_query = 'SELECT count(*) c FROM (' . $count_sql[0] . ') as n';

        // We have a count query.  Run it and get the results.
        $result = $this->db->query($count_query);
        $assoc = $this->db->fetchByAssoc($result);
        if (!empty($assoc['c'])) {
            $total_rows = $assoc['c'];
        }

        $html = "<table class='list' id='report_table" . $group_value . "' width='100%' cellspacing='0' cellpadding='0' border='0' repeat_header='1'>";

        if ($offset >= 0) {
            $start = 0;
            $end = 0;
            $previous_offset = 0;
            $next_offset = 0;
            $last_offset = 0;

            if ($total_rows > 0) {
                $start = $offset + 1;
                $end = (($offset + $max_rows) < $total_rows) ? $offset + $max_rows : $total_rows;
                $previous_offset = ($offset - $max_rows) < 0 ? 0 : $offset - $max_rows;
                $next_offset = $offset + $max_rows;
                if (is_int($total_rows / $max_rows)) {
                    $last_offset = $max_rows * ($total_rows / $max_rows - 1);
                } else {
                    $last_offset = $max_rows * floor($total_rows / $max_rows);
                }

            }

            $html .= "<thead><tr class='pagination'>";


            $moduleFieldByGroupValue = $this->getModuleFieldByGroupValue($beanList, $group_value);

            $html .= "<td colspan='18'>
                       <table class='paginationTable' border='0' cellpadding='0' cellspacing='0' width='100%'>
                        <td style='text-align:left' ><H3><a href=\"javascript:void(0)\" class=\"collapseLink\" onclick=\"groupedReportToggler.toggleList(this);\"><img border=\"0\" id=\"detailpanel_1_img_hide\" src=\"themes/SuiteR/images/basic_search.gif\"></a>$moduleFieldByGroupValue</H3></td>
                        <td class='paginationChangeButtons' align='right' nowrap='nowrap' width='1%'>";

            if ($offset == 0) {
                $html .= "<button type='button' id='listViewStartButton_top' name='listViewStartButton' title='Start' class='button' disabled='disabled'>
                    <img src='" . SugarThemeRegistry::current()->getImageURL('start_off.gif') . "' alt='Start' align='absmiddle' border='0'>
                </button>
                <button type='button' id='listViewPrevButton_top' name='listViewPrevButton' class='button' title='Previous' disabled='disabled'>
                    <img src='" . SugarThemeRegistry::current()->getImageURL('previous_off.gif') . "' alt='Previous' align='absmiddle' border='0'>
                </button>";
            } else {
                $html .= "<button type='button' id='listViewStartButton_top' name='listViewStartButton' title='Start' class='button' onclick='changeReportPage(\"" . $this->id . "\",0,\"" . $group_value . "\",\"" . $tableIdentifier . "\")'>
                    <img src='" . SugarThemeRegistry::current()->getImageURL('start.gif') . "' alt='Start' align='absmiddle' border='0'>
                </button>
                <button type='button' id='listViewPrevButton_top' name='listViewPrevButton' class='button' title='Previous' onclick='changeReportPage(\"" . $this->id . "\"," . $previous_offset . ",\"" . $group_value . "\",\"" . $tableIdentifier . "\")'>
                    <img src='" . SugarThemeRegistry::current()->getImageURL('previous.gif') . "' alt='Previous' align='absmiddle' border='0'>
                </button>";
            }
            $html .= " <span class='pageNumbers'>(" . $start . " - " . $end . " of " . $total_rows . ")</span>";
            if ($next_offset < $total_rows) {
                $html .= "<button type='button' id='listViewNextButton_top' name='listViewNextButton' title='Next' class='button' onclick='changeReportPage(\"" . $this->id . "\"," . $next_offset . ",\"" . $group_value . "\",\"" . $tableIdentifier . "\")'>
                        <img src='" . SugarThemeRegistry::current()->getImageURL('next.gif') . "' alt='Next' align='absmiddle' border='0'>
                    </button>
                     <button type='button' id='listViewEndButton_top' name='listViewEndButton' title='End' class='button' onclick='changeReportPage(\"" . $this->id . "\"," . $last_offset . ",\"" . $group_value . "\",\"" . $tableIdentifier . "\")'>
                        <img src='" . SugarThemeRegistry::current()->getImageURL('end.gif') . "' alt='End' align='absmiddle' border='0'>
                    </button>";
            } else {
                $html .= "<button type='button' id='listViewNextButton_top' name='listViewNextButton' title='Next' class='button'  disabled='disabled'>
                        <img src='" . SugarThemeRegistry::current()->getImageURL('next_off.gif') . "' alt='Next' align='absmiddle' border='0'>
                    </button>
                     <button type='button' id='listViewEndButton_top' name='listViewEndButton' title='End' class='button'  disabled='disabled'>
                        <img src='" . SugarThemeRegistry::current()->getImageURL('end_off.gif') . "' alt='End' align='absmiddle' border='0'>
                    </button>";

            }

            $html .= "</td>
                       </table>
                      </td>";

            $html .= "</tr></thead>";
        } else {

            $moduleFieldByGroupValue = $this->getModuleFieldByGroupValue($beanList, $group_value);

            $html = "<H3>$moduleFieldByGroupValue</H3>" . $html;
        }

        $sql = "SELECT id FROM aor_fields WHERE aor_report_id = '" . $this->id . "' AND deleted = 0 ORDER BY field_order ASC";
        $result = $this->db->query($sql);

        $html .= "<thead>";
        $html .= "<tr>";

        $fields = array();
        $i = 0;
        while ($row = $this->db->fetchByAssoc($result)) {

            $field = new AOR_Field();
            $field->retrieve($row['id']);

            $path = unserialize(base64_decode($field->module_path));

            $field_bean = new $beanList[$this->report_module]();

            $field_module = $this->report_module;
            $field_alias = $field_bean->table_name;
            if ($path[0] != $this->report_module) {
                foreach ($path as $rel) {
                    if (empty($rel)) {
                        continue;
                    }
                    $field_module = getRelatedModule($field_module, $rel);
                    $field_alias = $field_alias . ':' . $rel;
                }
            }
            $label = str_replace(' ', '_', $field->label) . $i;
            $fields[$label]['field'] = $field->field;
            $fields[$label]['label'] = $field->label;
            $fields[$label]['display'] = $field->display;
            $fields[$label]['function'] = $field->field_function;
            $fields[$label]['module'] = $field_module;
            $fields[$label]['alias'] = $field_alias;
            $fields[$label]['link'] = $field->link;
            $fields[$label]['total'] = $field->total;

            $fields[$label]['params'] = $field->format;


            if ($fields[$label]['display']) {
                $html .= "<th scope='col'>";
                $html .= "<div style='white-space: normal;' width='100%' align='left'>";
                $html .= $field->label;
                $html .= "</div></th>";
            }
            ++$i;
        }

        $html .= "</tr>";
        $html .= "</thead>";
        $html .= "<tbody>";

        if ($offset >= 0) {
            $result = $this->db->limitQuery($report_sql, $offset, $max_rows);
        } else {
            $result = $this->db->query($report_sql);
        }

        $row_class = 'oddListRowS1';


        $totals = array();
        while ($row = $this->db->fetchByAssoc($result)) {
            $html .= "<tr class='" . $row_class . "' height='20'>";

            foreach ($fields as $name => $att) {
                if ($att['display']) {
                    $html .= "<td class='' valign='top' align='left'>";
                    if ($att['link'] && $links) {
                        $html .= "<a href='" . $sugar_config['site_url'] . "/index.php?module=" . $att['module'] . "&action=DetailView&record=" . $row[$att['alias'] . '_id'] . "'>";
                    }

                    $currency_id = isset($row[$att['alias'] . '_currency_id']) ? $row[$att['alias'] . '_currency_id'] : '';

                    if ($att['function'] == 'COUNT' || !empty($att['params'])) {
                        $html .= $row[$name];
                    } else {
                        $html .= getModuleField($att['module'], $att['field'], $att['field'], 'DetailView', $row[$name],
                            '', $currency_id);
                    }

                    if ($att['total']) {
                        $totals[$name][] = $row[$name];
                    }
                    if ($att['link'] && $links) {
                        $html .= "</a>";
                    }
                    $html .= "</td>";
                }
            }
            $html .= "</tr>";

            $row_class = $row_class == 'oddListRowS1' ? 'evenListRowS1' : 'oddListRowS1';
        }
        $html .= "</tbody>";

        $html .= $this->getTotalHTML($fields, $totals);

        $html .= "</table>";

        $html .= "    <script type=\"text/javascript\">
                            groupedReportToggler = {

                                toggleList: function(elem) {
                                    $(elem).closest('table.list').find('thead, tbody').each(function(i, e){
                                        if(i>1) {
                                            $(e).toggle();
                                        }
                                    });
                                    if($(elem).find('img').first().attr('src') == 'themes/SuiteR/images/basic_search.gif') {
                                        $(elem).find('img').first().attr('src', 'themes/SuiteR/images/advanced_search.gif');
                                    }
                                    else {
                                        $(elem).find('img').first().attr('src', 'themes/SuiteR/images/basic_search.gif');
                                    }
                                }

                            };
                        </script>";

        return $html;
    }

    /**
     * @param $beanList
     * @param $group_value
     * @return string
     */
    private function getModuleFieldByGroupValue($beanList, $group_value)
    {
        $moduleFieldByGroupValues = array();

        $sql = "SELECT id FROM aor_fields WHERE aor_report_id = '" . $this->id . "' AND group_display = 1 AND deleted = 0 ORDER BY field_order ASC";
        $result = $this->db->limitQuery($sql, 0, 1);
        while ($row = $this->db->fetchByAssoc($result)) {

            $field = new AOR_Field();
            $field->retrieve($row['id']);

            if ($field->field_function != 'COUNT' || $field->format != '') {
                $moduleFieldByGroupValues[] = $group_value;
                continue;
            }

            $path = unserialize(base64_decode($field->module_path));

            $field_bean = new $beanList[$this->report_module]();

            $field_module = $this->report_module;
            $field_alias = $field_bean->table_name;
            if ($path[0] != $this->report_module) {
                foreach ($path as $rel) {
                    if (empty($rel)) {
                        continue;
                    }
                    $field_module = getRelatedModule($field_module, $rel);
                    $field_alias = $field_alias . ':' . $rel;
                }
            }

            $currency_id = isset($row[$field_alias . '_currency_id']) ? $row[$field_alias . '_currency_id'] : '';
            $moduleFieldByGroupValues[] = getModuleField($this->report_module, $field->field, $field->field,
                'DetailView', $group_value, '', $currency_id);

        }

        $moduleFieldByGroupValue = implode(', ', $moduleFieldByGroupValues);

        return $moduleFieldByGroupValue;
    }

    /**
     * @param $fields
     * @param $totals
     * @return string
     */
    public function getTotalHTML($fields, $totals)
    {
        global $app_list_strings;

        $currency = new Currency();
        $currency->retrieve($GLOBALS['current_user']->getPreference('currency'));

        $html = '';
        $html .= "<tbody>";
        $html .= "<tr>";
        foreach ($fields as $label => $field) {
            if (!$field['display']) {
                continue;
            }
            if ($field['total']) {
                $totalLabel = $field['label'] . " " . $app_list_strings['aor_total_options'][$field['total']];
                $html .= "<th>{$totalLabel}</th>";
            } else {
                $html .= "<th></th>";
            }
        }
        $html .= "</tr>";
        $html .= "<tr>";
        foreach ($fields as $label => $field) {
            if (!$field['display']) {
                continue;
            }
            if ($field['total'] && isset($totals[$label])) {
                $type = $field['total'];
                $total = $this->calculateTotal($type, $totals[$label]);
                // Customise display based on the field type
                $moduleBean = BeanFactory::newBean($field['module']);
                $fieldDefinition = $moduleBean->field_defs[$field['field']];
                $fieldDefinitionType = $fieldDefinition['type'];
                switch ($fieldDefinitionType) {
                    case "currency":
                        // Customise based on type of function
                        switch ($type) {
                            case 'SUM':
                            case 'AVG':
                                if ($currency->id == -99) {
                                    $total = $currency->symbol . format_number($total, null, null);
                                } else {
                                    $total = $currency->symbol . format_number($total, null, null,
                                            array('convert' => true));
                                }
                                break;
                            case 'COUNT':
                            default:
                                break;
                        }
                        break;
                    default:
                        break;
                }
                $html .= "<td>" . $total . "</td>";
            } else {
                $html .= "<td></td>";
            }
        }
        $html .= "</tr>";
        $html .= "</tbody>";

        return $html;
    }

    /**
     * @param $type
     * @param $totals
     * @return float|int|string
     */
    public function calculateTotal($type, $totals)
    {
        switch ($type) {
            case 'SUM':
                return array_sum($totals);
            case 'COUNT':
                return count($totals);
            case 'AVG':
                return array_sum($totals) / count($totals);
            default:
                return '';
        }
    }

    /**
     * @param $field
     * @return string
     */
    private function encloseForCSV($field)
    {
        return '"' . $field . '"';
    }

    /**
     *
     */
    public function build_report_csv()
    {
        global $beanList;
        ini_set('zlib.output_compression', 'Off');

        ob_start();
        require_once('include/export_utils.php');

        $delimiter = getDelimiter();
        $csv = '';
        //text/comma-separated-values

        $sql = "SELECT id FROM aor_fields WHERE aor_report_id = '" . $this->id . "' AND deleted = 0 ORDER BY field_order ASC";
        $result = $this->db->query($sql);

        $fields = array();
        $i = 0;
        while ($row = $this->db->fetchByAssoc($result)) {

            $field = new AOR_Field();
            $field->retrieve($row['id']);

            $path = unserialize(base64_decode($field->module_path));
            $field_bean = new $beanList[$this->report_module]();
            $field_module = $this->report_module;
            $field_alias = $field_bean->table_name;

            if ($path[0] != $this->report_module) {
                foreach ($path as $rel) {
                    if (empty($rel)) {
                        continue;
                    }
                    $field_module = getRelatedModule($field_module, $rel);
                    $field_alias = $field_alias . ':' . $rel;
                }
            }
            $label = str_replace(' ', '_', $field->label) . $i;
            $fields[$label]['field'] = $field->field;
            $fields[$label]['display'] = $field->display;
            $fields[$label]['function'] = $field->field_function;
            $fields[$label]['module'] = $field_module;
            $fields[$label]['alias'] = $field_alias;
            $fields[$label]['params'] = $field->format;

            if ($field->display) {
                $csv .= $this->encloseForCSV($field->label);
                $csv .= $delimiter;
            }
            ++$i;
        }

        try {
            $sql = $this->buildReportQuery();
        } catch (Exception $e) {
        }
        $result = $this->db->query($sql);

        while ($row = $this->db->fetchByAssoc($result)) {
            $csv .= "\r\n";
            foreach ($fields as $name => $att) {
                $currency_id = isset($row[$att['alias'] . '_currency_id']) ? $row[$att['alias'] . '_currency_id'] : '';
                if ($att['display']) {
                    if ($att['function'] != '' || $att['params'] != '') {
                        $csv .= $this->encloseForCSV($row[$name]);
                    } else {
                        $csv .= $this->encloseForCSV(trim(strip_tags(getModuleField($att['module'], $att['field'],
                            $att['field'], 'DetailView', $row[$name], '', $currency_id))));
                    }
                    $csv .= $delimiter;
                }
            }
        }

        $csv = $GLOBALS['locale']->translateCharset($csv, 'UTF-8', $GLOBALS['locale']->getExportCharset());

        ob_clean();
        header("Pragma: cache");
        header("Content-type: text/comma-separated-values; charset=" . $GLOBALS['locale']->getExportCharset());
        header("Content-Disposition: attachment; filename=\"{$this->name}.csv\"");
        header("Content-transfer-encoding: binary");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . TimeDate::httpTime());
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Content-Length: " . mb_strlen($csv, '8bit'));
        if (!empty($sugar_config['export_excel_compatible'])) {
            $csv = chr(255) . chr(254) . mb_convert_encoding($csv, 'UTF-16LE', 'UTF-8');
        }
        print $csv;

        sugar_cleanup(true);
    }


    /**
     * @param string $group_value
     * @param array $extra
     * @return array|bool|string
     * @throws Exception
     */
    public function buildReportQuery($group_value = '', $extra = array())
    {
        global $beanList;
        $module = new $beanList[$this->report_module]();
        $tableName = $module->table_name;
        $sqlQuery = '';

        //Check if the user has access to the target module
        if (!(ACLController::checkAccess($this->report_module, 'list', true))) {
            return false;
        }

        try {
            $query_array = $this->buildReportQuerySelect($group_value);
            $query_array = $this->buildQueryArrayWhere($query_array, $extra);
        } catch (Exception $e) {
            throw new Exception('Caught exception:' . $e->getMessage(), $e->getCode());
        }

        $sqlQuery = $this->buildSqlQuerySelect($query_array, $sqlQuery);

        $sqlQuery = $this->buildSqlQueryGroupBy($query_array, $sqlQuery);

        $sqlQuery = $this->buildSqlQueryFrom($query_array, $sqlQuery);

        $sqlQuery = $this->buildSqlQueryJoin($query_array, $sqlQuery);

        $sqlQuery = $this->buildSqlQueryWhere($query_array, $sqlQuery);

        $sqlQuery = $this->buildSqlQueryGroupBy2($query_array, $sqlQuery);

        $sqlQuery = $this->buildSqlQuerySortBy($query_array, $sqlQuery);

        return $sqlQuery;

    }

    /**
     * @param $query_where
     * @return mixed
     */
    private function queryWhereRepair($query_where)
    {

        // remove empty parenthesis and fix query syntax

        $safe = 0;
        $query_where_clean = '';
        while ($query_where_clean != $query_where) {
            $query_where_clean = $query_where;
            $query_where = preg_replace('/\b(AND|OR)\s*\(\s*\)|[^\w+\s*]\(\s*\)/i', '', $query_where_clean);
            $safe++;
            if ($safe > 100) {
                $GLOBALS['log']->fatal('Invalid report query conditions');
                break;
            }
        }

        return $query_where;
    }

    public function buildReportChart($chartIds = null, $chartType = self::CHART_TYPE_PCHART)
    {
        global $beanList, $timedate, $app_list_strings, $sugar_config;
        $moduleName = $this->report_module;
        $ResultRowArray = $this->getResultRows();
        $fields = $this->createLabels($ResultRowArray, $beanList);
        $mainGroupField = $this->getMainGroupSet($ResultRowArray);
        $bean = BeanFactory::getBean($moduleName);

        $dataObject = array(
            'reportId'=>null,
            'beanList'=>null,
            'queryArray'=>null,
            'sqlQuery'=>null,
            'dataArray'=>null,
            'field'=>null,
            'module'=>null,
            'fieldModule'=>null,
            'tableAlias'=>null,
            'oldAlias'=>null,
            'timeDate'=>null,
            'selectField'=>null,
            'condition'=>null,
            'conditionFieldDefs'=>null,
            'tiltLogicOperator'=>true,
            'allowedOperatorList'=>$this->getAllowedOperatorList(),
        );

        $reportId = $this->id;
        $dataObject['reportId']=$reportId;
        $dataObject['reportModuleBean'] = $bean;
        $dataObject['queryArray'] = array();;
        $dataObject['beanList'] = $beanList;
        $dataObject['timeDate']= $timedate;

        $moduleBeanName = $beanList[$moduleName];
        if($moduleBeanName === null){
            throw new Exception('AOR_Report:buildQueryArraySelectForChart: Module Bean Does Not Exist',103);
        }
        //Check if the user has access to the target module
        if (!(ACLController::checkAccess($moduleName, 'list', true))) {
            throw new Exception('AOR_Report:buildReportQueryChart: User Not Allowed Access To This Module', 101);
        }

        try {
            $this->buildReportQueryChart($dataObject, $app_list_strings, $sugar_config);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }

        $result2 = $this->db->query($dataObject['sqlQuery']);
        $data = $this->BuildDataRowsForChart($result2, $fields);

        $fields = $this->getReportFields();

        $html = $this->StartBuildChartHTML($chartIds, $chartType, $data, $fields, $mainGroupField);

        return $html;
    }


    public function buildReportQueryChart(&$dataObject, $app_list_strings, $sugar_config, $extra = array())
    {
        $model = new Model();
        $this->DataArrayGetTableData($dataObject);
        $this->buildQueryArraySelectForChart($dataObject, $model);
        try {
            $this->buildQueryArrayWhereForChart($dataObject, $model, $app_list_strings, $sugar_config, $extra);
        } catch (Exception $e) {
            throw new Exception('Caught exception:' . $e->getMessage(), $e->getCode());
        }
        $this->buildSqlQuerySelectChart($dataObject);
        $this->buildSqlQueryGroupByChart($dataObject);
        $this->buildSqlQueryFromChart($dataObject);
        $this->buildSqlQueryJoinChart($dataObject);
        $this->buildSqlQueryWhereChart($dataObject);
        $this->buildSqlQueryGroupBy2Chart($dataObject);
        $this->buildSqlQuerySortByChart($dataObject);
    }



    public function buildQueryArraySelectForChart(&$dataObject, Model $model)
    {

        try {

            $rowArray = $model->getChartDataArrayForSelect($dataObject['reportId'],$dataObject['reportModuleBean']);
            $i = 0;
            foreach($rowArray as $row){
                //getField($id)
                $field = new AOR_Field();
                $field->retrieve($row['id']);
                $field->label = str_replace(' ', '_', $field->label) . $i;
                $dataObject['field'] = $field;
                $dataObject['tableAlias'] = $dataObject['reportModuleBean']->table_name;
//                $dataObject['oldAlias'] = $dataObject['tableAlias'];// why set this now?

                $this->createQueryDataArrayChart($dataObject);
                ++$i;
            }

        } catch (Exception $e) {
            throw new Exception('Exception Caught :'.$e->getMessage(),$e->getCode());
        }
    }

    /**
     * @param string $group_value
     * @return array|mixed
     * @internal param array $queryDataArray
     */
    public function buildReportQuerySelect($group_value = '')
    {
        global $beanList, $timedate;

        $queryDataArray = array();
        if ($beanList[$this->report_module]) {
            $module = new $beanList[$this->report_module]();
            $queryDataArray['tableName'] = $module->table_name;
            $queryDataArray['id_select'][$module->table_name] = $this->db->quoteIdentifier($module->table_name) . ".id AS '" . $module->table_name . "_id'";
            $queryDataArray['id_select_group'][$module->table_name] = $this->db->quoteIdentifier($module->table_name) . ".id";

            $sql = "SELECT id FROM aor_fields WHERE aor_report_id = '" . $this->id . "' AND deleted = 0 ORDER BY field_order ASC";

            $result = $this->db->query($sql);
            $i = 0;
            while ($row = $this->db->fetchByAssoc($result)) {
                $queryDataArray = $this->createQueryDataArray($queryDataArray, $group_value, $row, $i, $module, $beanList, $timedate);
                ++$i;
            }
        }

        return $queryDataArray;
    }


    public function buildReportQueryJoin(
        $name,
        $alias,
        $parentAlias,
        SugarBean $module,
        $type,
        $query = array(),
        SugarBean $rel_module = null
    ) {

        if (!isset($query['join'][$alias])) {

            switch ($type) {
                case 'custom':
                    $query['join'][$alias] = 'LEFT JOIN ' . $this->db->quoteIdentifier($module->get_custom_table_name()) . ' ' . $this->db->quoteIdentifier($name) . ' ON ' . $this->db->quoteIdentifier($parentAlias) . '.id = ' . $this->db->quoteIdentifier($name) . '.id_c ';
                    break;

                case 'relationship':
                    if ($module->load_relationship($name)) {
                        $params['join_type'] = 'LEFT JOIN';
                        if ($module->$name->relationship_type != 'one-to-many') {
                            if ($module->$name->getSide() == REL_LHS) {
                                $params['right_join_table_alias'] = $this->db->quoteIdentifier($alias);
                                $params['join_table_alias'] = $this->db->quoteIdentifier($alias);
                                $params['left_join_table_alias'] = $this->db->quoteIdentifier($parentAlias);
                            } else {
                                $params['right_join_table_alias'] = $this->db->quoteIdentifier($parentAlias);
                                $params['join_table_alias'] = $this->db->quoteIdentifier($alias);
                                $params['left_join_table_alias'] = $this->db->quoteIdentifier($alias);
                            }

                        } else {
                            $params['right_join_table_alias'] = $this->db->quoteIdentifier($parentAlias);
                            $params['join_table_alias'] = $this->db->quoteIdentifier($alias);
                            $params['left_join_table_alias'] = $this->db->quoteIdentifier($parentAlias);
                        }
                        $linkAlias = $parentAlias . "|" . $alias;
                        $params['join_table_link_alias'] = $this->db->quoteIdentifier($linkAlias);
                        $join = $module->$name->getJoin($params, true);
                        $query['join'][$alias] = $join['join'];
                        if ($rel_module != null) {
                            $query['join'][$alias] .= $this->build_report_access_query($rel_module, $name);
                        }
                        $query['id_select'][$alias] = $join['select'] . " AS '" . $alias . "_id'";
                        $query['id_select_group'][$alias] = $join['select'];
                    }
                    break;
                default:
                    break;

            }

        }

        return $query;
    }


    public function buildReportQueryJoinChart(&$dataObject, $relationship, $type = 'relationship') {


        $query = $dataObject['queryArray'];
        $parentModulebean = $dataObject['reportModuleBean']; /**  @var $parentModulebean SugarBean */
        $relationshipExists = $parentModulebean->load_relationship($relationship);
        $relationshipLink = $parentModulebean->$relationship; /** @var $relationshipObj Link2 */
        $relationshipSide = $parentModulebean->$relationship->getSide();
        $relObj = $relationshipLink->getRelationshipObject();

        if($relationshipSide == 'LHS'){
            $relModuleSide = 'rhs_module';
        }else{
            $relModuleSide = 'lhs_module';
        }
        $relatedModuleName = $relObj->def[$relModuleSide];

        $relatedModuleBean = BeanFactory::getBean($relatedModuleName);

        $dataObject['fieldModule'] = $relatedModuleBean;//TODO: rename fieldModule to relatedModule
        $tableAlias  = $relatedModuleBean->table_name;
        $parentAlias = $parentModulebean->table_name;
        $tableAlias = $tableAlias . ":" . $tableAlias;

        if (!isset($query['join'][$tableAlias])) {

            switch ($type) {
                case 'custom':
                    $query['join'][$tableAlias] = 'LEFT JOIN ' . $this->db->quoteIdentifier($parentModulebean->get_custom_table_name()) . ' ' . $this->db->quoteIdentifier($relationship) . ' ON ' . $this->db->quoteIdentifier($parentAlias) . '.id = ' . $this->db->quoteIdentifier($relationship) . '.id_c ';
                    break;

                case 'relationship':
                    if ($relationshipExists) {
                        $params['join_type'] = 'LEFT JOIN';
                        $isOneToMany = $parentModulebean->$relationship->relationship_type != 'one-to-many';
                        if ($isOneToMany) {
                            $RelationshipIsLeftHandSide = $relationshipSide == REL_LHS;
                            if ($RelationshipIsLeftHandSide) {
                                $params['right_join_table_alias'] = $this->db->quoteIdentifier($tableAlias);
                                $params['join_table_alias'] = $this->db->quoteIdentifier($tableAlias);
                                $params['left_join_table_alias'] = $this->db->quoteIdentifier($parentAlias);
                            } else {
                                $params['right_join_table_alias'] = $this->db->quoteIdentifier($parentAlias);
                                $params['join_table_alias'] = $this->db->quoteIdentifier($tableAlias);
                                $params['left_join_table_alias'] = $this->db->quoteIdentifier($tableAlias);
                            }

                        } else {
                            $params['right_join_table_alias'] = $this->db->quoteIdentifier($parentAlias);
                            $params['join_table_alias'] = $this->db->quoteIdentifier($tableAlias);
                            $params['left_join_table_alias'] = $this->db->quoteIdentifier($parentAlias);
                        }
                        $linkAlias = $parentAlias . "|" . $tableAlias;
                        $params['join_table_link_alias'] = $this->db->quoteIdentifier($linkAlias);
                        $join = $parentModulebean->$relationship->getJoin($params, true);
                        $query['join'][$tableAlias] = $join['join'];
                        if ($relatedModuleBean != null) {
                            $query['join'][$tableAlias] .= $this->build_report_access_query($relatedModuleBean, $relationship);
                        }
                        $query['id_select'][$tableAlias] = $join['select'] . " AS '" . $tableAlias . "_id'";
                        $query['id_select_group'][$tableAlias] = $join['select'];
                    }
                    break;
                default:
                    break;

            }

        }

        $dataObject['queryArray'] = $query;
        $dataObject['fieldModule'] = $relatedModuleBean;
    }



    public function build_report_access_query(SugarBean $module, $alias)
    {

        $module->table_name = $alias;
        $where = '';
        if ($module->bean_implements('ACL') && ACLController::requireOwner($module->module_dir, 'list')) {
            global $current_user;
            $owner_where = $module->getOwnerWhere($current_user->id);
            $where = ' AND ' . $owner_where;

        }

        if (file_exists('modules/SecurityGroups/SecurityGroup.php')) {
            /* BEGIN - SECURITY GROUPS */
            if ($module->bean_implements('ACL') && ACLController::requireSecurityGroup($module->module_dir, 'list')) {
                require_once('modules/SecurityGroups/SecurityGroup.php');
                global $current_user;
                $owner_where = $module->getOwnerWhere($current_user->id);
                $group_where = SecurityGroup::getGroupWhere($alias, $module->module_dir, $current_user->id);
                if (!empty($owner_where)) {
                    $where .= " AND (" . $owner_where . " or " . $group_where . ") ";
                } else {
                    $where .= ' AND ' . $group_where;
                }
            }
            /* END - SECURITY GROUPS */
        }

        return $where;
    }


    /**
     * @return mixed
     */
    private function getAllowedOperatorList()
    {
        $aor_sql_operator_list['Equal_To'] = '=';
        $aor_sql_operator_list['Not_Equal_To'] = '!=';
        $aor_sql_operator_list['Greater_Than'] = '>';
        $aor_sql_operator_list['Less_Than'] = '<';
        $aor_sql_operator_list['Greater_Than_or_Equal_To'] = '>=';
        $aor_sql_operator_list['Less_Than_or_Equal_To'] = '<=';
        $aor_sql_operator_list['Contains'] = 'LIKE';
        $aor_sql_operator_list['Starts_With'] = 'LIKE';
        $aor_sql_operator_list['Ends_With'] = 'LIKE';

        return $aor_sql_operator_list;
    }

    /**
     * @param array $query
     * @param array $extra
     * @return array|mixed|string
     * @throws Exception
     */
    public function buildQueryArrayWhere($query = array(), $extra = array())
    {
        global $beanList, $app_list_strings, $sugar_config;
        $aor_sql_operator_list = $this->getAllowedOperatorList();

        if (isset($extra['where']) && $extra['where']) {
            $query_array['where'][] = implode(' AND ', $extra['where']) . ' AND ';
        }

        $closure = false;
        if (!empty($query['where'])) {
            $query['where'][] = '(';
            $closure = true;
        }

        if ($beanList[$this->report_module]) {
            $module = new $beanList[$this->report_module]();

            $sql = "SELECT id FROM aor_conditions WHERE aor_report_id = '" . $this->id . "' AND deleted = 0 ORDER BY condition_order ASC";
            $result = $this->db->query($sql);
//            $resultCopy = $this->db->query($sql);;
            $tiltLogicOp = true;

            $rowArray = array();
            while ($row = $this->db->fetchByAssoc($result)) {
                array_push($rowArray, $row);
            }

            //checkIfUserIsAllowAccessToModule
            if (!$this->checkIfUserIsAllowedAccessToRelatedModules($rowArray, $module, $beanList)) {
                throw new Exception('AOR_Report:buildQueryArrayWhere: User Not Allowed Access To Module '.$module, 102);
            }


            //build where statement
            foreach ($rowArray as $row) {
                $condition = new AOR_Condition();
                $condition->retrieve($row['id']);

                //path is stored as base64 encoded serialized php object
                $path = unserialize(base64_decode($condition->module_path));

                $condition_module = $module;
                $table_alias = $condition_module->table_name;
                $oldAlias = $table_alias;
                $isRelationshipExternalModule = !empty($path[0]) && $path[0] != $module->module_dir;
                //check if relationship to field outside this module is set for condition
                if ($isRelationshipExternalModule) {
                    //loop over each relationship field and check if allowed access
                    foreach ($path as $rel) {
                        if (empty($rel)) {
                            continue;
                        }
                        // Bug: Prevents relationships from loading.
                        $new_condition_module = new $beanList[getRelatedModule($condition_module->module_dir, $rel)];
                        $oldAlias = $table_alias;
                        $table_alias = $table_alias . ":" . $rel;
                        $query = $this->buildReportQueryJoin($rel, $table_alias, $oldAlias, $condition_module,
                            'relationship', $query, $new_condition_module);
                        $condition_module = $new_condition_module;
                    }
                }

                //check if condition is in the allowed operator list
                if (isset($aor_sql_operator_list[$condition->operator])) {
                    $where_set = false;
                    $data = $condition_module->field_defs[$condition->field];
                    //check data type of field and process
                    switch ($data['type']) {
                        case 'relate':
                            list($data, $condition) = $this->primeDataForRelate($data, $condition, $condition_module);
                            break;
                        case 'link':
                            list($table_alias, $query, $condition_module) = $this->primeDataForLink($query,
                                $data, $beanList, $condition_module, $oldAlias, $path, $rel, $condition, $table_alias);
                            break;
                    }


                    $tableName = $table_alias;
                    $fieldName = $condition->field;
                    $dataSourceIsSet = isset($data['source']);
                    if ($dataSourceIsSet) {
                        $isCustomField = ($data['source'] == 'custom_fields') ? true : false;
                    }
                    //setValueSuffix
                    $field = $this->setFieldTablesSuffix($isCustomField, $tableName, $table_alias, $fieldName);

                    //check if its a custom field the set the field parameter
//                    $field = $this->setFieldSuffixOld($data, $table_alias, $condition);

                    //buildJoinQueryForCustomFields
                    $query = $this->buildJoinQueryForCustomFields($isCustomField, $query, $table_alias, $tableName,
                        $condition_module);

                    //check for custom selectable parameter from report
                    $this->buildConditionParams($condition);

                    $conditionType = $condition->value_type;
                    //what type of condition is it?
                    list(
                        $data,
                        $condition,
                        $table_alias,
                        $query,
                        $condition_module,
                        $tableName,
                        $fieldName,
                        $dataSourceIsSet,
                        $isCustomField,
                        $value,
                        $params,
                        $field,
                        $where_set,
                        $current_user
                        ) = $this->buildQueryForConditionType(
                        $query,
                        $conditionType,
                        $condition_module,
                        $condition,
                        $beanList,
                        $oldAlias,
                        $path,
                        $rel,
                        $table_alias,
                        $sugar_config,
                        $field,
                        $app_list_strings,
                        $aor_sql_operator_list,
                        $tiltLogicOp
                    );

                    //handle like conditions
                    $conditionOperator = $condition->operator;
                    $value = $this->handleLikeConditions($conditionOperator, $value);

                    if ($condition->value_type == 'Value' && !$condition->value && $condition->operator == 'Equal_To') {
                        $value = "{$value} OR {$field} IS NULL";
                    }

                    list($value, $query) = $this->whereNotSet($query, $where_set, $condition,
                        $app_list_strings, $tiltLogicOp, $aor_sql_operator_list, $field, $value);

                    $tiltLogicOp = false;
                } else {
                    if ($condition->parenthesis) {
                        if ($condition->parenthesis == 'START') {
                            $query['where'][] = ($tiltLogicOp ? '' : ($condition->logic_op ? $condition->logic_op . ' ' : 'AND ')) . '(';
                            $tiltLogicOp = true;
                        } else {
                            $query['where'][] = ')';
                            $tiltLogicOp = false;
                        }
                    } else {
                        $GLOBALS['log']->debug('illegal condition');
                    }
                }

            }

            if (isset($query['where']) && $query['where']) {
                array_unshift($query['where'], '(');
                $query['where'][] = ') AND ';
            }
            $query['where'][] = $module->table_name . ".deleted = 0 " . $this->build_report_access_query($module,
                    $module->table_name);

        }

        if ($closure) {
            $query['where'][] = ')';
        }

        return $query;
    }

    public function buildQueryArrayWhereForChart(&$dataObject, $model, $app_list_strings, $sugar_config,  $extra = array())
    {
        if (isset($extra['where']) && $extra['where']) {
            $query_array['where'][] = implode(' AND ', $extra['where']) . ' AND ';
        }

        $closure = false;
        if (!empty($dataObject['queryArray']['where'])) {
            $dataObject['queryArray']['where'][] = '(';
            $closure = true;
        }

        $rowArray = $model->getChartDataArray2($dataObject['reportId'], $dataObject['reportModuleBean']);

        //checkIfUserIsAllowAccessToModule
        if (!$this->checkIfUserIsAllowedAccessToRelatedModulesChart($rowArray, $dataObject)) {
            throw new Exception('AOR_Report:buildQueryArrayWhere: User Not Allowed Access To Module '.$dataObject['reportModuleBean'], 102);
        }

        //build where statement
        foreach ($rowArray as $row) {
            $condition = new AOR_Condition();
            $condition->retrieve($row['id']);
            $dataObject['condition'] = $condition;
            $path = unserialize(base64_decode($dataObject['field']->module_path));
            $pathExists = !empty($path[0]);
            $PathIsNotModuleDir = $path[0] != $dataObject['reportModuleBean']->module_dir;
            if ($pathExists && $PathIsNotModuleDir) {
                foreach ($path as $relationship) {
                    $this->buildReportQueryJoinChart($dataObject,$relationship,'relationship');
                }
            }

            $isSqlOperatorAllowed = isset($dataObject['allowedOperatorList'][$condition->operator]);
            if ($isSqlOperatorAllowed) {
                $this->setFieldTablesSuffixChart($dataObject);

                $this->buildJoinQueryForCustomFieldsChart($dataObject);

                $this->buildConditionUserParamsChart($dataObject);

                //what type of condition is it?
//                $relationship,//$data['relationship']? see buildReportQueryJoin
                list(
                    $condition,
                    $dataObject['queryArray'],
                    $value,
                    $conditionField,
                    $where_set
                    ) = $this->buildQueryForConditionTypeChart( $dataObject, $sugar_config,$app_list_strings );
//                    $condition = $dataObject['condition']
//                    $dataObject['queryArray'],
//                    $value,//not query
//                    $conditionField,
//                    $where_set


                //handle like conditions
                $conditionOperator = $condition->operator;
                $value = $this->handleLikeConditions($conditionOperator, $value);

                if ($condition->value_type == 'Value' && !$condition->value && $condition->operator == 'Equal_To') {
                    $value = "{$value} OR {$conditionField} IS NULL";
                }

                $dataObject['queryArray'] = $this->whereNotSet(
                    $dataObject['queryArray'],
                    $where_set,
                    $condition,
                    $app_list_strings,
                    $dataObject['tiltLogicOperator'],
                    $dataObject['allowedOperatorList'],
                    $conditionField,
                    $value);

                $dataObject['tiltLogicOperator'] = false;
            } else {
                if ($condition->parenthesis) {
                    if ($condition->parenthesis == 'START') {
                        $dataObject['queryArray']['where'][] = ($dataObject['tiltLogicOperator'] ? '' : ($condition->logic_op ? $condition->logic_op . ' ' : 'AND ')) . '(';
                        $dataObject['tiltLogicOperator'] = true;
                    } else {
                        $dataObject['queryArray']['where'][] = ')';
                        $dataObject['tiltLogicOperator'] = false;
                    }
                } else {
                    $GLOBALS['log']->debug('illegal condition');
                }
            }

        }

        if (isset($queryArray['where']) && $queryArray['where']) {
            array_unshift($queryArray['where'], '(');
            $queryArray['where'][] = ') AND ';
        }
        $queryArray['where'][] = $dataObject['reportModuleBean']->table_name . ".deleted = 0 " . $this->build_report_access_query($dataObject['reportModuleBean'],
                $dataObject['reportModuleBean']->table_name);



        if ($closure) {
            $queryArray['where'][] = ')';
        }

        return $queryArray;
    }


    private function createLabels($rowArray,$beanList) {
        $i = 0;

        foreach ($rowArray as $row) {

            $field = new AOR_Field();
            $field->retrieve($row['id']);

            $path = unserialize(base64_decode($field->module_path));

            $field_bean = new $beanList[$this->report_module]();

            $field_module = $this->report_module;
            $field_alias = $field_bean->table_name;
            if ($path[0] != $this->report_module) {
                foreach ($path as $rel) {
                    if (empty($rel)) {
                        continue;
                    }
                    $field_module = getRelatedModule($field_module, $rel);
                    $field_alias = $field_alias . ':' . $rel;
                }
            }
            $label = str_replace(' ', '_', $field->label) . $i;
            $fieldsArray[$label]['field'] = $field->field;
            $fieldsArray[$label]['label'] = $field->label;
            $fieldsArray[$label]['display'] = $field->display;
            $fieldsArray[$label]['function'] = $field->field_function;
            $fieldsArray[$label]['module'] = $field_module;
            $fieldsArray[$label]['alias'] = $field_alias;
            $fieldsArray[$label]['link'] = $field->link;
            $fieldsArray[$label]['total'] = $field->total;
            $fieldsArray[$label]['params'] = $field->format;

            ++$i;
        }
        return $fieldsArray;
    }

    /**
     * @param $result
     * @param $fields
     * @return array
     */
    private function BuildDataRowsForChart(
        $result,
        $fields
    ) {
        $data = array();
        while ($row = $this->db->fetchByAssoc($result, false)) {
            foreach ($fields as $name => $att) {

                $currency_id = isset($row[$att['alias'] . '_currency_id']) ? $row[$att['alias'] . '_currency_id'] : '';

                if ($att['function'] != 'COUNT' && empty($att['params']) && !is_numeric($row[$name])) {
                    $row[$name] = trim(strip_tags(getModuleField($att['module'], $att['field'], $att['field'],
                        'DetailView', $row[$name], '', $currency_id)));
                }
            }
            $data[] = $row;
        }

        return $data;
    }

    /**
     * @param $query
     * @param $field
     * @param $module
     * @param $beanList
     * @param $field_module
     * @param $table_alias
     * @param $oldAlias
     * @return array
     */
    private function BuildJoinsForEachExternalRelatedField(
        $query,
        $field,
        $module,
        $beanList,
        $field_module,
        $table_alias,
        $oldAlias
    ) {

        $path = unserialize(base64_decode($field->module_path));
        $pathExists = !empty($path[0]);
        $PathIsNotModuleDir = $path[0] != $module->module_dir;
        if ($pathExists && $PathIsNotModuleDir) {
            foreach ($path as $rel) {
                $new_field_module = new $beanList[getRelatedModule($field_module->module_dir, $rel)];
                $oldAlias = $table_alias;
                $table_alias = $table_alias . ":" . $rel;
                $query =
                    $this->buildReportQueryJoin(
                        $rel,
                        $table_alias,
                        $oldAlias,
                        $field_module,
                        'relationship',
                        $query,
                        $new_field_module);
                $field_module = $new_field_module;
            }

            return array($oldAlias, $table_alias, $query, $field_module);
        }

        return array($oldAlias, $table_alias, $query, $field_module);
    }


    /**
     * @param $field_module
     * @param $field
     * @return mixed
     */
    private function BuildDataForRelateType(
        $field_module,
        $field
    ) {
        $data = $field_module->field_defs[$field->field];
        if ($data['type'] == 'relate' && isset($data['id_name'])) {
            $field->field = $data['id_name'];
            $data_new = $field_module->field_defs[$field->field];
            if (isset($data_new['source']) && $data_new['source'] == 'non-db' && $data_new['type'] != 'link' && isset($data['link'])) {
                $data_new['type'] = 'link';
                $data_new['relationship'] = $data['link'];
            }
            $data = $data_new;

            return $data;
        }

        return $data;
    }


    private function BuildDataForRelateTypeChart(&$dataObject) {
        $field = $dataObject['field'];
        $field_module = $dataObject['fieldModule'];
        $dataArray = $field_module->field_defs[$field->field];
        if ($dataArray['type'] == 'relate' && isset($dataArray['id_name'])) {
            $field->field = $dataArray['id_name'];
            $dataArray_new = $field_module->field_defs[$field->field];
            if (isset($dataArray_new['source']) && $dataArray_new['source'] == 'non-db' && $dataArray_new['type'] != 'link' && isset($dataArray['link'])) {
                $dataArray_new['type'] = 'link';
                $dataArray_new['relationship'] = $dataArray['link'];
            }
            $dataArray = $dataArray_new;
        }
        $dataObject['dataArray'] = $dataArray;
    }

    /**
     * @param $query
     * @param $data
     * @param $beanList
     * @param $field_module
     * @param $oldAlias
     * @param $field
     * @param $table_alias
     * @return array
     */
    private function BuildDataForLinkType(
        $query,
        $data,
        $beanList,
        $field_module,
        $oldAlias,
        $field,
        $table_alias
    ) {
        if ($data['type'] == 'link' && $data['source'] == 'non-db') {
            $new_field_module = new $beanList[getRelatedModule($field_module->module_dir,
                $data['relationship'])];
            $table_alias = $data['relationship'];
            $query = $this->buildReportQueryJoin($data['relationship'], $table_alias, $oldAlias,
                $field_module, 'relationship', $query, $new_field_module);
            $field_module = $new_field_module;
            $field->field = 'id';

            return array($table_alias, $query, $field_module);
        }

        return array($table_alias, $query, $field_module);
    }


    /**
     * @param $query
     * @param $data
     * @param $beanList
     * @param $field_module
     * @param $oldAlias
     * @param $field
     * @param $table_alias
     * @return array
     */
    private function BuildDataForLinkTypeChart(&$dataObject) {

        $queryArray = $dataObject['queryArray'];
        $dataArray = $dataObject['dataArray'];
        $beanList = $dataObject['beanList'];
        $field_module = $dataObject['fieldModule'];
        $oldAlias = $dataObject['oldAlias'];
        $field = $dataObject['field'];
        $table_alias = $dataObject['tableAlias'];
        if ($dataArray['type'] == 'link' && $dataArray['source'] == 'non-db') {
            $new_field_module = new $beanList[getRelatedModule($field_module->module_dir,
                $dataArray['relationship'])];
            $table_alias = $dataArray['relationship'];
            $queryArray = $this->buildReportQueryJoin($dataArray['relationship'], $table_alias, $oldAlias,
                $field_module, 'relationship', $queryArray, $new_field_module);
            $field_module = $new_field_module;
            $field->field = 'id';

            return array($table_alias, $queryArray, $field_module);
        }

        return array($table_alias, $queryArray, $field_module);
    }


    /**
     * @param $query
     * @param $data
     * @param $field_module
     * @param $table_alias
     * @return mixed
     */
    private function BuildDataForCurrencyType(
        $query,
        $data,
        $field_module,
        $table_alias
    ) {
        if ($data['type'] == 'currency' && isset($field_module->field_defs['currency_id'])) {
            if ((isset($field_module->field_defs['currency_id']['source']) && $field_module->field_defs['currency_id']['source'] == 'custom_fields')) {
                $query['select'][$table_alias . '_currency_id'] = $this->db->quoteIdentifier($table_alias . '_cstm') . ".currency_id AS '" . $table_alias . "_currency_id'";
                $query['second_group_by'][] = $this->db->quoteIdentifier($table_alias . '_cstm') . ".currency_id";

                return $query;
            } else {
                $query['select'][$table_alias . '_currency_id'] = $this->db->quoteIdentifier($table_alias) . ".currency_id AS '" . $table_alias . "_currency_id'";
                $query['second_group_by'][] = $this->db->quoteIdentifier($table_alias) . ".currency_id";

                return $query;
            }
        }

        return $query;
    }



    /**
     * @param $query
     * @param $data
     * @param $field_module
     * @param $table_alias
     * @return mixed
     */
    private function BuildDataForCurrencyTypeChart(&$dataObject) {

        $queryArray = $dataObject['queryArray'];
        $dataArray = $dataObject['dataArray'];
        $field_module = $dataObject['fieldModule'];
        $table_alias = $dataObject['tableAlias'];
        
        if ($dataArray['type'] == 'currency' && isset($field_module->field_defs['currency_id'])) {
            if ((isset($field_module->field_defs['currency_id']['source']) && $field_module->field_defs['currency_id']['source'] == 'custom_fields')) {
                $queryArray['select'][$table_alias . '_currency_id'] = $this->db->quoteIdentifier($table_alias . '_cstm') . ".currency_id AS '" . $table_alias . "_currency_id'";
                $queryArray['second_group_by'][] = $this->db->quoteIdentifier($table_alias . '_cstm') . ".currency_id";

                return $queryArray;
            } else {
                $queryArray['select'][$table_alias . '_currency_id'] = $this->db->quoteIdentifier($table_alias) . ".currency_id AS '" . $table_alias . "_currency_id'";
                $queryArray['second_group_by'][] = $this->db->quoteIdentifier($table_alias) . ".currency_id";


            }
        }
        $dataObject['queryArray']=$queryArray;

    }

    /**
     * @param $query
     * @param $data
     * @param $table_alias
     * @param $field
     * @param $field_module
     * @return array
     */
    private function BuildDataForCustomField(
        $query,
        $data,
        $table_alias,
        $field,
        $field_module
    ) {
        if ((isset($data['source']) && $data['source'] == 'custom_fields')) {
            $select_field = $this->db->quoteIdentifier($table_alias . '_cstm') . '.' . $field->field;
            $query = $this->buildReportQueryJoin($table_alias . '_cstm', $table_alias . '_cstm',
                $table_alias, $field_module, 'custom', $query);

            return array($select_field, $query);
        } else {
            $select_field = $this->db->quoteIdentifier($table_alias) . '.' . $field->field;

            return array($select_field, $query);
        }
    }



    /**
     * @param $query
     * @param $data
     * @param $table_alias
     * @param $field
     * @param $field_module
     * @return array
     */
    private function BuildDataForCustomFieldChart(&$dataObject) {

        $queryArray = $dataObject['queryArray'];
        $dataArray = $dataObject['dataArray'];
        $table_alias = $dataObject['tableAlias'];
        $field = $dataObject['field'];
        $field_module = $dataObject['fieldModule'];
        if ((isset($dataArray['source']) && $dataArray['source'] == 'custom_fields')) {
            $select_field = $this->db->quoteIdentifier($table_alias . '_cstm') . '.' . $field->field;
            $queryArray = $this->buildReportQueryJoin($table_alias . '_cstm', $table_alias . '_cstm',
                $table_alias, $field_module, 'custom', $queryArray);
        } else {
            $select_field = $this->db->quoteIdentifier($table_alias) . '.' . $field->field;
        }

        $dataObject['selectField'] = $select_field;
        $dataObject['queryArray'] = $queryArray;

    }


    /**
     * @param $field
     * @param $data
     * @param $select_field
     * @param $timedate
     * @return string
     */
    private function BuildDataForDateType(
        $field,
        $data,
        $select_field,
        $timedate
    ) {
        if ($field->format && in_array($data['type'], array('date', 'datetime', 'datetimecombo'))) {
            if (in_array($data['type'], array('datetime', 'datetimecombo'))) {
                $select_field = $this->db->convert($select_field, 'add_tz_offset');
            }
            $select_field = $this->db->convert($select_field, 'date_format',
                array($timedate->getCalFormat($field->format)));

            return $select_field;
        }

        return $select_field;
    }

    /**
     * @param $field
     * @param $data
     * @param $select_field
     * @param $timedate
     * @return string
     */
    private function BuildDataForDateTypeChart(&$dataObject) {
        $field = $dataObject['field'];
        $dataArray = $dataObject['dataArray'];
        $select_field = $dataObject['selectField'];
        $timedate =$dataObject['timeDate'];

        if ($field->format && in_array($dataArray['type'], array('date', 'datetime', 'datetimecombo'))) {
            if (in_array($dataArray['type'], array('datetime', 'datetimecombo'))) {
                $select_field = $this->db->convert($select_field, 'add_tz_offset');
            }
            $select_field = $this->db->convert($select_field, 'date_format',
                array($timedate->getCalFormat($field->format)));
        }
        $dataObject['selectField'] = $select_field;
    }

    /**
     * @param $query
     * @param $field
     * @param $table_alias
     * @return mixed
     */
    private function SetTableAlias(
        $query,
        $field,
        $table_alias
    ) {
        if ($field->link && isset($query['id_select'][$table_alias])) {
            $query['select'][] = $query['id_select'][$table_alias];
            $query['second_group_by'][] = $query['id_select_group'][$table_alias];
            unset($query['id_select'][$table_alias]);

            return $query;
        }

        return $query;
    }


    /**
     * @param $query
     * @param $field
     * @param $table_alias
     * @return mixed
     */
    private function SetTableAliasChart(&$dataObject) {
        $queryArray = $dataObject['queryArray'];
        $field = $dataObject['field'];
        $table_alias =$dataObject['tableAlias'];
        if ($field->link && isset($queryArray['id_select'][$table_alias])) {
            $queryArray['select'][] = $queryArray['id_select'][$table_alias];
            $queryArray['second_group_by'][] = $queryArray['id_select_group'][$table_alias];
            unset($queryArray['id_select'][$table_alias]);


        }
        $dataObject['queryArray'] = $queryArray;
    }

    /**
     * @param $query
     * @param $field
     * @param $select_field
     * @return array
     */
    private function SetGroupBy(
        $query,
        $field,
        $select_field
    ) {
        if ($field->group_by == 1) {
            $query['group_by'][] = $select_field;

            return array($query, $select_field);
        } elseif ($field->field_function != null) {
            $select_field = $field->field_function . '(' . $select_field . ')';

            return array($query, $select_field);
        } else {
            $query['second_group_by'][] = $select_field;

            return array($query, $select_field);
        }
    }

    /**
     * @param $query
     * @param $field
     * @param $select_field
     * @return array
     */
    private function SetGroupByChart(&$dataObject) {
        $queryArray = $dataObject['queryArray'];
        $field = $dataObject['field'];
        $select_field = $dataObject['selectField'];

        if ($field->group_by == 1) {
            $queryArray['group_by'][] = $select_field;
        } elseif ($field->field_function != null) {
            $select_field = $field->field_function . '(' . $select_field . ')';
        } else {
            $queryArray['second_group_by'][] = $select_field;
        }
        $dataObject['queryArray'] = $queryArray;
        $dataObject['selectField'] = $select_field;
    }

    /**
     * @param $query
     * @param $field
     * @param $select_field
     * @return mixed
     */
    private function SetSortBy(
        $query,
        $field,
        $select_field
    ) {
        if ($field->sort_by != '') {
            $query['sort_by'][] = $select_field . " " . $field->sort_by;

            return $query;
        }

        return $query;
    }


    /**
     * @param $query
     * @param $field
     * @param $select_field
     * @return mixed
     */
    private function SetSortByChart(&$dataObject) {
        $queryArray = $dataObject['queryArray'];
        $field = $dataObject['field'];
        $select_field = $dataObject['selectField'];
        if ($field->sort_by != '') {
            $queryArray['sort_by'][] = $select_field . " " . $field->sort_by;
        }
        $dataObject['queryArray'] = $queryArray;
    }

    private function createQueryDataArray(
        $queryArray,
        $group_value,
        $row,
        $i,
        $module,
        $beanList,
        $timedate
    ) {
        $field = new AOR_Field();
        $field->retrieve($row['id']);

        $field->label = str_replace(' ', '_', $field->label) . $i;
        $field_module = $module;
        $table_alias = $field_module->table_name;
        $oldAlias = $table_alias;

        list($oldAlias, $table_alias, $queryArray, $field_module) = $this->BuildJoinsForEachExternalRelatedField($queryArray,
            $field, $module, $beanList, $field_module, $table_alias, $oldAlias);

        $data = $this->BuildDataForRelateType($field_module, $field);

        list($table_alias, $queryArray, $field_module) = $this->BuildDataForLinkType($queryArray, $data, $beanList,
            $field_module, $oldAlias, $field, $table_alias);

        $queryArray = $this->BuildDataForCurrencyType($queryArray, $data, $field_module, $table_alias);

        list($select_field, $queryArray) = $this->BuildDataForCustomField($queryArray, $data, $table_alias, $field,
            $field_module);

        $select_field = $this->BuildDataForDateType($field, $data, $select_field, $timedate);

        $queryArray = $this->SetTableAlias($queryArray, $field, $table_alias);

        list($queryArray, $select_field) = $this->SetGroupBy($queryArray, $field, $select_field);

        $queryArray = $this->SetSortBy($queryArray, $field, $select_field);

        $queryArray['select'][] = $select_field . " AS '" . $field->label . "'";

        if ($field->group_display == 1 && $group_value) {
            $queryArray['where'][] = $select_field . " = '" . $group_value . "' AND ";
        }

        return $queryArray;
    }



    private function createQueryDataArrayChart(&$dataObject) {
        // --- sql query building
        $path = unserialize(base64_decode($dataObject['field']->module_path));
        $pathExists = !empty($path[0]);
        $PathIsNotModuleDir = $path[0] != $dataObject['reportModuleBean']->module_dir;
        if ($pathExists && $PathIsNotModuleDir) {
            foreach ($path as $relationship) {
                $this->buildReportQueryJoinChart($dataObject,$relationship,'relationship');
            }

        }
        // --- sql query building
        // --- data queryArray building
        $this->BuildDataForRelateTypeChart($dataObject);
        $this->BuildDataForLinkTypeChart($dataObject);
        $this->BuildDataForCurrencyTypeChart($dataObject);
        $this->BuildDataForCustomFieldChart($dataObject);
        $this->BuildDataForDateTypeChart($dataObject);
        $this->SetTableAliasChart($dataObject);
        $this->SetGroupByChart($dataObject);
        $this->SetSortByChart($dataObject);

        $queryArray = $dataObject['queryArray'];
        $select_field = $dataObject['selectField'];
        $queryArray['select'][] = $select_field . " AS '" . $dataObject['field']->label . "'";

        //disabled as not being set for chart creation will look into this for duplicate code createQueryDataArray
//        if ($field->group_display == 1 && $group_value) {
//            $queryArray['where'][] = $select_field . " = '" . $group_value . "' AND ";
//        }
        $dataObject['queryArray'] = $queryArray;
    }


    /**
     * @param $data
     * @param $condition
     * @param $condition_module
     * @return array
     */
    private function primeDataForRelate(
        $data,
        $condition,
        $condition_module
    ) {
        if (isset($data['id_name'])) {
            $condition->field = $data['id_name'];
            $data_new = $condition_module->field_defs[$condition->field];
            if (!empty($data_new['source']) && $data_new['source'] == 'non-db' && $data_new['type'] != 'link' && isset($data['link'])) {
                $data_new['type'] = 'link';
                $data_new['relationship'] = $data['link'];
            }
            $data = $data_new;

            return array($data, $condition);
        }

        return array($data, $condition);
    }



    private function primeDataForRelateChart($dataObject) {
        $condition = $dataObject['condition'];
        $conditionFieldDefs = $dataObject['conditionFieldDefs'];
        if (isset($conditionFieldDefs['id_name'])) {
            $condition->field = $conditionFieldDefs['id_name'];
            $data_new = $dataObject['reportModuleBean']->field_defs[$condition->field];
            if (!empty($data_new['source']) && $data_new['source'] == 'non-db' && $data_new['type'] != 'link' && isset($conditionFieldDefs['link'])) {
                $data_new['type'] = 'link';
                $data_new['relationship'] = $conditionFieldDefs['link'];
            }
            $conditionFieldDefs = $data_new;
        }
    }

    /**
     * @param $query
     * @param $data
     * @param $beanList
     * @param $condition_module
     * @param $oldAlias
     * @param $path
     * @param $relationship
     * @param $condition
     * @param $table_alias
     * @return array
     */
    private function primeDataForLink(
        $query,
        $data,
        $beanList,
        $condition_module,
        $oldAlias,
        $path,
        $relationship,
        $condition,
        $table_alias
    ) {
        if ($data['source'] == 'non-db') {
            $new_field_module = new $beanList[getRelatedModule($condition_module->module_dir,
                $data['relationship'])];
            $table_alias = $data['relationship'];
            $query = $this->buildReportQueryJoin($data['relationship'], $table_alias, $oldAlias,
                $condition_module, 'relationship', $query, $new_field_module);
            $condition_module = $new_field_module;

            // Debugging: security groups conditions - It's a hack to just get the query working
            if ($condition_module->module_dir = 'SecurityGroups' && count($path) > 1) {
                $table_alias = $oldAlias . ':' . $relationship;
            }
            $condition->field = 'id';

            return array($table_alias, $query, $condition_module);
        }

        return array($table_alias, $query, $condition_module);
    }



    private function primeDataForLinkChart(
        $query,
        $data,
        $beanList,
        $condition_module,
        $oldAlias,
        $path,
        $rel,
        $condition,
        $table_alias
    ) {
        if ($data['source'] == 'non-db') {
            $new_field_module = new $beanList[getRelatedModule($condition_module->module_dir,
                $data['relationship'])];
            $table_alias = $data['relationship'];
            $query = $this->buildReportQueryJoin($data['relationship'], $table_alias, $oldAlias,
                $condition_module, 'relationship', $query, $new_field_module);
            $condition_module = $new_field_module;

            // Debugging: security groups conditions - It's a hack to just get the query working
            if ($condition_module->module_dir = 'SecurityGroups' && count($path) > 1) {
                $table_alias = $oldAlias . ':' . $rel;
            }
            $condition->field = 'id';

            return array($table_alias, $query, $condition_module);
        }

        return array($table_alias, $query, $condition_module);
    }



    /**
     * @param $isCustomField
     * @param $tableName
     * @param $tableAlias
     * @param $fieldName
     * @param string $suffix
     * @return string
     */
    private function setFieldTablesSuffix(
        $isCustomField,
        $tableName,
        $tableAlias,
        $fieldName,
        $suffix = '_cstm'
    ) {

        if ($isCustomField) {
            $value = $tableName . $suffix . '.' . $fieldName;

            return $value;
        } else {
            $value = ($tableAlias ? "`$tableAlias`" : $tableName) . '.' . $fieldName;

            return $value;
        }
    }


    private function setFieldTablesSuffixChart(&$dataObject){
        $suffix = '_cstm';
        $tableName = $dataObject['tableAlias'];
        $tableAlias = $tableName;
        $fieldName = $dataObject['condition']->field;
        $tableName = $dataObject['tableAlias'];

        $conditionFieldDefs = $dataObject['reportModuleBean']->field_defs[$fieldName];
        $dataSourceIsSet = isset($conditionFieldDefs['source']);
        if ($dataSourceIsSet) {
            $isCustomField = ($conditionFieldDefs['source'] == 'custom_fields') ? true : false;
            if ($isCustomField) {
                $value = $tableName . $suffix . '.' . $fieldName;
            }
        }else{
            $value = ($tableAlias ? "`$tableAlias`" : $tableName) . '.' . $fieldName;
        }

        $conditionsFields = array();
        array_push($conditionsFields,$value);
        $dataObject['queryArray']['conditionFields'] = $conditionsFields;
    }

    /**
     * @param $condition
     */
    private function buildConditionParams(&$condition) {
        if (!empty($this->user_parameters[$condition->id])) {
            if ($condition->parameter) {
                $condParam = $this->user_parameters[$condition->id];
                $condition->value = $condParam['value'];
                $condition->operator = $condParam['operator'];
                $condition->value_type = $condParam['type'];
            }
        }
    }


    private function buildConditionUserParamsChart(&$dataObject) {
        $condition = $dataObject['condition'];
        $params = $dataObject['user_parameters'];
        if (!empty($params[$condition->id])) {
            if ($condition->parameter) {
                $condParam = $params[$condition->id];
                $condition->value = $condParam['value'];
                $condition->operator = $condParam['operator'];
                $condition->value_type = $condParam['type'];
            }
        }
    }

    /**
     * @param $isCustomField
     * @param $query
     * @param $table_alias
     * @param $tableName
     * @param $condition_module
     * @return string
     * @internal param $data
     */
    private function buildJoinQueryForCustomFields(
        $isCustomField,
        $query,
        $table_alias,
        $tableName,
        $condition_module
    ) {
        if ($isCustomField) {

            $query = $this->buildReportQueryJoin(
                $tableName . '_cstm',
                $table_alias . '_cstm',
                $table_alias,
                $condition_module,
                'custom',
                $query);

            return $query;
        }

        return $query;
    }


    private function buildJoinQueryForCustomFieldsChart(&$dataObject) {

        $query = $dataObject['queryArray'];
        $table_alias = $dataObject['tableAlias'];
        $tableName = $dataObject['tableAlias'];
        $condition_module = $dataObject['reportModuleBean'];
        $fieldName = $dataObject['condition']->field;

        $conditionFieldDefs = $dataObject['reportModuleBean']->field_defs[$fieldName];
        $dataSourceIsSet = isset($conditionFieldDefs['source']);
        if ($dataSourceIsSet) {
            $isCustomField = ($conditionFieldDefs['source'] == 'custom_fields') ? true : false;
            if ($isCustomField) {
                $query = $this->buildReportQueryJoin(
                $tableName . '_cstm',
                $table_alias . '_cstm',
                $table_alias,
                $condition_module,
                'custom',
                $query);
            }

        }
        $dataObject['queryArray'] =  $query;
    }



    /**
     * @param $firstParam
     * @param $sugar_config
     * @param $field
     * @return array
     */
    private function processForDateFrom($firstParam, $sugar_config, $field, $query, $condition_module)
    {
        switch ($firstParam) {
            case 'now':
                if ($sugar_config['dbconfig']['db_type'] == 'mssql') {
                    $value = 'GetDate()';
                } else {
                    $value = 'NOW()';
                }
                break;
            case 'today':
                if ($sugar_config['dbconfig']['db_type'] == 'mssql') {
                    //$field =
                    $value = 'CAST(GETDATE() AS DATE)';
                } else {
                    $field = 'DATE(' . $field . ')';
                    $value = 'Curdate()';
                }
                break;
            default:
                $data = $condition_module->field_defs[$firstParam];
                $tableName = $condition_module->table_name;
                $table_alias = $tableName;
                $fieldName = $firstParam;
                $dataSourceIsSet = isset($data['source']);
                if ($dataSourceIsSet) {
                    $isCustomField = ($data['source'] == 'custom_fields') ? true : false;
                }

                //setValueSuffix
                $value = $this->setFieldTablesSuffix($isCustomField, $tableName, $table_alias,
                    $fieldName);
                $query = $this->buildJoinQueryForCustomFields($isCustomField, $query,
                    $table_alias, $tableName, $condition_module);


                break;
        }

        return array($value, $field, $query);
    }

    /**
     * @param $secondParam
     * @param $fourthParam
     * @param $sugar_config
     * @param $app_list_strings
     * @param $thirdParam
     * @param $value
     * @return string
     */
    private function processForDateOther(
        $secondParam,
        $fourthParam,
        $sugar_config,
        $app_list_strings,
        $thirdParam,
        $value
    ) {
        if ($secondParam != 'now') {
            switch ($fourthParam) {
                case 'business_hours';
                    //business hours not implemented for query, default to hours
                    $fourthParam = 'hours';
                default:
                    if ($sugar_config['dbconfig']['db_type'] == 'mssql') {
                        $value = "DATEADD(" . $fourthParam . ",  " . $app_list_strings['aor_date_operator'][$secondParam] . " $thirdParam, $value)";
                    } else {
                        $value = "DATE_ADD($value, INTERVAL " . $app_list_strings['aor_date_operator'][$secondParam] . " $thirdParam " . $fourthParam . ")";
                    }
                    break;
            }

            return $value;
        }

        return $value;
    }

    /**
     * @param $query
     * @param $conditionType
     * @param $condition_module
     * @param $condition
     * @param $beanList
     * @param $oldAlias
     * @param $path
     * @param $rel
     * @param $table_alias
     * @param $sugar_config
     * @param $field
     * @param $app_list_strings
     * @param $aor_sql_operator_list
     * @param $tiltLogicOp
     * @param $current_user
     * @return array
     */
    private function buildQueryForConditionType(
        $query,
        $conditionType,
        $condition_module,
        $condition,
        $beanList,
        $oldAlias,
        $path,
        $rel,
        $table_alias,
        $sugar_config,
        $field,
        $app_list_strings,
        $aor_sql_operator_list,
        $tiltLogicOp
    ) {
        switch ($conditionType) {
            case 'Field': // is it a specific field
                //processWhereConditionForTypeField
                $data = $condition_module->field_defs[$condition->value];

                switch ($data['type']) {
                    case 'relate':
                        list($data, $condition) = $this->primeDataForRelate($data, $condition,
                            $condition_module);
                        break;
                    case 'link':
                        list($table_alias, $query, $condition_module) = $this->primeDataForLink($query,
                            $data, $beanList, $condition_module, $oldAlias, $path, $rel, $condition,
                            $table_alias);
                        break;
                }


                $tableName = $condition_module->table_name;
                $fieldName = $condition->value;
                $dataSourceIsSet = isset($data['source']);
                if ($dataSourceIsSet) {
                    $isCustomField = ($data['source'] == 'custom_fields') ? true : false;
                }

                //setValueSuffix
                $value = $this->setFieldTablesSuffix($isCustomField, $tableName, $table_alias, $fieldName);
                $query = $this->buildJoinQueryForCustomFields($isCustomField, $query, $table_alias,
                    $tableName,
                    $condition_module);
                break;

            case 'Date': //is it a date
                //processWhereConditionForTypeDate
                $params = unserialize(base64_decode($condition->value));

                // Fix for issue #1272 - AOR_Report module cannot update Date type parameter.
                if ($params == false) {
                    $params = $condition->value;
                }

                $firstParam = $params[0];
                list($value, $field, $query) = $this->processForDateFrom(
                    $firstParam,
                    $sugar_config,
                    $field,
                    $query,
                    $condition_module);

                $secondParam = $params[1];
                $thirdParam = $params[2];
                $fourthParam = $params[3];
                $value = $this->processForDateOther($secondParam, $fourthParam, $sugar_config,
                    $app_list_strings, $thirdParam, $value);

                break;

            case 'Multi': //are there multiple conditions setup
                //processWhereConditionForTypeMulti
                $sep = ' AND ';
                if ($condition->operator == 'Equal_To') {
                    $sep = ' OR ';
                }
                $multi_values = unencodeMultienum($condition->value);
                if (!empty($multi_values)) {
                    $value = '(';
                    foreach ($multi_values as $multi_value) {
                        if ($value != '(') {
                            $value .= $sep;
                        }
                        $value .= $field . ' ' . $aor_sql_operator_list[$condition->operator] . " '" . $multi_value . "'";
                    }
                    $value .= ')';
                }
                $query['where'][] = ($tiltLogicOp ? '' : ($condition->logic_op ? $condition->logic_op . ' ' : 'AND ')) . $value;
                $where_set = true;
                break;
            case "Period": //is it a period of time
                //processWhereConditionForTypePeriod
                if (array_key_exists($condition->value, $app_list_strings['date_time_period_list'])) {
                    $params = $condition->value;
                } else {
                    $params = base64_decode($condition->value);
                }
                $value = '"' . getPeriodDate($params)->format('Y-m-d H:i:s') . '"';
                break;
            case "CurrentUserID": //not sure what this is for
                //processWhereConditionForTypeCurrentUser
                global $current_user;
                $value = '"' . $current_user->id . '"';
                break;
            case 'Value': //is it a specific value
                //processWhereConditionForTypeValue
                $value = "'" . $this->db->quote($condition->value) . "'";
                break;
            default:
                $value = "'" . $this->db->quote($condition->value) . "'";
                break;
        }

        return array(
            $data,
            $condition,
            $table_alias,
            $query,
            $condition_module,
            $tableName,
            $fieldName,
            $dataSourceIsSet,
            $isCustomField,
            $value,
            $params,
            $field,
            $where_set,
            $current_user
        );
    }



    private function buildQueryForConditionTypeChart(&$dataObject,$sugar_config,$app_list_strings) {
        $path = unserialize(base64_decode($dataObject['field']->module_path));
        $relationship ='';//TODO: need to try and figure out where this should come from
        $table_alias = $dataObject['tableAlias'];
        $field = $dataObject['queryArray']['conditionFields'][0];
        $aor_sql_operator_list = $dataObject['allowedOperatorList'];
        $tiltLogicOp = $dataObject['tiltLogicOperator'];
        switch ($dataObject['condition']->value_type) {
            case 'Field': // is it a specific field
                //processWhereConditionForTypeField
                $data = $dataObject['reportModuleBean']->field_defs[$dataObject['condition']->value];

                switch ($data['type']) {
                    case 'relate':
                        list($data, $dataObject['condition']) = $this->primeDataForRelate($data, $dataObject['condition'],
                            $dataObject['reportModuleBean']);
                        break;
                    case 'link':
                        list($table_alias, $dataObject['queryArray'], $dataObject['reportModuleBean']) = $this->primeDataForLink($dataObject['queryArray'],
                            $data, $dataObject['beanList'], $dataObject['reportModuleBean'], $dataObject['oldAlias'], $path, $relationship, $dataObject['condition'],
                            $table_alias);
                        break;
                }


                $tableName = $dataObject['reportModuleBean']->table_name;
                $fieldName = $dataObject['condition']->value;
                $dataSourceIsSet = isset($data['source']);
                if ($dataSourceIsSet) {
                    $isCustomField = ($data['source'] == 'custom_fields') ? true : false;
                }

                //setValueSuffix
                $value = $this->setFieldTablesSuffix($isCustomField, $tableName, $table_alias, $fieldName);
                $dataObject['queryArray'] = $this->buildJoinQueryForCustomFields($isCustomField, $dataObject['queryArray'], $table_alias,
                    $tableName,
                    $dataObject['reportModuleBean']);
                break;

            case 'Date': //is it a date
                //processWhereConditionForTypeDate
                $params = unserialize(base64_decode($dataObject['condition']->value));

                // Fix for issue #1272 - AOR_Report module cannot update Date type parameter.
                if ($params == false) {
                    $params = $dataObject['condition']->value;
                }

                $firstParam = $params[0];
                list($value, $field, $dataObject['queryArray']) = $this->processForDateFrom(
                    $firstParam,
                    $sugar_config,
                    $field,
                    $dataObject['queryArray'],
                    $dataObject['reportModuleBean']);

                $secondParam = $params[1];
                $thirdParam = $params[2];
                $fourthParam = $params[3];
                $value = $this->processForDateOther($secondParam, $fourthParam, $sugar_config,
                    $app_list_strings, $thirdParam, $value);

                break;

            case 'Multi': //are there multiple conditions setup
                //processWhereConditionForTypeMulti
                $sep = ' AND ';
                if ($dataObject['condition']->operator == 'Equal_To') {
                    $sep = ' OR ';
                }
                $multi_values = unencodeMultienum($dataObject['condition']->value);
                if (!empty($multi_values)) {
                    $value = '(';
                    foreach ($multi_values as $multi_value) {
                        if ($value != '(') {
                            $value .= $sep;
                        }
                        $value .= $field . ' ' . $aor_sql_operator_list[$dataObject['condition']->operator] . " '" . $multi_value . "'";
                    }
                    $value .= ')';
                }
                $dataObject['queryArray']['where'][] = ($tiltLogicOp ? '' : ($dataObject['condition']->logic_op ? $dataObject['condition']->logic_op . ' ' : 'AND ')) . $value;
                $where_set = true;
                break;
            case "Period": //is it a period of time
                //processWhereConditionForTypePeriod
                if (array_key_exists($dataObject['condition']->value, $app_list_strings['date_time_period_list'])) {
                    $params = $dataObject['condition']->value;
                } else {
                    $params = base64_decode($dataObject['condition']->value);
                }
                $value = '"' . getPeriodDate($params)->format('Y-m-d H:i:s') . '"';
                break;
            case "CurrentUserID": //not sure what this is for
                //processWhereConditionForTypeCurrentUser
                global $current_user;
                $value = '"' . $current_user->id . '"';
                break;
            case 'Value': //is it a specific value
                //processWhereConditionForTypeValue
                $value = "'" . $this->db->quote($dataObject['condition']->value) . "'";
                break;
            default:
                $value = "'" . $this->db->quote($dataObject['condition']->value) . "'";
                break;
        }

        return array(
            $dataObject['condition'],
            $dataObject['queryArray'],
            $value,
            $field,
            $where_set
        );
    }


    /**
     * @param $conditionOperator
     * @param $value
     * @return string
     */
    private function handleLikeConditions($conditionOperator, $value)
    {
        Switch ($conditionOperator) {
            case 'Contains':
                $value = "CONCAT('%', " . $value . " ,'%')";
                break;
            case 'Starts_With':
                $value = "CONCAT(" . $value . " ,'%')";
                break;
            case 'Ends_With':
                $value = "CONCAT('%', " . $value . ")";
                break;
        }

        return $value;
    }

    /**
     * @param $query
     * @param $where_set
     * @param $condition
     * @param $app_list_strings
     * @param $tiltLogicOp
     * @param $aor_sql_operator_list
     * @param $field
     * @param $value
     * @return array
     */
    private function whereNotSet(
        $query,
        $where_set,
        $condition,
        $app_list_strings,
        $tiltLogicOp,
        $aor_sql_operator_list,
        $field,
        $value
    ) {
        if (!$where_set) {
            if ($condition->value_type == "Period") {
                if (array_key_exists($condition->value, $app_list_strings['date_time_period_list'])) {
                    $params = $condition->value;
                } else {
                    $params = base64_decode($condition->value);
                }
                $date = getPeriodEndDate($params)->format('Y-m-d H:i:s');
                $value = '"' . getPeriodDate($params)->format('Y-m-d H:i:s') . '"';

                $query['where'][] = ($tiltLogicOp ? '' : ($condition->logic_op ? $condition->logic_op . ' ' : 'AND '));
                $tiltLogicOp = false;

                switch ($aor_sql_operator_list[$condition->operator]) {
                    case "=":
                        $query['where'][] = $field . ' BETWEEN ' . $value . ' AND ' . '"' . $date . '"';
                        break;
                    case "!=":
                        $query['where'][] = $field . ' NOT BETWEEN ' . $value . ' AND ' . '"' . $date . '"';
                        break;
                    case ">":
                    case "<":
                    case ">=":
                    case "<=":
                        $query['where'][] = $field . ' ' . $aor_sql_operator_list[$condition->operator] . ' ' . $value;
                        break;
                }

                return array($value, $query, $tiltLogicOp);
            } else {
                $query['where'][] = ($tiltLogicOp ? '' : ($condition->logic_op ? $condition->logic_op . ' ' : 'AND ')) . $field . ' ' . $aor_sql_operator_list[$condition->operator] . ' ' . $value;

                return  $query;
            }
        }

        return $query;
    }


    private function checkIfUserIsAllowedAccessToRelatedModules($rowArray, $bean, $beanList)
    {
        $isAllowed = true;
        foreach ($rowArray as $row) {
            $condition = new AOR_Condition();
            $condition->retrieve($row['id']);

            //path is stored as base64 encoded serialized php object
            $path = unserialize(base64_decode($condition->module_path));

            $condition_module = $bean;
            $isRelationshipExternalModule = !empty($path[0]) && $path[0] != $bean->module_dir;
            if ($isRelationshipExternalModule) {
                //loop over each relationship field and check if allowed access
                foreach ($path as $rel) {
                    if (!empty($rel)) {
                        // Bug: Prevents relationships from loading.
                        $new_condition_module = new $beanList[getRelatedModule($condition_module->module_dir, $rel)];
                        //Check if the user has access to the related module
                        if (!(ACLController::checkAccess($new_condition_module->module_name, 'list', true))) {
                            $isAllowed = false;
                        }
                    }
                }
            }

        }

        return $isAllowed;
    }


    private function checkIfUserIsAllowedAccessToRelatedModulesChart($rowArray, $dataObject)
    {
        $bean = $dataObject['reportModuleBean'];
        $beanList = $dataObject['beanList'];
        $isAllowed = true;
        foreach ($rowArray as $row) {
            $condition = new AOR_Condition();
            $condition->retrieve($row['id']);

            //path is stored as base64 encoded serialized php object
            $path = unserialize(base64_decode($condition->module_path));

            $condition_module = $bean;
            $isRelationshipExternalModule = !empty($path[0]) && $path[0] != $bean->module_dir;
            if ($isRelationshipExternalModule) {
                //loop over each relationship field and check if allowed access
                foreach ($path as $rel) {
                    if (!empty($rel)) {
                        // Bug: Prevents relationships from loading.
                        $new_condition_module = new $beanList[getRelatedModule($condition_module->module_dir, $rel)];
                        //Check if the user has access to the related module
                        if (!(ACLController::checkAccess($new_condition_module->module_name, 'list', true))) {
                            $isAllowed = false;
                        }
                    }
                }
            }

        }

        return $isAllowed;
    }


    /**
     * @param $query_array
     * @param $query
     * @return array
     */
    private function buildSqlQuerySelect($query_array, $query)
    {
        foreach ($query_array['select'] as $select) {
            $query .= ($query == '' ? 'SELECT ' : ', ') . $select;
        }

        return $query;
    }


    private function buildSqlQuerySelectChart(&$dataObject)
    {
        foreach ($dataObject['queryArray']['select'] as $select) {
            $dataObject['sqlQuery'] .= ($dataObject['sqlQuery'] == '' ? 'SELECT ' : ', ') . $select;
        }
    }


    /**
     * @param $query_array
     * @param $query
     * @return array
     */
    private function buildSqlQueryGroupBy($query_array, $query)
    {
        if (empty($query_array['group_by'])) {
            foreach ($query_array['id_select'] as $select) {
                $query .= ', ' . $select;
            }

            return $query;
        }

        return $query;
    }


    private function buildSqlQueryGroupByChart(&$dataObject)
    {
        if (empty($dataObject['queryArray']['group_by'])) {
            foreach ($dataObject['queryArray']['id_select'] as $select) {
                $dataObject['sqlQuery'] .= ', ' . $select;
            }
        }
    }

    /**
     * @param $tableName
     * @param $query
     * @return string
     * @internal param $module
     */
    private function buildSqlQueryFrom($query_array, $query)
    {
        $query .= ' FROM ' . $this->db->quoteIdentifier($query_array['tableName']) . ' ';

        return $query;
    }

    private function buildSqlQueryFromChart(&$dataObject)
    {
        $dataObject['sqlQuery'] .= ' FROM ' . $this->db->quoteIdentifier($dataObject['queryArray']['tableName']) . ' ';
    }

    /**
     * @param $query_array
     * @param $query
     * @return array
     */
    private function buildSqlQueryJoin($query_array, $query)
    {
        if (isset($query_array['join'])) {
            foreach ($query_array['join'] as $join) {
                $query .= $join;
            }

            return $query;
        }

        return $query;
    }

    private function buildSqlQueryJoinChart(&$dataObject)
    {
        if (isset($dataObject['queryArray']['join'])) {
            foreach ($dataObject['queryArray']['join'] as $join) {
                $dataObject['sqlQuery'] .= $join;
            }
        }
    }

    /**
     * @param $query_array
     * @param $query
     * @return array
     */
    private function buildSqlQueryWhere($query_array, $query)
    {
        if (isset($query_array['where'])) {
            $query_where = '';
            foreach ($query_array['where'] as $where) {
                $query_where .= ($query_where == '' ? 'WHERE ' : ' ') . $where;
            }
            $query_where = $this->queryWhereRepair($query_where);
            $query .= ' ' . $query_where;

            return $query;
        }

        return $query;
    }


    private function buildSqlQueryWhereChart(&$dataObject)
    {
        if (isset($dataObject['queryArray']['where'])) {
            $query_where = '';
            foreach ($dataObject['queryArray']['where'] as $where) {
                $query_where .= ($query_where == '' ? 'WHERE ' : ' ') . $where;
            }
            $query_where = $this->queryWhereRepair($query_where);
            $dataObject['sqlQuery'] .= ' ' . $query_where;
        }
    }

    /**
     * @param $query_array
     * @param $query
     * @return array
     */
    private function buildSqlQueryGroupBy2($query_array, $query)
    {
        if (isset($query_array['group_by'])) {
            $query_group_by = '';
            foreach ($query_array['group_by'] as $group_by) {
                $query_group_by .= ($query_group_by == '' ? 'GROUP BY ' : ', ') . $group_by;
            }
            if (isset($query_array['second_group_by']) && $query_group_by != '') {
                foreach ($query_array['second_group_by'] as $group_by) {
                    $query_group_by .= ', ' . $group_by;
                }
            }
            $query .= ' ' . $query_group_by;

            return $query;
        }

        return $query;
    }



    private function buildSqlQueryGroupBy2Chart(&$dataObject)
    {
        if (isset($dataObject['queryArray']['group_by'])) {
            $query_group_by = '';
            foreach ($dataObject['queryArray']['group_by'] as $group_by) {
                $query_group_by .= ($query_group_by == '' ? 'GROUP BY ' : ', ') . $group_by;
            }
            if (isset($dataObject['queryArray']['second_group_by']) && $query_group_by != '') {
                foreach ($dataObject['queryArray']['second_group_by'] as $group_by) {
                    $query_group_by .= ', ' . $group_by;
                }
            }
            $dataObject['sqlQuery'] .= ' ' . $query_group_by;
        }
    }



    /**
     * @param $query_array
     * @param $query
     * @return string
     */
    private function buildSqlQuerySortBy($query_array, $query)
    {
        if (isset($query_array['sort_by'])) {
            $query_sort_by = '';
            foreach ($query_array['sort_by'] as $sort_by) {
                $query_sort_by .= ($query_sort_by == '' ? 'ORDER BY ' : ', ') . $sort_by;
            }
            $query .= ' ' . $query_sort_by;

            return $query;
        }

        return $query;
    }


    private function buildSqlQuerySortByChart(&$dataObject)
    {
        if (isset($dataObject['queryArray']['sort_by'])) {
            $query_sort_by = '';
            foreach ($dataObject['queryArray']['sort_by'] as $sort_by) {
                $query_sort_by .= ($query_sort_by == '' ? 'ORDER BY ' : ', ') . $sort_by;
            }
            $dataObject['sqlQuery'] .= ' ' . $query_sort_by;
        }
    }

    /**
     * @param $chartIds
     * @param $chartType
     * @param $data
     * @param $fields
     * @param $mainGroupField
     * @return string
     */
    private function StartBuildChartHTML($chartIds, $chartType, $data, $fields, $mainGroupField)
    {
        switch ($chartType) {
            case self::CHART_TYPE_PCHART:
                $html = '<script src="modules/AOR_Charts/lib/pChart/imagemap.js"></script>';
                break;
            case self::CHART_TYPE_CHARTJS:
                $html = '<script src="modules/AOR_Reports/js/Chart.js"></script>';
                break;
            case self::CHART_TYPE_RGRAPH:
                if ($_REQUEST['module'] != 'Home') {
                    require_once('include/SuiteGraphs/RGraphIncludes.php');
                }

                break;
        }
        $x = 0;

        $linkedCharts = $this->get_linked_beans('aor_charts', 'AOR_Charts');
        if (!$linkedCharts) {
            //No charts to display
            return '';
        }

        foreach ($linkedCharts as $chart) {
            if ($chartIds !== null && !in_array($chart->id, $chartIds)) {
                continue;
            }
            $html .= $chart->buildChartHTML($data, $fields, $x, $chartType, $mainGroupField);
            $x++;
        }

        return $html;
    }

    /**
     * @return bool|resource
     */
    private function getResultRows()
    {
        $sql = "SELECT id FROM aor_fields WHERE aor_report_id = '" . $this->id . "' AND deleted = 0 ORDER BY field_order ASC";
        $result = $this->db->query($sql);
        $ResultRowArray = array();
        while ($row = $this->db->fetchByAssoc($result)) {
            array_push($ResultRowArray, $row);
        }

        return $ResultRowArray;
    }

    /**
     * @param $rowArray
     * @param $mainGroupField
     * @return AOR_Field
     */
    private function getMainGroupSet($rowArray)
    {
        $mainGroupField = 'NOTSET';
        foreach ($rowArray as $row) {

            $field = new AOR_Field();
            $field->retrieve($row['id']);

            // get the main group

            if ($field->group_display) {

                // if we have a main group already thats wrong cause only one main grouping field possible
                if ( $mainGroupField=='NOTSET') {
                    $GLOBALS['log']->fatal('main group already found');
                }

                $mainGroupField = $field;
            }
        }

        return $mainGroupField;
    }

    /**
     * @param $queryDataArray
     * @param $bean
     * @return array
     * @internal param $moduleName
     */
    private function DataArrayGetTableData(&$dataObject)
    {

        $bean = $dataObject['reportModuleBean'];
        $queryDataArray['tableName'] = $bean->table_name;
        $queryDataArray['id_select'][$bean->table_name] = $bean->db->quoteIdentifier($bean->table_name) . ".id AS '" . $bean->table_name . "_id'";
        $queryDataArray['id_select_group'][$bean->table_name] = $bean->db->quoteIdentifier($bean->table_name) . ".id";

        $dataObject['queryArray'] =  $queryDataArray;
    }
}