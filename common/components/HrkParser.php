<?php
/**
 * Created by PhpStorm.
 * User: ypatrin
 * Date: 25.07.2018
 * Time: 11:22
 */

namespace app\common\components;


class HrkParser implements ScheduleParser
{
    const AIRPORT_BASE_URL  = "https://hrk.aero";
    const SCHEDULE_URL     = 'https://hrk.aero/table/ajax_tablo_new.php?lang=ru&full=1&first=1';

    private $_schedule_parse_result = false;
    private $_extended_info = false;

    public function getSchedule()
    {
        $this->_parseSchedule();

        return $this->_schedule_parse_result;
    }

    protected function _parseSchedule()
    {
        $flights = [];
        $schedule = @file_get_contents(self::SCHEDULE_URL);

        if (!$schedule) return [];
        libxml_use_internal_errors(true);

        $html = "<!DOCTYPE html>\n";
        $html .= "<html>\n";
        $html .= "<head><meta charset=\"utf-8\"></head>\n";
        $html .= "<body>{$schedule}</body>\n";
        $html .= "</html> ";

        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $finder = new \DOMXpath($dom);

        //DEPARTURE
        $nodes = $finder->query("//table[@id=\"table-departing\"]/tr");
        for ($i = 1; $i < $nodes->length; $i++) {
            $class = $nodes[$i]->getAttribute('class');
            $class = trim(str_replace('flight-item', '', $class));

            if ($class == "flight-another-day") continue;

            $td = $nodes[$i]->childNodes;
            $flightObject = new FlightObject();

            if (isset($td[1]))
                $flightObject->flightNumber = $td[1]->nodeValue;
            if (isset($td[2]))
                $flightObject->airport = $td[2]->nodeValue;
            if (isset($td[4]))
                $flightObject->status = $td[4]->nodeValue;

            $realTime = $td[3]->nodeValue;
            if ( $class == 'flight-today' ) {
                $flightObject->rel_date = "today";
                $realDate = date('Y-m-d');
            }
            if ( $class == 'flight-yesterday' ) {
                $flightObject->rel_date = "yesterday";
                $realDate = date('Y-m-d', strtotime('-1 day'));
            }
            if ( $class == 'flight-tomorrow' ) {
                $flightObject->rel_date = "tomorrow";
                $realDate = date('Y-m-d', strtotime('+1 day'));
            }

            $flightObject->real_time = $realDate.' '.$realTime;
            $flightObject->direction = "departure";
            $flightObject->_source = 'HRK';

            if ($this->_extended_info) {
                $flightInfoLink = $td[1]->firstChild->getAttribute('href');
                $flightInfo = $this->_loadFlightInfo($flightInfoLink);
                $flightObject->schedule_time = $flightInfo['scheduleTime'];
            } else {
                $flightObject->schedule_time = $flightObject->real_time;
                $flightObject->real_time = false;
            }

            if (!empty($flightObject->rel_date)) {
                $flights[] = $flightObject;
            }

            unset($flightObject);
        }

        $nodes = $finder->query("//table[@id=\"table-arrival\"]/tr");

        for ($i = 1; $i < $nodes->length; $i++) {
            $class = $nodes[$i]->getAttribute('class');
            $class = trim(str_replace('flight-item', '', $class));

            if ($class == "flight-another-day") continue;

            $td = $nodes[$i]->childNodes;
            $flightObject = new FlightObject();

            if (isset($td[1]))
                $flightObject->flightNumber = $td[1]->nodeValue;
            if (isset($td[2]))
                $flightObject->airport = $td[2]->nodeValue;
            if (isset($td[4]))
                $flightObject->status = $td[4]->nodeValue;

            $realTime = $td[3]->nodeValue;
            if ( $class == 'flight-today' ) {
                $flightObject->rel_date = "today";
                $realDate = date('Y-m-d');
            }
            if ( $class == 'flight-yesterday' ) {
                $flightObject->rel_date = "yesterday";
                $realDate = date('Y-m-d', strtotime('-1 day'));
            }
            if ( $class == 'flight-tomorrow' ) {
                $flightObject->rel_date = "tomorrow";
                $realDate = date('Y-m-d', strtotime('+1 day'));
            }

            $flightObject->real_time = $realDate.' '.$realTime;
            $flightObject->direction = "arrival";
            $flightObject->_source = 'HRK';

            if ($this->_extended_info) {
                $flightInfoLink = $td[1]->firstChild->getAttribute('href');
                $flightInfo = $this->_loadFlightInfo($flightInfoLink);
                $flightObject->schedule_time = $flightInfo['scheduleTime'];
            } else {
                $flightObject->schedule_time = $flightObject->real_time;
                $flightObject->real_time = false;
            }

            if (!empty($flightObject->rel_date)) {
                $flights[] = $flightObject;
            }

            unset($flightObject);
        }

        if (empty($this->_schedule_parse_result)) $this->_schedule_parse_result = $flights;
        else $this->_schedule_parse_result = array_merge($this->_schedule_parse_result, $flights);
    }

    protected function _loadFlightInfo($link)
    {
        $flightInfoHtml = @file_get_contents(self::AIRPORT_BASE_URL . $link);

        $dom = new \DOMDocument();
        $dom->loadHTML($flightInfoHtml);

        $finder = new \DOMXpath($dom);
        $nodes = $finder->query("//table");
        $table = $nodes[0];

        $flightNum = '';
        $flightScheduleTime = '';
        $flightRealTime = '';
        $airline = '';
        $destiny = '';

        foreach ($table->getElementsByTagName('tr') as $tr)
        {
            $name = str_replace([' ','/'], '', $tr->childNodes[1]->nodeValue);
            $name = trim(strtolower($this->_translit($name)));

            switch($name)
            {
                case "reys":
                    $flightNum = $tr->childNodes[3]->nodeValue;
                    break;
                case "vremyaporaspisaniyu":
                case "prilet":
                    $flightScheduleTime = trim(str_replace(['(',')'], '', $tr->childNodes[3]->nodeValue));
                    $flightScheduleTime = \DateTime::createFromFormat('H:i d.m.Y', $flightScheduleTime);
                    $flightScheduleTime = $flightScheduleTime->format('Y-m-d H:i');
                    break;
                case "vremyaozhidaemoefakticheskoe":
                    $flightRealTime = trim(str_replace(['(',')'], '', $tr->childNodes[3]->nodeValue));
                    $flightRealTime = \DateTime::createFromFormat('H:i d.m.Y', $flightRealTime);
                    $flightRealTime = $flightRealTime->format('Y-m-d H:i');
                    break;
                case "aviakompaniya":
                    $airline = $tr->childNodes[3]->nodeValue;
                    break;
                case "mestonaznacheniya":
                    $destiny = $tr->childNodes[3]->nodeValue;
                    break;
            }
        }

        return [
            'flightNum' => $flightNum,
            'scheduleTime' => $flightScheduleTime,
            'realTime' => $flightRealTime,
            'airline' => $airline,
            'destiny' => $destiny
        ];
    }

    protected function _translit($string)
    {
        $converter = array(
            'а' => 'a',   'б' => 'b',   'в' => 'v',
            'г' => 'g',   'д' => 'd',   'е' => 'e',
            'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
            'и' => 'i',   'й' => 'y',   'к' => 'k',
            'л' => 'l',   'м' => 'm',   'н' => 'n',
            'о' => 'o',   'п' => 'p',   'р' => 'r',
            'с' => 's',   'т' => 't',   'у' => 'u',
            'ф' => 'f',   'х' => 'h',   'ц' => 'c',
            'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
            'ь' => '\'',  'ы' => 'y',   'ъ' => '\'',
            'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

            'А' => 'A',   'Б' => 'B',   'В' => 'V',
            'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
            'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
            'И' => 'I',   'Й' => 'Y',   'К' => 'K',
            'Л' => 'L',   'М' => 'M',   'Н' => 'N',
            'О' => 'O',   'П' => 'P',   'Р' => 'R',
            'С' => 'S',   'Т' => 'T',   'У' => 'U',
            'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
            'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
            'Ь' => '\'',  'Ы' => 'Y',   'Ъ' => '\'',
            'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
        );
        return strtr($string, $converter);
    }
}