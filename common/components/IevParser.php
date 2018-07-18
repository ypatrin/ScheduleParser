<?php
/**
 * Created by PhpStorm.
 * User: ypatrin
 * Date: 17.07.2018
 * Time: 15:55
 */

namespace app\common\components;


class IevParser implements ScheduleParser
{
    const SCHEDULE_PAGE_URL = 'https://api.iev.aero/api/flights/';

    private $_schedule_page_content = false;
    private $_schedule_parse_result = false;

    public function getSchedule()
    {
        $this->_loadSchedulePage(date('d-m-Y'));
        $this->_parseSchedulePage();

        return $this->_schedule_parse_result;
    }

    protected function _loadSchedulePage($date)
    {
        $this->_schedule_page_content = @file_get_contents(self::SCHEDULE_PAGE_URL . $date);
    }

    protected function _parseSchedulePage()
    {
        $results = json_decode($this->_schedule_page_content);
        $flights = [];

        if (!isset($results->body->departure) || !isset($results->body->arrival))
            return [];

        foreach ($results->body->departure as $flight)
        {
            if ($flight->fltNo == "1723") {
                //var_dump($flight); exit;
            }

            $flightObject = new FlightObject();
            $flightObject->flightNumber = (isset($flight->{"carrierID.IATA"}) ? $flight->{"carrierID.IATA"} : $flight->{"carrierID.code"}) . ' ' . $flight->fltNo;
            $flightObject->airport = $flight->{"airportToID.city_ru"};
            $flightObject->status = $this->_getStatus($flight->status);

            if ($flightObject->status == FlightObject::SCHEDULE_STATUS_DP) {
                $flightObject->schedule_time = date('Y-m-d H:i', strtotime($flight->timeDepShedule) + 60*60*3);
                if (isset($flight->timeTakeofFact))
                    $flightObject->real_time = date('Y-m-d H:i', strtotime($flight->timeTakeofFact) + 60*60*3);
            } else {
                $flightObject->schedule_time = date('Y-m-d H:i', strtotime($flight->timeDepShedule) + 60*60*3);
            }


            $flightObject->carrier = $flight->airline->ru->name;
            $flightObject->direction = "departure";
            $flightObject->gate = isset($flight->gateNo) ? $flight->gateNo : '';
            $flightObject->terminal = $flight->term;

            $flightObject->_source = 'IEV';

            if (date('dmY', strtotime('-1 day')) == date('dmY', strtotime($flightObject->schedule_time)))
                $flightObject->rel_date = "yesterday";
            if (date('dmY', strtotime('now')) == date('dmY', strtotime($flightObject->schedule_time)))
                $flightObject->rel_date = "today";
            if (date('dmY', strtotime('+1 day')) == date('dmY', strtotime($flightObject->schedule_time)))
                $flightObject->rel_date = "tomorrow";

            if (!in_array($flightObject, $flights))
                $flights[] = $flightObject;

            unset($flightObject);
        }

        foreach ($results->body->arrival as $flight)
        {
            $flightObject = new FlightObject();
            $flightObject->flightNumber = (isset($flight->{"carrierID.IATA"}) ? $flight->{"carrierID.IATA"} : $flight->{"carrierID.code"}) . ' ' . $flight->fltNo;
            $flightObject->airport = $flight->{"airportFromID.city_ru"};
            $flightObject->status = $this->_getStatus($flight->status);

            if ($flightObject->status == FlightObject::SCHEDULE_STATUS_LN) {
                $flightObject->schedule_time = date('Y-m-d H:i', strtotime($flight->timeToStand) + 60*60*3);
                if (isset($flight->timeLandFact))
                    $flightObject->real_time = date('Y-m-d H:i', strtotime($flight->timeLandFact) + 60*60*3);
            }
            else {
                $flightObject->schedule_time = date('Y-m-d H:i', strtotime($flight->timeToStand) + 60*60*3);
            }


            $flightObject->carrier = $flight->airline->ru->name;
            $flightObject->direction = "arrival";
            $flightObject->gate = isset($flight->gateNo) ? $flight->gateNo : '';
            $flightObject->terminal = $flight->term;


            // задержка рейса
            if ($flightObject->status == FlightObject::SCHEDULE_STATUS_DL && !empty($flight->actual))
            {
                $flightObject->real_time = date('Y-m-d H:i', strtotime($flight->actual) + 60*60*3);
            }

            if ($flight->status == "dl" && !empty($flight->timeTakeofFact)) {
                if (strtotime($flight->timeTakeofFact) != strtotime($flight->timeDepShedule)) {
                    $flightObject->real_time = date('Y-m-d H:i', strtotime($flight->timeTakeofFact) + 60*60*3);
                }
            }

            $flightObject->_source = 'IEV';

            if (date('dmY', strtotime('-1 day')) == date('dmY', strtotime($flightObject->schedule_time)))
                $flightObject->rel_date = "yesterday";
            if (date('dmY', strtotime('now')) == date('dmY', strtotime($flightObject->schedule_time)))
                $flightObject->rel_date = "today";
            if (date('dmY', strtotime('+1 day')) == date('dmY', strtotime($flightObject->schedule_time)))
                $flightObject->rel_date = "tomorrow";

            $flights[] = $flightObject;
            unset($flightObject);
        }

        usort($flights, function ($a, $b) {
            return strtotime($a->schedule_time) - strtotime($b->schedule_time);
        });

        $flights = array_unique($flights, SORT_REGULAR);

        if (!$this->_schedule_parse_result)
            $this->_schedule_parse_result = $flights;
        else
            $this->_schedule_parse_result = array_merge($this->_schedule_parse_result, $flights);
    }

    protected function _getStatus($status)
    {
        switch (strtoupper($status)) {
            case "CX": return FlightObject::SCHEDULE_STATUS_CX; break;
            case "DP": return FlightObject::SCHEDULE_STATUS_DP; break;
            case "LN": return FlightObject::SCHEDULE_STATUS_LN; break;
            case "BD": return FlightObject::SCHEDULE_STATUS_BD; break;
            case "GC": return FlightObject::SCHEDULE_STATUS_BD; break;
            case "CK": return FlightObject::SCHEDULE_STATUS_CK; break;
            case "ON": return FlightObject::SCHEDULE_STATUS_ON; break;
            case "FR": return FlightObject::SCHEDULE_STATUS_FR; break;
            case "DL": return FlightObject::SCHEDULE_STATUS_DL; break;

            default: return strtoupper($status); break;
        }
    }
}