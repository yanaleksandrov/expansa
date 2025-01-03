<?php

declare(strict_types=1);

namespace Expansa\Patterns;

trait Singleton
{
    /**
     * Реальный экземпляр класса находится внутри статического поля.
     * В этом случае статическое поле является массивом, где каждый
     * подкласс Одиночки хранит свой собственный экземпляр.
     *
     * @since 2025.1
     */
    protected static $instance;

    /**
     * Это статический метод, управляющий доступом к экземпляру одиночки.
     * При первом запуске, он создаёт экземпляр одиночки и помещает его в статическое поле.
     * При последующих запусках, он возвращает клиенту объект, хранящийся в статическом поле.
     *
     * Эта реализация позволяет вам расширять класс Одиночки, сохраняя повсюду
     * только один экземпляр каждого подкласса.
     *
     * @since 2025.1
     */
    public static function init(...$args): self
    {
        if (! isset(self::$instance)) {
            self::$instance = new self(...$args);
        }
        return self::$instance;
    }

    /**
     * Конструктор Одиночки не должен быть публичным, а должен быть скрытым,
     * чтобы предотвратить создание объекта через оператор new.
     * Однако он не может быть приватным, если мы хотим разрешить создание подклассов.
     *
     * @since 2025.1
     */
    protected function __construct(...$args) {} // phpcs:ignore

    /**
     * Cloning and deserialization are not allowed.
     *
     * @since 2025.1
     */
    protected function __clone() {} // phpcs:ignore

    /**
     * Singleton should not be recoverable from strings.
     *
     * @since 2025.1
     */
    public function __wakeup() {} // phpcs:ignore
}
