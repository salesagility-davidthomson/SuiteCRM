<?php
namespace modules\AOR_Reports\includes;

class AorReportUtils{
    public static function requestToUserParameters()
    {
        $params = array();
        if(isset($_REQUEST['parameter_id']) && $_REQUEST['parameter_id']) {
            foreach ($_REQUEST['parameter_id'] as $key => $parameterId) {
                if ($_REQUEST['parameter_type'][$key] === 'Multi') {
                    $_REQUEST['parameter_value'][$key] = encodeMultienumValue(explode(',', $_REQUEST['parameter_value'][$key]));
                }
                $params[$parameterId] = array('id' => $parameterId,
                    'operator' => $_REQUEST['parameter_operator'][$key],
                    'type' => $_REQUEST['parameter_type'][$key],
                    'value' => $_REQUEST['parameter_value'][$key],
                );

                // Fix for issue #1272 - AOR_Report module cannot update Date type parameter.
                if ($_REQUEST['parameter_type'][$key] === 'Date') {
                    $values = array();
                    $values[] = $_REQUEST['parameter_value'][0];
                    $values[] = $_REQUEST['parameter_value'][1];;
                    $values[] = $_REQUEST['parameter_value'][2];;
                    $values[] = $_REQUEST['parameter_value'][3];;

                    $params[$parameterId] = array(
                        'id' => $parameterId,
                        'operator' => $_REQUEST['parameter_operator'][$key],
                        'type' => $_REQUEST['parameter_type'][$key],
                        'value' => $values,
                    );
                }
            }
        }
        return $params;
    }


    /**
     * getPeriodDate
     * @param $date_time_period_list_selected
     * @return DateTime
     */
    public static function getPeriodDate($date_time_period_list_selected)
    {
        global $sugar_config;
        $datetime_period = new DateTime();

        // Setup when year quarters start & end
        if ($sugar_config['aor']['quarters_begin']) {
            $q = calculateQuarters($sugar_config['aor']['quarters_begin']);
        } else {
            $q = calculateQuarters();
        }

        if ($date_time_period_list_selected == 'today') {
            $datetime_period = new DateTime();
        } else if ($date_time_period_list_selected == 'yesterday') {
            $datetime_period = $datetime_period->sub(new DateInterval("P1D"));
        } else if ($date_time_period_list_selected == 'this_week') {
            $datetime_period = $datetime_period->setTimestamp(strtotime('this week'));
        } else if ($date_time_period_list_selected == 'last_week') {
            $datetime_period = $datetime_period->setTimestamp(strtotime('last week'));
        } else if ($date_time_period_list_selected == 'this_month') {
            $datetime_period = $datetime_period->setDate($datetime_period->format('Y'), $datetime_period->format('m'), 1);
        } else if ($date_time_period_list_selected == 'last_month') {
            $datetime_period = $datetime_period->modify('first day of last month');
        } else if ($date_time_period_list_selected == 'this_quarter') {
            $thisMonth = $datetime_period->setDate($datetime_period->format('Y'), $datetime_period->format('m'), 1);
            if ($thisMonth >= $q[1]['start'] && $thisMonth <= $q[1]['end']) {
                // quarter 1
                $datetime_period = $datetime_period->setDate($q[1]['start']->format('Y'), $q[1]['start']->format('m'), $q[1]['start']->format('d'));
            } else if ($thisMonth >= $q[2]['start'] && $thisMonth <= $q[2]['end']) {
                // quarter 2
                $datetime_period = $datetime_period->setDate($q[2]['start']->format('Y'), $q[2]['start']->format('m'), $q[2]['start']->format('d'));
            } else if ($thisMonth >= $q[3]['start'] && $thisMonth <= $q[3]['end']) {
                // quarter 3
                $datetime_period = $datetime_period->setDate($q[3]['start']->format('Y'), $q[3]['start']->format('m'), $q[3]['start']->format('d'));
            } else if ($thisMonth >= $q[4]['start'] && $thisMonth <= $q[4]['end']) {
                // quarter 4
                $datetime_period = $datetime_period->setDate($q[4]['start']->format('Y'), $q[4]['start']->format('m'), $q[4]['start']->format('d'));
            }
        } else if ($date_time_period_list_selected == 'last_quarter') {
            $thisMonth = $datetime_period->setDate($datetime_period->format('Y'), $datetime_period->format('m'), 1);
            if ($thisMonth >= $q[1]['start'] && $thisMonth <= $q[1]['end']) {
                // quarter 1 - 3 months
                $datetime_period = $q[1]['start']->sub(new DateInterval('P3M'));
            } else if ($thisMonth >= $q[2]['start'] && $thisMonth <= $q[2]['end']) {
                // quarter 2 - 3 months
                $datetime_period = $q[2]['start']->sub(new DateInterval('P3M'));
            } else if ($thisMonth >= $q[3]['start'] && $thisMonth <= $q[3]['end']) {
                // quarter 3 - 3 months
                $datetime_period = $q[3]['start']->sub(new DateInterval('P3M'));
            } else if ($thisMonth >= $q[4]['start'] && $thisMonth <= $q[4]['end']) {
                // quarter 4 - 3 months
                $datetime_period = $q[3]['start']->sub(new DateInterval('P3M'));
            }
        } else if ($date_time_period_list_selected == 'this_year') {
            $datetime_period = $datetime_period = $datetime_period->setDate($datetime_period->format('Y'), 1, 1);
        } else if ($date_time_period_list_selected == 'last_year') {
            $datetime_period = $datetime_period = $datetime_period->setDate($datetime_period->format('Y') - 1, 1, 1);
        }
        // set time to 00:00:00
        $datetime_period = $datetime_period->setTime(0, 0, 0);
        return $datetime_period;
    }

    /**
     * getPeriodDate
     * @param $date_time_period_list_selected
     * @return DateTime
     */
    public static function getPeriodEndDate($dateTimePeriodListSelected)
    {
        switch($dateTimePeriodListSelected) {
            case 'today':
            case 'yesterday':
                $datetimePeriod = new DateTime();
                break;
            case 'this_week':
                $datetimePeriod = new DateTime("next week monday");
                $datetimePeriod->setTime(0, 0, 0);
                break;
            case 'last_week':
                $datetimePeriod = new DateTime("this week monday");
                $datetimePeriod->setTime(0, 0, 0);
                break;
            case 'this_month':
                $datetimePeriod = new DateTime('first day of next month');
                $datetimePeriod->setTime(0, 0, 0);
                break;
            case 'last_month':
                $datetimePeriod = new DateTime("first day of this month");
                $datetimePeriod->setTime(0, 0, 0);
                break;
            case 'this_quarter':
                $thisMonth = new DateTime('first day of this month');
                $thisMonth = $thisMonth->format('n');
                if ($thisMonth < 4) {
                    // quarter 1
                    $datetimePeriod = new DateTime('first day of april');
                    $datetimePeriod->setTime(0, 0, 0);
                } elseif ($thisMonth > 3 && $thisMonth < 7) {
                    // quarter 2
                    $datetimePeriod = new DateTime('first day of july');
                    $datetimePeriod->setTime(0, 0, 0);
                } elseif ($thisMonth > 6 && $thisMonth < 10) {
                    // quarter 3
                    $datetimePeriod = new DateTime('first day of october');
                    $datetimePeriod->setTime(0, 0, 0);
                } elseif ($thisMonth > 9) {
                    // quarter 4
                    $datetimePeriod = new DateTime('next year first day of january');
                    $datetimePeriod->setTime(0, 0, 0);
                }
                break;
            case 'last_quarter':
                $thisMonth = new DateTime('first day of this month');
                $thisMonth = $thisMonth->format('n');
                if ($thisMonth < 4) {
                    // previous quarter 1
                    $datetimePeriod = new DateTime('this year first day of january');
                    $datetimePeriod->setTime(0, 0, 0);
                } elseif ($thisMonth > 3 && $thisMonth < 7) {
                    // previous quarter 2
                    $datetimePeriod = new DateTime('first day of april');
                    $datetimePeriod->setTime(0, 0, 0);
                } elseif ($thisMonth > 6 && $thisMonth < 10) {
                    // previous quarter 3
                    $datetimePeriod = new DateTime('first day of july');
                    $datetimePeriod->setTime(0, 0, 0);
                } elseif ($thisMonth > 9) {
                    // previous quarter 4
                    $datetimePeriod = new DateTime('first day of october');
                    $datetimePeriod->setTime(0, 0, 0);
                }
                break;
            case 'this_year':
                $datetimePeriod = new DateTime('next year first day of january');
                $datetimePeriod->setTime(0, 0, 0);
                break;
            case 'last_year':
                $datetimePeriod = new DateTime("this year first day of january");
                $datetimePeriod->setTime(0, 0, 0);
                break;
        }

        return $datetimePeriod;
    }

    /**
     * @param int $offsetMonths - defines start of the year.
     * @return array - The each quarters boundary
     */
    public static function calculateQuarters($offsetMonths = 0)
    {
        // define quarters
        $q = array();
        $q['1'] = array();
        $q['2'] = array();
        $q['3'] = array();
        $q['4'] = array();

        // Get the start of this year
        $q1start = new DateTime();
        $q1start = $q1start->setTime(0, 0, 0);
        $q1start = $q1start->setDate($q1start->format('Y'), 1, 1);
        /*
         * $offsetMonths gets added to the current month. Therefor we need this variable to equal one less than the start
         * month. So Jan becomes 0. Feb => 1 and so on.
         */
        $offsetMonths -= 1;
        // Offset months
        if ($offsetMonths > 0) {
            $q1start->add(new DateInterval('P' . $offsetMonths . 'M'));
        }
        $q1end = DateTime::createFromFormat(DATE_ISO8601, $q1start->format(DATE_ISO8601));
        $q1end->add(new DateInterval('P2M'));

        $q2start = DateTime::createFromFormat(DATE_ISO8601, $q1start->format(DATE_ISO8601));
        $q2start->add(new DateInterval('P3M'));
        $q2end = DateTime::createFromFormat(DATE_ISO8601, $q2start->format(DATE_ISO8601));
        $q2end->add(new DateInterval('P2M'));

        $q3start = DateTime::createFromFormat(DATE_ISO8601, $q2start->format(DATE_ISO8601));
        $q3start->add(new DateInterval('P3M'));
        $q3end = DateTime::createFromFormat(DATE_ISO8601, $q3start->format(DATE_ISO8601));
        $q3end->add(new DateInterval('P2M'));

        $q4start = DateTime::createFromFormat(DATE_ISO8601, $q3start->format(DATE_ISO8601));
        $q4start->add(new DateInterval('P3M'));
        $q4end = DateTime::createFromFormat(DATE_ISO8601, $q4start->format(DATE_ISO8601));
        $q4end->add(new DateInterval('P2M'));

        // Assign quarter boundaries
        $q['1']['start'] = $q1start;
        $q['1']['end'] = $q1end;
        $q['2']['start'] = $q2start;
        $q['2']['end'] = $q2end;
        $q['3']['start'] = $q3start;
        $q['3']['end'] = $q3end;
        $q['4']['start'] = $q4start;
        $q['4']['end'] = $q4end;

        return $q;
    }
}
