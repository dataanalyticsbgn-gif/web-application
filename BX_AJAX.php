<?
//подключаем пролог ядра bitrix
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
//устанавливаем заголовок страницы
$APPLICATION->SetTitle("AJAX");

   CJSCore::Init(array('ajax'));//подключение расширения ajax
   $sidAjax = 'testAjax';
   //если значение глобальной переменной $_REQUEST - не пустое и не null (запрос из формы "ajax_form" обработан) и значениеглобальной переменной $_REQUEST равно значению переменой $sidAjax
if(isset($_REQUEST['ajax_form']) && $_REQUEST['ajax_form'] == $sidAjax){
   $GLOBALS['APPLICATION']->RestartBuffer();//сброс буфера вывода, удаляется содежмиое, которое было выведено до настоящего момента
   // массив PHP в JavaScript-объект
   echo CUtil::PhpToJSObject(array(
            'RESULT' => 'HELLO',
            'ERROR' => ''
   ));
   die();
}

?>
<!--создан блок с текстом процесс... -->
<div class="group">
   <div id="block"></div >
   <div id="process">wait ... </div >
</div>
<!--создается скрипт: запуск отладчика window.BXDEBUG -->
<script>
   window.BXDEBUG = true;
//создается функция для скрытия блока "block" и показа элемента "process"
function DEMOLoad(){
   BX.hide(BX("block"));
   BX.show(BX("process"));
   /*подготовка URL для запроса $APPLICATION->GetCurPage() подставляет текущую страницу
   ?ajax_form=..$sidAjax добавляет параметр ajax_form со значением переменной $sidAjax
   Функция BX.ajax.loadJSON() отправляет GET-запрос на сформированный адрес. 
   Это происходит в фоновом режиме — страница не перезагружается, пользователь может продолжать работу.
   вызов функции DEMOResponse*/
   BX.ajax.loadJSON(
      '<?=$APPLICATION->GetCurPage()?>?ajax_form=<?=$sidAjax?>',
      DEMOResponse
   );
}

function DEMOResponse (data){
   BX.debug('AJAX-DEMOResponse ', data);//Запись в консоль отладки сообщение "AJAX-DEMOResponse" и полученные данные data
   BX("block").innerHTML = data.RESULT;
   /*Находит элемент с ID "block" на странице
Заменяет его HTML-содержимое на данные из data.RESULT
Предполагается, что сервер вернул готовую HTML-разметку в поле RESULT
    */
   BX.show(BX("block"));// показывает элемент "block"
   BX.hide(BX("process")); //скрывает элемент "process"
/*Создаёт и запускает пользовательское событие DEMOUpdate на элементе "block"
Другие части кода могут "подписаться" на это событие и выполнить дополнительные действия
Пример работы:
Пользователь нажимает кнопку → показывается индикатор загрузки ("process")
Отправляется AJAX-запрос на сервер
Сервер обрабатывает данные и возвращает HTML
Вызывается DEMOResponse:Обновляется содержимое блока
Прячется индикатор загрузки
Показывается обновлённый блок
Запускаются дополнительные скрипты через событие DEMOUpdate

Таким образом, это стандартный обработчик успешного AJAX-запроса в системе Битрикс.
 */
   BX.onCustomEvent(
      BX(BX("block")),
      'DEMOUpdate'
   );
}
/*проверка на готовность документа Битрикс */
BX.ready(function(){
   /*
   BX.addCustomEvent(BX("block"), 'DEMOUpdate', function(){
      window.location.href = window.location.href;
   });
   */
   BX.hide(BX("block"));//скрыт элемент "block"
   BX.hide(BX("process"));//скрыт элемент "process"
   
   /*
   вызов метода для делегирования событий: клик на любом элементе (потомке) с классом css_ajax 
   запустит выполнение функции
   */ 
  BX.bindDelegate(
      document.body, 'click', {className: 'css_ajax' },
      function(e){
         if(!e)
            e = window.event;
         
         DEMOLoad();// вызов функции DEMOLoad  - подготовка URL
         return BX.PreventDefault(e);//отмена стандартного поведения
      }
   );
   
});
// создается блок с именем класса "css_ajax" и надписью "click Me"
</script>
<div class="css_ajax">click Me</div>
<?
//подключаем эпилог ядра bitrix
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>