<?php

// 依赖下载 https://github.com/PHPOffice/PHPExcel
// 产生的文件自己想办法处理，可以参考sendFile函数
// API用法 https://apifox.com/apidoc/shared-a4595cc8-44c5-4678-a2a3-eed7738dab03/api-69943082

require_once 'PHPExcel/PHPExcel.php';

function generateResellDetailExcel($apiKey) {
    // 设置API请求参数
    $url = 'https://api.v2.rainyun.com/user/vip/resell_detail';
    $headers = array('x-api-key: ' . $apiKey);
    $params = array(
        'options' => '{"columnFilters":{"users.id":""},"sort":[],"page":1,"perPage":100}'
    );
    
    // 创建Excel对象
    $objPHPExcel = new PHPExcel();
    $sheet = $objPHPExcel->getActiveSheet();
    
    // 发送API请求并处理数据
    $page = 1;
    $perPage = 100;
    $totalRecords = 0;
    $rowCount = 1; // 行计数器
    
    do {
        // 设置请求参数
        $params['options'] = '{"columnFilters":{"users.id":""},"sort":[],"page":' . $page . ',"perPage":' . $perPage . '}';
        $query = http_build_query($params);
        $fullUrl = $url . '?' . $query;
        // 发送API请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        // 解析API响应
        $data = json_decode($response, true);
        
        // 处理API响应中的记录
        if ($data['code'] === 200) {
            $totalRecords = $data['data']['TotalRecords'];
            $records = $data['data']['Records'];
            
            // 添加表头
            $sheet->setCellValue('A1', 'user_id');
            $sheet->setCellValue('B1', 'user_name');
            $sheet->setCellValue('C1', 'points');
            $sheet->setCellValue('D1', 'money');
            $sheet->setCellValue('E1', 'level');
            $sheet->setCellValue('F1', 'stock');
            $sheet->setCellValue('G1', 'time');
            
            // 设置表头样式（可选）
            $style = $sheet->getStyle('A1:G1');
            $style->getFont()->setBold(true);
            $style->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF00');
            
            foreach ($records as $record) {
                // 将记录数据写入Excel表格
                $sheet->setCellValue('A' . $rowCount, $record['user_id']);
                $sheet->setCellValue('B' . $rowCount, $record['user_name']);
                $sheet->setCellValue('C' . $rowCount, $record['points']);
                $sheet->setCellValue('D' . $rowCount, $record['money']);
                $sheet->setCellValue('E' . $rowCount, $record['level']);
                $sheet->setCellValue('F' . $rowCount, $record['stock']);
                $sheet->setCellValue('G' . $rowCount, $record['time']);
            
                // 添加其他字段...
            
                $rowCount++;
            }
            
            $page++;
        } else {
            // API请求失败
            echo 'API请求失败: ' . $data['code'] . ' - ' . $data['message'];
            return;
        }
    } while ($rowCount <= $totalRecords);
    
    // 保存Excel文件
    $filename = 'cache-xlsx/'.$GLOBALS['userid'] . '-' . time() . '.xlsx';
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($filename);
    
    return $filename;
}


?>