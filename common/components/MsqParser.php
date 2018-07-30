<?php
/**
 * Created by PhpStorm.
 * User: yury
 * Date: 30.07.18
 * Time: 9:52
 */

namespace app\common\components;


class MsqParser implements ScheduleParser
{
    const SCHEDULE_ARRIVAL_URL      = 'http://airport.by/timetable/online-arrival';
    const SCHEDULE_DEPARTURE_URL    = 'http://airport.by/timetable/online-departure';

    protected $_indexes_arrival = [
        'carrier' => 0,
        'schedule_time' => 1,
        'real_time' => 2,
        'flight_num' => 3,
        'airport' => 4,
        'gate' => 5,
        'status' => 6,
    ];
    protected $_indexes_departure = [
        'carrier' => 0,
        'schedule_time' => 1,
        'flight_num' => 2,
        'airport' => 3,
        'gate' => 5,
        'status' => 6,
    ];

    private $_schedule_parse_result = false;

    public function getSchedule()
    {
        $this->_parseSchedule(self::SCHEDULE_ARRIVAL_URL, 'arrival');
        $this->_parseSchedule(self::SCHEDULE_DEPARTURE_URL, 'departure');
        return $this->_schedule_parse_result;
    }

    protected function _parseSchedule($url, $direction)
    {
        $arrivalHtml = @file_get_contents($url);

        $flights = [];
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML($arrivalHtml);
        $finder = new \DOMXpath($dom);

        $nodes = $finder->query("//table/tr");

        foreach ($nodes as $node) {
            $rel_date = $node->getAttribute('class');

            if ($rel_date != 'yesterday' && $rel_date != 'today' && $rel_date != 'tomorrow')
                continue;

            $tdList = $node->getElementsByTagName('td');

            $flightObject = new FlightObject();

            $flightObject->carrier = $tdList[$this->_indexes_arrival['carrier']]->nodeValue;
            if ($rel_date == "today") {
                $sTime = date('d.m.Y', strtotime('now')) . ' ' . $tdList[$this->_indexes_arrival['schedule_time']]->nodeValue;
            } else {
                $sTimeArr = explode(' ', $tdList[$this->_indexes_arrival['schedule_time']]->nodeValue);
                $sTime = $sTimeArr[0] . date('.Y') . ' ' . $sTimeArr[1];
            }

            $flightObject->schedule_time = date('Y-m-d H:i', strtotime($sTime));

            if ($direction == "arrival") {

                if (!empty($tdList[$this->_indexes_arrival['real_time']]->nodeValue)) {
                    if ($rel_date == "today") {
                        $rTime = date('d.m.Y', strtotime('now')) . ' ' . $tdList[$this->_indexes_arrival['real_time']]->nodeValue;
                    } else {
                        $rTimeArr = explode(' ', $tdList[$this->_indexes_arrival['real_time']]->nodeValue);
                        if (isset($rTimeArr[1])) {
                            $rTime = $rTimeArr[0] . date('.Y') . ' ' . $rTimeArr[1];
                        } else {
                            $rTime = date('d.m.Y', strtotime('now')) . ' ' . $tdList[$this->_indexes_arrival['real_time']]->nodeValue;
                        }
                    }

                    $flightObject->real_time = date('Y-m-d H:i', strtotime($rTime));
                }

                $flightObject->flightNumber = $tdList[$this->_indexes_arrival['flight_num']]->nodeValue;
                $flightObject->airport = $tdList[$this->_indexes_arrival['airport']]->nodeValue;
                $flightObject->gate = $tdList[$this->_indexes_arrival['gate']]->nodeValue;
                $flightObject->status = $tdList[$this->_indexes_arrival['status']]->nodeValue;
                $flightObject->direction = $direction;
                $flightObject->rel_date = $rel_date;
            }
            else
            {
                $flightObject->flightNumber = $tdList[$this->_indexes_departure['flight_num']]->nodeValue;
                $flightObject->airport = $tdList[$this->_indexes_departure['airport']]->nodeValue;
                $flightObject->gate = $tdList[$this->_indexes_departure['gate']]->nodeValue;
                $flightObject->status = $tdList[$this->_indexes_departure['status']]->nodeValue;
                $flightObject->direction = $direction;
                $flightObject->rel_date = $rel_date;
            }

            foreach (explode('/', $flightObject->flightNumber) as $fNumber) {
                $fNumber = trim($fNumber);

                if (strlen($fNumber) == 5) {
                    $airline = substr($fNumber, 0, 2);
                    $flightObject->flightNumber = str_replace($airline, $airline . ' ', $flightObject->flightNumber);
                }
                if (strlen($fNumber) == 6) {
                    $airline = substr($fNumber, 0, 2);
                    $flightObject->flightNumber = str_replace($airline, $airline . ' ', $flightObject->flightNumber);
                }
                if (strlen($fNumber) == 7) {
                    $airline = substr($fNumber, 0, 3);
                    $flightObject->flightNumber = str_replace($airline, $airline . ' ', $flightObject->flightNumber);
                }
            }


            $flightObject->_source = 'MSQ';

            $flights[] = $flightObject;
            unset($flightObject);
        }

        if (empty($this->_schedule_parse_result)) $this->_schedule_parse_result = $flights;
        else $this->_schedule_parse_result = array_merge($this->_schedule_parse_result, $flights);
    }
}