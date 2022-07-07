<?php

namespace App\Project;

use http\Env;

class DadataTools
{
    public const METHOD = "findById/address";
    const DADATA_API_URL = "https://suggestions.dadata.ru/suggestions/api/4_1/rs/";
    private $_curl;
    private $_header;

    public function __construct($sAction = self::METHOD)
    {
        $sEnvApi = env("DADATA_API_KEY");
        $this->_header = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: Token " . $sEnvApi
        ];
        $this->_init($sAction);
    }

    /**
     * Возвращает варианты адреса
     *
     * @param $fields
     * @param string $bounds
     * @return mixed
     */
    public function suggest($fields, $bounds = "")
    {

        if (!empty($bounds)) {
            $fields["from_bound"]   = ["value" => $bounds];
            $fields["to_bound"]     = ["value" => $bounds];
        }

        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, json_encode($fields));
        return json_decode(curl_exec($this->_curl), true);
    }

    /**
     * Возвращает данные по идентификатору
     *
     * @param $id string - ФИАС или КЛАДР ID
     * @return mixed
     */
    public function getElementsById($id)
    {
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, json_encode(["query" => $id]));

        return json_decode(curl_exec($this->_curl), true);
    }

    private function _init($sAction = "")
    {
        $this->_curl = curl_init(self::DADATA_API_URL . $sAction);
        curl_setopt_array(
            $this->_curl,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $this->_header
            ]
        );
    }

    public function __destruct()
    {
        curl_close($this->_curl);
    }
}