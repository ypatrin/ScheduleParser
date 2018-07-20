<?php
/**
 * Created by PhpStorm.
 * User: ypatrin
 * Date: 20.07.2018
 * Time: 14:52
 */

namespace app\common\components;


class LwoParser implements ScheduleParser
{
    const SCHEDULE_PAGE_URL = 'http://lwo.aero/flight_scoreboard?_=';

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
        $this->_schedule_page_content = @file_get_contents(self::SCHEDULE_PAGE_URL . microtime());
        // fix json
        $this->_schedule_page_content = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $this->_schedule_page_content);
    }

    protected function _parseSchedulePage()
    {
        $schedule = json_decode($this->_schedule_page_content, true);
        $flights = [];

        $arrival = $schedule[0];
        $departure= $schedule[1];

        foreach ($arrival as $day => $depFlights) {
            foreach ($depFlights as $flight) {
                if (empty($flight["name_airline"]) || empty($flight['status_class']))
                    continue;

                $flightObject = new FlightObject();
                $flightObject->flightNumber = $flight['flight_num'];
                $flightObject->airport = $flight['code_direction'];
                $flightObject->status = $this->_getStatus($flight['status_class']);

                $flightObject->carrier = $flight["name_airline"];
                $flightObject->direction = "arrival";

                $flightObject->_source = 'LWO';

                if ($day == 0) {
                    $date = date('Y-m-d', strtotime('-1 day'));
                    $flightObject->rel_date = "today";
                }
                if ($day == 1) {
                    $date = date('Y-m-d', strtotime('now'));
                    $flightObject->rel_date = "yesterday";
                }
                if ($day == 2) {
                    $date = date('Y-m-d', strtotime('+1 day'));
                    $flightObject->rel_date = "tomorrow";
                }

                $flightObject->schedule_time = $date . $flight['time'];

                if (!in_array($flightObject, $flights))
                    $flights[] = $flightObject;

                unset($flightObject);
            }
        }

        foreach ($departure as $day => $depFlights) {
            foreach ($depFlights as $flight) {
                if (empty($flight["name_airline"]) || empty($flight['status_class']))
                    continue;

                $flightObject = new FlightObject();
                $flightObject->flightNumber = $flight['flight_num'];
                $flightObject->airport = $flight['code_direction'];
                $flightObject->status = $this->_getStatus($flight['status_class']);

                $flightObject->carrier = $flight["name_airline"];
                $flightObject->direction = "departure";

                $flightObject->_source = 'LWO';

                if ($day == 0) {
                    $date = date('Y-m-d', strtotime('-1 day'));
                    $flightObject->rel_date = "today";
                }
                if ($day == 1) {
                    $date = date('Y-m-d', strtotime('now'));
                    $flightObject->rel_date = "yesterday";
                }
                if ($day == 2) {
                    $date = date('Y-m-d', strtotime('+1 day'));
                    $flightObject->rel_date = "tomorrow";
                }

                $flightObject->schedule_time = $date . $flight['time'];

                if (!in_array($flightObject, $flights))
                    $flights[] = $flightObject;

                unset($flightObject);
            }
        }

        $this->_schedule_parse_result = $flights;
    }

    protected function _getStatus($status)
    {
        switch (strtoupper($status)) {
            case "ARRIVED": return FlightObject::SCHEDULE_STATUS_LN; break;
            case "APPROACH": return FlightObject::SCHEDULE_STATUS_FR; break;
            case "TAKE_OFF": return FlightObject::SCHEDULE_STATUS_DP; break;
            case "DELAYED": return FlightObject::SCHEDULE_STATUS_DL; break;
            case "BOARDING": return FlightObject::SCHEDULE_STATUS_СС; break;
            case "CHECK-IN": return FlightObject::SCHEDULE_STATUS_CK; break;
            case "EXPECTED": return FlightObject::SCHEDULE_STATUS_ON; break;

            default: return strtoupper($status); break;
        }
    }
}