<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 09/02/17
 * Time: 15:50
 */
namespace modules\AOR_Reports\models\query;
include_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'rootPath.php';

class MysqlQuery extends AbstractQuery
{


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

    public function  __construct()
    {
        parent::__construct();
    }

    public function buildQuery()
    {
        // TODO: Implement buildQuery() method.
        $this->buildReportQueryChart();
    }

    /**
     * @param string $group_value
     * @param array $extra
     * @return array|string
     * @throws Exception
     */
    private function buildReportQueryChart($group_value = '', $extra = array())
    {
        //Check if the user has access to the target module
        if (!(ACLController::checkAccess($this->report_module, 'list', true))) {
            throw new Exception('User Not Allowed Access To This Module', 101);
        }

        $beanList =$this->getBeanList();
        $module = new $beanList[$this->report_module]();
        $query = '';
        $query_array = array();

        $query_array = $this->buildQueryArraySelectForChart($query_array, $group_value);

        try {
            $query_array = $this->buildQueryArrayWhere($query_array, $extra);
        } catch (Exception $e) {
            throw new Exception('Caught exception:' . $e->getMessage(), $e->getCode());
        }

        $query = $this->buildQuerySelect($query_array, $query);

        $query = $this->buildQueryGroupBy($query_array, $query);

        $query = $this->buildQueryFrom($module, $query);

        $query = $this->buildQueryJoin($query_array, $query);

        $query = $this->buildQueryWhere($query_array, $query);

        $query = $this->buildQueryGroupBy2($query_array, $query);

        $query = $this->buildQuerySortBy($query_array, $query);

        return $query;

    }



    /**
     * @param $query
     * @param $group_value
     * @param $row
     * @param $i
     * @param $module
     * @param $beanList
     * @param $timedate
     * @return mixed
     * @internal param $chartbean
     */
    private function createQuery(
        $query,
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

        list($oldAlias, $table_alias, $query, $field_module) = $this->BuildJoinsForEachExternalRelatedField($query,
            $field, $module, $beanList, $field_module, $table_alias, $oldAlias);

        $data = $this->BuildDataForRelateType($field_module, $field);

        list($table_alias, $query, $field_module) = $this->BuildDataForLinkType($query, $data, $beanList,
            $field_module, $oldAlias, $field, $table_alias);

        $query = $this->BuildDataForCurrencyType($query, $data, $field_module, $table_alias);

        list($data, $select_field, $query) = $this->BuildDataForCustomField($query, $data, $table_alias, $field,
            $field_module);

        $select_field = $this->BuildDataForDateType($field, $data, $select_field, $timedate);

        $query = $this->SetTableAlias($query, $field, $table_alias);

        list($query, $select_field) = $this->SetGroupBy($query, $field, $select_field);

        $query = $this->SetSortBy($query, $field, $select_field);

        $query['select'][] = $select_field . " AS '" . $field->label . "'";

        if ($field->group_display == 1 && $group_value) {
            $query['where'][] = $select_field . " = '" . $group_value . "' AND ";
        }

        return $query;
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

                return array($value, $query);
            }
        }

        return array($value, $query);
    }



    /**
     * @param $query_array
     * @param $query
     * @return array
     */
    private function buildQuerySelect($query_array, $query)
    {
        foreach ($query_array['select'] as $select) {
            $query .= ($query == '' ? 'SELECT ' : ', ') . $select;
        }

        return $query;
    }

    /**
     * @param $query_array
     * @param $query
     * @return array
     */
    private function buildQueryGroupBy($query_array, $query)
    {
        if (empty($query_array['group_by'])) {
            foreach ($query_array['id_select'] as $select) {
                $query .= ', ' . $select;
            }

            return $query;
        }

        return $query;
    }

    /**
     * @param $module
     * @param $query
     * @return string
     */
    private function buildQueryFrom($module, $query)
    {
        $query .= ' FROM ' . $this->db->quoteIdentifier($module->table_name) . ' ';

        return $query;
    }

    /**
     * @param $query_array
     * @param $query
     * @return array
     */
    private function buildQueryJoin($query_array, $query)
    {
        if (isset($query_array['join'])) {
            foreach ($query_array['join'] as $join) {
                $query .= $join;
            }

            return $query;
        }

        return $query;
    }

    /**
     * @param $query_array
     * @param $query
     * @return array
     */
    private function buildQueryWhere($query_array, $query)
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

    /**
     * @param $query_array
     * @param $query
     * @return array
     */
    private function buildQueryGroupBy2($query_array, $query)
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

    /**
     * @param $query_array
     * @param $query
     * @return string
     */
    private function buildQuerySortBy($query_array, $query)
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

}