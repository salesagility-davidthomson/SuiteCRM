<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 09/02/17
 * Time: 15:50
 */
namespace modules\AOR_Reports\models\report;

include_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'rootPath.php';
include_once ROOTPATH.'/modules/AOR_Reports/models/report/AbstractReport.php';
include_once ROOTPATH.'/modules/AOR_Fields/AOR_Field.php';

use modules\AOR_Reports\models\markup\MarkupFactory;
use modules\AOR_Reports\models\query\QueryFactory;

class ChartReport extends AbstractReport
{
    public function  __construct(QueryFactory $queryFactory,MarkupFactory $markupFactory)
    {
        parent::__construct($queryFactory, $markupFactory);
    }

    protected function generateReport()
    {
        // TODO: Implement generateReport() method.
        $this->createLabelData();
        $test = '';
        $qf = $this->getQueryFactory();/** @var QueryFactory $qf  */
        $query = $qf->makeQuery('MySQL');

    }


    /**
     *
     */
    private function createLabelData() {
        $i = 0;
        $bean = $this->getBean();
        $report_module = $bean->report_module;
        $fields = $this->getFields();
        if($this->getMainGroupField() == ''){
            //TODO: investigate if maingroup field need to be set this way
            $mainGroupField = null; //need to set so AOR_Chart->buildChartHTML functions correctly
        }

        while ($row = $bean->db->fetchByAssoc($this->getResult())) {

            $field = new \AOR_Field();
            $field->retrieve($row['id']);

            $path = unserialize(base64_decode($field->module_path));
            $beanlist = $this->getBeanList();
            $field_bean = new $beanlist[$this->getBean()->report_module]();

            $field_module = $report_module;
            $field_alias = $field_bean->table_name;
            if ($path[0] != $report_module) {
                foreach ($path as $rel) {
                    if (empty($rel)) {
                        continue;
                    }
                    $field_module = getRelatedModule($field_module, $rel);//TODO: remove dependence on AOW_util
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
            $this->setFields($fields);

            // get the main group

            if ($field->group_display) {

                // if we have a main group already that's wrong because only one main grouping field possible
                if (!is_null($this->getMainGroupField())) {
                    $GLOBALS['log']->fatal('main group already found');
                }

                $this->setMainGroupField($field);
            }

            ++$i;
        }
    }



    /**
     * @param $result
     * @param $fields
     * @return array
     */
    private function BuildDataRowsForChart() {
        $bean = $this->getBean();
        $fields = $this->getFields();
        $data = array();
        while ($row = $bean->db->fetchByAssoc($this->getResult(),false)) {
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
    private function BuildDataForRelateType() {
        $field = new \AOR_Field();
        $beanList = $this->$this->getBeanList();
        $field_module = new $beanList[$this->report_module]();

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
        $data,
        $beanList,
        $field_module,
        $field,
        $table_alias
    ) {
        if ($data['type'] == 'link' && $data['source'] == 'non-db') {
            $new_field_module = new $beanList[getRelatedModule($field_module->module_dir, $data['relationship'])];
            $table_alias = $data['relationship'];

            $field_module = $new_field_module;
            $field->field = 'id';

            return array($table_alias, $field_module);
        }

        return array($table_alias, $field_module);
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

            return array($data, $select_field, $query);
        } else {
            $select_field = $this->db->quoteIdentifier($table_alias) . '.' . $field->field;

            return array($data, $select_field, $query);
        }
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

    /**
     * @param $query
     * @param $data
     * @param $beanList
     * @param $condition_module
     * @param $oldAlias
     * @param $path
     * @param $rel
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


    /**
     * @param $condition
     */
    private function buildConditionParams($condition) {
        if (!empty($this->user_parameters[$condition->id])) {
            if ($condition->parameter) {
                $condParam = $this->user_parameters[$condition->id];
                $condition->value = $condParam['value'];
                $condition->operator = $condParam['operator'];
                $condition->value_type = $condParam['type'];
            }
        }
    }


    /**
     * @param $rowArray
     * @param $module
     * @param $beanList
     * @return array
     */
    private function checkIfUserIsAllowedAccessToRelatedModules($rowArray, $module, $beanList)
    {
        $isAllowed = true;
        foreach ($rowArray as $row) {
            $condition = new AOR_Condition();
            $condition->retrieve($row['id']);

            //path is stored as base64 encoded serialized php object
            $path = unserialize(base64_decode($condition->module_path));

            $condition_module = $module;
            $isRelationshipExternalModule = !empty($path[0]) && $path[0] != $module->module_dir;
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


}