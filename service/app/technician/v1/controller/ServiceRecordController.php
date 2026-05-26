<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\technician\v1\controller;

use app\common\BaseController;
use app\model\ServiceRecord;
use Webman\Http\Request;

/**
 * 服务记录控制器
 * 技师上传/查看服务前后照片
 */
class ServiceRecordController extends BaseController
{
    /**
     * 上传服务记录（before / after 照片）
     * POST /api/technician/service-records
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function store(Request $request)
    {
        $technicianId = $request->technician_id;
        $orderId = $this->decodeId($request->input('order_id'));

        if (!$orderId) {
            return $this->error('订单ID无效');
        }

        $beforePhotos = $request->input('before_photos', []);
        $afterPhotos = $request->input('after_photos', []);
        $notes = $request->input('notes', '');

        if (!is_array($beforePhotos)) {
            $beforePhotos = [];
        }
        if (!is_array($afterPhotos)) {
            $afterPhotos = [];
        }

        // 检查是否已存在该订单的记录
        $existing = ServiceRecord::where('order_id', $orderId)->first();

        if ($existing) {
            // 更新已有记录
            if (!empty($beforePhotos)) {
                $existing->before_photos = array_merge(
                    is_array($existing->before_photos) ? $existing->before_photos : [],
                    $beforePhotos
                );
            }
            if (!empty($afterPhotos)) {
                $existing->after_photos = array_merge(
                    is_array($existing->after_photos) ? $existing->after_photos : [],
                    $afterPhotos
                );
            }
            if ($notes) {
                $existing->notes = $notes;
            }
            $existing->save();

            return $this->success($existing, '服务记录已更新');
        }

        // 创建新记录
        $record = ServiceRecord::create([
            'id' => ServiceRecord::generateId(),
            'order_id' => $orderId,
            'technician_id' => $technicianId,
            'before_photos' => $beforePhotos,
            'after_photos' => $afterPhotos,
            'notes' => $notes,
        ]);

        return $this->success($record, '服务记录已保存');
    }

    /**
     * 查看服务记录
     * GET /api/technician/service-records/{id}
     *
     * @param mixed $id
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function show($id, Request $request)
    {
        $technicianId = $request->technician_id;
        $decodedId = $this->decodeId((string)$id);

        if ($decodedId === null) {
            // 尝试以 order_id 方式查询
            $record = ServiceRecord::where('order_id', (string)$id)
                ->where('technician_id', $technicianId)
                ->with(['order' => function ($query) {
                    $query->select('id', 'order_no', 'user_id', 'service_time', 'status');
                }])
                ->first();

            if (!$record) {
                return $this->error('服务记录不存在');
            }

            return $this->success($record);
        }

        // 以记录 ID 查询
        $record = ServiceRecord::where('technician_id', $technicianId)
            ->with(['order' => function ($query) {
                $query->select('id', 'order_no', 'user_id', 'service_time', 'status');
            }])
            ->find($decodedId);

        if (!$record) {
            return $this->error('服务记录不存在');
        }

        return $this->success($record);
    }
}
