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

                // if we have a main group already thats wrong cause only one main grouping field possible
                if (!is_null($this->getMainGroupField())) {
                    $GLOBALS['log']->fatal('main group already found');
                }

                $this->setMainGroupField($field);
            }

            ++$i;
        }
    }


}