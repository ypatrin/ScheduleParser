<?php
/**
 * Created by PhpStorm.
 * User: ypatrin
 * Date: 27.07.2018
 * Time: 10:54
 */

namespace app\common\components;


class OzhParser implements ScheduleParser
{
    const SCHEDULE_URL = 'https://ozh.aero/ru/';
    private $_schedule_parse_result = false;
    protected $_indexes = [
        'flight_num' => 1,
        'airline' => 3,
        'destiny' => 5,
        'time' => 7,
        'status' => 9
    ];

    public function getSchedule()
    {
        $this->_parseSchedule();
        return array_values($this->_schedule_parse_result);
    }

    protected function _parseSchedule()
    {
        $flights = [];

        $scheduleHtml = @file_get_contents(self::SCHEDULE_URL);
        if (!$scheduleHtml) return [];

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($scheduleHtml);
        $finder = new \DOMXpath($dom);

        $nodes = $finder->query("//div[@class=\"schedule__table js-tab active animate departure\"]/div[@class=\"schedule__row\"]");

        foreach ($nodes as $node) {
            $flightNum = trim($node->childNodes[$this->_indexes['flight_num']]->nodeValue);
            $airline = trim($node->childNodes[$this->_indexes['airline']]->nodeValue);
            $destiny = trim($node->childNodes[$this->_indexes['destiny']]->nodeValue);
            $time = trim($node->childNodes[$this->_indexes['time']]->nodeValue);
            $status = trim($node->childNodes[$this->_indexes['status']]->nodeValue);

            $key = 'departure'.$flightNum.$airline.$destiny.$time;

            $flightObject = new FlightObject();
            $flightObject->flightNumber = $flightNum;
            $flightObject->airport = str_replace('Zaporizhzhya', '', $destiny);
            $flightObject->airport = str_replace('―', '', $flightObject->airport);
            $flightObject->airport = trim($flightObject->airport);
            $flightObject->status = preg_replace("/  +/"," ",$status);
            $flightObject->schedule_time = date('Y-m-d') . ' ' . $time;
            $flightObject->carrier = $airline;

            if ( $flightObject->flightNumber{2} != ' ' ) {
                $carrier = substr($flightObject->flightNumber, 0, 3);
                $num = substr($flightObject->flightNumber, 3, strlen($flightObject->flightNumber));
                $flightObject->flightNumber = $carrier.' '.$num;
            }

            $flightObject->rel_date = "today";
            $flightObject->direction = "departure";
            $flightObject->_source = 'OZH';

            $flights[$key] = $flightObject;
        }

        $nodes = $finder->query("//div[@class=\"schedule__table js-tab arrival\"]/div[@class=\"schedule__row\"]");

        foreach ($nodes as $node) {
            $flightNum = trim($node->childNodes[$this->_indexes['flight_num']]->nodeValue);
            $airline = trim($node->childNodes[$this->_indexes['airline']]->nodeValue);
            $destiny = trim($node->childNodes[$this->_indexes['destiny']]->nodeValue);
            $time = trim($node->childNodes[$this->_indexes['time']]->nodeValue);
            $status = trim($node->childNodes[$this->_indexes['status']]->nodeValue);

            $key = 'arrival'.$flightNum.$airline.$destiny.$time;

            $flightObject = new FlightObject();
            $flightObject->flightNumber = $flightNum;
            $flightObject->airport = $destiny;
            $flightObject->airport = str_replace('Zaporizhzhya', '', $destiny);
            $flightObject->airport = str_replace('―', '', $flightObject->airport);
            $flightObject->airport = trim($flightObject->airport);

            $flightObject->status = preg_replace("/  +/"," ",$status);
            $flightObject->schedule_time = date('Y-m-d') . ' ' . $time;
            $flightObject->carrier = $airline;

            if ( $flightObject->flightNumber{2} != ' ' ) {
                $carrier = substr($flightObject->flightNumber, 0, 3);
                $num = substr($flightObject->flightNumber, 3, strlen($flightObject->flightNumber));
                $flightObject->flightNumber = $carrier.' '.$num;
            }

            $flightObject->rel_date = "today";
            $flightObject->direction = "arrival";
            $flightObject->_source = 'OZH';

            $flights[$key] = $flightObject;
        }

        if (empty($this->_schedule_parse_result)) $this->_schedule_parse_result = $flights;
        else $this->_schedule_parse_result = array_merge($this->_schedule_parse_result, $flights);
    }
}