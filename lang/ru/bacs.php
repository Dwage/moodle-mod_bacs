<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License.
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Description
 *
 * @package    mod_bacs
 * @copyright  SybonTeam, sybon.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['actions'] = 'Действия';
$string['actionswithcontest'] = 'Действия с контестом';
$string['add'] = 'Добавить';
$string['advancedcontestsettings'] = 'Продвинутые настройки контеста';
$string['advancedsettingsmessage1'] =
    'Эта секция предоставляет прямой доступ для осуществления сложных операций с данными контеста. Например, быстрое копирование контеста, отладка или добавление задач, отсутствующих в базе данных. ';
$string['advancedsettingsmessage2'] =
    'Обратите внимание, что другие компоненты не отслеживают изменения в этих полях и перезаписывают их содержимое при выполнении большинства операций. ';
$string['advancedsettingsmessage3'] =
    'Используйте только если вы знаете что делаете. ';
$string['advancedwarning'] = 'Внимание!';
$string['allcollections'] = 'Все коллекции';
$string['alltasks'] = 'Все задачи';
$string['alltasksfrom'] = 'Все задачи из';
$string['amountofaccepted'] = 'Количество решивших';
$string['amountofpretests'] = 'Кол-во претестов';
$string['amountoftests'] = 'Кол-во тестов';
$string['amountoftried'] = 'Количество попытавшихся';
$string['applyfilter'] = 'Применить фильтр';
$string['author'] = 'Автор';
$string['backtosubmit'] = 'Назад к посылке';
$string['backtosubmits'] = 'Назад к посылкам';
$string['bacs:addinstance'] = 'Добавлять в курс и удалять из него контесты';
$string['bacs:edit'] = 'Изменять любые настройки контеста, перепроверять решения';
$string['bacs:readtasks'] = 'Просматривать полные условия любых задач в контесте';
$string['bacs:submit'] = 'Отправлять решения любых задач в контесте';
$string['bacs:view'] = 'Просматривать задачи и свои посылки в контесте';
$string['bacs:viewany'] = 'Просматривать детальную информацию о любой посылке в контесте';
$string['bacsrating:averagerating'] = 'Средний рейтинг участников';
$string['bacsrating:participants'] = 'Участники';
$string['bacsrating:participantslist'] = 'Список участников';
$string['bacsrating:participant'] = 'Участник';
$string['bacsrating:rated'] = 'С рейтингом';
$string['bacsrating:rating'] = 'Рейтинг';
$string['bacsrating:sortby'] = 'Сортировать по';
$string['bacsrating:sortby:rating_asc'] = 'Рейтинг (по возрастанию)';
$string['bacsrating:sortby:rating_desc'] = 'Рейтинг (по убыванию)';
$string['beforethecontest'] = 'До начала контеста';
$string['bright_brighttheme'] = 'Светлая';
$string['cannotviewsubmit'] = 'Нельзя посмотреть эту посылку';
$string['changegrouptosubmit'] = 'Для отправки решений вам нужно выбрать группу, членом которой вы являетесь.';
$string['charactermustbeadded'] = 'этот символ должен быть добавлен';
$string['charactermustberemoved'] = 'этот символ должен быть удален';
$string['chartdayhourdistribution'] = 'Диаграмма распределения по часу дня';
$string['chartverdicts'] = 'Диаграмма вердиктов';
$string['choosetask'] = 'Выберите задачу';
$string['clear'] = 'Очистить';
$string['clearform'] = 'Очистить форму';
$string['compare'] = 'Сравнить';
$string['comparison'] = 'Сравнение';
$string['compilermessage'] = 'Сообщение компилятора';
$string['configmaxselectableyear'] = 'Максимальный год, который можно выбрать во времени начала контеста или времени конца контеста на странице настроек контеста';
$string['configminselectableyear'] = 'Минимальный год, который можно выбрать во времени начала контеста или времени конца контеста на странице настроек контеста';
$string['configpreferedlanguage'] = 'Языки, которые будут использоваться по умолчанию для отображения условий задач';
$string['preferedlanguage'] = 'Предпочитаемые языки';
$string['configsybonapikey'] = 'Sybon API key используется для отправки посылок, получения задач и получения языков программирования';
$string['contesthasstartednotification'] = 'Контест начался. Хотите войти в контест?';
$string['contestmode'] = 'Режим контеста';
$string['contestname'] = 'Название контеста';
$string['contestsettings'] = 'Настройки контеста';
$string['contesttasks'] = 'Задачи контеста';
$string['count'] = 'Количество';
$string['course'] = 'Курс';
$string['coverssametests'] = 'покрывает те же тесты, что и новая группа';
$string['dark_darktheme'] = 'Темная';
$string['dashboard'] = 'Дашборд';
$string['dateandtime'] = 'Дата и время';
$string['days_morethanxdays'] = 'дней';
$string['default_defaulttheme'] = 'Стандартная';
$string['delete'] = 'Удалить';
$string['detectincidents'] = 'Выявлять инциденты';
$string['devirtualize'] = 'Отменить виртуальный';
$string['devirtualizewarning'] =
    'Вы уверены что хотите удалить виртуальное участие? Посылки пользователя НЕ будут удалены. Данные о виртуальном участии будет нельзя восстановить.';
$string['diagnostics:check'] = 'Проверка';
$string['diagnostics:deprecated_tasks_msg'] = 'Проверка устаревших задач. Доступно устаревших задач: {$a}';
$string['diagnostics:duplicate_tasks_msg'] = 'Проверка дубликатов задач. {$a->tasks_to_be_replaced} задач должно быть заменено / {$a->tasks_without_replacement} дубликатов без замены / {$a->tasks_with_the_same_name} задач с одинаковыми названиями';
$string['diagnostics:duration'] = 'Длительность';
$string['diagnostics:error'] = 'Ошибка';
$string['diagnostics:message'] = 'Сообщение';
$string['diagnostics:milliseconds_short'] = 'мс';
$string['diagnostics:ok'] = 'OK';
$string['diagnostics:showdetailedlogs'] = 'Показать детальные логи';
$string['diagnostics:status'] = 'Статус';
$string['diagnostics:sybon_api_collections_msg'] = 'Проверка Sybon API задач. Доступно коллекций задач: {$a}';
$string['diagnostics:sybon_api_compilers_msg'] = 'Проверка Sybon API языков. Доступно компиляторов/языков: {$a}';
$string['diagnostics:sybon_api_submits_msg'] = 'Проверка Sybon API посылок. Sybon ID тестовой посылки: {$a}';
$string['diagnostics:sybon_api_submits_msg_no_submits'] = 'Проверка Sybon API посылок: нет доступных посылок. Должна быть доступна хотя бы одна посылка (не "В очереди") для проверки Sybon API посылок';
$string['diagnostics:task_pretests_msg'] = 'Проверка претестов задач. {$a->tasks_in_total} всего задач / {$a->tasks_with_wrong_pretests_count} задач с неверным количеством претестов / {$a->tasks_with_wrong_pretests_index} задач с неверной нумерацией претестов / {$a->tasks_without_pretests} задач без претестов';
$string['diagnostics:task_statement_format_msg'] = 'Проверка форматов условий задач. {$a->with_doc} в DOC(DOCX) / {$a->with_pdf} в PDF / {$a->with_html} в HTML / {$a->with_other_format} в других форматах';
$string['diagnostics:test_points_strings_msg'] = 'Проверка настроек задач в контестах. {$a->records_in_total} всего записей / {$a->records_with_custom_points} с нестандартыми баллами / {$a->records_with_missing_task} задач не найдено / {$a->records_mismatched} с несовпадающими баллами';
$string['diagnostics:warning'] = 'Внимание';
$string['download'] = 'Скачать';
$string['duplicatetasks'] = 'Несколько одинаковых задач в контесте не поддерживаются!';
$string['editortheme'] = 'Тема редактора';
$string['endtime'] = 'Время конца';
$string['entercomment'] = 'Введите комментарий:';
$string['entercontest'] = 'Войти в контест';
$string['entercontestwithoutvirtual'] = 'Войти в контест без виртуального участия';
$string['enterpoints'] = 'Введите баллы:';
$string['enterverdict'] = 'Введите вердикт:';
$string['errordeletingtask'] = "Ошибка удаления задачи с id=";
$string['fillwithintegers'] = "Все поля должны быть заполнены целыми неотрицительными числами.";
$string['format'] = 'Формат';
$string['from'] = 'Время начала контеста';
$string['fromshort'] = 'От';
$string['futurepointsnotification'] =
    'Обратите внимание, что изменение стоимости тестов не повлияет на все предыдущие посылки. Вам необходимо нажать "Пересчитать баллы" в меню "Действия" для того, чтоб применить новые баллы за тесты к старым посылкам.';
$string['generalnopermission'] = 'У вас нет прав на эту операцию!';
$string['gotogroupsettings'] = 'Перейти к настройкам для отдельных групп';
$string['groupname'] = 'Название группы';
$string['groupsettingsarenotused'] = 'Настройки для отдельных групп не используются';
$string['groupsettingsareused'] = 'Для {$a->with_group_settings} из {$a->total_count} групп используются отдельные настройки';
$string['hideinactive'] = 'Скрыть неактивных';
$string['hidesolution'] = 'Скрыть решение';
$string['hideupsolving'] = 'Скрыть дорешивание';
$string['id'] = 'ID';
$string['incident'] = 'Инцидент';
$string['incidentdetectiondisabledalert'] = 'Автоматическое выявление инцидентов для этого контеста отключено. Включите "Выявлять инциденты" в секции "Инциденты" в настройках контеста.';
$string['incidents'] = 'Инциденты';
$string['incidentssettings'] = 'Настройки инцидентов';
$string['input'] = 'Ввод';
$string['invalidrange'] = "Некорректный диапазон!";
$string['isolatedparticipants'] = 'Изолированные участники';
$string['isolateparticipants'] = 'Изолировать участников';
$string['language'] = 'Язык';
$string['lastimprovedat'] = 'Улучшено в';
$string['letterlimit26'] = 'Список ограничен максимум в 26 букв. Добавлять больше нельзя.';
$string['letterlistempty'] = "Ошибка удаления последней буквы: список букв пуст";
$string['linktothissubmission'] = 'Ссылка на эту посылку';
$string['load_from_file'] = 'Загрузить из файла';
$string['maximumtasks26'] = "Конест ограничен максимум в 26 задач. Добавлять больше нельзя.";
$string['maxselectableyear'] = 'Максимальный доступный год';
$string['method'] = 'Метод';
$string['memory'] = 'Память';
$string['memorylimit'] = 'Лимит памяти';
$string['minselectableyear'] = 'Минимальный доступный год';
$string['modulename'] = 'Контест BACS';
$string['modulename_help'] = 'BACS is the plugin that do smth. Sure, it\'s better than nothing';
$string['modulenameplural'] = 'Контесты BACS';
$string['more'] = 'Больше';
$string['morethan'] = 'больше чем';
$string['mysubmits'] = 'Мои посылки';
$string['n'] = 'N';
$string['negativepointsnotallowed'] = "Отрицательные баллы не поддерживаются";
$string['nopermissiontosubmit'] = 'У вас нет прав на отправку решений.';
$string['not_found'] = 'Не найдено!';
$string['not_started'] = 'Контест не начался!';
$string['open'] = 'Открыть';
$string['outputexpected'] = 'Ожидаемый вывод';
$string['outputreal'] = 'Реальный вывод';
$string['penalty'] = "Штраф";
$string['pluginadministration'] = 'Настройки BACS';
$string['plugindiagnosticspage'] = 'Страница диагностики плагина';
$string['pluginname'] = 'BACS контесты';
$string['points'] = "Баллы";
$string['pointsforfullsolution'] = 'Баллов за полное решение';
$string['pointsformissingtask'] = "Нельзя загрузить баллы за тесты для отсутствующей задачи.";
$string['pointspergroup'] = 'Баллов на группу';
$string['pointspertest'] = 'Баллов за тест';
$string['presolving'] = 'Разрешить прорешивание до начала контеста';
$string['pretest'] = 'Претест';
$string['privacy:metadata:bacs'] = 'Хранит информацию о контестах и их мониторах';
$string['privacy:metadata:bacs:standings'] = 'JSON-закешированная информация о всех посылках, которые отображены в мониторе';
$string['privacy:metadata:bacs_group_info'] = 'Хранит специальные настройки для групп и монитор группы';
$string['privacy:metadata:bacs_group_info:standings'] = 'JSON-закешированная информация о всех посылках, которые отображены в мониторе группы';
$string['privacy:metadata:bacs_submits'] = 'Хранит информацию о посылках';
$string['privacy:metadata:bacs_submits:contest_id'] = 'ID контеста, в котором была сделана посылка';
$string['privacy:metadata:bacs_submits:group_id'] = 'ID группы в которой была сделана посылка (или ноль если группы не использовались)';
$string['privacy:metadata:bacs_submits:info'] = 'Сообщение компилятора или специальная информация';
$string['privacy:metadata:bacs_submits:lang_id'] = 'ID язка программирования';
$string['privacy:metadata:bacs_submits:max_memory_used'] = 'Максимальное потребление памяти среди всех тестов в байтах';
$string['privacy:metadata:bacs_submits:max_time_used'] = 'Максимальное время исполнения среди всех тестов в миллисекундах';
$string['privacy:metadata:bacs_submits:points'] = 'Баллы';
$string['privacy:metadata:bacs_submits:result_id'] = 'Результат проверки';
$string['privacy:metadata:bacs_submits:source'] = 'Исходный код';
$string['privacy:metadata:bacs_submits:submit_time'] = 'Время, когда была сделана посылка';
$string['privacy:metadata:bacs_submits:task_id'] = 'ID задачи';
$string['privacy:metadata:bacs_submits:test_num_failed'] = 'Номер первого непройденного теста';
$string['privacy:metadata:bacs_submits:user_id'] = 'Автор посылки';
$string['privacy:metadata:bacs_submits_tests'] = 'Хранит информацию о всех запусках решения на всех тестах задачи';
$string['privacy:metadata:bacs_submits_tests:memory_used'] = 'Потребление памяти в байтах';
$string['privacy:metadata:bacs_submits_tests:status_id'] = 'Результат проверки';
$string['privacy:metadata:bacs_submits_tests:submit_id'] = 'ID посылки';
$string['privacy:metadata:bacs_submits_tests:test_id'] = 'ID теста';
$string['privacy:metadata:bacs_submits_tests:time_used'] = 'Время исполнения в миллисекундах';
$string['privacy:metadata:bacs_submits_tests_output'] = 'Хранит вывод посылки на претестах';
$string['privacy:metadata:bacs_submits_tests_output:output'] = 'Вывод посылки';
$string['privacy:metadata:bacs_submits_tests_output:submit_id'] = 'ID посылки';
$string['privacy:metadata:bacs_submits_tests_output:test_id'] = 'ID теста';
$string['privacy:metadata:sybon_checking_service'] = 'Используется для запуска решений и получения результатов проверки';
$string['privacy:metadata:sybon_checking_service:lang_id'] = 'ID языка программирования';
$string['privacy:metadata:sybon_checking_service:source'] = 'Исходный код';
$string['privacy:metadata:sybon_checking_service:task_id'] = 'ID задачи';
$string['privacy:metadata:sybon_checking_service:timestamp'] = 'Время, когда посылка была передана на проверку';
$string['prog_lang'] = 'Язык программирования';
$string['programcode'] = 'Код программы';
$string['rawcontesttaskids'] = 'Закодированная строка ID задач';
$string['rawcontesttasktestpoints'] = 'Закодированная строка баллов за тесты';
$string['recalcpoints'] = 'Пересчитать баллы';
$string['recalculateincidents'] = 'Пересчитать инциденты';
$string['recalculatepoints'] = 'Пересчитать баллы';
$string['recalculatepointsfor'] = 'Пересчитать баллы по:';
$string['rejectsubmit'] = 'Отклонить посылку';
$string['rejudge'] = 'Перепроверить';
$string['rejudgesubmits'] = 'Перепроверить посылки';
$string['rejudgesubmitsfor'] = 'Перепроверить посылки по:';
$string['rememberlanguage'] = 'Запомнить выбранный язык';
$string['result'] = 'Результат';
$string['resultsgraph'] = 'График результатов';
$string['search'] = 'Поиск';
$string['seconds_short'] = 'с';
$string['send'] = 'Отправить';
$string['sendforjudgement'] = 'Отправить решение на проверку';
$string['sendinginprogress'] = 'Идет отправка';
$string['sentat'] = 'Отправлено в';
$string['setcomment'] = 'Задать комментарий';
$string['setpoints'] = 'Задать баллы';
$string['settings'] = 'Настройки';
$string['setverdict'] = 'Задать вердикт';
$string['showfirstacceptedflag'] = 'Отображать метку первого верного решения';
$string['showincidentflags'] = 'Отображать инциденты';
$string['showlastimprovementcolumn'] = 'Отображать столбец последнего улучшения результата';
$string['showsolution'] = 'Показать решение';
$string['showsubmitsfor'] = 'Показать посылки по ';
$string['showsubmitsuptobest'] = 'Отображать количество попыток до лучшего результата';
$string['showtestingflag'] = 'Отображать метку тестирования';
$string['showupsolving'] = 'Показать дорешивание';
$string['source'] = 'Текст решения';
$string['standings'] = 'Монитор';
$string['standingsmode'] = 'Режим монитора';
$string['standingssettings'] = 'Настройки монитора';
$string['starttime'] = 'Время начала';
$string['startvirtualparticipationnow'] = 'Начать виртуальное участие сейчас';
$string['statement'] = 'Условия';
$string['status'] = 'Статус';
$string['statusfrozen'] = 'Заморожен';
$string['statusnotstarted'] = 'Не начался';
$string['statusover'] = 'Окончен';
$string['statusrunning'] = 'Идет';
$string['statusunknown'] = 'Неизвестен';
$string['submissionsspampenalty'] =
    "Вы отправили слишком много посылок! За последние 5 минут вы отправили 50 посылок. Отправка посылок временно запрещена. Попробуйте обновить эту страницу позже.";
$string['submissionsspamwarning'] =
    "Вы отправляете много посылок! Если вы отправите 50 посылок в течении 5 минут, то вы временно потеряете возможность отправлять посылки.";
$string['submit_verdict_0'] = "Неизвестен";
$string['submit_verdict_1'] = "В очереди";
$string['submit_verdict_10'] = "Превышено ограничение размера выходных данных";
$string['submit_verdict_11'] = "Неправильный формат вывода";
$string['submit_verdict_12'] = "Неправильный ответ";
$string['submit_verdict_13'] = "Верное решение";
$string['submit_verdict_14'] = "Некорректный запрос";
$string['submit_verdict_15'] = "Выводится недостаточно данных";
$string['submit_verdict_16'] = "Превышено ограничение количества запросов";
$string['submit_verdict_17'] = "Выводятся лишние данные";
$string['submit_verdict_18'] = "Посылка отклонена";
$string['submit_verdict_2'] = "Тестируется";
$string['submit_verdict_3'] = "Ошибка сервера";
$string['submit_verdict_4'] = "Ошибка компиляции";
$string['submit_verdict_5'] = "Ошибка исполнения";
$string['submit_verdict_6'] = "Ошибка тестирования";
$string['submit_verdict_7'] = "Превышено ограничение времени исполнения";
$string['submit_verdict_8'] = "Превышено ограничение реального времени";
$string['submit_verdict_9'] = "Превышено ограничение памяти";
$string['submitmessagetaskismissing'] =
    'Эта задача отсутствует в базе данных Moodle. Удалите эту задачу из контеста или обновите информацию о доступных задачах.';
$string['submits'] = 'Посылки';
$string['submitsfrom'] = 'Посылки от';
$string['submitslowercase'] = 'посылки';
$string['sumofpoints'] = 'Сумма баллов';
$string['sybonapikey'] = 'Sybon API key';
$string['task'] = 'Задача';
$string['taskdynamics'] = 'Динамика задач';
$string['taskid'] = 'ID задачи';
$string['tasklist'] = 'Список задач';
$string['taskname'] = 'Название задачи';
$string['taskofsubmitismissingincontest'] = '
    Задача (ID {$a->taskid}) этой посылки отсутствует в данном контесте.
    Вам следует добавить эту задачу обратно в контест, если вы хотите, чтобы посылка отображалась правильно.';
$string['taskofsubmitismissingincontestanddb'] = '
    Задача (ID {$a->taskid}) этой посылки отсутствует как в данном контесте, так и в базе данных Moodle.
    Вам следует обновить информацию о доступных задачах и добавить эту задачу обратно в контест, если вы хотите, чтобы посылка отображалась правильно.';
$string['tasks'] = 'Задачи';
$string['test'] = 'Тест';
$string['testgroup'] = 'Группа тестов';
$string['testpoints'] = 'Баллы за тесты';
$string['tests'] = 'Тесты';
$string['therearenoresults'] = 'Нет результатов';
$string['time'] = 'Время';
$string['timelimit'] = 'Лимит времени';
$string['to'] = 'Время конца контеста';
$string['toshort'] = 'До';
$string['totalincidents'] = 'Всего инцидентов подозрительного поведения';
$string['updatestandings'] = 'Обновить монитор';
$string['uppercaselanguagenotfound'] = 'ЯЗЫК НЕ НАЙДЕН';
$string['uppercasetasknotfound'] = 'ЗАДАЧА НЕ НАЙДЕНА';
$string['upsolving'] = 'Разрешить дорешивание';
$string['upsolving_help'] =
    'Студентам будет разрешено посылать решения после конца контеста. Результаты дорешивания будут показаны отдельно.';
$string['upsolvingisdisabled'] = 'Дорешивание для этого контеста отключено.';
$string['usecustomtestpoints'] = 'Использовать нестандартные баллы';
$string['usegroupsettings'] = 'Включить специальные настройки для этой группы';
$string['userdynamics'] = 'Динамика участников';
$string['username'] = 'Имя участника';
$string['verdict'] = 'Вердикт';
$string['virtualparticipants'] = 'Виртуальные участники';
$string['virtualparticipantslist'] = 'Список виртуальных участников';
$string['virtualparticipantslistisempty'] = 'Список виртуальных участников пуст.';
$string['virtualparticipation'] = 'Виртуальное участие';
$string['virtualparticipationallow'] = 'Разрешить виртуальное участие';
$string['virtualparticipationallowmsg'] = 'В этом контесте доступно виртуальное участие. Виртуальное участие станет доступным после начала контеста.';
$string['virtualparticipationalreadyhavesubmits'] = 'Вы не можете начать виртуальное участие из-за того, что у вас уже есть посылки в этом контесте.';
$string['virtualparticipationconfirmstartdmsg'] = 'Вы уверены что хотите начать виртуальное участие сейчас? Вы не сможете отменить виртуальное участие после его начала.';
$string['virtualparticipationdisable'] = 'Запретить виртуальное участие';
$string['virtualparticipationdisabledmsg'] = 'Виртуальное участие для этого контеста отключено.';
$string['virtualparticipationgeneralwarning'] = '
    Виртуальное участие &mdash; это способ поучаствовать в контесте в независимое время.
    Результаты всех пользователей показываются в соответствии с их различными моментами старта.
    Если вы уже участвовали в этом контесте или если вы уже видели задачи этого контеста, то вам следует использовать дорешивание вместо этого.
    <br><br>
    <b>Внимание!</b> Каждый пользователь может начать виртуальное участие только один раз.
    Если у вас уже есть неотклоненные посылки в этом контесте, то вы не сможете участвовать в нем виртуально.';
$string['virtualparticipationonly'] = 'Только виртуальное участие';
$string['virtualparticipationonlymsg'] = 'Этот контест только для виртуального участия. Виртуальное участие станет доступным после начала контеста.';
$string['virtualparticipationselectyourgroup'] = 'Для того, чтобы начать виртуальное участие, выберите группу, членом которой вы являетесь.';
$string['virtualparticipationstartedat'] = 'Ваше виртуальное участие началось в ';

$string['difficultyanalysis'] = 'Анализ сложности контеста';
$string['analyzecontestdifficulty'] = 'Анализ сложности контеста';
$string['no_plugin_installed'] = 'Плагин bacs_rating не установлен. Вы не можете использовать этот функционал';
$string['notasksselected'] = 'Не выбрано ни одной задачи. Пожалуйста, добавьте задачи в контест перед анализом.';
$string['difficulty_analysis_students_can_solve'] = 'Студенты, которые могут решить задачу';
$string['difficulty_analysis_ideal_curve'] = 'Идеальная кривая';
$string['difficulty_analysis_number_of_students'] = 'Количество студентов';
$string['difficulty_analysis_tasks'] = 'Задачи';
$string['difficulty_analysis_no_students'] = 'В этот курс не зачислено ни одного студента.';
$string['difficulty_analysis_no_taskids'] = 'Не удалось получить идентификаторы задач.';
$string['difficulty_analysis_no_userids'] = 'Не удалось получить идентификаторы пользователей.';

$string['chart_mode_realtime'] = 'Реальное время';
$string['chart_mode_normalized'] = 'Нормализованное (Гонка)';
$string['view_leaderdynamics'] = 'Динамика лидеров';
$string['view_resultsgraph'] = 'График результатов';
$string['view_taskdynamics'] = 'Динамика задач';
$string['select_task'] = 'Выберите задачу';
$string['view_monitor'] = 'Участники';
$string['verdict_ok'] = 'Принято (OK)';
$string['verdict_not_ok'] = 'Отклонено (Not OK)';
$string['timefromstart'] = 'Время от начала контеста';