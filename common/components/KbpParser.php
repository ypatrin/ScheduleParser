<?php
/**
 * Created by PhpStorm.
 * User: ypatrin
 * Date: 17.07.2018
 * Time: 13:15
 */

namespace app\common\components;

class KbpParser implements ScheduleParser
{
    const SCHEDULE_PAGE_URL = 'https://kbp.aero/wp-content/themes/borispol-magenta/js/board.js?v=1531826639.3483';

    private $_schedule_page_content = false;
    private $_schedule_parse_result = false;

    public function getSchedule()
    {
        $this->_loadSchedulePage();
        $this->_parseSchedulePage();

        return $this->_schedule_parse_result;
    }

    protected function _loadSchedulePage()
    {
        //load JS data
        $this->_schedule_page_content = @file_get_contents(self::SCHEDULE_PAGE_URL);
        $this->_schedule_page_content = str_replace('kbp', '$kbp', $this->_schedule_page_content);
        $this->_schedule_page_content = str_replace('{', '[', $this->_schedule_page_content);
        $this->_schedule_page_content = str_replace('}', ']', $this->_schedule_page_content);
        $this->_schedule_page_content = str_replace('":"', '"=>"', $this->_schedule_page_content);
        $this->_schedule_page_content = str_replace(',]', ']', $this->_schedule_page_content);
    }

    protected function _parseSchedulePage()
    {
        eval($this->_schedule_page_content);

        $flights = [];

        foreach ($kbp['boardModel'] as $boardModel)
        {
            $flightObject = new FlightObject();

            $date = date('Y-m-d');
            if ($boardModel['rel_day_f'] == 'yesterday') {
                $date = date('Y-m-d', strtotime('-1 day'));
                $flightObject->rel_date = 'yesterday';
            }
            if ($boardModel['rel_day_f'] == 'today') {
                $date = date('Y-m-d');
                $flightObject->rel_date = 'today';
            }
            if ($boardModel['rel_day_f'] == 'todayActiveOnly') {
                $date = date('Y-m-d');
                $flightObject->rel_date = 'today';
            }
            if ($boardModel['rel_day_f'] == 'tomorrow') {
                $date = date('Y-m-d', strtotime('+1 day'));
                $flightObject->rel_date = 'tomorrow';
            }

            $time = $date . ' ' . str_replace('&#8203;', '', $boardModel['dt_plan_f']);

            $flightObject->flightNumber = $boardModel['fltname'];
            $flightObject->airport = $boardModel['airport1']; // airport0 - укр, airport1 - рус, airport2 - англ
            $flightObject->status = $this->_getStatus($boardModel['status_alias']);
            $flightObject->schedule_time = $time;
            $flightObject->carrier = $boardModel['airline_name'];
            $flightObject->direction = $boardModel['direction'] == 1 ? "departure" : "arrival";
            $flightObject->gate = $boardModel['gate'];
            $flightObject->terminal = $boardModel['terminal'];

            if ($flightObject->status == FlightObject::SCHEDULE_STATUS_DP) {
                $flightObject->real_time = $date . ' ' . str_replace('&#8203;', '', $boardModel['takeoff_time']);
            }
            if ($flightObject->status == FlightObject::SCHEDULE_STATUS_DL) {
                $flightObject->real_time = $date . ' ' . str_replace('&#8203;', '', $boardModel['act_time_expected']);
            }

            $flightObject->_source = 'KBP';

            $flights[] = $flightObject;
            unset($flightObject);
        }

        $this->_schedule_parse_result = $flights;
    }

    protected function _getStatus($status)
    {
        switch (strtoupper($status)) {
            case "TAKEOFF": return FlightObject::SCHEDULE_STATUS_DP; break;
            case "EXPECTED": return FlightObject::SCHEDULE_STATUS_DL; break;
            case "BOARDINGSTOP": return FlightObject::SCHEDULE_STATUS_BD; break;
            case "SCHEDULED": return FlightObject::SCHEDULE_STATUS_ON; break;
            case "BOARDING": return FlightObject::SCHEDULE_STATUS_BO; break;
            case "CHECKIN": return FlightObject::SCHEDULE_STATUS_CK; break;

            case "ARRIVED": return FlightObject::SCHEDULE_STATUS_LN; break;

            default: return strtoupper($status); break;
        }
    }
}