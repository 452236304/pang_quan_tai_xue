<?php

class AlipayConfig{

    public static function getConfig(){
        $config = array (
            //应用ID,您的APPID。
            'app_id' => "2019021863252202",

            //商户私钥
            'merchant_private_key' => "MIIEpAIBAAKCAQEAtXicmDQxqXKhC/CTqzW40hfqdtcvcMKofAtuW4caIiqig7xJU1pjvJAzwTwfGYQ4Ie7QQyJCm/z+atne/cw7ciCbQ/pFiFpqy2dN8c6KYYutaZwhYMKAS+mkh9WvhIbcOfFVAo93+g/UfOjnw5kEuxf6UcswAZOrOJ+nhigsxllqQxkvPqS3aQNlTo1ZL28zV+Bf3uiL6Esm9AwTWYIKLAHgpcUtAnx8OE4ujnfKlCJEuLD1BjqWSJW57nFca9EEAolD/6BD/Wm+Zya0Nt6zLF8E6UQrtG1FjDO2NTD87ummvMImu71049RoqqALhyEzny8V2DCn1N/U+Gb/5JPLmwIDAQABAoIBADLf5l7ROx77EOUtA+kavX7Az62TdCxTljJ4be7g+FWdyEPQVGo2zAFAGBJTTZC0vZvdvKslfrEGgzjnbZmPWRcMxxqOnlG0r33orzRcJ/Vi2DHkYIMk5BzkGokuQe+Qc0sXOjLmj8ceCv8duGPgkS/2b+ngYcv/XyAXujplJvBQBheKRiuOpdtp2NVlw3MezWRbAn1qu6LGy8S94mWvEw0stBoSJ0bYnDEHIWb/U/IR7PHBCGdUPNQPwDpsCFlFaR49sFTtWXiUFvd0qkN+852jpA+eUVzPS4Sxqli0oSS+R7kCa7/h67NfUTtbcE9yNgx7g3bEgbTw/KIxiW8MZekCgYEA27cw+716jp/RB9B609W+F+1541qlx/hS4IL//9/8gKQEslyu0fx6I+mp2vzPiffbGaTb8L5oCp+Nugb6gZqFUyUIXxu9Zd6owLujEh6v+mt+IWSETviSwOg7vzjSpern7hte7uWrXn9O2WzYMqJFTDTGi+SlRq1wogLgO/OVbM0CgYEA03CUD5Yb+CEbdIHXCnyJNVf5g8UhzEpWH6EbzheI5h2jWQXMh/V/4pokmyhPVGP17AFJdOnxjy5okY/8d1eXCasRI7i9FqKnht2N8qfZA+cGWJyrAQruUCOnDW1waesPU+a0ZeHVygTlW5gr+lmSlDyruhZvL3mTmWjWbag1GgcCgYEAms28nnIHCrxBi87w65X6ZGN2cKCqk3U792F1O3Q2NPDbOkwhBGRuLiVw/pEHLP9MCQplyH5vUaTPN5W5JF2ZuFYsSs6BqGez4G+T2q1yce8QpDhtHDL5Ox+lEPrZN/uFQ/dW8N0y29a42Gpe/XXle2bnySLk7CJBbHS4RqU9z7UCgYBScNW6EgxLvhTnY2zGMPKKswedojJgZieYY4fk3nZJSwsSvdkWtdksMG/Pc3Mi82rCn2nVxRWjfzPUdbC5k5RXd7TSGcjYV4k8Y4xiLaHHDMADsupWGL38zznmWWJ4Wed7DjwOcXSbNTd8dCPJlD07wUAv6WlmuF+ddCwPS4OQ5wKBgQDMkRHdHhffL/PUu4nx5cd4p2ek6s2fZ1qWeL7zoAufO387L+9bu3cwvcYeAA+kjbzKcj1wd2i4MXfIFKWvzx1sbi2ueRd2Iv85SQfuNBTBVDbfDuCv8hAwMHmUl4jk7gd2k+/6BOjHoOP3PczwUYnQbrWfnmJJbfMzlBGQyEuEOg==",

            //异步通知地址
            'notify_url' => "https://".$_SERVER['HTTP_HOST']."/payment.php/Alipay/notify",

            //同步跳转
            'return_url' => "https://".$_SERVER['HTTP_HOST']."/payment.php/Alipay/keep_notify",

            //编码格式
            'charset' => "UTF-8",

            //签名方式
            'sign_type'=>"RSA2",

            //支付宝网关
            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

            //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
            'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjFr8LZOGvqRiDgPbNPvoxISwV9t1t4ue1gpz+mVs4xtSIrzKmGIplMwfBEu+LIMpHuCvvqaAalj5z+rEwatIHqmpAKB3Z1x1iEkfCRsbFrF45mHLtpBX94sjYQ1dmM7ZVGP0R7VDnsxFjqnwFp0STLDW4d0BOaArp5bvk15pzUel8Uvi+qMR76xsybFqvoTvwsCvmeEEIyrAn6LPRHowjslTyljoL+3n7s7EhQqxW9bij+9odSTd+9D/SqwuEbDX450NUX/N1ufaX2TLHfgSv5nX0J04Dq47+Zno2jpquh6HyJvHm5Bm/1vm1mfPuRcu5kX+AWikL50UKplg2ab/0QIDAQAB"
        );

        return $config;
    }
}