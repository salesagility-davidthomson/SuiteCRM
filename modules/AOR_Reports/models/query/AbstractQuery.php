<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 09/02/17
 * Time: 15:46
 */
namespace modules\AOR_Reports\models\query;
include_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'rootPath.php';

abstract class AbstractQuery implements QueryInterface
{

    private $bean = null;
    private $beanList = null;
    private $queryStr = null;
    private $report_module = null;

    protected $dataObject = array();


    public function __construct()
    {


    }

    public function getQuery(){
        if(!$this->checkProperties()){
            throw new \Exception('Not all needed properties set for query', 103);
        }
        try {
            $content = $this->buildQuery();
        } catch (Exception $e) {
            throw new \Exception('Exception Caught with message:'.$e->getMessage(),$e->getCode());
        }
        return $content;
    }



    abstract protected function buildQuery();

    private function checkProperties(){
        $allSet = true;
        if($this->bean  === null){
            $allSet = false;
        }
        if($this->beanList  === null){
            $allSet = false;
        }
        if($this->queryStr === null){
            $allSet = false;
        }
        if($this->report_module  === null){
            $allSet = false;
        }
        return $allSet;
    }


    /**
     * @return null
     */
    protected function getReportModule()
    {
        return $this->report_module;
    }

    /**
     * @param null $report_module
     */
    public function setReportModule($report_module)
    {
        $this->report_module = $report_module;
        return $this;
    }

    /**
     * @return null
     */
    protected function getBean()
    {
        return $this->bean;
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
     * @return null
     */
    protected function getBeanList()
    {
        return $this->beanList;
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
     * @return null
     */
    public function getQueryStr()
    {
        return $this->queryStr;
    }

    /**
     * @param null $queryStr
     */
    public function setQueryStr($queryStr)
    {
        $this->queryStr = $queryStr;
        return $this;
    }



}