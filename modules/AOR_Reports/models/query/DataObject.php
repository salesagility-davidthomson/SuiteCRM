<?php
namespace  modules\AOR_Reports\models\query;

class DataObject
{
    private $reportId = null;
    private $beanList = null;
    private $queryArray = null;
    private $sqlQuery = null;
    private $dataArray = null;
    private $field = null;
    private $reportModuleBean = null;
    private $relatedModuleBean = null;
    private $tableAlias = null;
    private $oldAlias = null;
    private $timeDate = null;
    private $currentFieldString = null;
    private $condition = null;
    private $conditionFieldDefs = null;
    private $tiltLogicOperator = true;
    private $allowedOperatorList = null;
    private $fieldMetaData = null;
    private $WhereStatement;

    public function __construct(){

    }

    /**
     * @return null
     */
    public function getReportId()
    {
        return $this->reportId;
    }

    /**
     * @param null $reportId
     * @return DataObject
     */
    public function setReportId($reportId)
    {
        $this->reportId = $reportId;

        return $this;
    }

    /**
     * @return null
     */
    public function getBeanList()
    {
        return $this->beanList;
    }

    /**
     * @param null $beanList
     * @return DataObject
     */
    public function setBeanList($beanList)
    {
        $this->beanList = $beanList;

        return $this;
    }

    /**
     * @return null
     */
    public function getQueryArray()
    {
        return $this->queryArray;
    }

    /**
     * @param null $queryArray
     * @return DataObject
     */
    public function setQueryArray($queryArray)
    {
        $this->queryArray = $queryArray;

        return $this;
    }

    /**
     * @return null
     */
    public function getSqlQuery()
    {
        return $this->sqlQuery;
    }

    /**
     * @param null $sqlQuery
     * @return DataObject
     */
    public function setSqlQuery($sqlQuery)
    {
        $this->sqlQuery = $sqlQuery;

        return $this;
    }

    /**
     * @return null
     */
    public function getDataArray()
    {
        return $this->dataArray;
    }

    /**
     * @param null $dataArray
     * @return DataObject
     */
    public function setDataArray($dataArray)
    {
        $this->dataArray = $dataArray;

        return $this;
    }

    /**
     * @return null
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param null $field
     * @return DataObject
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * @return null
     */
    public function getReportModuleBean()
    {
        return $this->reportModuleBean;
    }

    /**
     * @param null $reportModuleBean
     * @return DataObject
     */
    public function setReportModuleBean($reportModuleBean)
    {
        $this->reportModuleBean = $reportModuleBean;

        return $this;
    }

    /**
     * @return null
     */
    public function getRelatedModuleBean()
    {
        return $this->relatedModuleBean;
    }

    /**
     * @param null $relatedModuleBean
     * @return DataObject
     */
    public function setRelatedModuleBean($relatedModuleBean)
    {
        $this->relatedModuleBean = $relatedModuleBean;

        return $this;
    }

    /**
     * @return null
     */
    public function getTableAlias()
    {
        return $this->tableAlias;
    }

    /**
     * @param null $tableAlias
     * @return DataObject
     */
    public function setTableAlias($tableAlias)
    {
        $this->tableAlias = $tableAlias;

        return $this;
    }

    /**
     * @return null
     */
    public function getOldAlias()
    {
        return $this->oldAlias;
    }

    /**
     * @param null $oldAlias
     * @return DataObject
     */
    public function setOldAlias($oldAlias)
    {
        $this->oldAlias = $oldAlias;

        return $this;
    }

    /**
     * @return null
     */
    public function getTimeDate()
    {
        return $this->timeDate;
    }

    /**
     * @param null $timeDate
     * @return DataObject
     */
    public function setTimeDate($timeDate)
    {
        $this->timeDate = $timeDate;

        return $this;
    }

    /**
     * @return null
     */
    public function getCurrentFieldString()
    {
        return $this->currentFieldString;
    }

    /**
     * @param null $currentFieldString
     * @return DataObject
     */
    public function setCurrentFieldString($currentFieldString)
    {
        $this->currentFieldString = $currentFieldString;

        return $this;
    }

    /**
     * @return null
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @param null $condition
     * @return DataObject
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * @return null
     */
    public function getConditionFieldDefs()
    {
        return $this->conditionFieldDefs;
    }

    /**
     * @param null $conditionFieldDefs
     * @return DataObject
     */
    public function setConditionFieldDefs($conditionFieldDefs)
    {
        $this->conditionFieldDefs = $conditionFieldDefs;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTiltLogicOperator()
    {
        return $this->tiltLogicOperator;
    }

    /**
     * @param bool $tiltLogicOperator
     * @return DataObject
     */
    public function setTiltLogicOperator($tiltLogicOperator)
    {
        $this->tiltLogicOperator = $tiltLogicOperator;

        return $this;
    }

    /**
     * @return null
     */
    public function getAllowedOperatorList()
    {
        return $this->allowedOperatorList;
    }

    /**
     * @param null $allowedOperatorList
     * @return DataObject
     */
    public function setAllowedOperatorList($allowedOperatorList)
    {
        $this->allowedOperatorList = $allowedOperatorList;

        return $this;
    }

    /**
     * @return null
     */
    public function getFieldMetaData()
    {
        return $this->fieldMetaData;
    }

    /**
     * @param null $fieldMetaData
     * @return DataObject
     */
    public function setFieldMetaData($fieldMetaData)
    {
        $this->fieldMetaData = $fieldMetaData;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWhereStatement()
    {
        return $this->WhereStatement;
    }

    /**
     * @param mixed $WhereStatement
     * @return DataObject
     */
    public function setWhereStatement($WhereStatement)
    {
        $this->WhereStatement = $WhereStatement;

        return $this;
    }

}