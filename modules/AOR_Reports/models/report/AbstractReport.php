<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 09/02/17
 * Time: 15:46
 */
namespace modules\AOR_Reports\models\report;
include_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'rootPath.php';
include_once ROOTPATH.'/modules/AOR_Reports/models/report/ReportInterface.php';

abstract class AbstractReport implements ReportInterface
{
    private $queryFactory = null;


    private $markupFactory = null;
    private $fields = null;
    private $mainGroupField = null;
    private $bean = null;
    private $beanList = null;
    private $result = null;



    public function __construct($queryFactory, $markupFactory)
    {
        $this->queryFactory = $queryFactory;
        $this->markupFactory = $markupFactory;
    }

    private function checkProperties(){
        $allSet = true;
        if($this->fields === null){
            $allSet = false;
        }
        if($this->mainGroupField === null){
            $allSet = false;
        }
        if($this->bean  === null){
            $allSet = false;
        }
        if($this->beanList  === null){
            $allSet = false;
        }
        if($this->result === null){
            $allSet = false;
        }
        return $allSet;
    }


    /**
     * @return mixed
     * @throws \Exception
     */
    public function getReport(){
        if(!$this->checkProperties()){
            throw new \Exception('Not all needed properties set for Report', 103);
        }
        try {
            $content = $this->generateReport();
        } catch (Exception $e) {
            throw new \Exception('Exception Caught with message:'.$e->getMessage(),$e->getCode());
        }
        return $content;
    }

    abstract protected function generateReport();

    /**
     * @param null $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * @param null $mainGroupField
     */
    public function setMainGroupField($mainGroupField)
    {
        $this->mainGroupField = $mainGroupField;
        return $this;
    }

    /**
     * @param null $bean
     */
    public function setBean($bean)
    {
        $this->bean = $bean;
        return $this;
    }

    /**
     * @param null $beanList
     */
    public function setBeanList($beanList)
    {
        $this->beanList = $beanList;
        return $this;
    }

    /**
     * @param null $result
     */
    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }


    protected function getFields()
    {
        return $this->fields;
    }


    protected function getMainGroupField()
    {
        return $this->mainGroupField;
    }


    protected function getBean()
    {
        return $this->bean;
    }

    protected function getBeanList()
    {
        return $this->beanList;
    }


    protected function getResult()
    {
        return $this->result;
    }

    /**
     * @return null
     */
    protected function getQueryFactory()
    {
        return $this->queryFactory;
    }

    /**
     * @return null
     */
    protected function getMarkupFactory()
    {
        return $this->markupFactory;
    }
}