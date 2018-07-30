<?php

/* @var $this yii\web\View */

$this->title = 'Schedule Application';
$display = 'all';
$type = 'all';
$source = 'all';

if (isset($_GET['filter']))
    $display = $_GET['filter'];
if (isset($_GET['type']))
    $type = $_GET['type'];
if (isset($_GET['source']))
    $source = $_GET['source'];
?>
<div class="site-index">
    <form>
        <div class="form-group">
            <label>Показать рейсы за</label>
            <select name="filter">
               <option value="all" <?php if ($display == "all"): ?>selected<?php endif ?>>Все</option>
               <option value="yesterday" <?php if ($display == "yesterday"): ?>selected<?php endif ?>>Вчера</option>
               <option value="today" <?php if ($display == "today"): ?>selected<?php endif ?>>Сегодня</option>
               <option value="tomorrow" <?php if ($display == "tomorrow"): ?>selected<?php endif ?>>Завтра</option>
            </select>
        </div>
        <div class="form-group">
            <label>Тип рейсов</label>
            <select name="type">
                <option value="all" <?php if ($type == "all"): ?>selected<?php endif ?>>Все</option>
                <option value="departure" <?php if ($type == "departure"): ?>selected<?php endif ?>>Отправление</option>
                <option value="arrival" <?php if ($type == "arrival"): ?>selected<?php endif ?>>Прибытие</option>
            </select>
        </div>
        <div class="form-group">
            <label>Источник расписания</label>
            <select name="source">
                <option value="all" <?php if ($source == "all"): ?>selected<?php endif ?>>Все</option>
                <?php foreach ($parserList as $parser): ?>
                    <option value="<?=strtolower($parser)?>" <?php if (strtolower($source) == strtolower($parser)): ?>selected<?php endif ?>><?=strtoupper($parser)?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-secondary">Применить</button>
        </div>
    </form>

    <table class="table table-striped">
        <thead>
            <th scope="col">#</th>
            <th scope="col">Источник</th>
            <th scope="col">Направление</th>
            <th scope="col">А/К</th>
            <th scope="col">Рейс</th>
            <th scope="col">Время</th>
            <th scope="col">Перевозчик</th>
            <th scope="col">Терминал</th>
            <th scope="col">Гейт</th>
            <th scope="col">Направление</th>
            <th scope="col">статус</th>
        </thead>
        <tbody>
            <?php $num = 1; ?>
            <?php foreach ($schedule AS $flight): ?>
                <?php if (
                        ($display == "all" || $display == $flight->rel_date) &&
                        ($type == "all" || $type == $flight->direction) &&
                        ($source == "all" || $source == strtolower($flight->_source))
                ): ?>
                <tr class="<?php if ($flight->status == "canceled"): ?>cx<?php endif ?>">
                    <td scope="col"><?= $num ?></td>
                    <td><?= $flight->_source ?></td>
                    <td><?= $flight->airport ?></td>
                    <td><?= $flight->flightCarrier ?></td>
                    <td><?= $flight->flightNumber ?></td>
                    <td>
                        <?php if (empty($flight->real_time)): ?>
                            <?= date('d.m.Y H:i', strtotime($flight->schedule_time)) ?>
                        <?php else: ?>
                            <?= date('d.m.Y H:i', strtotime($flight->real_time)) ?>
                            <br/>
                            <span style="color:#c1c1c1;text-decoration:line-through; font-size: 12px"><?= date('d.m.Y H:i', strtotime($flight->schedule_time)) ?></span>
                        <?php endif ?>
                    </td>
                    <td><?= $flight->carrier ?></td>
                    <td><?= $flight->terminal ?></td>
                    <td><?= $flight->gate ?></td>
                    <td><?= $flight->direction ?></td>
                    <td><?= $flight->status ?></td>
                </tr>
                    <?php $num++; ?>
                <?php endif ?>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
