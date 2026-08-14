<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Dompdf\Dompdf;
use app\common\EncryptionService;
use app\model\AdminUser;
use app\model\OperationLog;
use app\model\AdminRole;
use app\model\SystemConfig;
use app\model\User;
use app\model\Order;
use app\model\TechnicianProfile;
use support\Request;

class ExportController extends BaseController
{
    /**
     * @Apidoc\Title("导出Excel")
     * @Apidoc\Group("export")
     * @Apidoc\Method("POST")
     * @Apidoc\Url("/admin/export/excel")
     * @Apidoc\Desc("导出数据为Excel文件，支持多表多字段")
     * @Apidoc\Param("table", type="string", require=true, desc="表名(admin_user/operation_log/admin_role/system_config)")
     * @Apidoc\Param("columns", type="array", require=false, desc="导出字段列表")
     * @Apidoc\Param("conditions", type="object", require=false, desc="筛选条件")
     * @Apidoc\Param("title", type="string", require=false, desc="导出标题", default="数据导出")
     */
    public function excel(Request $request): Response
    {
        $table = $request->input('table', 'admin_user');
        $columns = $request->input('columns', []);
        $conditions = $request->input('conditions', []);
        $title = $request->input('title', '数据导出');

        // 获取导出字段映射
        $exportColumns = $this->getExportColumns($table);
        if (empty($columns)) {
            $columns = array_keys($exportColumns);
        }

        // 查询数据
        $data = $this->fetchExportData($table, $columns, $conditions);
        $sensitiveFields = $this->getSensitiveFields($table);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($title);

        // 表头样式
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1677FF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        // 数据行样式
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $colIndex = 'A';
        foreach ($columns as $col) {
            $label = $exportColumns[$col] ?? $col;
            $cell = $sheet->getCell($colIndex . '1');
            $cell->setValue($label);
            $sheet->getStyle($colIndex . '1')->applyFromArray($headerStyle);
            $sheet->getColumnDimension($colIndex)->setAutoSize(true);
            $colIndex++;
        }

        // 填充数据
        $row = 2;
        foreach ($data as $item) {
            $colIndex = 'A';
            foreach ($columns as $col) {
                $value = $item[$col] ?? '';
                if (in_array($col, $sensitiveFields) && !empty($value)) {
                    $decrypted = EncryptionService::decrypt((string) $value);
                    if ($col === 'phone') {
                        $value = EncryptionService::maskPhone($decrypted);
                    } elseif ($col === 'email') {
                        $value = EncryptionService::maskEmail($decrypted);
                    } else {
                        $value = str_repeat('*', 8); // id_card等彻底隐藏
                    }
                }
                $sheet->getCell($colIndex . $row)->setValue($this->safeCellValue($value));
                $sheet->getStyle($colIndex . $row)->applyFromArray($dataStyle);
                $colIndex++;
            }
            $row++;
        }

        // 冻结首行
        $sheet->freezePane('A2');
        // 自动筛选
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        $filename = sprintf('export_%s_%s.xlsx', $table, date('YmdHis'));
        $tmpFile = runtime_path() . '/tmp/' . $filename;

        $dir = dirname($tmpFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpFile);

        return $this->downloadAndCleanup($tmpFile, $filename);
    }

    /**
     * @Apidoc\Title("导出PDF")
     * @Apidoc\Group("export")
     * @Apidoc\Method("POST")
     * @Apidoc\Url("/admin/export/pdf")
     * @Apidoc\Desc("导出数据为PDF文件")
     * @Apidoc\Param("type", type="string", require=true, desc="导出类型(table/dashboard)")
     * @Apidoc\Param("title", type="string", require=false, desc="导出标题", default="数据导出")
     * @Apidoc\Param("data", type="object", require=false, desc="导出数据")
     */
    public function pdf(Request $request): Response
    {
        $type = $request->input('type', 'table');
        $title = $request->input('title', '数据导出');
        $data = $request->input('data', []);

        $html = $this->buildPdfHtml($type, $title, $data);

        $dompdf = new Dompdf();
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->loadHtml($html);
        $dompdf->render();

        $filename = sprintf('export_%s_%s.pdf', $type, date('YmdHis'));
        $tmpFile = runtime_path() . '/tmp/' . $filename;

        $dir = dirname($tmpFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($tmpFile, $dompdf->output());

        return $this->downloadAndCleanup($tmpFile, $filename);
    }

    /**
     * 构建 PDF HTML 模板
     */
    private function buildPdfHtml(string $type, string $title, array $data): string
    {
        $timestamp = date('Y-m-d H:i:s');

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">';
        $html .= '<style>
            body { font-family: "DejaVu Sans", sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 20px; }
            .header h1 { font-size: 20px; color: #1677FF; margin-bottom: 4px; }
            .header .meta { font-size: 11px; color: #999; }
            table { width: 100%; border-collapse: collapse; margin-top: 12px; }
            th { background-color: #1677FF; color: #fff; padding: 8px 10px; text-align: left; font-size: 12px; }
            td { padding: 6px 10px; border-bottom: 1px solid #eee; font-size: 11px; }
            tr:nth-child(even) { background-color: #fafafa; }
            .footer { text-align: center; font-size: 10px; color: #999; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
            .cards { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 12px; }
            .card { flex: 1; min-width: 140px; padding: 16px; background: #f5f5f5; border-radius: 8px; text-align: center; }
            .card-label { font-size: 12px; color: #666; }
            .card-value { font-size: 24px; font-weight: bold; color: #1677FF; }
        </style></head><body>';

        $html .= '<div class="header">';
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        $html .= '<div class="meta">Copyright (c) 2026 erik &lt;erik@erik.xyz&gt; — https://erik.xyz</div>';
        $html .= '<div class="meta">导出时间: ' . $timestamp . '</div>';
        $html .= '</div>';

        if ($type === 'dashboard') {
            $html .= '<div class="cards">';
            foreach ($data['stats'] ?? [] as $card) {
                $html .= '<div class="card"><div class="card-label">' . htmlspecialchars($card['label']) . '</div>';
                $html .= '<div class="card-value">' . htmlspecialchars($card['value']) . '</div></div>';
            }
            $html .= '</div>';
        } elseif (!empty($data['rows'])) {
            $html .= '<table><thead><tr>';
            foreach ($data['columns'] as $col) {
                $html .= '<th>' . htmlspecialchars($col) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($data['rows'] as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . htmlspecialchars((string) $cell) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }

        $html .= '<div class="footer">Copyright (c) 2026 erik — https://erik.xyz | 本文件包含不可移除的版权信息</div>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * 自定义导出
     * 根据动态参数构建查询并导出 Excel
     * @Apidoc\Param("table", type="string", require=true, desc="数据表: users/orders/technicians/finance")
     * @Apidoc\Param("columns", type="array", require=true, desc="导出字段名列表")
     * @Apidoc\Param("date_start", type="string", require=false, desc="开始日期")
     * @Apidoc\Param("date_end", type="string", require=false, desc="结束日期")
     * @Apidoc\Param("filters", type="object", require=false, desc="额外筛选条件")
     * @Apidoc\Param("title", type="string", require=false, desc="导出标题", default="自定义导出")
     */
    public function custom(Request $request): Response
    {
        $table     = $request->input('table', 'users');
        $columns   = $request->input('columns', []);
        $dateStart = $request->input('date_start', '');
        $dateEnd   = $request->input('date_end', '');
        $filters   = $request->input('filters', []);
        $title     = $request->input('title', '自定义导出');

        if (empty($columns)) {
            return $this->fail('columns 参数不能为空', 422);
        }

        $exportColumns = $this->getCustomExportColumns($table);
        if (empty($exportColumns)) {
            return $this->fail('不支持的表名: ' . $table, 422);
        }

        // 动态构建查询
        $data = $this->fetchCustomExportData($table, $columns, $dateStart, $dateEnd, $filters);
        $sensitiveFields = $this->getCustomSensitiveFields($table);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($title);

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1677FF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $colIndex = 'A';
        foreach ($columns as $col) {
            $label = $exportColumns[$col] ?? $col;
            $cell = $sheet->getCell($colIndex . '1');
            $cell->setValue($label);
            $sheet->getStyle($colIndex . '1')->applyFromArray($headerStyle);
            $sheet->getColumnDimension($colIndex)->setAutoSize(true);
            $colIndex++;
        }

        $row = 2;
        foreach ($data as $item) {
            $colIndex = 'A';
            foreach ($columns as $col) {
                $value = $item[$col] ?? '';

                // 敏感字段自动脱敏
                if (in_array($col, $sensitiveFields) && !empty($value)) {
                    $decrypted = EncryptionService::decrypt((string) $value);
                    if ($col === 'phone') {
                        $value = EncryptionService::maskPhone($decrypted);
                    } elseif ($col === 'email') {
                        $value = EncryptionService::maskEmail($decrypted);
                    } else {
                        $value = str_repeat('*', 8);
                    }
                }

                $sheet->getCell($colIndex . $row)->setValue($this->safeCellValue($value));
                $sheet->getStyle($colIndex . $row)->applyFromArray($dataStyle);
                $colIndex++;
            }
            $row++;
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        $filename = sprintf('custom_%s_%s.xlsx', $table, date('YmdHis'));
        $tmpFile = runtime_path() . '/tmp/' . $filename;

        $dir = dirname($tmpFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpFile);

        return $this->downloadAndCleanup($tmpFile, $filename);
    }

    /**
     * 调度定期报表导出
     * 配置保存到 erik_system_config
     * @Apidoc\Param("type", type="string", require=true, desc="报表类型")
     * @Apidoc\Param("frequency", type="string", require=true, desc="频率: daily/weekly/monthly")
     * @Apidoc\Param("recipients", type="array", require=true, desc="接收人邮箱列表")
     * @Apidoc\Param("format", type="string", require=false, desc="格式: excel/pdf", default="excel")
     */
    public function scheduled(Request $request): Response
    {
        $type       = $request->input('type', '');
        $frequency  = $request->input('frequency', 'daily');
        $recipients = $request->input('recipients', []);
        $format     = $request->input('format', 'excel');

        $validator = validator($request->all(), [
            'type'       => 'required|string',
            'frequency'  => 'required|string|in:daily,weekly,monthly',
            'recipients' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        // 验证邮箱格式
        foreach ($recipients as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->fail("无效的邮箱地址: {$email}", 422);
            }
        }

        $scheduledReport = [
            'id'         => (string) $this->generateId(),
            'type'       => $type,
            'frequency'  => $frequency,
            'recipients' => $recipients,
            'format'     => $format,
            'status'     => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // 读取已有调度配置
        $config = SystemConfig::where('group', 'export')
            ->where('key', 'scheduled_reports')
            ->first();

        $reports = [];
        if ($config && !empty($config->value)) {
            $reports = json_decode($config->value, true) ?: [];
        }

        $reports[] = $scheduledReport;

        if ($config) {
            $config->value = json_encode($reports, JSON_UNESCAPED_UNICODE);
            $config->save();
        } else {
            $config = new SystemConfig();
            $config->id = $this->generateId();
            $config->group = 'export';
            $config->key = 'scheduled_reports';
            $config->value = json_encode($reports, JSON_UNESCAPED_UNICODE);
            $config->type = 'json';
            $config->description = '定期报表调度配置';
            $config->save();
        }

        return $this->success($scheduledReport, '报表调度配置保存成功');
    }

    /**
     * 自定义导出的字段映射
     */
    private function getCustomExportColumns(string $table): array
    {
        return match ($table) {
            'users' => [
                'id' => '用户ID', 'phone' => '手机号', 'nickname' => '昵称',
                'real_name' => '真实姓名', 'gender' => '性别', 'status' => '状态',
                'region' => '地区', 'last_login_at' => '最后登录', 'created_at' => '注册时间',
            ],
            'orders' => [
                'id' => '订单ID', 'order_no' => '订单号', 'user_id' => '用户ID',
                'total_amount' => '订单金额', 'status' => '订单状态',
                'pay_type' => '支付方式', 'created_at' => '下单时间',
            ],
            'technicians' => [
                'id' => '技师ID', 'real_name' => '姓名', 'gender' => '性别',
                'rating' => '评分', 'order_count' => '接单数', 'favorite_count' => '收藏数',
                'status' => '状态', 'created_at' => '注册时间',
            ],
            'finance' => [
                'id' => 'ID', 'order_id' => '订单ID', 'amount' => '金额',
                'type' => '类型', 'pay_method' => '支付方式', 'status' => '状态',
                'created_at' => '交易时间',
            ],
            default => $this->getExportColumns($table),
        };
    }

    /**
     * 自定义导出的敏感字段
     */
    private function getCustomSensitiveFields(string $table): array
    {
        return match ($table) {
            'users' => ['phone', 'real_name', 'id_card'],
            'orders' => ['phone'],
            'technicians' => ['real_name', 'id_card'],
            'finance' => ['phone', 'id_card'],
            default => [],
        };
    }

    /**
     * 自定义导出的数据查询
     */
    private function fetchCustomExportData(string $table, array $columns, string $dateStart, string $dateEnd, array $filters): array
    {
        switch ($table) {
            case 'users':
                $query = User::query();
                if ($dateStart) {
                    $query->whereDate('created_at', '>=', $dateStart);
                }
                if ($dateEnd) {
                    $query->whereDate('created_at', '<=', $dateEnd);
                }
                if (isset($filters['status']) && $filters['status'] !== '') {
                    $query->where('status', (int) $filters['status']);
                }
                if (!empty($filters['keyword'])) {
                    $query->where(function ($q) use ($filters) {
                        $q->where('nickname', 'like', "%{$filters['keyword']}%")
                          ->orWhere('phone', 'like', "%{$filters['keyword']}%");
                    });
                }
                return $query->limit(10000)->get()->toArray();

            case 'orders':
                $query = Order::query();
                if ($dateStart) {
                    $query->whereDate('created_at', '>=', $dateStart);
                }
                if ($dateEnd) {
                    $query->whereDate('created_at', '<=', $dateEnd);
                }
                if (isset($filters['status']) && $filters['status'] !== '') {
                    $query->where('status', (int) $filters['status']);
                }
                return $query->limit(10000)->get()->toArray();

            case 'technicians':
                $query = TechnicianProfile::query();
                if ($dateStart) {
                    $query->whereDate('created_at', '>=', $dateStart);
                }
                if ($dateEnd) {
                    $query->whereDate('created_at', '<=', $dateEnd);
                }
                if (isset($filters['status']) && $filters['status'] !== '') {
                    $query->where('status', (int) $filters['status']);
                }
                return $query->limit(10000)->get()->toArray();

            case 'finance':
                // 财务数据从订单支付表读取
                $query = \app\model\OrderPayment::query();
                if ($dateStart) {
                    $query->whereDate('created_at', '>=', $dateStart);
                }
                if ($dateEnd) {
                    $query->whereDate('created_at', '<=', $dateEnd);
                }
                return $query->limit(10000)->get()->toArray();

            default:
                return $this->fetchExportData($table, $columns, $filters);
        }
    }

    private function fetchExportData(string $table, array $columns, array $conditions): array
    {
        $modelMap = [
            'admin_user' => AdminUser::class,
            'operation_log' => OperationLog::class,
            'admin_role' => AdminRole::class,
            'system_config' => SystemConfig::class,
        ];

        if (!isset($modelMap[$table])) {
            return [];
        }

        $model = new $modelMap[$table]();
        $query = $model->newQuery();

        foreach ($conditions as $field => $value) {
            if (!empty($value) || $value === '0') {
                $query->where($field, $value);
            }
        }

        return $query->limit(10000)->get()->toArray();
    }

    private function getExportColumns(string $table): array
    {
        $maps = [
            'admin_user' => [
                'id' => '用户ID', 'username' => '用户名', 'real_name' => '真实姓名',
                'phone' => '手机号', 'email' => '邮箱', 'status' => '状态',
                'last_login_at' => '最后登录时间', 'last_login_ip' => '最后登录IP',
                'created_at' => '创建时间',
            ],
            'operation_log' => [
                'id' => 'ID', 'user_id' => '用户ID', 'action' => '操作动作',
                'method' => '请求方法', 'path' => '请求路径', 'ip' => 'IP地址',
                'created_at' => '操作时间',
            ],
            'admin_role' => [
                'id' => 'ID', 'name' => '角色名称', 'slug' => '角色标识',
                'description' => '描述', 'status' => '状态', 'created_at' => '创建时间',
            ],
            'system_config' => [
                'id' => 'ID', 'group' => '分组', 'key' => '配置键',
                'value' => '配置值', 'type' => '类型', 'description' => '说明',
                'created_at' => '创建时间',
            ],
        ];

        return $maps[$table] ?? [];
    }

    private function getSensitiveFields(string $table): array
    {
        $maps = [
            'admin_user' => ['phone', 'email', 'id_card'],
        ];
        return $maps[$table] ?? [];
    }

    /**
     * M2: Excel 公式注入防护
     * 字符串单元格若以公式触发符开头（= + - @ 或 Tab/CR），加单引号前缀使其按纯文本处理；
     * 数字/日期等非字符串值保持原样。
     */
    private function safeCellValue(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }
        if (preg_match('/^[=+\-@\t\r]/', $value) === 1) {
            return "'" . $value;
        }
        return $value;
    }

    private function downloadAndCleanup(string $tmpFile, string $filename): Response
    {
        register_shutdown_function(function () use ($tmpFile) {
            @unlink($tmpFile);
        });
        return $this->downloadAndCleanup($tmpFile, $filename);
    }

    public static function cleanStaleFiles(?string $dir = null, int $maxAgeSeconds = 3600): int
    {
        $dir = $dir ?? runtime_path() . '/tmp';
        $count = 0;
        if (!is_dir($dir)) {
            return 0;
        }
        foreach ((array) glob($dir . '/*') as $file) {
            if (is_file($file) && (time() - (int) filemtime($file)) > $maxAgeSeconds) {
                @unlink($file);
                $count++;
            }
        }
        return $count;
    }
}
