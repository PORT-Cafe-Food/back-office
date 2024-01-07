<?php
class ApiResponse
{
    public $success;
    public $data;
    public $error;
    public $message;

    public function __construct(bool $success = true, $data = null, $error = null, $message = null)
    {
        $this->success = $success;
        $this->data = $data;
        $this->error = $error;
        $this->message = $message;
    }

    public function toJson()
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }
}
