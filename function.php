<?php

// 核心屎山 摘录部分还能看的开源
// 包含签到 完成任务 按要求提现

function checkin($token) {
    // 初始化cURL
    $ch = curl_init();
    // 设置请求的URL地址
    curl_setopt($ch, CURLOPT_URL, 'https://api.v2.rainyun.com/user/reward/tasks');
    // 设置请求头
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json', // 设置请求头类型
        'X-Api-Key: ' . $token // 设置X-Api-Key
    ));
    // 设置请求方法为POST
    curl_setopt($ch, CURLOPT_POST, true);
    // 设置请求数据
    $data = array(
        'task_name' => '每日签到'
    );
    $jsonData = json_encode($data);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    // 设置返回结果不直接输出
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // 发送请求
    $result = curl_exec($ch);
    // 关闭cURL资源
    curl_close($ch);
    $arr = json_decode($result,true);
    if ($arr['code'] == 200) {
        return '签到成功 :)';
    } else if ($arr['code'] == 30011) {
        return '签到失败，今天已经签到过了 =-=';
    } else {
        return '签到失败，' . $arr['message'];
    }
}

function get_points($token) {
    $api_key = $token;
    $url = "https://api.v2.rainyun.com/user/";
    $max_attempts = 2;
    $attempt = 0;
    while ($attempt < $max_attempts) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 设置超时时间为10秒
        $headers = array(
            'X-Api-Key: ' . $api_key
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $arr = json_decode($response, true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code == 200 && $arr['code'] == 200) {
            return $arr['data']['Points'];
        } else {
            $attempt++;
            // 如果不是正常响应，则暂停1秒后重试
            sleep(1);
        }
    }
    // 如果最终仍然无法获取到正确的响应，则返回false
    return false;
}

function draw($token,$keeppoint,$target) {
    if ($target == 'alipay' or $target == 'rainyun') {
        $point = get_points($token);
        if ($point >= $keeppoint + 60000) {
            $drawpoint = $point - $keeppoint;
            $result = withdraw($token,$drawpoint,$target);
            if ($result == 'ok') {
                return '提现任务执行成功！提现了'.$drawpoint.'积分，当前剩余'.get_points($token).'积分~';
            } else {
                return '提现失败：' . $result;
            }
        } else {
            return '当前积分剩余'.$point.'，任务未执行！';
        }
    } else {
        return '未设置提现目标，执行失败！';
    }
}

function withdraw($token,$point,$target) {
    // 初始化cURL
    $ch = curl_init();
    // 设置请求的URL地址
    curl_setopt($ch, CURLOPT_URL, 'https://api.v2.rainyun.com/user/reward/withdraw');
    // 设置请求头
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json', // 设置请求头类型
        'X-Api-Key: ' . $token // 设置X-Api-Key
    ));
    // 设置请求方法为POST
    curl_setopt($ch, CURLOPT_POST, true);
    // 设置请求数据
    $data = array(
        'points' => $point,
        'target' => $target
    );
    $jsonData = json_encode($data);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    // 设置返回结果不直接输出
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $max_attempts = 3; // 最大重试次数
    $attempts = 0; // 当前重试次数
    do {
        $result = curl_exec($ch);
        $arr = json_decode($result,true);
        $code = $arr['code'] ?? null;
        $attempts++;
    } while (($code !== 200) && ($attempts < $max_attempts));

    // 关闭cURL资源
    curl_close($ch);

    if ($code === 200) {
        return 'ok';
    } else {
        return $arr['message'];
        //throw new Exception($result);
    }
}

function user_info($token) {

    $api_key = $token;
    $url = "https://api.v2.rainyun.com/user/";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = array(
        'X-Api-Key: ' . $api_key
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $arr = json_decode($response,true);
    curl_close($ch);
    if ($arr['code'] == 200) {
        return translate_info($arr);
    } else {
        return '获取失败';
    }

}

function translate_info($arr) {
    $id = $arr['data']['ID'];
    $name = $arr['data']['Name'];
    $balance = $arr['data']['Money'];
    $points = $arr['data']['Points'];
    $lastLoginArea = $arr['data']['LastLoginArea'];
    $lastLoginTime = date("Y-m-d H:i:s", $arr['data']['LastLogin']);
    $registerTime = date("Y-m-d H:i:s", $arr['data']['RegisterTime']);
    $vipLevel = $arr['data']['VipLevel'];
    
    $output = "\n--- 基本信息 ---\n";
    $output .= "ID：{$id}\n";
    $output .= "名字：{$name}\n";
    $output .= "余额：{$balance}\n";
    $output .= "积分：{$points}\n";
    $output .= "上次登录地点：{$lastLoginArea}\n";
    $output .= "上次登录时间：{$lastLoginTime}\n";
    $output .= "登录次数：{$arr['data']['LoginCount']}\n";
    $output .= "注册时间：{$registerTime}\n";
    $output .= "雨云等级：{$vipLevel}\n";
    $output .= "我的邀请码：{$arr['data']['ShareCode']}\n";
    $output .= "邀请者：{$arr['data']['Inviter']}\n";
    
    if (isset($arr['data']['Email']) || isset($arr['data']['Phone']) || isset($arr['data']['QQ']) || isset($arr['data']['WechatOpenID'])) {
        if (isset($arr['data']['Email'])) {
            $output .= "邮箱地址：{$arr['data']['Email']}\n";
        }
        if (isset($arr['data']['Phone'])) {
            $output .= "手机号码：{$arr['data']['Phone']}\n";
        }
        if (isset($arr['data']['QQOpenID'])) {
            $output .= "QQ Openid：{$arr['data']['QQOpenID']}\n";
        }
        if (isset($arr['data']['WechatOpenID'])) {
            $output .= "微信 OpenID：{$arr['data']['WechatOpenID']}\n";
        }
    }
    
    
    $output .= "\n--- 积分信息 ---\n";
    $output .= "积分：{$points}\n";
    
    if (isset($arr['data']['LockPoints'])) {
        $output .= "锁定积分：{$arr['data']['LockPoints']}\n";
    }
    
    if (isset($arr['data']['ConsumeMonthly']) || isset($arr['data']['ConsumeQuarter']) || isset($arr['data']['ConsumeAll'])) {
        $output .= "\n--- 消费信息 ---\n";
        if (isset($arr['data']['ConsumeMonthly'])) {
            $output .= "本月消费：{$arr['data']['ConsumeMonthly']}元\n";
        }
        if (isset($arr['data']['ConsumeQuarter'])) {
            $output .= "本季度消费：{$arr['data']['ConsumeQuarter']}元\n";
        }
        if (isset($arr['data']['ConsumeAll'])) {
            $output .= "总消费：{$arr['data']['ConsumeAll']}元\n";
        }
    }
    
    if (isset($arr['data']['ResellMonthly']) || isset($arr['data']['ResellQuarter']) || isset($arr['data']['ResellAll'])) {
        $output .= "\n--- 销售信息 ---\n";
        if (isset($arr['data']['ResellMonthly'])) {
            $output .= "本月销售：{$arr['data']['ResellMonthly']}元\n";
        }
        if (isset($arr['data']['ResellQuarter'])) {
            $output .= "本季度销售：{$arr['data']['ResellQuarter']}元\n";
        }
        if (isset($arr['data']['ResellAll'])) {
            $output .= "总销售：{$arr['data']['ResellAll']}元\n";
        }
    }
    
    if (isset($arr['data']['ResellPointsMonthly']) || isset($arr['data']['ResellPointsAll'])) {
        if (isset($arr['data']['ResellPointsMonthly'])) {
            $output .= "本月销售积分收入：{$arr['data']['ResellPointsMonthly']}\n";
        }
        if (isset($arr['data']['ResellPointsAll'])) {
            $output .= "总销售积分收入：{$arr['data']['ResellPointsAll']}\n";
        }
    }
    
    if (isset($arr['data']['SubUserMonthly']) || isset($arr['data']['SubUserAll'])) {
        $output .= "\n--- 二级用户信息 ---\n";
        if (isset($arr['data']['SubUserMonthly'])) {
            $output .= "本月二级用户收入：{$arr['data']['SubUserMonthly']}元\n";
        }
        if (isset($arr['data']['SubUserAll'])) {
            $output .= "总二级用户收入：{$arr['data']['SubUserAll']}元\n";
        }
    }
    
    if (isset($arr['data']['StockMonthly']) || isset($arr['data']['StockQuarter']) || isset($arr['data']['StockAll'])) {
        $output .= "\n--- 进货信息 ---\n";
        if (isset($arr['data']['StockMonthly'])) {
            $output .= "本月进货：{$arr['data']['StockMonthly']}元\n";
        }
        if (isset($arr['data']['StockQuarter'])) {
            $output .= "季度进货：{$arr['data']['StockQuarter']}元\n";
        }
        if (isset($arr['data']['StockAll'])) {
            $output .= "总进货：{$arr['data']['StockAll']}元\n";
        }
    }
    
    if (isset($arr['data']['VIP'])) {
        $vip = $arr['data']['VIP'];
        $output .= "\n--- 雨云会员信息 ---\n";
        $output .= "等级：{$vip['Title']}\n";
        $output .= "销售要求：{$vip['SaleRequire']}\n";
        $output .= "转售要求：{$vip['ResellRequire']}\n";
        $output .= "认证要求：" . ($vip['CertifyRequired'] ? "是" : "否") . "\n";
        $output .= "销售利润：{$vip['SaleProfit']}%\n";
        $output .= "转售利润：{$vip['ResellProfit']}%\n";
        $output .= "二级转售利润：{$vip['SecondResellProfit']}%\n";
        $output .= "可发送优惠券：" . ($vip['CanSendCoupons'] ? "是" : "否") . "\n";
        $output .= "可定制代码：" . ($vip['CanCustomCode'] ? "是" : "否") . "\n";
        $output .= "可发送消息：" . ($vip['CanSendMsg'] ? "是" : "否") . "\n";
        $output .= "可试用普及版：" . ($vip['CanTryUsual'] ? "是" : "否") . "\n";
        $output .= "免费域名数：{$vip['FreeDomainCount']}\n";
        $output .= "免费 SSL 数：{$vip['FreeSSLCount']}\n";
        $output .= "可成为代理：" . ($vip['CanBeAgent'] ? "是" : "否") . "\n";
        $output .= "代理等级：{$vip['AgentTitle']}\n";
        $output .= "库存要求：{$vip['StockRequire']}\n";
        $output .= "二级库存要求：{$vip['SecondStockRequire']}\n";
        $output .= "库存折扣：{$vip['StockDiscount']}%\n";
        $output .= "二级库存利润：{$vip['SecondStockProfit']}%\n";
    }
    
    return $output;
}

function finish_tasks($token) {
    if (!token_check($token)) {
        return '无效的token';
    }
    $out = '';
    $point = 0;
    if (send_task_request($token,'{"task_name":"加入Q群","verifyCode":"我爱雨云"}')) {
        $out .= '加入Q群任务完成✔'. PHP_EOL;
        $point += 1500;
    } else {
        $out .= '加入Q群任务完成失败×'. PHP_EOL;
    }
    if (send_task_request($token,'{"task_name":"关注雨云B站号","verifyCode":"雨云爱你"}')) {
        $out .= '关注雨云B站号任务完成✔' . PHP_EOL;
        $point += 1000;
    } else {
        $out .= '关注雨云B站号任务完成失败×'. PHP_EOL;
    }
    if (send_task_request($token,'{"task_name":"关注雨云淘宝店","verifyCode":"成功加入"}')) {
        $out .= '关注雨云淘宝店任务完成✔'. PHP_EOL;
        $point += 1000;
    } else {
        $out .= '关注雨云淘宝店任务完成失败×'. PHP_EOL;
    }
    $out .= '一键完成任务为您赚取了'.$point.'个积分~';
    return $out;
}

function send_task_request($token,$field) {
    
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
       CURLOPT_URL => 'https://api.v2.rainyun.com/user/reward/tasks',
       CURLOPT_RETURNTRANSFER => true,
       CURLOPT_ENCODING => '',
       CURLOPT_MAXREDIRS => 10,
       CURLOPT_TIMEOUT => 0,
       CURLOPT_FOLLOWLOCATION => true,
       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
       CURLOPT_CUSTOMREQUEST => 'POST',
       CURLOPT_POSTFIELDS => $field,
       CURLOPT_HTTPHEADER => array(
          'x-api-key: '.$token,
          'User-Agent: Apifox/1.0.0 (https://apifox.com)',
          'Content-Type: application/json'
       ),
    ));
    
    $response = curl_exec($curl);
    
    curl_close($curl);
    
    $arr = json_decode($response,true);
    if ($arr['code'] == 200) {
        return true;
    } else {
        return false;
    }
    
    
}

function token_check($token) {
    $api_key = $token;
    $url = "https://api.v2.rainyun.com/user/";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = array(
        'X-Api-Key: ' . $api_key
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $arr = json_decode($response,true);
    curl_close($ch);
    if ($arr['code'] == 200) {
        return true;
    } else {
        return false;
    }
}

function sendFile($chat_id,$filename,$reply_to_message_id) {
    $absolute_path = realpath($filename);
    
    $cfile = new CURLFile($absolute_path);
    $parameters = [
        'chat_id' => $chat_id,
        'document' => $cfile,
        'reply_to_message_id' => $reply_to_message_id,
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $GLOBALS['link'] . '/sendDocument');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

}

?>