<?php
namespace IT\Services;

/**
 * Class ErrorFormatterService
 * @package IT\Services
 */
class ErrorFormatterService
{
    /**
     * @param array $errors_data
     * @return string
     */
    public function format(array $errors_data): string
    {
        $error_messages = '';
        foreach ($errors_data as $item => $messages) {
            $error_messages .= $this->mountErrorMessage($item, $messages);
        }

        return $error_messages;
    }

    /**
     * @param string $name
     * @param array $messages
     * @return string
     */
    private function mountErrorMessage(string $name, array $messages): string
    {
        $error_message = $name . ' : ';
        foreach ($messages as $cause => $message) {
            $error_message .= $cause . ' - ' . $message . PHP_EOL;
        }

        return $error_message;
    }
}
