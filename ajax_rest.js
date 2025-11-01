$(document).ready(function () {
  $("form").submit(function (event) {
    var formData = {
      query: $("#ip").val(),
    };
	var url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs/iplocate/address?ip=";
	var token = "477398970ae9180bd4fb2ae061da2b88ca1a9540";

    $.ajax({
      type: "GET",
      url: url + formData.query,
	  beforeSend: function(xhr) {
                 xhr.setRequestHeader("Authorization", "Token "+ token) 
                 xhr.setRequestHeader("Accept", "application/json");  // запрашиваем ответ в JSON.
            },
     // data: '',
      dataType: "json",
      encode: true,
    }).done(function (result) {  // Если запрос успешен, эта функция выполняется.
      console.log(result);  // Выводим весь ответ в консоль для отладки 
      
      // Выводим результат на страницу в <div id="result">. Взяла только ключевые поля из ответа Dadata.
      
      var output = "";
      if (result.location && result.location.data) {  // Проверяем, есть ли данные.
        output += "Город: " + (result.location.data.city || "Неизвестно") + "<br>";  // Пример: город.
        output += "Регион: " + (result.location.data.region || "Неизвестно") + "<br>";  // Пример: регион.
        output += "Страна: " + (result.location.data.country || "Неизвестно") + "<br>";  // Пример: страна.
        
      } else {
        output = "Нет данных по этому IP.";
      }
      $("#result").html(output);  // Вставляем текст в <div id="result"></div>.
    }).fail(function (error) {  // Если запрос провалился (например, неверный токен или IP).
      console.log("Ошибка: " + error.status + " " + error.statusText);  // Вывод в консоль.
      $("#result").html("Ошибка запроса. Проверь токен или IP.");  // Вывод на страницу.
    });

    event.preventDefault();
  });
});