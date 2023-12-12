<?php
class Dellin
{
    private static $config = Array(
        'appkey' => '****'
        'login' => '****'
        'password' => '****'
        'uid' => '*', //Контрагент ООО "ХОРЕКА ЦЕНТР"
        'members' => Array(
            'requester' => Array(
               'role' => 'sender',
               'uid' => '****'
            )
        ),
        'derivalPoint' => '7700000000000000000000000'
    );

    private static $methods = Array(
        'addresses' => Array(
            'url' => 'https://api.dellin.ru/v1/customers/book/addresses.json',
            'data' => Array('appkey')
        ),
        'calculator' => Array(
            'url' => 'https://api.dellin.ru/v2/calculator.json',
            'data' => Array('appkey', 'members')
        ),
        'counteragents' => Array(
            'url' => 'https://api.dellin.ru/v1/customers/counteragents.json',
            'data' => Array('appkey')
        ),
        'deliveryTypes' => Array(
            'url' => 'https://api.dellin.ru/v1/public/request_delivery_types.json',
            'data' => Array('appkey')
        ),
        'login' => Array(
            'url' => 'https://api.dellin.ru/v1/customers/login.json',
            'data' => Array('appkey', 'login', 'password')
        ),
        'pvz' => Array(
            'url' => 'https://api.dellin.ru/v3/public/terminals.json',
            'data' => Array('appkey')
        ),
        'produceDate' => Array(
            'url' => 'https://api.dellin.ru/v1/public/produce_date.json',
            'data' => Array('appkey', 'derivalPoint')
        )
    );

    public $sessionID, $produceDate;

    public function method($method, $data = Array(), $debug = false)
    {
        $ch = curl_init(self::$methods[$method]['url']);
        foreach (self::$methods[$method]['data'] as $dataItem) {
            if (!isset($data[$dataItem])) {
                $data[$dataItem] = self::$config[$dataItem];
            }
        }
        $data_string = json_encode($data);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($data_string)));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 9);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        return $result;
    }

    public function calc($data)
    {
        $this->produceDate = $this->method('produceDate', Array(
            'produceDate' => date('Y-m-d'),
            'deliveryType' => 1
        ))['available'][0]['date'];
file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/delivery.log', $this->produceDate . PHP_EOL);
file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/delivery.log', print_r($data, 1), FILE_APPEND);
$calc = $this->method(
            'calculator',
            Array(
                'delivery' => Array(
                    'arrival' => Array(
                        'city' => $data['city'],
                        'time' => Array(
                            'worktimeStart' => '10:00',
                            'worktimeEnd' => '19:00'
                        ),
                        'variant' => 'terminal',
                    ),
                    'derival' => Array(
                        'produceDate' => $this->produceDate,
                        'time' => Array(
                            'worktimeStart' => '11:00',
                            'worktimeEnd' => '16:00'
                        ),
                        'variant' => 'terminal',
                        'terminalID' => '342'
                    ),
                    'packages' => Array(
                        Array(
                            'uid' => '0xA6A7BD2BF950E67F4B2CF7CC3A97C111',
                            'count' => 1
                        )
                    )
                ),
                'cargo' => Array(
                    "length" => $data['length'],
                    "width" => $data['width'],
                    "height" => $data['height'],
                    'totalVolume' => $data['totalVolume'],
                    'totalWeight' => $data['totalWeight'],
                    'freightName' => 'Оборудование'
                )
            ),
            0
        );
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/delivery.log', '* * *' . PHP_EOL . print_r($calc, 1), FILE_APPEND);

        return $calc;
    }
}
