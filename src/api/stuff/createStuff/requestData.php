


<?php


class CreateSuffRequest
{

    public $fullname;
    public $username;
    public $email;
    public $password;
    public $role;



    public function __construct(string $jsonData)
    {

        $data = json_decode($jsonData, true);


        if (!isset($data['fullname'], $data['username'], $data['email'], $data['password'], $data['role'])) {
            http_response_code(400);
            echo $jsonData;
            die('Invalid request data');
        }

        $this->fullname = $data['fullname'];
        $this->username = $data['username'];
        $this->email = $data['email'];
        $this->password = $data['password'];
        $this->role = $data['role'];
    }

    public function toJson()
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }
}
?>