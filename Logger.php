<?php

/* Определены основные типы сообщений */

class LogLevel
{
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';
}

/* Определены методы, которые должно быть использованы */

interface LoggerInterface
{
    public function emergency($message, array $context = array());
    public function alert($message, array $context = array());
    public function critical($message, array $context = array());
    public function error($message, array $context = array());
    public function warning($message, array $context = array());
    public function notice($message, array $context = array());
    public function info($message, array $context = array());
    public function debug($message, array $context = array());
    public function log($level, $message, array $context = array());
}

/* Определены правила, что именно должно происходить при вызове методов */

abstract class AbstractLogger implements LoggerInterface
{
    public function emergency($message, array $context = array())
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = array())
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = array())
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = array())
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = array())
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
}

/* Class Logger */

class Logger extends AbstractLogger implements LoggerInterface
{
    public $routes; // список роутов

    public function __construct()
    {
        $this->routes = new SplObjectStorage(); // хранилище роутов
    }

    public function log($level, $message, array $context = [])
    {
        // пробегаемся по всем активным роутом, вызывая в каждом из них метод Log
        foreach ($this->routes as $route)
        {
            if (!$route instanceof Route)
            {
                continue;
            }
            if (!$route->isEnable)
            {
                continue;
            }
            $route->log($level, $message, $context);
        }
    }
}

/* Class Route */

abstract class Route extends AbstractLogger implements LoggerInterface
{
    public $isEnable = true; // включен ли роут
    public $dateFormat = DateTime::RFC2822; // формат даты

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $attribute => $value)
        {
            if (property_exists($this, $attribute))
            {
                $this->{$attribute} = $value;
            }
        }
    }

    // получение текущей даты
    public function getDate()
    {
        return (new DateTime())->format($this->dateFormat);
    }

    // context -> string
    public function contextStringify(array $context = [])
    {
        return !empty($context) ? json_encode($context) : null;
    }
}

/* Class FileRoute */ /* для записи в файл*/

class FileRoute extends Route
{
    public $filePath; // путь к файлу
    public $template = "{date} {level} {message} {context}"; // шаблон сообщения

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (!file_exists($this->filePath))
        {
            touch($this->filePath);
        }
    }

    public function log($level, $message, array $context = [])
    {
        file_put_contents($this->filePath, trim(strtr($this->template, [
            '{date}' => $this->getDate(),
            '{level}' => $level,
            '{message}' => $message,
            '{context}' => $this->contextStringify($context),
        ])) . PHP_EOL, FILE_APPEND);
    }
}

/* использование всего что выше */

$logger = new Logger();

// подключаем запись в файл, таким же способом можно реализовать запись в бд/json и любой другой удобный формат
$logger->routes->attach(new FileRoute([
    'isEnable' => true,
    'filePath' => 'log.log',
]));

// пишем логи
$logger->info("Any info message");
$logger->alert("Any alert message");
$logger->error("Any error message");
$logger->debug("Any debug message");
$logger->notice("Any notice message");
$logger->warning("Any warning message");
$logger->critical("Any critical message");
$logger->emergency("Any emergency message");

echo ")))";