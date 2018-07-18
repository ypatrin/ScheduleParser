<?php
/**
 * Created by PhpStorm.
 * User: ypatrin
 * Date: 17.07.2018
 * Time: 14:37
 */

namespace app\common\components;


class FlightObject
{
    // рейс отменен
    const SCHEDULE_STATUS_CX = 'canceled';

    // рейс уже вылетел
    const SCHEDULE_STATUS_DP = 'departed';

    // рейс уже прилетел
    const SCHEDULE_STATUS_LN = 'arrived';

    // рейс уже прилетел
    const SCHEDULE_STATUS_BO = 'boarding';

    // посадка окончена
    const SCHEDULE_STATUS_BD = 'landing_over';
    const SCHEDULE_STATUS_GC = 'landing_over';

    // регистрация на рейс
    const SCHEDULE_STATUS_CK = 'check-in';

    // запланировано
    const SCHEDULE_STATUS_ON = 'scheduled';

    // в полете
    const SCHEDULE_STATUS_FR = 'in-flight';

    // задержка
    const SCHEDULE_STATUS_DL = 'delayed';

    /**
     * Flight number. Ex. AZ 123
     * @var string
     */
    public $flightNumber;

    /**
     * Flight status. See const variables.
     * @var string
     */
    public $status;

    /**
     * Schedule date and time. Format: dd-mm-yyy hh:ss
     * @var string
     */
    public $schedule_time;

    /**
     * Real schedule date and time. Format: dd-mm-yyy hh:ss
     * @var string
     */
    public $real_time = false;

    /**
     * Carrier name
     * @var string
     */
    public $carrier;
    /**
     * Airport name
     * @var string
     */
    public $airport;

    /**
     * Direction. Departure OR Arrival
     * @var string
     */
    public $direction;

    /**
     * Gate in terminal
     * @var string
     */
    public $gate;
    /**
     * Terminal
     * @var string
     */
    public $terminal;

    /**
     * Relation date for sorting. today, yesterday or tomorrow
     * @var string
     */
    public $rel_date;
    /**
     * Scgedule source. KBP or IEV
     * @var string
     */
    public $_source;
}