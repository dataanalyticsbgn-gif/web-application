<?php

/**
 * Используйте эти классы, если не умеете или не хотите работать с `composer`
 * и использовать библиотеку [dadata-php](https://github.com/hflabs/dadata-php/).
 * 
 * Классы не имеют внешних зависимостей, кроме `curl`. Примеры вызова внизу файла.
 */

class TooManyRequests extends Exception
{
}

class Dadata
{
    private $clean_url = "https://cleaner.dadata.ru/api/v1/clean";
    private $suggest_url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs";
    private $token;
    private $secret;
    private $handle;

    public function __construct($token, $secret)
    {
        $this->token = $token;
        $this->secret = $secret;
    }

    /**
     * Initialize connection.
     */
    public function init()
    {
        $this->handle = curl_init();
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->handle, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: Token " . $this->token,
            "X-Secret: " . $this->secret,
        ));
        curl_setopt($this->handle, CURLOPT_POST, 1);
    }

    /**
     * Clean service.
     * See for details:
     *   - https://dadata.ru/api/clean/address
     *   - https://dadata.ru/api/clean/phone
     *   - https://dadata.ru/api/clean/passport
     *   - https://dadata.ru/api/clean/name
     * 
     * (!) This is a PAID service. Not included in free or other plans.
     */
    public function clean($type, $value)
    {
        $url = $this->clean_url . "/$type";
        $fields = array($value);
        return $this->executeRequest($url, $fields);
    }

    /**
     * Find by ID service.
     * See for details:
     *   - https://dadata.ru/api/find-party/
     *   - https://dadata.ru/api/find-bank/
     *   - https://dadata.ru/api/find-address/
     */
    public function findById($type, $fields)
    {
        $url = $this->suggest_url . "/findById/$type";
        return $this->executeRequest($url, $fields);
    }

    /**
     * Reverse geolocation service.
     * See https://dadata.ru/api/geolocate/ for details.
     */
    public function geolocate($lat, $lon, $count = 10, $radius_meters = 100)
    {
        $url = $this->suggest_url . "/geolocate/address";
        $fields = array(
            "lat" => $lat,
            "lon" => $lon,
            "count" => $count,
            "radius_meters" => $radius_meters
        );
        return $this->executeRequest($url, $fields);
    }

    /**
     * Detect city by IP service.
     * See https://dadata.ru/api/iplocate/ for details.
     */
    public function iplocate($ip)
    {
        $url = $this->suggest_url . "/iplocate/address";
        $fields = array(
            "ip" => $ip
        );
        return $this->executeRequest($url, $fields);
    }

    /**
     * Suggest service.
     * See for details:
     *   - https://dadata.ru/api/suggest/address
     *   - https://dadata.ru/api/suggest/party
     *   - https://dadata.ru/api/suggest/bank
     *   - https://dadata.ru/api/suggest/name
     *   - ...
     */
    public function suggest($type, $fields)
    {
        $url = $this->suggest_url . "/suggest/$type";
        return $this->executeRequest($url, $fields);
    }

    /**
     * Close connection.
     */
    public function close()
    {
        curl_close($this->handle);
    }

    private function executeRequest($url, $fields)
    {
        curl_setopt($this->handle, CURLOPT_URL, $url);
        if ($fields != null) {
            curl_setopt($this->handle, CURLOPT_POST, 1);
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, json_encode($fields));
        } else {
            curl_setopt($this->handle, CURLOPT_POST, 0);
        }
        $result = $this->exec();
        $result = json_decode($result, true);
        return $result;
    }

    private function exec()
    {
        $result = curl_exec($this->handle);
        $info = curl_getinfo($this->handle);
        if ($info['http_code'] == 429) {
            throw new TooManyRequests();
        } elseif ($info['http_code'] != 200) {
            throw new Exception('Request failed with http code ' . $info['http_code'] . ': ' . $result);
        }
        return $result;
    }
}

// ====================================================================================
// НАЧАЛО ОБРАБОТКИ ФОРМЫ
// ====================================================================================

// Устанавливаем кодировку UTF-8 для корректного отображения русских букв
header('Content-Type: text/html; charset=utf-8');

// Мои API-ключи от DaData (токен и секретный ключ)
$token = "3a587b20c866b5bb14c227e7dc974be108......";
$secret = "089178424a81e7144b6ba64daeae20d48.......";

// Проверяем, была ли отправлена форма методом POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    
    // Получаем данные из формы и очищаем их от лишних пробелов
    $lastName = trim($_POST['user_last_name']);   // Фамилия
    $firstName = trim($_POST['user_name']);        // Имя
    $middleName = trim($_POST['user_second_name']); // Отчество
    
    // Проверяем, что все поля заполнены (базовая валидация)
    if (empty($lastName) || empty($firstName) || empty($middleName)) {
        // Если какое-то поле пустое - показываем ошибку
        echo '<!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <title>Ошибка</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
                .error { background: #ffebee; border: 1px solid #f44336; padding: 15px; border-radius: 4px; color: #c62828; }
                a { color: #4CAF50; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="error">
                <h2>❌ Ошибка!</h2>
                <p>Все поля (Фамилия, Имя, Отчество) обязательны для заполнения.</p>
                <p><a href="form.html">← Вернуться к форме</a></p>
            </div>
        </body>
        </html>';
        exit; // Прекращаем выполнение скрипта
    }
    
    // Формируем полное ФИО в формате "Фамилия Имя Отчество"
    $fullName = $lastName . ' ' . $firstName . ' ' . $middleName;
    
    try {
        // Создаем объект класса Dadata, передавая ему токен и секретный ключ
        $dadata = new Dadata($token, $secret);
        
        // Инициализируем соединение с API
        $dadata->init();
        
        // Отправляем запрос на стандартизацию ФИО
        // Метод clean() принимает тип данных ("name") и значение (полное ФИО)
        $result = $dadata->clean("name", $fullName);
        
        // Закрываем соединение с API
        $dadata->close();
        
        // Проверяем, что API вернул результат
        if ($result && isset($result[0])) {
            // Получаем стандартизированные данные из ответа API
            $standardized = $result[0];
            
            // Начинаем формировать HTML-страницу с результатами
            echo '<!DOCTYPE html>
            <html lang="ru">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Результат стандартизации</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        max-width: 800px;
                        margin: 50px auto;
                        padding: 20px;
                        background-color: #f5f5f5;
                    }
                    .container {
                        background: white;
                        padding: 30px;
                        border-radius: 8px;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }
                    h1 {
                        color: #4CAF50;
                        text-align: center;
                    }
                    .section {
                        margin: 20px 0;
                        padding: 15px;
                        background: #f9f9f9;
                        border-left: 4px solid #4CAF50;
                    }
                    .section h2 {
                        margin-top: 0;
                        color: #333;
                        font-size: 18px;
                    }
                    .data-row {
                        display: flex;
                        padding: 8px 0;
                        border-bottom: 1px solid #eee;
                    }
                    .data-row:last-child {
                        border-bottom: none;
                    }
                    .label {
                        font-weight: bold;
                        width: 200px;
                        color: #666;
                    }
                    .value {
                        color: #333;
                        flex: 1;
                    }
                    .btn-back {
                        display: inline-block;
                        margin-top: 20px;
                        padding: 10px 20px;
                        background: #4CAF50;
                        color: white;
                        text-decoration: none;
                        border-radius: 4px;
                    }
                    .btn-back:hover {
                        background: #45a049;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>✅ Результат стандартизации ФИО</h1>
                    
                    <!-- Раздел: исходные данные -->
                    <div class="section">
                        <h2>Введенные данные:</h2>
                        <div class="data-row">
                            <span class="label">Фамилия:</span>
                            <span class="value">' . htmlspecialchars($lastName) . '</span>
                        </div>
                        <div class="data-row">
                            <span class="label">Имя:</span>
                            <span class="value">' . htmlspecialchars($firstName) . '</span>
                        </div>
                        <div class="data-row">
                            <span class="label">Отчество:</span>
                            <span class="value">' . htmlspecialchars($middleName) . '</span>
                        </div>
                    </div>
                    
                    <!-- Раздел: стандартизированные данные -->
                    <div class="section">
                        <h2>Стандартизированные данные:</h2>
                        <div class="data-row">
                            <span class="label">Полное ФИО:</span>
                            <span class="value">' . htmlspecialchars($standardized['result'] ?? 'Не определено') . '</span>
                        </div>
                        <div class="data-row">
                            <span class="label">Фамилия:</span>
                            <span class="value">' . htmlspecialchars($standardized['surname'] ?? 'Не определено') . '</span>
                        </div>
                        <div class="data-row">
                            <span class="label">Имя:</span>
                            <span class="value">' . htmlspecialchars($standardized['name'] ?? 'Не определено') . '</span>
                        </div>
                        <div class="data-row">
                            <span class="label">Отчество:</span>
                            <span class="value">' . htmlspecialchars($standardized['patronymic'] ?? 'Не определено') . '</span>
                        </div>
                        <div class="data-row">
                            <span class="label">Пол:</span>
                            <span class="value">' . ($standardized['gender'] == 'М' ? 'Мужской' : ($standardized['gender'] == 'Ж' ? 'Женский' : 'Не определен')) . '</span>
                        </div>
                    </div>
                    
                    <!-- Дополнительная информация (если есть) -->
                    <div class="section">
                        <h2>Дополнительная информация:</h2>
                        <div class="data-row">
                            <span class="label">Качество данных:</span>
                            <span class="value">' . htmlspecialchars($standardized['qc'] ?? 'Не определено') . '</span>
                        </div>
                    </div>
                    
                    <!-- Кнопка возврата к форме -->
                    <a href="form.html" class="btn-back">← Вернуться к форме</a>
                </div>
            </body>
            </html>';
            
        } else {
            // Если API не вернул данные - показываем ошибку
            throw new Exception('API не вернул результат');
        }
        
    } catch (Exception $e) {
        // Если произошла ошибка при работе с API - показываем сообщение об ошибке
        echo '<!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <title>Ошибка API</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
                .error { background: #ffebee; border: 1px solid #f44336; padding: 15px; border-radius: 4px; color: #c62828; }
                a { color: #4CAF50; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="error">
                <h2>❌ Ошибка при обработке данных</h2>
                <p>Произошла ошибка при обращении к API DaData:</p>
                <p><strong>' . htmlspecialchars($e->getMessage()) . '</strong></p>
                <p><a href="form.html">← Вернуться к форме</a></p>
            </div>
        </body>
        </html>';
    }
    
} else {
    // Если кто-то попытался открыть этот файл напрямую (без отправки формы)
    echo '<!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Ошибка доступа</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
            .warning { background: #fff3e0; border: 1px solid #ff9800; padding: 15px; border-radius: 4px; color: #e65100; }
            a { color: #4CAF50; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class="warning">
            <h2>⚠️ Неверный доступ</h2>
            <p>Этот файл предназначен для обработки данных формы.</p>
            <p>Пожалуйста, используйте форму для отправки данных.</p>
            <p><a href="form.html">→ Перейти к форме</a></p>
        </div>
    </body>
    </html>';
}
?>
